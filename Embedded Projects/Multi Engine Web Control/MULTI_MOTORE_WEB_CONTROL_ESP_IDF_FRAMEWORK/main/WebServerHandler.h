// WebServerHandler.h
#ifndef WEB_SERVER_HANDLER_H
#define WEB_SERVER_HANDLER_H

#include <stdio.h>
#include <string.h>
#include "esp_log.h"
#include "esp_wifi.h"
#include "esp_event.h"
#include "nvs_flash.h"
#include "driver/gpio.h"
#include "driver/ledc.h"
#include "esp_http_server.h"
#include "Config.h"
#include "HtmlTemplates.h"

static const char *TAG_WEB = "WEB_SERVER";

// ============================================================
// 1. PWM (usando LEDC) - usato sia per servo che per motore
// ============================================================
#define LEDC_TIMER      LEDC_TIMER_0
#define LEDC_MODE       LEDC_LOW_SPEED_MODE
#define LEDC_CHANNEL    LEDC_CHANNEL_0
//#define LEDC_DUTY_RES   16   16
//#define LEDC_FREQUENCY  50    // 50Hz per servo, per motore si può aumentare
#define LEDC_DUTY_RES   8  // cambiato da 16 a 8 
#define LEDC_FREQUENCY  1000       // CAMBIATO DA 5000 a 1000

// ============================================================
// 2. FUNZIONI PER IL SERVO (commentate)
// ============================================================
/*
void servo_init(void) {
    ledc_timer_config_t timer_conf = {
        .speed_mode       = LEDC_MODE,
        .timer_num        = LEDC_TIMER,
        .duty_resolution  = LEDC_DUTY_RES,
        .freq_hz          = LEDC_FREQUENCY,
        .clk_cfg          = LEDC_AUTO_CLK
    };
    ledc_timer_config(&timer_conf);
    
    ledc_channel_config_t channel_conf = {
        .gpio_num       = SERVO_GPIO,
        .speed_mode     = LEDC_MODE,
        .channel        = LEDC_CHANNEL,
        .intr_type      = LEDC_INTR_DISABLE,
        .timer_sel      = LEDC_TIMER,
        .duty           = 0,
        .hpoint         = 0
    };
    ledc_channel_config(&channel_conf);
    
    ESP_LOGI(TAG_WEB, "✅ Servo inizializzato su GPIO %d", SERVO_GPIO);
}

void servo_set_angle(int angle) {
    if (angle < 0) angle = 0;
    if (angle > 180) angle = 180;
    
    uint32_t duty = (uint32_t)(((angle * 2000.0 / 180.0) + 500) / 20000.0 * 65536);
    ledc_set_duty(LEDC_MODE, LEDC_CHANNEL, duty);
    ledc_update_duty(LEDC_MODE, LEDC_CHANNEL);
    
    ESP_LOGI(TAG_WEB, "Servo → %d°", angle);
}
*/

// ============================================================
// 3. FUNZIONI PER IL MOTORE DC 12V
// ============================================================
void motor_init(void) {
    // Configura i pin del motore come output
    gpio_set_direction(PWM_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(DIR_GPIO, GPIO_MODE_OUTPUT);
    gpio_set_direction(BRAKE_GPIO, GPIO_MODE_OUTPUT);
    
    // Configura il timer PWM per il motore (frequenza più alta per motori DC)
    ledc_timer_config_t timer_conf = {
        .speed_mode       = LEDC_MODE,
        .timer_num        = LEDC_TIMER,
        .duty_resolution  = LEDC_DUTY_RES,
        .freq_hz          = 5000,      // 5kHz per motori DC (invece di 50Hz)
        .clk_cfg          = LEDC_AUTO_CLK
    };
    ledc_timer_config(&timer_conf);
    
    ledc_channel_config_t channel_conf = {
        .gpio_num       = PWM_GPIO,
        .speed_mode     = LEDC_MODE,
        .channel        = LEDC_CHANNEL,
        .intr_type      = LEDC_INTR_DISABLE,
        .timer_sel      = LEDC_TIMER,
        .duty           = 0,
        .hpoint         = 0
    };
    ledc_channel_config(&channel_conf);
    
    // Stato iniziale: fermo
    gpio_set_level(PWM_GPIO, 0);
    gpio_set_level(DIR_GPIO, 1);     // Avanti (default)
    gpio_set_level(BRAKE_GPIO, 1);   // Freno attivo
    
    ESP_LOGI(TAG_WEB, "✅ Motore DC inizializzato su PWM=%d, DIR=%d, BRAKE=%d", 
             PWM_GPIO, DIR_GPIO, BRAKE_GPIO);
}


void motor_set_speed(int speed, bool direction, bool brake) {
    if (speed < 0) speed = 0;
    if (speed > 255) speed = 255;
    
    gpio_set_level(DIR_GPIO, direction ? 1 : 0);
    gpio_set_level(BRAKE_GPIO, brake ? 1 : 0);
    
    // PER PWM 8 BIT: il duty massimo è 255 (non 65535)
    uint32_t duty = speed;  // 0-255
    ledc_set_duty(LEDC_MODE, LEDC_CHANNEL, duty);
    ledc_update_duty(LEDC_MODE, LEDC_CHANNEL);
    
    ESP_LOGI(TAG_WEB, "Motore → speed=%d, dir=%s, brake=%s", 
             speed, direction ? "avanti" : "indietro", brake ? "on" : "off");
}

// ============================================================
// 4. HANDLER HTTP
// ============================================================

// --- Handler per la pagina principale ---
static esp_err_t root_get_handler(httpd_req_t *req) {
    // Usa getMotorPage() per il motore DC
    // Per usare il servo, decommenta getServoPage() e commenta getMotorPage()
    const char* html = getMotorPage();  // ← Pagina per motore DC
    // const char* html = getServoPage();  // ← Pagina per servo (commentata)
    httpd_resp_set_type(req, "text/html");
    httpd_resp_send(req, html, strlen(html));
    return ESP_OK;
}

// --- Handler per il servo (commentato) ---
/*
static esp_err_t servo_get_handler(httpd_req_t *req) {
    char buf[128];
    int angle = 90;
    
    if (httpd_req_get_url_query_str(req, buf, sizeof(buf)) == ESP_OK) {
        char param[16];
        if (httpd_query_key_value(buf, "angle", param, sizeof(param)) == ESP_OK) {
            angle = atoi(param);
        }
    }
    
    servo_set_angle(angle);
    
    char response[32];
    snprintf(response, sizeof(response), "OK: %d°", angle);
    httpd_resp_send(req, response, strlen(response));
    return ESP_OK;
}
*/

// --- Handler per il motore DC ---
static esp_err_t motor_get_handler(httpd_req_t *req) {
    char buf[128];
    int speed = 0;
    int direction = 1;   // 1 = avanti, 0 = indietro
    int brake = 0;       // 0 = rilascia, 1 = freno attivo
    
    // Estrae i parametri dalla URL (es. /motor?speed=128&dir=1&brake=0)
    if (httpd_req_get_url_query_str(req, buf, sizeof(buf)) == ESP_OK) {
        char param[16];
        
        if (httpd_query_key_value(buf, "speed", param, sizeof(param)) == ESP_OK) {
            speed = atoi(param);
        }
        if (httpd_query_key_value(buf, "dir", param, sizeof(param)) == ESP_OK) {
            direction = atoi(param);
        }
        if (httpd_query_key_value(buf, "brake", param, sizeof(param)) == ESP_OK) {
            brake = atoi(param);
        }
    }
    
    // Limita i valori
    if (speed < 0) speed = 0;
    if (speed > 255) speed = 255;
    if (direction != 0 && direction != 1) direction = 1;
    if (brake != 0 && brake != 1) brake = 0;
    
    // Muove il motore
    motor_set_speed(speed, direction == 1, brake == 1);
    
    // Risponde al browser
    char response[64];
    snprintf(response, sizeof(response), "OK: speed=%d, dir=%d, brake=%d", speed, direction, brake);
    httpd_resp_send(req, response, strlen(response));
    return ESP_OK;
}

// ============================================================
// 5. AVVIO DEL SERVER WEB
// ============================================================
void start_webserver(void) {
    httpd_handle_t server = NULL;
    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.lru_purge_enable = true;
    
    if (httpd_start(&server, &config) == ESP_OK) {
        // --- Pagina principale ---
        httpd_uri_t uri_root = {
            .uri       = "/",
            .method    = HTTP_GET,
            .handler   = root_get_handler,
            .user_ctx  = NULL
        };
        httpd_register_uri_handler(server, &uri_root);
        
        // --- Endpoint per il servo (commentato) ---
        /*
        httpd_uri_t uri_servo = {
            .uri       = "/servo",
            .method    = HTTP_GET,
            .handler   = servo_get_handler,
            .user_ctx  = NULL
        };
        httpd_register_uri_handler(server, &uri_servo);
        */
        
        // --- Endpoint per il motore DC ---
        httpd_uri_t uri_motor = {
            .uri       = "/motor",
            .method    = HTTP_GET,
            .handler   = motor_get_handler,
            .user_ctx  = NULL
        };
        httpd_register_uri_handler(server, &uri_motor);
        
        ESP_LOGI(TAG_WEB, "✅ Server web avviato su 192.168.4.1");
    }
}

// ============================================================
// 6. INIZIALIZZAZIONE WiFi (ACCESS POINT)
// ============================================================
void init_wifi_ap(void) {
    nvs_flash_init();
    esp_netif_init();
    esp_event_loop_create_default();

    //WI-FI CREATE DEFAULT WI-FI
    esp_netif_create_default_wifi_ap();
    
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    esp_wifi_init(&cfg);
    
    wifi_config_t wifi_config = {
        .ap = {
            .ssid = AP_SSID,
            .password = AP_PASSWORD,
            .ssid_len = 0,
            .max_connection = AP_MAX_CONN,
            .authmode = WIFI_AUTH_WPA_WPA2_PSK,
            .channel = AP_CHANNEL,
        },
    };
    
    esp_wifi_set_mode(WIFI_MODE_AP);
    esp_wifi_set_config(WIFI_IF_AP, &wifi_config);
    esp_wifi_start();
    
    ESP_LOGI(TAG_WEB, "✅ WiFi AP avviato!");
    ESP_LOGI(TAG_WEB, "   SSID: %s", AP_SSID);
    ESP_LOGI(TAG_WEB, "   Password: %s", AP_PASSWORD);
    ESP_LOGI(TAG_WEB, "   IP: 192.168.4.1");
}

#endif