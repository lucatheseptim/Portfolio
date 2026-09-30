// main.c
#include <stdio.h>
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "esp_log.h"
#include "driver/gpio.h"
#include "Config.h"
#include "WebServerHandler.h"
#include "I2CHandler.h"

static const char *TAG = "MAIN";

void app_main(void) {
    ESP_LOGI(TAG, "🚀 Avvio Controllo Servo 12V + MPU6050 + Web Server (AP)");
    
    // 1️⃣ LED per segnalare l'avvio
    gpio_set_direction(LED_GPIO, GPIO_MODE_OUTPUT);
    for (int i = 0; i < 5; i++) {
        gpio_set_level(LED_GPIO, 1);
        vTaskDelay(200 / portTICK_PERIOD_MS);
        gpio_set_level(LED_GPIO, 0);
        vTaskDelay(200 / portTICK_PERIOD_MS);
    }
    
    // 2️⃣ Inizializza I2C (per MPU6050)
    i2c_master_init();
    
    // 3️⃣ Inizializza il servo
    //servo_init();
    //servo_set_angle(90); //  inizializza il servo 

    // 3️⃣ Inizializza il Motore
    motor_init();
    motor_set_speed(0 , true , true);
    
    // 4️⃣ Avvia WiFi (Access Point) e server web
    init_wifi_ap();
    vTaskDelay(2000 / portTICK_PERIOD_MS);
    start_webserver();
    
    ESP_LOGI(TAG, "✅ Sistema pronto!");
    ESP_LOGI(TAG, "📱 Connettiti al WiFi: %s", AP_SSID);
    ESP_LOGI(TAG, "📱 Apri il browser su: http://192.168.4.1");
    
    // 5️⃣ Lampeggio continuo del LED
    while (1) {
        gpio_set_level(LED_GPIO, 1);
        vTaskDelay(500 / portTICK_PERIOD_MS);
        gpio_set_level(LED_GPIO, 0);
        vTaskDelay(500 / portTICK_PERIOD_MS);
        
        // Legge MPU6050 ogni 2 secondi (opzionale)
        float ax, ay, az;
        if (mpu6050_read(&ax, &ay, &az)) {
            // I dati sono disponibili se vuoi usarli
            ESP_LOGI(TAG, "MPU: x=%.2f y=%.2f z=%.2f", ax, ay, az);
        }
    }
}


/*///////////////////////////// VER 1 PROGETTO MULTIMOTORE WEB CONTROL */
/*
#include <stdio.h>
#include <string.h>
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "esp_log.h"
#include "esp_wifi.h"
#include "esp_event.h"
#include "nvs_flash.h"
#include "driver/gpio.h"
#include "driver/i2c.h"
#include "esp_http_server.h"

// ============================================================
// 1. CONFIGURAZIONE WiFi
// ============================================================
#define WIFI_SSID "IlTuoWiFi"
#define WIFI_PASS "LaTuaPassword"

// ============================================================
// 2. PIN MOTORI (TROVATI CON I TEST)
// ============================================================
// Motore 1
#define PWM1_GPIO    3
#define DIR1_GPIO    7
#define BRAKE1_GPIO  23

// Motore 2
#define PWM2_GPIO    10
#define DIR2_GPIO    8
#define BRAKE2_GPIO  24

// Motore 3
#define PWM3_GPIO    2
#define DIR3_GPIO    22
#define BRAKE3_GPIO  26

// LED sulla AirM2M CORE
#define LED_GPIO     12

// ============================================================
// 3. PIN I2C per MPU-6050
// ============================================================
#define I2C_MASTER_SCL_IO   22
#define I2C_MASTER_SDA_IO   21
#define I2C_MASTER_FREQ_HZ  400000

static const char *TAG = "MOTORI_WEB";
// ============================================================
// 4. FUNZIONI PER I MOTORI
// ============================================================
void set_motor_speed(int pwm_pin, int speed) {
    // speed: 0-255 (come analogWrite in Arduino)
    // ESP-IDF usa ledc per il PWM
    // Per semplicità, usiamo gpio_set_level per ora
    // (in una versione completa, usa ledc)
    gpio_set_level(pwm_pin, speed > 0 ? 1 : 0);
}

void set_motor_direction(int dir_pin, int brake_pin, int pwm_pin, const char *dir) {
    if (strcmp(dir, "F") == 0) {
        gpio_set_level(dir_pin, 1);    // Avanti
        gpio_set_level(brake_pin, 0);  // Rilascia freno
        set_motor_speed(pwm_pin, 128); // Velocità 50%
    } else if (strcmp(dir, "B") == 0) {
        gpio_set_level(dir_pin, 0);    // Indietro
        gpio_set_level(brake_pin, 0);  // Rilascia freno
        set_motor_speed(pwm_pin, 128);
    } else if (strcmp(dir, "S") == 0) {
        set_motor_speed(pwm_pin, 0);
        gpio_set_level(brake_pin, 1);  // Freno attivo
    }
}

// ============================================================
// 5. SERVER WEB
// ============================================================
static esp_err_t root_get_handler(httpd_req_t *req) {
    const char* html = 
        "<!DOCTYPE html>"
        "<html>"
        "<head><title>Controllo Motori ESP32-C3</title>"
        "<meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<style>"
        "body { font-family: Arial; text-align: center; margin: 20px; }"
        ".motor { border: 1px solid #ccc; padding: 15px; margin: 10px; border-radius: 10px; }"
        "button { font-size: 24px; padding: 10px 20px; margin: 5px; border-radius: 10px; }"
        ".btn-avanti { background: green; color: white; }"
        ".btn-indietro { background: red; color: white; }"
        ".btn-stop { background: orange; color: white; }"
        "</style>"
        "</head>"
        "<body>"
        "<h1>🚀 Controllo 3 Motori 12V</h1>"
        "<div class='motor'>"
        "<h2>Motore 1</h2>"
        "<button class='btn-avanti' onclick=\"fetch('/motor?m=1&dir=F')\">⬆ AVANTI</button>"
        "<button class='btn-indietro' onclick=\"fetch('/motor?m=1&dir=B')\">⬇ INDIETRO</button>"
        "<button class='btn-stop' onclick=\"fetch('/motor?m=1&dir=S')\">⏹ STOP</button>"
        "</div>"
        "<div class='motor'>"
        "<h2>Motore 2</h2>"
        "<button class='btn-avanti' onclick=\"fetch('/motor?m=2&dir=F')\">⬆ AVANTI</button>"
        "<button class='btn-indietro' onclick=\"fetch('/motor?m=2&dir=B')\">⬇ INDIETRO</button>"
        "<button class='btn-stop' onclick=\"fetch('/motor?m=2&dir=S')\">⏹ STOP</button>"
        "</div>"
        "<div class='motor'>"
        "<h2>Motore 3</h2>"
        "<button class='btn-avanti' onclick=\"fetch('/motor?m=3&dir=F')\">⬆ AVANTI</button>"
        "<button class='btn-indietro' onclick=\"fetch('/motor?m=3&dir=B')\">⬇ INDIETRO</button>"
        "<button class='btn-stop' onclick=\"fetch('/motor?m=3&dir=S')\">⏹ STOP</button>"
        "</div>"
        "<hr>"
        "<button class='btn-avanti' onclick=\"fetch('/motor?m=0&dir=F')\">▶ AVANTI TUTTI</button>"
        "<button class='btn-indietro' onclick=\"fetch('/motor?m=0&dir=B')\">◀ INDIETRO TUTTI</button>"
        "<button class='btn-stop' onclick=\"fetch('/motor?m=0&dir=S')\">⏹ STOP TUTTI</button>"
        "</body>"
        "</html>";
    
    httpd_resp_set_type(req, "text/html");
    httpd_resp_send(req, html, strlen(html));
    return ESP_OK;
}

static esp_err_t motor_post_handler(httpd_req_t *req) {
    char buf[128];
    int ret = httpd_req_recv(req, buf, sizeof(buf) - 1);
    if (ret <= 0) return ESP_FAIL;
    buf[ret] = '\0';
    
    int motor = 0;
    char dir[2] = "S";
    
    // Parsing semplice dei parametri (es. m=1&dir=F)
    char *m_ptr = strstr(buf, "m=");
    char *dir_ptr = strstr(buf, "dir=");
    if (m_ptr) {
        motor = atoi(m_ptr + 2);
    }
    if (dir_ptr) {
        dir[0] = dir_ptr[4];
        dir[1] = '\0';
    }
    
    ESP_LOGI(TAG, "Comando: motore=%d, direzione=%s", motor, dir);
    
    // Applica il comando ai motori
    if (motor == 0) {
        set_motor_direction(DIR1_GPIO, BRAKE1_GPIO, PWM1_GPIO, dir);
        set_motor_direction(DIR2_GPIO, BRAKE2_GPIO, PWM2_GPIO, dir);
        set_motor_direction(DIR3_GPIO, BRAKE3_GPIO, PWM3_GPIO, dir);
    } else if (motor == 1) {
        set_motor_direction(DIR1_GPIO, BRAKE1_GPIO, PWM1_GPIO, dir);
    } else if (motor == 2) {
        set_motor_direction(DIR2_GPIO, BRAKE2_GPIO, PWM2_GPIO, dir);
    } else if (motor == 3) {
        set_motor_direction(DIR3_GPIO, BRAKE3_GPIO, PWM3_GPIO, dir);
    }
    
    httpd_resp_send(req, "OK", 2);
    return ESP_OK;
}

// ============================================================
// 6. CONFIGURAZIONE I2C (per MPU-6050)
// ============================================================
void init_i2c(void) {
    i2c_config_t conf = {
        .mode = I2C_MODE_MASTER,
        .sda_io_num = I2C_MASTER_SDA_IO,
        .scl_io_num = I2C_MASTER_SCL_IO,
        .sda_pullup_en = GPIO_PULLUP_ENABLE,
        .scl_pullup_en = GPIO_PULLUP_ENABLE,
        .master.clk_speed = I2C_MASTER_FREQ_HZ,
    };
    i2c_param_config(I2C_NUM_0, &conf);
    i2c_driver_install(I2C_NUM_0, conf.mode, 0, 0, 0);
    ESP_LOGI(TAG, "✅ I2C inizializzato su SDA=%d, SCL=%d", I2C_MASTER_SDA_IO, I2C_MASTER_SCL_IO);
}

// ============================================================
// 7. INIZIALIZZAZIONE WiFi
// ============================================================
void init_wifi(void) {
    nvs_flash_init();
    esp_netif_init();
    esp_event_loop_create_default();
    
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    esp_wifi_init(&cfg);
    
    wifi_config_t wifi_config = {
        .sta = {
            .ssid = WIFI_SSID,
            .password = WIFI_PASS,
        },
    };
    
    esp_wifi_set_mode(WIFI_MODE_STA);
    esp_wifi_set_config(WIFI_IF_STA, &wifi_config);
    esp_wifi_start();
    
    ESP_LOGI(TAG, "✅ WiFi in connessione a %s...", WIFI_SSID);
}

// ============================================================
// 8. AVVIO DEL SERVER WEB
// ============================================================
void start_webserver(void) {
    httpd_handle_t server = NULL;
    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.lru_purge_enable = true;
    
    if (httpd_start(&server, &config) == ESP_OK) {
        httpd_uri_t uri_root = {
            .uri       = "/",
            .method    = HTTP_GET,
            .handler   = root_get_handler,
            .user_ctx  = NULL
        };
        httpd_register_uri_handler(server, &uri_root);
        
        httpd_uri_t uri_motor = {
            .uri       = "/motor",
            .method    = HTTP_POST,
            .handler   = motor_post_handler,
            .user_ctx  = NULL
        };
        httpd_register_uri_handler(server, &uri_motor);
        
        ESP_LOGI(TAG, "✅ Server web avviato!");
    }
}

// ============================================================
// 9. APP_MAIN (ENTRY POINT)
// ============================================================
void app_main(void) {
    ESP_LOGI(TAG, "🚀 Avvio controllo 3 motori + MPU6050 + Web Server");
    
    // LED per segnalare l'avvio
    gpio_set_direction(LED_GPIO, GPIO_MODE_OUTPUT);
    for (int i = 0; i < 5; i++) {
        gpio_set_level(LED_GPIO, 1);
        vTaskDelay(200 / portTICK_PERIOD_MS);
        gpio_set_level(LED_GPIO, 0);
        vTaskDelay(200 / portTICK_PERIOD_MS);
    }
    
    // Inizializza I2C per MPU-6050
    init_i2c();
    
    // Configura i pin dei motori
    gpio_set_direction(PWM1_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(DIR1_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(BRAKE1_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(PWM2_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(DIR2_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(BRAKE2_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(PWM3_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(DIR3_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(BRAKE3_GPIO, GPIO_MODE_OUTPUT);
    
    // Stato iniziale: tutti fermi
    gpio_set_level(BRAKE1_GPIO, 1);
    gpio_set_level(BRAKE2_GPIO, 1);
    gpio_set_level(BRAKE3_GPIO, 1);
    gpio_set_level(PWM1_GPIO, 0);
    gpio_set_level(PWM2_GPIO, 0);
    gpio_set_level(PWM3_GPIO, 0);
    
    ESP_LOGI(TAG, "✅ Motori configurati e fermi");
    
    // Avvia WiFi e server web
    init_wifi();
    vTaskDelay(3000 / portTICK_PERIOD_MS);
    start_webserver();
    
    ESP_LOGI(TAG, "✅ Sistema pronto!");
    ESP_LOGI(TAG, "📱 Apri il browser sull'indirizzo IP dell'ESP32");
    
    // Lampeggio continuo del LED per indicare che il sistema è vivo
    while (1) {
        gpio_set_level(LED_GPIO, 1);
        vTaskDelay(500 / portTICK_PERIOD_MS);
        gpio_set_level(LED_GPIO, 0);
        vTaskDelay(500 / portTICK_PERIOD_MS);
    }
} */

