SOSTITUISCI CON:
// SPIHandler.h
// ESP32-C3 acts as SPI slave, talking to the STM32 host over SPI mode 0.
// Protocol: fixed 20-byte ASCII frame.
//
// Important logic:
// 1. Do not pre-submit idle frames.
// 2. Submit an SPI transaction only after real data arrives from the web.
// 3. After the SPI transaction is submitted successfully, raise GPIO9 to
//    notify the STM32.
// 4. The STM32 must read SPI only after seeing the GPIO9 notification.
// 5. Do not raise GPIO9 a second time from WebServerHandler.h.


#ifndef SPI_HANDLER_H
#define SPI_HANDLER_H

#include <Arduino.h>
#include <driver/spi_slave.h>
#include <string.h>

/*
 * Debug log switch.
 *
 * Off by default. On the C3, if "USB CDC On Boot" is enabled (Arduino IDE
 * default), Serial goes through USB-CDC: when the host is not reading,
 * Serial.print blocks for an unpredictable time — which is exactly the
 * "send command latency jumps up and down" symptom. Even over hardware
 * UART, printBuffer alone would emit 60~80 Serial.print calls per frame
 * (20 bytes hex + 20 bytes ASCII), which is expensive. Set to 1 only when
 * debugging SPI wiring / frame content.
 */
#define SPI_DEBUG_LOG 0

/*
 * SPI transaction timeout (ms).
 *
 * prepareNextFrameAndNotifyIfNeeded() returns early when transaction_pending
 * is true, and that flag is only cleared when the STM32 actually reads once
 * (spi_slave_get_trans_result returns ESP_OK). If the STM32 misses the 100 us
 * GPIO9 pulse, the frame can never be submitted, subsequent frames pile up in
 * the queue, and after 8 frames they are silently dropped with no self-heal
 * — the only fix is to reboot the ESP32-C3.
 *
 * If no result is read within this timeout, the notification is considered
 * lost. The transaction is still queued and tx_buffer has not changed, so
 * simply pulsing GPIO9 again is enough; no need to cancel and re-queue.
 */
#define SPI_TX_TIMEOUT_MS 500UL

class SPIHandler {
private:
    // =========================
    // Hardware pin definitions
    // =========================
    // Adjust according to your actual wiring.
    //
    // Note:
    // On ESP32-C3, GPIO6~GPIO11 may be tied to Flash on some boards.
    // If issues persist, switch to known-good GPIOs.
    static const int MOSI_PIN   = 6;    // STM32 MOSI -> ESP32 MOSI
    static const int MISO_PIN   = 5;    // STM32 MISO <- ESP32 MISO
    static const int SCLK_PIN   = 4;    // STM32 SCK  -> ESP32 SCLK
    static const int CS_PIN     = 10;   // STM32 CS   -> ESP32 CS
    static const int NOTIFY_PIN = 9;    // ESP32 notifies STM32 that real data is ready

    static const int FRAME_SIZE = 20;
    static const int QUEUE_SIZE = 8;

    // =========================
    // TX queue
    // =========================
    uint8_t queue[QUEUE_SIZE][FRAME_SIZE];
    int queue_head;
    int queue_tail;
    int queue_count;

    // =========================
    // SPI slave transaction
    // =========================
    spi_slave_transaction_t trans;
    bool spi_initialized;
    bool transaction_pending;
   /* Transaction submit time, used by the SPI_TX_TIMEOUT_MS retry pulse. */
    unsigned long transaction_queued_ms;

    // 4-byte alignment, required by the low-level SPI driver
    alignas(4) uint8_t tx_buffer[FRAME_SIZE];
    alignas(4) uint8_t rx_buffer[FRAME_SIZE];

private:
    void printBuffer(const char* tag, const uint8_t* buf, int len) {
         /* 60~80 Serial.print calls per frame: the single biggest print cost
         * on the SPI path. It is called from 4 places: web frame, queued
         * frame, and RX/TX inside handleSPI(). */
#if SPI_DEBUG_LOG
        Serial.printf("[SPI] %s: ", tag);

        for (int i = 0; i < len; i++) {
            if (buf[i] < 0x10) Serial.print("0");
            Serial.print(buf[i], HEX);
            Serial.print(" ");
        }

        Serial.print(" | ASCII: ");

        for (int i = 0; i < len; i++) {
            char c = (char)buf[i];
            if (c >= 32 && c <= 126) {
                Serial.print(c);
            } else {
                Serial.print(".");
            }
        }

        Serial.println();
#else
        (void)tag;
        (void)buf;
        (void)len;
#endif
    }

    void pulseNotify() {
        // Notify the STM32 that the ESP32 has a real frame ready.
        digitalWrite(NOTIFY_PIN, LOW);
        delayMicroseconds(100);
        digitalWrite(NOTIFY_PIN, HIGH);

    #if SPI_DEBUG_LOG
            Serial.println("[SPI] GPIO9 notify pulse sent");
    #endif
        }

    void stringToFrame(const String& msg, uint8_t* dest) {
        // Fill with '0' first, so a short message cannot leave stale bytes.
        memset(dest, '0', FRAME_SIZE);

        int len = msg.length();
        if (len > FRAME_SIZE) {
            len = FRAME_SIZE;
        }

        memcpy(dest, msg.c_str(), len);
    }

    bool enqueueFrame(const uint8_t* frame) {
        if (queue_count >= QUEUE_SIZE) {
            Serial.println("[SPI] queue full, dropping new message");
            return false;
        }

        memcpy(queue[queue_tail], frame, FRAME_SIZE);

        queue_tail = (queue_tail + 1) % QUEUE_SIZE;
        queue_count++;

        #if SPI_DEBUG_LOG
                Serial.printf("[SPI] message enqueued, queue length %d/%d\n", queue_count, QUEUE_SIZE);
        #endif

        return true;
    }

    /*
     * 把队头那一帧**复制**到 tx_buffer，但**不出队**。
     *
     * 这里曾经是「复制 + 出队 + queue_count--」一步做完，配上下面的
     * prepareNextFrameAndNotifyIfNeeded()，顺序是「先出队、再提交事务」。
     * 于是 queueTransaction() 只要失败（SPI 没初始化，或 spi_slave_queue_trans
     * 返回非 ESP_OK），这一帧就已经从队列里消失了 —— tx_buffer 是唯一副本，
     * 下一次成功提交会把它整个覆盖掉，帧就这么没了。而 receiveFromWeb() 早已
     * 返回 true，网页照样收到 ok:true。这正是上一轮想根除、但只根除了「队列满」
     * 那一条分支的同一类故障。
     *
     * 拆成 peek + commit 之后，只有事务真的提交成功才出队，失败时队列原封不动，
     * 下一次循环会拿同一帧重试。
     */
    bool peekToTxBuffer() {
        if (queue_count <= 0) {
            return false;
        }

        memcpy(tx_buffer, queue[queue_head], FRAME_SIZE);

#if SPI_DEBUG_LOG
        printBuffer("准备发送帧", tx_buffer, FRAME_SIZE);
#endif

        return true;
    }

    /* 事务已提交成功，这时才真正把这一帧从队列里去掉。 */
    void commitDequeue() {
        if (queue_count <= 0) {
            return;
        }

        queue_head = (queue_head + 1) % QUEUE_SIZE;
        queue_count--;

    #if SPI_DEBUG_LOG
            Serial.printf("[SPI] one frame taken for sending, %d frames left in queue\n", queue_count);
    #endif

    bool queueTransaction() {

        if (!spi_initialized) {
            Serial.println("[SPI] SPI not initialized, cannot submit transaction");
            return false;
        }

        if (transaction_pending) {
            Serial.println("[SPI] another transaction is pending, cannot submit again");
            return false;
        }

        memset(rx_buffer, 0, FRAME_SIZE);
        memset(&trans, 0, sizeof(trans));

        trans.length = FRAME_SIZE * 8;
        trans.tx_buffer = tx_buffer;
        trans.rx_buffer = rx_buffer;

        esp_err_t ret = spi_slave_queue_trans(SPI2_HOST, &trans, 0);

       if (ret != ESP_OK) {
            Serial.printf("[SPI] spi_slave_queue_trans failed, err=%d 0x%x\n", ret, ret);
            transaction_pending = false;
            return false;
        }

        transaction_pending = true;
        transaction_queued_ms = millis();

    #if SPI_DEBUG_LOG
            Serial.println("[SPI] SPI transaction submitted, waiting for STM32 to pull CS low and clock");
    #endif

        return true;
    }

    void prepareNextFrameAndNotifyIfNeeded() {
        if (transaction_pending) {
            return;
        }

        if (queue_count <= 0) {

    if (queue_count <= 0) {
    #if SPI_DEBUG_LOG
                Serial.println("[SPI] queue empty, not submitting an idle transaction");
    #endif
                return;
            }
           

        if (!peekToTxBuffer()) {
            return;
        }

        if (queueTransaction()) {
            /* 只有提交成功才出队。失败时队列保持原样，下一轮拿同一帧重试
             * —— 否则这一帧会被 tx_buffer 的下一次覆盖静默销毁。 */
            commitDequeue();

            // 关键点：
            // 必须先提交 SPI 事务，再通知 STM32。
            pulseNotify();
        }
    }

public:
    SPIHandler()
        : queue_head(0),
          queue_tail(0),
          queue_count(0),
          spi_initialized(false),
          transaction_pending(false),
          transaction_queued_ms(0) {
        memset(queue, 0, sizeof(queue));
        memset(tx_buffer, 0, sizeof(tx_buffer));
        memset(rx_buffer, 0, sizeof(rx_buffer));
        memset(&trans, 0, sizeof(trans));
    }

    bool begin() {
        
        Serial.println("[SPI] starting SPI slave init...");

        pinMode(NOTIFY_PIN, OUTPUT);
        digitalWrite(NOTIFY_PIN, HIGH);

        spi_bus_config_t bus_cfg;
        memset(&bus_cfg, 0, sizeof(bus_cfg));

        bus_cfg.mosi_io_num = MOSI_PIN;
        bus_cfg.miso_io_num = MISO_PIN;
        bus_cfg.sclk_io_num = SCLK_PIN;
        bus_cfg.quadwp_io_num = -1;
        bus_cfg.quadhd_io_num = -1;
        bus_cfg.max_transfer_sz = FRAME_SIZE;

        spi_slave_interface_config_t slv_cfg;
        memset(&slv_cfg, 0, sizeof(slv_cfg));

        slv_cfg.spics_io_num = CS_PIN;
        slv_cfg.flags = 0;
        slv_cfg.queue_size = 1;
        slv_cfg.mode = 0;

        esp_err_t ret = spi_slave_initialize(
            SPI2_HOST,
            &bus_cfg,
            &slv_cfg,
            SPI_DMA_DISABLED
        );

        if (ret != ESP_OK) {
            Serial.printf("[SPI] SPI slave init failed, err=%d 0x%x\n", ret, ret);
            return false;
        }

        spi_initialized = true;
        transaction_pending = false;

        
        Serial.println("[SPI] SPI slave init OK");
        Serial.println("[SPI] not submitting idle frames, waiting for real data from the web");

        return true;
    }

    bool receiveFromWeb(const String& message) {

        #if SPI_DEBUG_LOG
            Serial.print("[SPI] web message received: ");
            Serial.println(message);
        #endif

        if (message.length() != FRAME_SIZE) {
            Serial.printf("[SPI] warning: message length is not 20, got %d, will truncate or pad with 0\n", message.length());
        }

        /*
         * SPI 从机没初始化成功时，这一帧**不可能**被送到 STM32 ——
         * 既没有事务可挂，也没有 GPIO9 脉冲可发。这里如实返回失败，
         * 让网页看到 ok:false，而不是收下、入队、再让它烂在队列里，
         * 同时界面还显示"已发送"。
         */
        if (!spi_initialized) {
            return false;
        }

        uint8_t frame[FRAME_SIZE];
        stringToFrame(message, frame);

        printBuffer("网页消息转换后的帧", frame, FRAME_SIZE);

        if (!enqueueFrame(frame)) {
            return false;
        }

        // 如果当前没有事务挂起，立即提交这一帧并通知 STM32。
        // 如果已有事务挂起，则等当前事务完成后再发送队列中的下一帧。
        prepareNextFrameAndNotifyIfNeeded();

        return true;
    }

    void handleSPI() {
        if (!spi_initialized || !transaction_pending) {
            return;
        }

        spi_slave_transaction_t* ret_trans = nullptr;

        esp_err_t ret = spi_slave_get_trans_result(
            SPI2_HOST,
            &ret_trans,
            0
        );

        if (ret == ESP_OK) {
            transaction_pending = false;

#if SPI_DEBUG_LOG
            Serial.println("[SPI] SPI transfer complete");

            printBuffer("STM32 RX", rx_buffer, FRAME_SIZE);
            printBuffer("ESP32 TX", tx_buffer, FRAME_SIZE);
#endif
            // 当前真实帧已经被 STM32 读走。
            // 如果队列里还有下一帧，则继续提交并再次通知 STM32。
            // 如果队列为空，则什么也不提交，等待下一次网页数据。
            prepareNextFrameAndNotifyIfNeeded();
        } else if (millis() - transaction_queued_ms > SPI_TX_TIMEOUT_MS) {
            /*
             * 事务挂起很久却没被读走 -> 认为 GPIO9 的通知脉冲丢了。
             *
             * 事务本身还在 SPI 从机队列里、tx_buffer 内容也没变，所以只要补一个
             * 下降沿让 STM32 来读即可，不需要取消重排事务。补发后重置计时，
             * 避免每个 loop 都补一次。
             *
             * 兜住的死法：STM32 漏掉脉冲 -> 这一帧永远悬着 -> 后续帧堆满 8 帧
             * 队列 -> 静默丢弃 -> 只能重启 ESP32-C3。
             */
            transaction_queued_ms = millis();

#if SPI_DEBUG_LOG
            Serial.println("[SPI] transaction timed out, notifying STM32 again");
#endif

            pulseNotify();
        }
    }

    /*
    * Whether the SPI slave has been initialized successfully.
    *
    * The caller uses this to distinguish the two reasons
    * receiveFromWeb() returns false:
    *   - queue full (retryable)
    *   - SPI not up at all (retry won't help, check wiring)
    */
    bool isReady() const {
        return spi_initialized;
    }

    void clearQueue() {
        queue_head = 0;
        queue_tail = 0;
        queue_count = 0;

        memset(queue, 0, sizeof(queue));

        Serial.println("[SPI] 发送队列已清空");
    }

    String getQueueStatus() {
        return "Queue: " + String(queue_count) + "/" + String(QUEUE_SIZE);
    }

    bool isTransactionPending() {
        return transaction_pending;
    }
};

#endif