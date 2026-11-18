#include "user_spi.h"

#include "control.h"
#include "usart.h"
#include "user_motor.h"
#include <string.h>

/* ESP32 SPI 片选脚。SPI2 的 SCK/MISO/MOSI 在 CubeMX 里初始化。 */
#define ESP_SPI_CS_PORT              GPIOB
#define ESP_SPI_CS_PIN               GPIO_PIN_12

/* 一帧 SPI 传输的超时时间。阻塞模式下用于 HAL_SPI_TransmitReceive。 */
#define ESP_SPI_TIMEOUT_MS           50U

/* 周期轮询关闭，当前只依靠 READY(PB5) 触发读取。 */
#define ESP_SPI_PERIODIC_POLL_MS     0U

/*
 * 调试开关：打开后会打印收到的 20 字节原始数据，便于用串口和逻辑分析仪对照。
 *
 * 默认关闭。UART_Printf 是**阻塞式**发送（HAL_UART_Transmit，115200 波特率下
 * 约 86.8us/字符），而这条打印有 75 个字符 ≈ 6.5ms，并且在每次 SPI 收发成功
 * 后都会执行一次 —— 等于每条控制指令白送 6.5ms 延迟。调试 SPI 帧内容时才打开。
 */
#define ESP_SPI_DEBUG_RX             0U

/*
 * 调试开关：打开后会打印 SPI 关键引脚电平与 HAL 状态。
 *
 * 默认关闭。这条打印约 70 个字符 ≈ 6ms，而每次 SPI 收发**前后各执行一次**
 * （"before DMA" + "after blocking"），合计约 12.5ms 的纯阻塞延迟，
 * 是 SPI 路径上最大的一笔开销。只在用逻辑分析仪对照波形、排查 CS/MISO 接线时打开。
 */
#define ESP_SPI_DEBUG_PINS           0U

/* 当前使用阻塞收发，调通后可改为 1 使用 DMA。 */
#define ESP_SPI_USE_DMA              0U

/* PB5 外部中断置位，通知主循环可以检查 ESP32 READY 状态。 */
volatile uint8_t esp_flag = 0;

/* 0=手动控制，1=自动追光。该变量由 ESP32 协议帧中的 mode 字段更新。 */
volatile uint8_t Mode_Flag = 0;

/*
 * SPI 用户状态机上下文
 *
 * ready_armed 用来避免 READY 一直为低时重复触发：
 * 只有检测到 READY 重新回到高电平后，下一次低电平才会再次发起传输。
 */
static struct {
    volatile bool is_busy;
    volatile bool dma_complete;
    volatile bool error;
    bool ready_armed;
    uint32_t last_transmit_time;
    uint32_t last_poll_time;
    uint8_t tx_data[SPI_BUF_SIZE];
    uint8_t rx_data[SPI_BUF_SIZE];
} esp_spi = {
    .is_busy = false,
    .dma_complete = false,
    .error = false,
    .ready_armed = true,
    .last_transmit_time = 0,
    .last_poll_time = 0,
    .tx_data = {0},
    .rx_data = {0}
};

static bool IsDigit(uint8_t value)
{
    /* 协议里速度、方向、舵机角度都使用 ASCII 数字传输。 */
    return value >= '0' && value <= '9';
}

static uint16_t Parse3Digits(const uint8_t *data)
{
    /* 把 3 个 ASCII 数字解析成 0~999，例如 '2''5''0' -> 250。 */
    return (uint16_t)((data[0] - '0') * 100U + (data[1] - '0') * 10U + (data[2] - '0'));
}

static void PrintRxFrame(const char *tag)
{
    /* 调试用：打印本次 SPI 接收到的完整 20 字节。 */
#if ESP_SPI_DEBUG_RX
    UART_Printf("[ESP SPI] %s:", tag);
    for (uint8_t i = 0; i < SPI_BUF_SIZE; i++) {
        UART_Printf(" %02X", esp_spi.rx_data[i]);
    }
    UART_Printf("\r\n");
#else
    (void)tag;
#endif
}

static bool IsFilledFrame(const uint8_t *rx, uint8_t value)
{
    /* 判断整帧是否全部为 0xFF 或 0x00，用于诊断 MISO 悬空/被拉高/被拉低。 */
    for (uint8_t i = 0; i < SPI_BUF_SIZE; i++) {
        if (rx[i] != value) {
            return false;
        }
    }

    return true;
}

static void PrintSpiPins(const char *tag)
{
    /* 打印 SPI 关键引脚和 HAL 状态，方便和逻辑分析仪波形对应。
     *
     * 由 ESP_SPI_DEBUG_PINS 控制，默认关闭。这里是阻塞式打印，而且
     * StartTransfer() 在收发前后各调一次，合计约 12.5ms。关掉之后
     * 三个调用点保持原样，不需要改。 */
#if ESP_SPI_DEBUG_PINS
    UART_Printf("[ESP SPI] %s CS=%d SCK=%d MISO=%d MOSI=%d state=%lu err=0x%08lX\r\n",
                tag,
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_12),
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_13),
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_14),
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_15),
                (uint32_t)HAL_SPI_GetState(&hspi2),
                hspi2.ErrorCode);
#else
    (void)tag;
#endif
}

static bool ValidateFrame(const uint8_t *rx)
{
    /*
     * 协议帧格式：
     *   rx[0] = 'S'，帧头
     *   rx[1] = '0' 手动模式，后续字段必须全部是数字
     *   rx[1] = '1' 自动追光模式，后续字段暂不解析
     */
    if (rx[0] != 'S') {
        return false;
    }

    if (rx[1] != '0' && rx[1] != '1') {
        return false;
    }

    if (rx[1] == '1') {
        return true;
    }

    for (uint8_t i = 2; i < SPI_BUF_SIZE; i++) {
        if (!IsDigit(rx[i])) {
            return false;
        }
    }

    return true;
}

static bool IsAlternatingTestFrame(const uint8_t *rx)
{
    /* ESP32 测试程序会发送 A5 5A 交替帧，用来验证 MISO/CS/SCK 是否正常。 */
    for (uint8_t i = 0; i < SPI_BUF_SIZE; i++) {
        uint8_t expected = (i % 2 == 0) ? 0xA5 : 0x5A;
        if (rx[i] != expected) {
            return false;
        }
    }

    return true;
}

uint8_t mode=0;
static void ProcessReceivedData(void)
{
    uint8_t *rx = esp_spi.rx_data;
	

    /* 全 0xFF 通常表示 ESP32 没有驱动 MISO，或者 MISO 被上拉/接线错误。 */
    if (IsFilledFrame(rx, 0xFF)) {
        PrintRxFrame("MISO high/floating");
        UART_Printf("[ESP SPI] all 0xFF: ESP is not driving MISO, or MISO is pulled high / wired wrong\r\n");
        return;
    }

    /* 全 0x00 通常表示 MISO 被拉低，或者 SPI 模式/CS 时序不对。 */
    if (IsFilledFrame(rx, 0x00)) {
        PrintRxFrame("MISO low");
        UART_Printf("[ESP SPI] all 0x00: MISO is held low, or SPI mode/CS is wrong\r\n");
		mode=0;
        return;
    }

    /* 测试帧只用于链路验证，不参与电机控制。 */
    if (IsAlternatingTestFrame(rx)) {
        UART_Printf("[ESP SPI] test frame OK: A5 5A alternating\r\n");
        return;
    }

    /* 无效帧直接丢弃，避免把错误数据转换成电机动作。 */
    if (!ValidateFrame(rx)) {
        PrintRxFrame("invalid frame");
        return;
    }

     mode = rx[1] - '0';
    Mode_Flag = mode;

    /* 自动追光模式下，只更新模式标志，电机控制交给 Track_Process。 */
    if (mode == 1) {
        /* 同样是阻塞打印（约 22 字符 ≈ 1.9ms），归到 ESP_SPI_DEBUG_RX 下。
         * 这一条曾经漏在开关外面：随动模式下每一帧都要走，等于把刚省下来的
         * 25ms 又还回去 1.9ms，而且翻 ESP_SPI_DEBUG_RX 也关不掉它。 */
#if ESP_SPI_DEBUG_RX
        UART_Printf("[ESP SPI] mode=track\r\n");
#endif
        return;
    }

    /*
     * 手动模式字段布局，共 20 字节：
     *   0      : 'S'
     *   1      : mode，'0'=手动
     *   2~4    : X PWM，000~999
     *   5      : X 方向，0/1
     *   6~8    : Y PWM，000~999
     *   9      : Y 方向，0/1
     *   10~12  : Z PWM，000~999
     *   13     : Z 方向，0/1
     *   14~16  : 舵机1角度，000~180（本机只接受 30~150，超出会被夹到边界）
     *   17~19  : 舵机2角度，000~180（同上）
     *
     * 舵机角度这里不做裁剪，统一交给 Turn_Servo() 按命令窗口处理。
     */
    uint16_t x_val = Parse3Digits(&rx[2])*10;
    uint8_t x_dir = rx[5] - '0';
    uint16_t y_val = Parse3Digits(&rx[6])*10;
    uint8_t y_dir = rx[9] - '0';
    uint16_t z_val = Parse3Digits(&rx[10])*10;
    uint8_t z_dir = rx[13] - '0';
    uint16_t servo1_angle = Parse3Digits(&rx[14]);
    uint16_t servo2_angle = Parse3Digits(&rx[17]);

    /*收到的全为0，模式切换中不处理*/
    if(x_val == 0 && y_val == 0 && z_val == 0 && servo1_angle == 0 && servo2_angle == 0) {
        /*
         * 这一帧对电机是 no-op，对舵机也必须是 no-op —— 所以把目标冻结在
         * 当前位置，而不是留在上一拍的值上。
         *
         * 这段代码只有 mode == 0 才走得到（mode == 1 在上面已经 return），
         * 而"全部归零"正是急停和模式切换用的帧。不冻结的话，刚从随动切过来的
         * 话舵机会继续朝上一帧光照算出的角度滑过去，"急停"就停不干净。
         */
        Servo_Ramp_Hold();
        return;
    }

    /* 约 64 个字符 ≈ 5.6ms 的阻塞打印，归到 ESP_SPI_DEBUG_RX 下。
     * 上面解析出的这些变量在下面的电机/舵机调用里都还会用到，所以关掉打印
     * 不会产生 unused 警告，也不需要额外的 (void) 强转。 */
#if ESP_SPI_DEBUG_RX
    UART_Printf("[ESP SPI] mode=manual x=%u/%u y=%u/%u z=%u/%u s1=%u s2=%u\r\n",
                x_val, x_dir, y_val, y_dir, z_val, z_dir, servo1_angle, servo2_angle);
#endif

    /*
     * 轴到电机的映射：
     *   X -> Motor_2 -> PB0 / PA5
     *   Y -> Motor_1 -> PA7 / PA4
     *   Z -> Motor_3 -> PB1 / PA6
     *
     * 如果机械结构希望 X/Y/Z 对应 Motor_1/2/3，可以只改这里。
     */
    
    if (x_val >= 0) {
        motor_turn_set(Motor_1, (x_dir == 0) ? GPIO_PIN_RESET : GPIO_PIN_SET);
        motor_PWM_set(Motor_1, 0, x_val);
    } else {
        motor_PWM_set(Motor_1, 0, 0);
    }

    if (y_val >= 0) {
        motor_turn_set(Motor_2, (y_dir == 0) ? GPIO_PIN_RESET : GPIO_PIN_SET);
        motor_PWM_set(Motor_2, 0, y_val);
    } else {
        motor_PWM_set(Motor_2, 0, 0);
    }

    if (z_val >= 0) {
        motor_turn_set(Motor_3, (z_dir == 0) ? GPIO_PIN_RESET : GPIO_PIN_SET);
        motor_PWM_set(Motor_3, 0, z_val);
    } else {
        motor_PWM_set(Motor_3, 0, 0);
    }

    /*
     * 不要在这里窄化成 uint8_t：解析结果最大 999，窄化会回绕并让舵机反向猛冲。
     * Servo_Ramp_SetGoal() 内部会按命令窗口 30~150 夹紧（0~30 固定为 30，
     * 150~180 固定为 150）。
     *
     * 这里只**设目标**，不直接写比较寄存器：舵机由 Servo_Ramp_Process() 按
     * SERVO_SLEW_DPS 平滑推过去，与随动模式同一套限速，观感一致。
     * 连续帧不会打架 —— 新目标直接覆盖旧的，斜坡下一拍就改向新目标。
     */
    Servo_Ramp_SetGoal(1, servo1_angle);
    Servo_Ramp_SetGoal(2, servo2_angle);
}

static bool ShouldStartTransfer(void)
{
    uint32_t now = HAL_GetTick();
    GPIO_PinState ready_state = HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_5);

    /* 上一次 SPI 传输还没结束时，不允许启动新传输。 */
    if (esp_spi.is_busy) {
        return false;
    }

    /*
     * 先看边沿通知，并且**绝不因为 READY 现在已经回到高电平就把它清掉**。
     *
     * ESP32 的 READY 是 100us 宽度的窄脉冲（SPIHandler.h 的 pulseNotify），
     * 不是 user_spi.h 里描述的"低电平表示有待读帧"那种电平信号。
     * STM32 在随动模式下主循环约 250us 一轮（每轮一次 BH1750 的 I2C 事务，
     * 每 10ms 还有一次 14 字节的 MPU6050 读），比脉冲还慢，很容易在 READY
     * 已经拉高之后才轮询到这里。但 PB5 的下降沿中断已经把这次通知锁存在
     * esp_flag 里了，不能丢。
     *
     * 丢掉一次的后果是**不可恢复**的：ESP32 那边 transaction_pending 会一直
     * 悬着（prepareNextFrameAndNotifyIfNeeded 直接 return），再也不会发新脉冲，
     * SPI 就此彻底死掉 —— 现象就是从手动切到随动之后再也没法切回手动，只能重启
     * ESP32。晚一点打时钟没有关系，ESP32 的事务会一直排队等着。
     */
    if (esp_flag) {
        esp_flag = 0;
        esp_spi.ready_armed = false;
        return true;
    }

    /*
     * 兼容 READY 保持低电平的电平式通知。
     * ready_armed 用来防止持续低电平时重复读同一帧。
     */
    if (ready_state == GPIO_PIN_RESET && esp_spi.ready_armed) {
        esp_flag = 0;
        esp_spi.ready_armed = false;
        return true;
    }

    /* READY 回到高电平，重新武装下一次低电平触发。 */
    if (ready_state == GPIO_PIN_SET) {
        esp_spi.ready_armed = true;
    }

    /* 保留周期轮询能力，默认关闭。 */
    if (ESP_SPI_PERIODIC_POLL_MS > 0U && now - esp_spi.last_poll_time >= ESP_SPI_PERIODIC_POLL_MS) {
        esp_spi.last_poll_time = now;
        return true;
    }

    return false;
}

static void SPI_ESP_Recover(void)
{
    /* SPI 异常恢复：停止 DMA、复位 SPI 使能位、释放 CS，并清理状态机标志。 */
    HAL_SPI_DMAStop(&hspi2);
    __HAL_SPI_DISABLE(&hspi2);
    __HAL_SPI_ENABLE(&hspi2);
    HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_SET);
    esp_spi.is_busy = false;
    esp_spi.dma_complete = false;
    esp_spi.error = false;
    esp_spi.ready_armed = false;
}

static bool StartTransfer(void)
{
    /* 每次收发前清空 RX，并把 TX 填成查询字节 0xFF。 */
    memset(esp_spi.rx_data, 0, sizeof(esp_spi.rx_data));
    memset(esp_spi.tx_data, ESP_QUERY_CMD, sizeof(esp_spi.tx_data));

    esp_spi.is_busy = true;
    esp_spi.dma_complete = false;
    esp_spi.error = false;
    esp_spi.last_transmit_time = HAL_GetTick();

    HAL_Delay(1);

    /* 拉低 CS 后启动 SPI。ESP32 必须已经提前 queue 好 slave transaction。 */
    HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_RESET);
    PrintSpiPins("before DMA");

#if ESP_SPI_USE_DMA
    /* DMA 模式：启动后立即返回，完成时由 HAL_SPI_TxRxCpltCallback 通知。 */
    HAL_StatusTypeDef status = HAL_SPI_TransmitReceive_DMA(
        &hspi2,
        esp_spi.tx_data,
        esp_spi.rx_data,
        SPI_BUF_SIZE
    );

    if (status != HAL_OK) {
        HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_SET);
        esp_spi.is_busy = false;
        UART_Printf("[ESP SPI] DMA start failed ret=%d state=%lu err=0x%08lX\r\n",
                    status,
                    (uint32_t)HAL_SPI_GetState(&hspi2),
                    hspi2.ErrorCode);
        return false;
    }

    return true;
#else
    /* 阻塞模式：当前调试阶段使用，时序更直观，便于排查 MISO/CS 问题。 */
    HAL_StatusTypeDef status = HAL_SPI_TransmitReceive(
        &hspi2,
        esp_spi.tx_data,
        esp_spi.rx_data,
        SPI_BUF_SIZE,
        ESP_SPI_TIMEOUT_MS
    );

    HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_SET);
    PrintSpiPins("after blocking");
    esp_spi.is_busy = false;
    esp_spi.dma_complete = false;

    if (status != HAL_OK) {
        UART_Printf("[ESP SPI] blocking failed ret=%d state=%lu err=0x%08lX\r\n",
                    status,
                    (uint32_t)HAL_SPI_GetState(&hspi2),
                    hspi2.ErrorCode);
        SPI_ESP_Recover();
        return false;
    }

    PrintRxFrame("rx");
    ProcessReceivedData();
    return true;
#endif
}

static void CheckTransferCompletion(void)
{
    /* 阻塞模式下 is_busy 很快清零；DMA 模式下需要在主循环里检查完成标志。 */
    if (!esp_spi.is_busy) {
        return;
    }

    if (esp_spi.dma_complete) {
        /* DMA 完成后释放 CS，再解析收到的数据。 */
        HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_SET);
        PrintSpiPins("after DMA");
        esp_spi.is_busy = false;

        if (esp_spi.error) {
            UART_Printf("[ESP SPI] callback error err=0x%08lX\r\n", hspi2.ErrorCode);
            SPI_ESP_Recover();
            return;
        }

        PrintRxFrame("rx");
        ProcessReceivedData();
        return;
    }

    if (hspi2.ErrorCode != HAL_SPI_ERROR_NONE) {
        /* HAL 层检测到错误时尝试恢复 SPI 状态。 */
        UART_Printf("[ESP SPI] HAL error err=0x%08lX\r\n", hspi2.ErrorCode);
        SPI_ESP_Recover();
        return;
    }

    if (HAL_GetTick() - esp_spi.last_transmit_time > ESP_SPI_TIMEOUT_MS) {
        /* DMA 模式兜底超时，避免状态机一直卡在 busy。 */
        UART_Printf("[ESP SPI] timeout state=%lu err=0x%08lX\r\n",
                    (uint32_t)HAL_SPI_GetState(&hspi2),
                    hspi2.ErrorCode);
        SPI_ESP_Recover();
    }
}

void SPI_ESP_Init(void)
{
    /* 初始化用户状态机，并默认释放 CS。 */
    esp_spi.is_busy = false;
    esp_spi.dma_complete = false;
    esp_spi.error = false;
    esp_spi.ready_armed = true;
    esp_spi.last_transmit_time = 0;
    esp_spi.last_poll_time = HAL_GetTick();
    memset(esp_spi.tx_data, ESP_QUERY_CMD, sizeof(esp_spi.tx_data));
    memset(esp_spi.rx_data, 0, sizeof(esp_spi.rx_data));

    HAL_GPIO_WritePin(ESP_SPI_CS_PORT, ESP_SPI_CS_PIN, GPIO_PIN_SET);
}

void SPI_ESP_Process(void)
{
    /* 先处理未完成传输，再判断是否需要开启新的传输。 */
    CheckTransferCompletion();

    if (ShouldStartTransfer()) {
        StartTransfer();
    }
}

void SPI_ESP_DMACompleteCallback(void)
{
    /* 中断回调中只置位，数据处理放到主循环中完成。 */
    esp_spi.dma_complete = true;
}

void SPI_ESP_ErrorCallback(void)
{
    /* SPI 错误也走完成路径，由主循环统一恢复。 */
    esp_spi.error = true;
    esp_spi.dma_complete = true;
}
