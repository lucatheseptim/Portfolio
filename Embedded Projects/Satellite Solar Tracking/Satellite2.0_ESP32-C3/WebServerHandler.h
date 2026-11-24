// WebServerHandler.h
#ifndef WEB_SERVER_HANDLER_H
#define WEB_SERVER_HANDLER_H

#include <WiFi.h>
#include <WebServer.h>
#include "config.h"
#include "HtmlTemplates.h"
#include "SPIHandler.h"
#include <DNSServer.h>
#include <LittleFS.h>

class WebServerHandler {
private:
    WebServer server;
    SPIHandler spiHandler;
    String messageHistory[MAX_HISTORY_SIZE];
    int messageCount;
    String apIp;
    String apMac;
    int currentSystemMode;  // 0: 手动, 1: 随动

    struct ControlState {
        int mode;       // 0 手动，1 随动
        int xVal;       // 0~100
        int xDir;       // 0/1
        int yVal;
        int yDir;
        int zVal;
        int zDir;
        int aVal;       // 舵机/帆板1，SERVO_ANGLE_MIN~SERVO_ANGLE_MAX（30~150）
        int bVal;       // 舵机/帆板2，同上
        unsigned long seq;
        unsigned long updateMillis;
        String source;  // "web" / "s3" / "boot"
        String frame;   // 最新 20 字节 SPI 帧
    };

    struct ScreenMessage {
        unsigned long msgId;
        unsigned long updateMillis;
        String text;
    };

    ControlState controlState;
    ScreenMessage screenMessage;

    /* 最近一次 applyControlState() 失败的原因，供各 HTTP 处理函数拼错误响应。
     * 三种失败：协议帧长度不对、SPI 队列满（帧没能真正下发）、
     * SPI 从机没初始化成功（重试也没用，要查接线）。
     * 有了它，界面才能区分"发出去了"和"没发出去"，而不是一律显示已发送。 */
    String lastApplyError;

    unsigned long imageId;
    size_t imageSize;
    String imageStatus;   // uploading / done

    // 截图通知状态：手机点击截图后递增，ESP32-S3 轮询这个状态
    unsigned long snapshotId;
    unsigned long snapshotMillis;

    // S3 显示控制状态：网页按键修改，ESP32-S3 轮询读取
    unsigned long s3DisplayCmdId;
    int s3DisplayMode;  // 0=idle/stop, 1=live
    unsigned long s3DisplayMillis;

    /*
     * 相机流"租约"。手机相机页每隔几秒续约一次（mode=stop），
     * 停止续约超过 S3_STREAM_CLAIM_TIMEOUT_MS 就把画面还给 S3。
     * 详见 config.h 里该超时的说明 —— 这是手机页面被系统杀掉时唯一的兜底。
     */
    bool s3StreamClaimed;
    unsigned long s3StreamClaimMillis;

    // Captive Portal / DNS：让手机访问任意域名时都能回到 ESP32 页面
    DNSServer dnsServer;
    IPAddress apIPAddr;
    IPAddress apGateway;
    IPAddress apSubnet;

    void toggleLed() {
        digitalWrite(8, !digitalRead(8));
    }

    String buildFullFrame(
        int mode,
        int xVal, int xDir,
        int yVal, int yDir,
        int zVal, int zDir,
        int aVal,
        int bVal
    ) {
        char fullFrame[21];
        snprintf(
            fullFrame,
            sizeof(fullFrame),
            "S%d%03d%d%03d%d%03d%d%03d%03d",
            mode,
            xVal, xDir,
            yVal, yDir,
            zVal, zDir,
            aVal, bVal
        );
        return String(fullFrame);
    }

    bool isValidFrameLength(const String& frame) {
        return frame.length() == 20;
    }

public:
    WebServerHandler()
        : server(WEB_SERVER_PORT),
          messageCount(0),
          currentSystemMode(0),
          snapshotId(0),
          snapshotMillis(0),
          s3DisplayCmdId(0),
          s3DisplayMode(0),
          s3DisplayMillis(0),
          s3StreamClaimed(false),
          s3StreamClaimMillis(0),
          imageId(0),
          imageSize(0),
          apIPAddr(192, 168, 4, 1),
          apGateway(192, 168, 4, 1),
          apSubnet(255, 255, 255, 0) {
        for (int i = 0; i < MAX_HISTORY_SIZE; i++) {
            messageHistory[i] = "";
        }

        controlState.mode = 0;
        controlState.xVal = 0;
        controlState.xDir = 0;
        controlState.yVal = 0;
        controlState.yDir = 0;
        controlState.zVal = 0;
        controlState.zDir = 0;
        controlState.aVal = 90;
        controlState.bVal = 90;
        controlState.seq = 0;
        controlState.updateMillis = 0;
        controlState.source = "boot";
        controlState.frame = buildFullFrame(0, 0, 0, 0, 0, 0, 0, 90, 90);

        screenMessage.msgId = 0;
        screenMessage.updateMillis = 0;
        screenMessage.text = "";
    }

    void begin() {
        pinMode(8, OUTPUT);
        digitalWrite(8, HIGH);

        WiFi.mode(WIFI_AP);
        WiFi.setSleep(false);
        // 固定 AP 地址，手机、ESP32-CAM、ESP32-S3 都以 192.168.4.1 作为网关
        /*
         * 第 4 个参数 dhcp_lease_start：DHCP 池从 .3 开始，**必须留出 .2**。
         * .2 是摄像头的固定地址（见 CAM 的 CameraWebServer.ino 里 WiFi.config 的说明）。
         * 如果 DHCP 把 .2 发给手机，就是两台设备抢同一个地址，ARP 来回翻，
         * 摄像头会变成时通时不通 —— 比地址漂移更难查。
         * 签名已核对：softAPConfig(local_ip, gateway, subnet, dhcp_lease_start, dns)
         */
        if (!WiFi.softAPConfig(apIPAddr, apGateway, apSubnet, IPAddress(192, 168, 4, 3))) {
            Serial.println("[WebServer] AP 固定 IP 配置失败");
        }

        // max_connection 提到 8：手机 + ESP32-S3 + ESP32-CAM 已占 3 个，
        // 原来只留 5 个位置，再多一台设备（第二部手机/笔记本）就会接入失败。
        bool apOk = WiFi.softAP(AP_SSID, AP_PASSWORD, 6, 0, 8);
        if (!apOk) {
            Serial.println("[WebServer] ESP32 AP 启动失败");
        } else {
            Serial.println("[WebServer] ESP32 AP 已启动");
        }

        delay(300);
        apIp = WiFi.softAPIP().toString();
        apMac = WiFi.softAPmacAddress();

        // DNS 通配：手机访问任意网址都会被解析到 ESP32-C3
        if (apOk) {
            dnsServer.start(53, "*", apIPAddr);
            Serial.println("[WebServer] DNS Captive Portal 已启动");
        }

        LittleFS.begin(true);

        setupRoutes();
        server.begin();

        Serial.print("[WebServer] IP: "); Serial.println(apIp);
        Serial.print("[WebServer] MAC: "); Serial.println(apMac);

        if (spiHandler.begin()) {
            Serial.println("[WebServer] SPI 初始化成功");
        } else {
            Serial.println("[WebServer] SPI 初始化失败");
        }
    }

    void handleClient() {
        dnsServer.processNextRequest();
        spiHandler.handleSPI();
        server.handleClient();
        serviceStreamLease();
    }

    /*
     * 相机流租约到期检查。
     *
     * 手机相机页在打开期间每 3 秒发一次 /s3_display_cmd?mode=keep 续租。
     * 只要超过 S3_STREAM_CLAIM_TIMEOUT_MS 没有续租，就认为手机已经不看了
     * —— 页面被系统回收、浏览器挂起、Wi-Fi 掉线这几种情况都**不会**触发
     * pagehide，光靠页面自己的归还逻辑兜不住，S3 会永久停在"已停止"。
     *
     * 这里由 C3 把画面主动还给 S3：递增 cmd_id，让 S3 的轮询看到一次
     * mode=live。S3 只在自己停在相机页时才真的恢复拉流（见 snapshot_viewer.c）。
     */
    void serviceStreamLease() {
        if (!s3StreamClaimed || s3DisplayMode != 0) {
            return;
        }

        if (millis() - s3StreamClaimMillis < S3_STREAM_CLAIM_TIMEOUT_MS) {
            return;
        }

        s3StreamClaimed = false;
        s3DisplayMode = 1;
        s3DisplayCmdId++;
        s3DisplayMillis = millis();

        Serial.println("[WebServer] 相机流租约到期，画面已归还 S3");
    }

private:
    void setupRoutes() {
        server.on("/", [this]() { handleRoot(); });
        server.on("/sendall", [this]() { handleSendAll(); });
        server.on("/setmode", [this]() { handleSetMode(); });
        server.on("/getmode", [this]() { handleGetMode(); });
        server.on("/control", [this]() { handleControl(); });
        server.on("/status", [this]() { handleStatus(); });
        server.on("/history", [this]() { handleHistory(); });
        server.on("/clear", [this]() { handleClear(); });
        server.on("/camera", [this]() { handleCamera(); });
        server.on("/message", [this]() { handleMessagePage(); });

        server.on("/upload", HTTP_POST,
            [this]() {
                server.send(200, "application/json", "{\"ok\":true}");
            },
            [this]() {
                HTTPUpload& upload = server.upload();

                if (upload.status == UPLOAD_FILE_START) {

                    // 上传开始，通知S3不要读取图片
                    imageStatus = "uploading";

                    File f = LittleFS.open("/display.jpg", "w");
                    if (f) f.close();

                    imageSize = 0;

                    Serial.println("Image upload start");
                }

                else if (upload.status == UPLOAD_FILE_WRITE) {

                    File f = LittleFS.open("/display.jpg", "a");

                    if (f) {
                        f.write(upload.buf, upload.currentSize);
                        f.close();

                        imageSize += upload.currentSize;
                    }
                }

                else if (upload.status == UPLOAD_FILE_END) {

                    // 只有完整接收后，S3才能发现新图片
                    imageId++;

                    imageStatus = "done";

                    Serial.println("Image upload done");
                    Serial.printf("Image id:%lu size:%u\\n",
                                  imageId,
                                  imageSize);
                }
            });


        server.on("/image_state", HTTP_GET, [this]() {

            String json = "{";
            json += "\"id\":" + String(imageId) + ",";
            json += "\"status\":\"" + imageStatus + "\",";
            json += "\"size\":" + String(imageSize);
            json += "}";

            server.send(
                200,
                "application/json",
                json
            );
        });

        server.on("/display.jpg", HTTP_GET, [this]() {
            File f = LittleFS.open("/display.jpg", "r");
            if (!f) {
                server.send(404, "text/plain", "no image");
                return;
            }
            server.streamFile(f, "image/jpeg");
            f.close();
        });

        // 新统一 API：网页和 ESP32-S3 共用
        server.on("/api/control", HTTP_POST, [this]() { handleApiControl(); });
        server.on("/api/control", HTTP_GET,  [this]() { handleApiControl(); });
        server.on("/api/emergency_stop", HTTP_POST, [this]() { handleEmergencyStop(); });
        server.on("/api/emergency_stop", HTTP_GET,  [this]() { handleEmergencyStop(); });
        server.on("/api/state",   HTTP_GET,  [this]() { handleApiState(); });
        server.on("/api/message", HTTP_POST, [this]() { handleApiMessage(); });
        server.on("/api/message", HTTP_GET,  [this]() { handleApiMessage(); });
        server.on("/s3_message_state", HTTP_GET, [this]() { handleS3MessageState(); });

        // 手机网页点击“截图”后调用这个接口，C3 记录一次新的截图事件
        server.on("/snapshot_notify", [this]() { handleSnapshotNotify(); });

        // ESP32-S3 轮询这个接口，发现 id 变化后就去 ESP32-CAM 的 /capture 获取图片
        server.on("/snapshot_state", [this]() { handleSnapshotState(); });

        // 摄像头实时显示控制：网页按键发送命令，ESP32-S3 轮询 /s3_display_state 执行
        server.on("/s3_display_cmd", [this]() { handleS3DisplayCmd(); });
        server.on("/s3_display_state", [this]() { handleS3DisplayState(); });

        // 常见手机网络连通性检测路径，直接显示主页或跳转主页
        server.on("/generate_204", [this]() { handleRoot(); });          // Android
        server.on("/gen_204", [this]() { handleRoot(); });               // Android/Chrome
        server.on("/hotspot-detect.html", [this]() { handleRoot(); });   // iOS/macOS
        server.on("/connecttest.txt", [this]() { handleRoot(); });       // Windows
        server.on("/ncsi.txt", [this]() { handleRoot(); });              // Windows
        server.on("/fwlink", [this]() { handleRoot(); });                // Windows
        server.on("/favicon.ico", [this]() {
            server.send(204, "image/x-icon", "");
        });

        // 兜底：任何未注册路径都回到主页，避免手机第一次进入时卡在错误路径
        server.onNotFound([this]() { handleRoot(); });
    }

    String jsonEscape(const String& input) {
        String out;
        out.reserve(input.length() + 16);

        for (size_t i = 0; i < input.length(); i++) {
            char c = input[i];

            if (c == '\\') out += "\\\\";
            else if (c == '"') out += "\\\"";
            else if (c == '\n') out += "\\n";
            else if (c == '\r') out += "\\r";
            else if (c == '\t') out += "\\t";
            else out += c;
        }

        return out;
    }

    bool readControlArgs(ControlState& out, const String& defaultSource) {
        if (!server.hasArg("mode") ||
            !server.hasArg("x_val") || !server.hasArg("x_dir") ||
            !server.hasArg("y_val") || !server.hasArg("y_dir") ||
            !server.hasArg("z_val") || !server.hasArg("z_dir") ||
            !server.hasArg("a_val") || !server.hasArg("b_val")) {
            return false;
        }

        out.mode = server.arg("mode").toInt();

        out.xVal = server.arg("x_val").toInt();
        out.xDir = server.arg("x_dir").toInt();

        out.yVal = server.arg("y_val").toInt();
        out.yDir = server.arg("y_dir").toInt();

        out.zVal = server.arg("z_val").toInt();
        out.zDir = server.arg("z_dir").toInt();

        out.aVal = server.arg("a_val").toInt();
        out.bVal = server.arg("b_val").toInt();

        if (out.mode != 0 && out.mode != 1) out.mode = 0;

        out.xVal = constrain(out.xVal, 0, 100);
        out.yVal = constrain(out.yVal, 0, 100);
        out.zVal = constrain(out.zVal, 0, 100);

        /* 舵机按命令窗口夹紧。与 STM32 的 control.h 口径一致：
         * 超出 30~150 的角度固定到边界，而不是拒绝整条指令。 */
        out.aVal = constrain(out.aVal, SERVO_ANGLE_MIN, SERVO_ANGLE_MAX);
        out.bVal = constrain(out.bVal, SERVO_ANGLE_MIN, SERVO_ANGLE_MAX);

        out.xDir = (out.xDir == 0 || out.xDir == 1) ? out.xDir : 0;
        out.yDir = (out.yDir == 0 || out.yDir == 1) ? out.yDir : 0;
        out.zDir = (out.zDir == 0 || out.zDir == 1) ? out.zDir : 0;

        out.source = server.hasArg("source") ? server.arg("source") : defaultSource;
        if (out.source != "web" && out.source != "s3") {
            out.source = defaultSource;
        }

        return true;
    }

    bool controlPayloadChanged(const ControlState& a, const ControlState& b) {
        return a.xVal != b.xVal ||
               a.xDir != b.xDir ||
               a.yVal != b.yVal ||
               a.yDir != b.yDir ||
               a.zVal != b.zVal ||
               a.zDir != b.zDir ||
               a.aVal != b.aVal ||
               a.bVal != b.bVal;
    }

    void preserveControlPayload(ControlState& target, const ControlState& source) {
        target.xVal = source.xVal;
        target.xDir = source.xDir;
        target.yVal = source.yVal;
        target.yDir = source.yDir;
        target.zVal = source.zVal;
        target.zDir = source.zDir;
        target.aVal = source.aVal;
        target.bVal = source.bVal;
    }

    bool applyControlState(ControlState nextState) {
        String protocolMsg = buildFullFrame(
            nextState.mode,
            nextState.xVal, nextState.xDir,
            nextState.yVal, nextState.yDir,
            nextState.zVal, nextState.zDir,
            nextState.aVal, nextState.bVal
        );

        if (!isValidFrameLength(protocolMsg)) {
            Serial.println("[WebServer] 协议帧长度错误");
            lastApplyError = "frame length";
            return false;
        }

        /*
         * 先把帧放进 SPI 队列，成功之后再推进本地状态。
         *
         * 原来的顺序是"先 controlState = nextState，再 receiveFromWeb"，
         * 而且**丢弃了入队返回值**。队列（8 深）满的时候帧被静默丢掉，
         * 本地状态却已经推进：界面显示"已发送"、seq 也白涨一次，
         * 而卫星从来没收过这条指令。现在入队失败就直接返回 false，
         * 由调用方回 ok:false，界面会提示发送失败。
         */
        if (!spiHandler.receiveFromWeb(protocolMsg)) {
            /* 两种失败要分开报：队列满是可以重试的，SPI 没初始化只能查接线。 */
            bool ready = spiHandler.isReady();
            lastApplyError = ready ? "spi queue full" : "spi not ready";
            Serial.printf("[WebServer] 指令未下发：%s\n", lastApplyError.c_str());
            return false;
        }

        unsigned long nextSeq = controlState.seq + 1;

        controlState = nextState;
        controlState.seq = nextSeq;
        controlState.updateMillis = millis();
        controlState.frame = protocolMsg;
        currentSystemMode = controlState.mode;

        addMessageToHistory(protocolMsg);
        toggleLed();

        Serial.printf(
            "[Control] source=%s seq=%lu frame=%s\n",
            controlState.source.c_str(),
            controlState.seq,
            controlState.frame.c_str()
        );

        return true;
    }

    void setEmergencyStopState(ControlState& state, const String& source) {
        state.mode = 0;
        state.xVal = 0;
        state.xDir = 0;
        state.yVal = 0;
        state.yDir = 0;
        state.zVal = 0;
        state.zDir = 0;
        state.aVal = 90;
        state.bVal = 90;
        state.source = source;
    }

    void handleEmergencyStop() {
        ControlState nextState = controlState;
        setEmergencyStopState(nextState, "web");

        if (!applyControlState(nextState)) {
            server.send(500, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"" + lastApplyError + "\"}");
            return;
        }

        String json = "{";
        json += "\"ok\":true,";
        json += "\"seq\":" + String(controlState.seq) + ",";
        json += "\"source\":\"" + controlState.source + "\",";
        json += "\"mode\":" + String(controlState.mode) + ",";
        json += "\"frame\":\"" + controlState.frame + "\"";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleApiControl() {
        ControlState nextState = controlState;

        if (!readControlArgs(nextState, "web")) {
            server.send(400, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"missing control args\"}");
            return;
        }

        // 随动模式下，禁止通过网页/API 改变飞轮和舵机参数。
        // 允许切回手动模式，也允许只切换到随动模式但保留原控制值。
        bool payloadChanged = controlPayloadChanged(nextState, controlState);
        if (controlState.mode == 1 && nextState.mode == 1 && payloadChanged) {
            server.send(409, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"controls locked in follow mode\"}");
            return;
        }
        if (nextState.mode == 1) {
            preserveControlPayload(nextState, controlState);
        }

        if (!applyControlState(nextState)) {
            server.send(500, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"" + lastApplyError + "\"}");
            return;
        }

        String json = "{";
        json += "\"ok\":true,";
        json += "\"seq\":" + String(controlState.seq) + ",";
        json += "\"source\":\"" + controlState.source + "\",";
        json += "\"frame\":\"" + controlState.frame + "\"";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleApiMessage() {
        String msg = "";

        if (server.hasArg("msg")) {
            msg = server.arg("msg");
        } else if (server.hasArg("text")) {
            msg = server.arg("text");
        } else {
            server.send(400, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"missing msg\"}");
            return;
        }

        msg.trim();

        if (msg.length() == 0) {
            server.send(400, "application/json; charset=utf-8",
                        "{\"ok\":false,\"error\":\"empty msg\"}");
            return;
        }

        if (msg.length() > SCREEN_MESSAGE_MAX_BYTES) {
            msg = msg.substring(0, SCREEN_MESSAGE_MAX_BYTES);
        }

        screenMessage.msgId++;
        screenMessage.updateMillis = millis();
        screenMessage.text = msg;

        String json = "{";
        json += "\"ok\":true,";
        json += "\"msg_id\":" + String(screenMessage.msgId) + ",";
        json += "\"len\":" + String(screenMessage.text.length());
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);

        Serial.printf("[Message] msg_id=%lu text=%s\n",
                      screenMessage.msgId,
                      screenMessage.text.c_str());
    }

    void handleApiState() {
        /* Heartbeat: the page polls this endpoint every second while it is open.
         * This refreshes the motor fail-safe timer inside SatLink. */
        spiHandler.touch();

        unsigned long now = millis();

        unsigned long controlAge = (controlState.seq == 0) ? 0 : (now - controlState.updateMillis);
        unsigned long msgAge = (screenMessage.msgId == 0) ? 0 : (now - screenMessage.updateMillis);
        unsigned long snapshotAge = (snapshotId == 0) ? 0 : (now - snapshotMillis);
        unsigned long displayAge = (s3DisplayCmdId == 0) ? 0 : (now - s3DisplayMillis);

        String displayModeName = (s3DisplayMode == 1) ? "live" : "stop";

        String json = "{";

        json += "\"control\":{";
        json += "\"seq\":" + String(controlState.seq) + ",";
        json += "\"age_ms\":" + String(controlAge) + ",";
        json += "\"source\":\"" + controlState.source + "\",";
        json += "\"mode\":" + String(controlState.mode) + ",";
        json += "\"x_val\":" + String(controlState.xVal) + ",";
        json += "\"x_dir\":" + String(controlState.xDir) + ",";
        json += "\"y_val\":" + String(controlState.yVal) + ",";
        json += "\"y_dir\":" + String(controlState.yDir) + ",";
        json += "\"z_val\":" + String(controlState.zVal) + ",";
        json += "\"z_dir\":" + String(controlState.zDir) + ",";
        json += "\"a_val\":" + String(controlState.aVal) + ",";
        json += "\"b_val\":" + String(controlState.bVal) + ",";
        json += "\"frame\":\"" + controlState.frame + "\"";
        json += "},";

        json += "\"message\":{";
        json += "\"msg_id\":" + String(screenMessage.msgId) + ",";
        json += "\"age_ms\":" + String(msgAge) + ",";
        json += "\"text\":\"" + jsonEscape(screenMessage.text) + "\"";
        json += "},";

        json += "\"snapshot\":{";
        json += "\"id\":" + String(snapshotId) + ",";
        json += "\"age_ms\":" + String(snapshotAge) + ",";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\"";
        json += "},";

        json += "\"display\":{";
        json += "\"cmd_id\":" + String(s3DisplayCmdId) + ",";
        json += "\"mode\":\"" + displayModeName + "\",";
        json += "\"age_ms\":" + String(displayAge) + ",";
        json += "\"stream_url\":\"" + String(CAM_STREAM_URL) + "\",";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\",";
        json += "\"capture_interval_ms\":200";
        json += "}";

        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleS3MessageState() {
        unsigned long age = (screenMessage.msgId == 0) ? 0 : (millis() - screenMessage.updateMillis);

        String json = "{";
        json += "\"msg_id\":" + String(screenMessage.msgId) + ",";
        json += "\"age_ms\":" + String(age) + ",";
        json += "\"text\":\"" + jsonEscape(screenMessage.text) + "\"";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleSnapshotNotify() {
        snapshotId++;
        snapshotMillis = millis();

        String json = "{";
        json += "\"ok\":true,";
        json += "\"id\":" + String(snapshotId) + ",";
        json += "\"age_ms\":0,";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\"";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);

        Serial.printf("[WebServer] 截图事件已更新，snapshotId=%lu\n", snapshotId);
    }

    void handleSnapshotState() {
        unsigned long age = (snapshotId == 0) ? 0 : (millis() - snapshotMillis);

        String json = "{";
        json += "\"id\":" + String(snapshotId) + ",";
        json += "\"age_ms\":" + String(age) + ",";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\"";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleS3DisplayCmd() {
        String mode = server.hasArg("mode") ? server.arg("mode") : "live";

        if (mode == "keep") {
            /*
             * 手机相机页的心跳：只续租，**不**改变状态、**不**递增 cmd_id。
             *
             * 必须单独一个值，不能复用 mode=stop —— S3 是靠 cmd_id 变化才动作的，
             * 如果用 stop 续租，S3 会每 3 秒重新执行一遍"停止"（清图、
             * 改提示文案、刷日志）。真正的"停止"仍然由 mode=stop 表达。
             */
            if (s3StreamClaimed) {
                s3StreamClaimMillis = millis();
            }
            server.sendHeader("Cache-Control", "no-store");
            server.send(200, "application/json; charset=utf-8", "{\"ok\":true,\"renewed\":true}");
            return;
        }

        if (mode == "live") {
            s3DisplayMode = 1;
        } else if (mode == "stop" || mode == "idle") {
            s3DisplayMode = 0;
        } else {
            server.send(400, "application/json; charset=utf-8", "{\"ok\":false,\"error\":\"invalid mode\"}");
            return;
        }

        s3DisplayCmdId++;
        s3DisplayMillis = millis();

        /* 手机认领这一路画面时开始计时；主动归还（mode=live）时立刻解除租约。 */
        if (s3DisplayMode == 0) {
            s3StreamClaimed = true;
            s3StreamClaimMillis = millis();
        } else {
            s3StreamClaimed = false;
        }

        String modeName = (s3DisplayMode == 1) ? "live" : "stop";

        String json = "{";
        json += "\"ok\":true,";
        json += "\"cmd_id\":" + String(s3DisplayCmdId) + ",";
        json += "\"mode\":\"" + modeName + "\",";
        json += "\"stream_url\":\"" + String(CAM_STREAM_URL) + "\",";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\",";
        json += "\"capture_interval_ms\":200";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);

        Serial.printf("[WebServer] S3 显示命令: cmdId=%lu, mode=%s\n", s3DisplayCmdId, modeName.c_str());
    }

    void handleS3DisplayState() {
        unsigned long age = (s3DisplayCmdId == 0) ? 0 : (millis() - s3DisplayMillis);
        String modeName = (s3DisplayMode == 1) ? "live" : "stop";

        String json = "{";
        json += "\"cmd_id\":" + String(s3DisplayCmdId) + ",";
        json += "\"mode\":\"" + modeName + "\",";
        json += "\"age_ms\":" + String(age) + ",";
        json += "\"stream_url\":\"" + String(CAM_STREAM_URL) + "\",";
        json += "\"capture_url\":\"" + String(CAM_CAPTURE_URL) + "\",";
        json += "\"capture_interval_ms\":200";
        json += "}";

        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleRoot() {
        String html = HtmlTemplates::getIndexPage(
            apIp,
            messageCount,
            messageHistory,
            MAX_HISTORY_SIZE
        );
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleCamera() {
        String html = HtmlTemplates::getCameraPage();
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleMessagePage() {
        String html = HtmlTemplates::getMessagePage();
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleSendAll() {
        ControlState nextState = controlState;

        if (!readControlArgs(nextState, "web")) {
            server.send(400, "text/plain; charset=utf-8", "缺少参数");
            return;
        }

        bool payloadChanged = controlPayloadChanged(nextState, controlState);
        if (controlState.mode == 1 && nextState.mode == 1 && payloadChanged) {
            server.send(409, "text/plain; charset=utf-8", "随动模式下飞轮和舵机控制已锁定");
            return;
        }
        if (nextState.mode == 1) {
            preserveControlPayload(nextState, controlState);
        }

        if (!applyControlState(nextState)) {
            server.send(500, "text/plain; charset=utf-8", "下发失败: " + lastApplyError);
            return;
        }

        String html = HtmlTemplates::getSuccessPage(
            "控制指令已发送",
            "协议: " + controlState.frame +
            "<br>来源: 网页" +
            "<br>X: " + String(controlState.xVal) + " " + (controlState.xDir == 0 ? "正转" : "反转") +
            "<br>Y: " + String(controlState.yVal) + " " + (controlState.yDir == 0 ? "正转" : "反转") +
            "<br>Z: " + String(controlState.zVal) + " " + (controlState.zDir == 0 ? "正转" : "反转") +
            "<br>太阳能板1: " + String(controlState.aVal) + "°" +
            "<br>太阳能板2: " + String(controlState.bVal) + "°"
        );
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleSetMode() {
        if (server.hasArg("mode")) {
            int mode = server.arg("mode").toInt();
            if (mode == 0 || mode == 1) {
                ControlState nextState = controlState;
                nextState.mode = mode;
                nextState.source = "web";

                if (!applyControlState(nextState)) {
                    server.send(500, "text/plain; charset=utf-8", "下发失败: " + lastApplyError);
                    return;
                }

                String modeName = (mode == 0) ? "手动控制" : "随动模式";
                String html = HtmlTemplates::getSuccessPage(
                    "模式切换成功",
                    "当前模式: " + modeName + "<br>协议: " + controlState.frame
                );
                server.send(200, "text/html; charset=utf-8", html);
                return;
            }
        }
        server.send(400, "text/plain; charset=utf-8", "无效的模式参数");
    }

    void handleGetMode() {
        String json = "{\"mode\":" + String(currentSystemMode) + "}";
        server.sendHeader("Cache-Control", "no-store");
        server.send(200, "application/json; charset=utf-8", json);
    }

    void handleControl() {
        // 单设备控制接口（保留兼容）。现在改为在现有 ControlState 上局部更新，避免其他设备被清零。
        if (server.hasArg("device") && server.hasArg("value") && server.hasArg("direction")) {
            String device = server.arg("device");
            int value = server.arg("value").toInt();
            int direction = server.arg("direction").toInt();

            bool isMotor = (device == "1" || device == "2" || device == "3");
            bool isServo = (device == "a" || device == "b");

            if (!isMotor && !isServo) {
                server.send(400, "text/plain; charset=utf-8", "错误：无效的设备号");
                return;
            }

            if (currentSystemMode == 1) {
                server.send(409, "text/plain; charset=utf-8", "随动模式下飞轮和舵机控制已锁定");
                return;
            }

            if (isMotor) {
                if (value < 0 || value > 100) {
                    server.send(400, "text/plain; charset=utf-8", "错误：电机转速必须在 0~100 之间");
                    return;
                }
            }

            if (isServo) {
                /* 与 /api/control 以及 STM32 的 control.h 保持同一套口径：按命令窗口
                 * 夹紧，而不是越界报错。原来这里是 0~180 并且越界直接返回 400，
                 * 先和网页滑条的量程对不上，二来同一个值走 /api/control 会被夹紧、
                 * 走这里却被拒绝，两条入口行为不一致。 */
                if (value < SERVO_ANGLE_MIN) value = SERVO_ANGLE_MIN;
                if (value > SERVO_ANGLE_MAX) value = SERVO_ANGLE_MAX;
            }

            if (direction != 0 && direction != 1) direction = 0;

            ControlState nextState = controlState;
            nextState.source = "web";

            if (device == "1") { nextState.xVal = value; nextState.xDir = direction; }
            else if (device == "2") { nextState.yVal = value; nextState.yDir = direction; }
            else if (device == "3") { nextState.zVal = value; nextState.zDir = direction; }
            else if (device == "a") { nextState.aVal = value; }
            else if (device == "b") { nextState.bVal = value; }

            if (!applyControlState(nextState)) {
                server.send(500, "text/plain; charset=utf-8", "下发失败: " + lastApplyError);
                return;
            }

            String deviceName;
            if (device == "1") deviceName = "X轴飞轮";
            else if (device == "2") deviceName = "Y轴飞轮";
            else if (device == "3") deviceName = "Z轴飞轮";
            else if (device == "a") deviceName = "太阳能板1舵机";
            else deviceName = "太阳能板2舵机";

            String valueDesc = isMotor ? ("转速 " + String(value)) : ("角度 " + String(value) + "°");
            String dirDesc = (direction == 0) ? "正转" : "反转";

            String html = HtmlTemplates::getSuccessPage(
                "控制指令已发送",
                "设备: " + deviceName + "<br>" +
                (isMotor ? ("方向: " + dirDesc + "<br>") : "") +
                valueDesc + "<br>协议: " + controlState.frame
            );

            server.send(200, "text/html; charset=utf-8", html);
            return;
        }
        server.send(400, "text/plain; charset=utf-8", "错误：缺少参数");
    }

    void handleStatus() {
        String lastMessage = (messageCount > 0)
            ? messageHistory[(messageCount - 1) % MAX_HISTORY_SIZE]
            : "暂无";
        unsigned long uptime = millis() / 1000;
        String html = HtmlTemplates::getStatusPage(
            AP_SSID, apIp, apMac, uptime, messageCount, lastMessage
        );
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleHistory() {
        String html = HtmlTemplates::getHistoryPage(
            messageCount, messageHistory, MAX_HISTORY_SIZE
        );
        server.send(200, "text/html; charset=utf-8", html);
    }

    void handleClear() {
        clearMessageHistory();
        String html = HtmlTemplates::getSuccessPage(
            "历史记录已清空", "所有消息历史记录已被清除"
        );
        server.send(200, "text/html; charset=utf-8", html);
    }

    void addMessageToHistory(const String& message) {
        messageHistory[messageCount % MAX_HISTORY_SIZE] = message;
        messageCount++;
    }

    void clearMessageHistory() {
        for (int i = 0; i < MAX_HISTORY_SIZE; i++) {
            messageHistory[i] = "";
        }
        messageCount = 0;
    }
};

#endif
