// Config.h
#ifndef CONFIG_H
#define CONFIG_H

// ============================================================
// 1. CONFIGURAZIONE WiFi (ACCESS POINT - l'ESP32 crea il WiFi)
// ============================================================
//#define AP_SSID "卫星演示控制界面"
#define AP_SSID "ESP_MOTOR_WEB_CONTROL" // Titolo per l'Access Point Wi-FI
//#define AP_SSID "卫星演示控制界面" // Titolo per l'Access Point Wi-FI
#define AP_PASSWORD "12345678"
#define AP_CHANNEL 6 
#define AP_MAX_CONN 5

// ============================================================
// 2. CONFIGURAZIONE SERVER
// ============================================================
#define WEB_SERVER_PORT 80
#define MAX_HISTORY_SIZE 10

// ============================================================
// 3. PIN DEL SERVO
// ============================================================
//#define SERVO_GPIO 3
//#define LED_GPIO 12

 
// 3. PIN DEL MOTORE (PIN VALIDI PER ESP32-C3)
// ============================================================
#define PWM_GPIO 3      // ✅ OK (GPIO 3)
#define DIR_GPIO 4      // ✅ OK (GPIO 4) 
#define BRAKE_GPIO 5    // ✅ OK (GPIO 5) 
#define LED_GPIO 12      // ✅ OK (GPIO 8)

// ============================================================
// 4. PIN I2C per MPU-6050 (PIN VALIDI PER ESP32-C3)
// ============================================================
#define I2C_MASTER_SCL_IO   9   // ✅ OK (GPIO 9) - CAMBIATO DA 22 a 9
#define I2C_MASTER_SDA_IO   8   // ✅ OK (GPIO 8) - CAMBIATO DA 21 a 8
#define I2C_MASTER_FREQ_HZ  100000  // RIDOTTO a 100kHz per stabilità
#define MPU6050_ADDR        0x68


// ============================================================
// 5. PARTI NON USATE (commentate per non creare conflitti)
// ============================================================
// #define SCREEN_MESSAGE_MAX_BYTES 300
// #define CAM_STREAM_URL "http://192.168.4.2:81/stream"
// #define CAM_CAPTURE_URL "http://192.168.4.2/capture"

#endif