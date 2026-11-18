
// main.cpp
#include <Arduino.h>
//#include "Config.h"
#include "WebServerHandler.h"

WebServerHandler webServer;

void setup() {

    Serial.begin(115200);
    delay(2000);
    Serial.println("=== Setup Start ===");
    
    //pinMode(8, OUTPUT);       // GPIO8作为网页指示灯
    //digitalWrite(8, LOW);   // 默认高电平
    //pinMode(9, OUTPUT);      // GPIO2作为通知引脚
    //digitalWrite(9, HIGH);   // 默认高电平
    //pinMode(10, INPUT);//CS脚初始化
    //pinMode(10, INPUT_PULLUP);   // 替换 INPUT
    
    // 初始化串口
    //Serial.begin(115200);
    delay(1000);  // 等待串口初始化
   
    // 打印系统信息
    //webServer.printSystemInfo();
    // 启动Web服务器
    webServer.begin();
    Serial.println("esp32 ");
}

void loop() {
    // 处理客户端请求
    webServer.handleClient();
    //delay(1000);
    //Serial.println("loop");
    delay(1);  // 防止看门狗复位
}

/*
#include <Arduino.h>
#define LED_PIN 8   // 请修改为您的板载LED实际引脚
void setup() {
    Serial.begin(115200);
    
    delay(2000);
    Serial.println("USB CDC");
    pinMode(LED_PIN, OUTPUT);
}

void loop() {
    digitalWrite(LED_PIN, !digitalRead(LED_PIN));
    delay(500);
    Serial.println("Loop");
}
*/
