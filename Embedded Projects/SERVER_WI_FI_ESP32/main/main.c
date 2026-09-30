/*///////////////////////////////////////////////////OLD VERSION WITHOUT MAVLINK */
/*
// ================================================================
// SLAVE - FTM RESPONDER per ESP32-C3
// Ruolo: Crea un Access Point WiFi e risponde alle richieste FTM
//        Non calcola distanze, fornisce solo i timestamp
//        Versione con HEARTBEAT per mantenere stabile la connessione
// In FreeRTOS, ogni task creato con xTaskCreate() viene eseguito in parallelo agli altri. 
// Ci sono due task principali che girano in parallelo, ma anche altri task di sistema che FreeRTOS gestisce automaticamente.

// ================================================================

// ================================================================
// PARTE 1: LIBRERIE INCLUSE
// ================================================================

#include <string.h>                  // Per strlen() - calcola lunghezza stringhe
#include "freertos/FreeRTOS.h"       // Sistema operativo real-time => Operation System of Esp32
#include "freertos/task.h"           // Per vTaskDelay() - pause
#include "esp_system.h"              // Funzioni di sistema ESP
#include "esp_wifi.h"                // Driver WiFi principale
#include "esp_event.h"               // Sistema eventi asincroni
#include "esp_log.h"                 // Logging (ESP_LOGI, ESP_LOGE) , LOGGING ESP32 
#include "nvs_flash.h"               // Memoria non volatile
#include "esp_netif.h"               // Per interfacce di rete (AP/STA)



// ================================================================
// PARTE 2: DEFINIZIONI (configurazione)
// ================================================================

#define AP_SSID "ESP_FTM_AP"              // Nome della rete WiFi
#define AP_PASS "12345678"                // Password (minimo 8 caratteri)
#define MAX_STA_CONN 4                    // Max client connessi

// AGGIUNTO PER FIX #4: Intervallo heartbeat (30 secondi)
#define HEARTBEAT_INTERVAL_MS 30000       // 30 secondi

static const char *TAG = "FTM_RESPONDER"; // Tag per i log

// ================================================================
// PARTE 3: TASK HEARTBEAT ( AGGIUNTO PER FIX #4)
// ================================================================
// Scopo: Mantenere stabile l'AP verificando che sia sempre attivo
//        Questo previene il timeout di inattività del WiFi
//        Il task "batte" ogni 30 secondi come un cuore

static void heartbeat_task(void *pvParameters)
{
    // Loop infinito: il task non deve mai terminare
    while (1)
    {
        //  PASSO 1: Aspetta 30 secondi
        // Il task si "addormenta" per 30 secondi
        // Durante questo tempo non consuma CPU
        vTaskDelay(pdMS_TO_TICKS(HEARTBEAT_INTERVAL_MS));
        
        //  PASSO 2: Verifica che l'AP sia ancora attivo
        // Chiede al driver WiFi: "Che modalità sei?"
        // WIFI_MODE_AP = Access Point (lo slave)
        wifi_mode_t mode;
        esp_err_t err = esp_wifi_get_mode(&mode);
        
        //  PASSO 3: Controlla se l'AP è attivo
        // Se la funzione fallisce (err != ESP_OK) OPPURE
        // Se il WiFi non è in modalità AP (mode != WIFI_MODE_AP)
        // → L'AP non è attivo!
        if (err != ESP_OK || mode != WIFI_MODE_AP)
        {
            //  AP non attivo! Riavvia il WiFi
            ESP_LOGW(TAG, " AP inattivo, riavvio...");
            
            // PASSO 4: Riavvia l'AP
            // Come quando il telefono non funziona e lo riavvii
            esp_wifi_stop();                // Spegni WiFi
            vTaskDelay(pdMS_TO_TICKS(1000)); // Aspetta 1 secondo
            esp_wifi_start();               // Riavvia WiFi
            
            ESP_LOGI(TAG, " AP riavviato!");
        }
        else
        {
            // AP attivo: tutto ok!
            // ESP_LOGD è per debug (non viene stampato di default)
            ESP_LOGD(TAG, " Heartbeat - AP OK");
        }
    }
}

// ================================================================
// PARTE 4: FUNZIONE PRINCIPALE - app_main()
// ================================================================

void app_main(void)
{
    ESP_LOGI(TAG, " Avvio FTM Responder (con Heartbeat)");

    // ------------------------------------------------------------------
    // PASSO 1: Inizializzazione della memoria NVS
    // ------------------------------------------------------------------
    // NVS è la memoria dove ESP32 salva le configurazioni WiFi
    // Tra un riavvio e l'altro
    
    esp_err_t ret = nvs_flash_init();
    
    // Se la memoria è corrotta o c'è una nuova versione
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES || ret == ESP_ERR_NVS_NEW_VERSION_FOUND)
    {
        // Cancella tutto il contenuto di NVS
        ESP_ERROR_CHECK(nvs_flash_erase());
        
        // Reinizializza NVS dopo la cancellazione
        ret = nvs_flash_init();
    }
    
    // Se nvs_flash_init() fallisce ancora, blocca il programma
    ESP_ERROR_CHECK(ret);

    // ------------------------------------------------------------------
    // PASSO 2: Inizializzazione dell'interfaccia di rete
    // ------------------------------------------------------------------
    // Preparazione del stack TCP/IP per la comunicazione di rete
    
    ESP_ERROR_CHECK(esp_netif_init());              // Stack TCP/IP
    ESP_ERROR_CHECK(esp_event_loop_create_default()); // Sistema eventi
    esp_netif_create_default_wifi_ap();            // Crea interfaccia AP

    // ------------------------------------------------------------------
    // PASSO 3: Inizializzazione del driver WiFi
    // ------------------------------------------------------------------
    // Prepara il chip WiFi con le configurazioni standard
    
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    // ------------------------------------------------------------------
    // PASSO 4: Configurazione dell'Access Point (AP)
    // ------------------------------------------------------------------
    // Questa è la parte più importante per lo SLAVE:
    // configuriamo il WiFi come Access Point con FTM responder abilitato
    wifi_config_t wifi_config = {
            .ap = {
                .ssid = AP_SSID,   // Nome rete
                .password = AP_PASS, // Password
                .ssid_len = strlen(AP_SSID),  // Lunghezza nome
                .channel = 6,  // Canale WiFi
                .authmode = WIFI_AUTH_WPA2_PSK,  // Sicurezza WPA2
                .ssid_hidden = 0,
                .max_connection = 4,  // Max 4 client
                .beacon_interval = 100,
                .ftm_responder = true // ABILITA FTM!
            },
    };

    // ------------------------------------------------------------------
    // PASSO 5: Applicazione configurazione e avvio WiFi
    // ------------------------------------------------------------------
    
    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_AP));  // Modalità AP
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_AP, &wifi_config)); // Applica
    ESP_ERROR_CHECK(esp_wifi_start());                  // Avvia WiFi

    // ------------------------------------------------------------------
    // PASSO 6: AVVIA HEARTBEAT TASK (FIX #4)
    // ------------------------------------------------------------------
    // Questo task mantiene stabile l'AP nel tempo
    // Senza heartbeat, la connessione cadrebbe dopo 4 minuti
    
    xTaskCreate(
        heartbeat_task,          // Funzione da eseguire
        "heartbeat_task",        // Nome del task
        4096,                    // Stack size (memoria allocata)
        NULL,                    // Parametri (nessuno)
        5,                       // Priorità (5 = alta)
        NULL                     // Handle (non ci serve)
    );
    
    ESP_LOGI(TAG, " Heartbeat task avviato (ogni %d ms)", HEARTBEAT_INTERVAL_MS);

    // ------------------------------------------------------------------
    // PASSO 7: Messaggi di conferma
    // ------------------------------------------------------------------
    
    ESP_LOGI(TAG, " FTM Responder AP avviato!");
    ESP_LOGI(TAG, "    SSID: %s", AP_SSID);
    ESP_LOGI(TAG, "    Password: %s", AP_PASS);
    ESP_LOGI(TAG, "    FTM responder: ATTIVO (in attesa di richieste)");

    // ------------------------------------------------------------------
    //  PASSO 8: LOOP INFINITO (FIX #4)
    // ------------------------------------------------------------------
    // Mantiene attivo il task principale
    // Senza questo loop, il programma terminerebbe
    // Anche se il WiFi continuerebbe a funzionare, il task heartbeat
    // potrebbe essere terminato dal sistema operativo
    
    while (1)
    {
        vTaskDelay(pdMS_TO_TICKS(10000));  // Aspetta 10 secondi
        // Il loop non fa nulla, ma mantiene attivo il programma
    }
    
    // ------------------------------------------------------------------
    // NOTA: Il codice non arriva mai qui perché il loop è infinito!
    // Lo slave rimane in attesa passiva. Il driver WiFi gestisce 
    // automaticamente le richieste FTM in background.
    // Non appena il master invia una richiesta, lo slave risponde.
    // Il programma termina qui, ma il WiFi continua a funzionare.
    // ================================================================
}
*/


// ================================================================
// SLAVE - FTM RESPONDER per ESP32-C3 con MAVLink + Ebyte
// Ruolo: Crea AP WiFi, risponde a richieste FTM,
//        invia dati MAVLink via Ebyte alla stazione di terra
// ================================================================



/////////////////////// CON MAVLINK PROCOL 
//In FreeRTOS, ogni task creato con xTaskCreate() viene eseguito in parallelo agli altri. 
//Ci sono due task principali che girano in parallelo, ma anche altri task di sistema che FreeRTOS gestisce automaticamente.

// ================================================================
// PARTE 1: LIBRERIE INCLUSE
// ================================================================

#include <string.h>                  // Per strlen() - calcola lunghezza stringhe
#include "freertos/FreeRTOS.h"       // Sistema operativo real-time => Operation System of Esp32
#include "freertos/task.h"           // Per vTaskDelay() - pause
#include "esp_system.h"              // Funzioni di sistema ESP
#include "esp_wifi.h"                // Driver WiFi principale
#include "esp_event.h"               // Sistema eventi asincroni
#include "esp_log.h"                 // Logging (ESP_LOGI, ESP_LOGE)
#include "nvs_flash.h"               // Memoria non volatile
#include "esp_netif.h"               // Per interfacce di rete (AP/STA)

// AGGIUNTE PER EBYTE
#include "driver/uart.h"  // UART	Stiamo passando da Serial1 (Arduino) a uart_driver (ESP-IDF) che fanno la stessa cosa
#include "driver/gpio.h"
 
// AGGIUNTE PER MAVLINK
#include "mavlink.h"

// ================================================================
// PARTE 2: DEFINIZIONI (configurazione)
// ================================================================

#define AP_SSID "ESP_FTM_AP"              // Nome della rete WiFi
#define AP_PASS "12345678"                // Password (minimo 8 caratteri)
#define MAX_STA_CONN 4                    // Max client connessi

// AGGIUNTO PER FIX #4: Intervallo heartbeat (30 secondi)
#define HEARTBEAT_INTERVAL_MS 30000       // 30 secondi

//  AGGIUNTE PER EBYTE
#define E22_RX 4  // GPIO4 → RX del modulo => configurazione sulla "mother board"
#define E22_TX 5   // GPIO5 → TX del modulo => configurazione sulla "mother board"
#define E22_M0 1  // GPIO1 → M0 del modulo => configurazione sulla "mother board"
#define E22_M1 2   // GPIO2 → M1 del modulo => configurazione sulla "mother board"
#define E22_UART_NUM UART_NUM_1 // usa UART1



static const char *TAG = "FTM_RESPONDER"; // Tag per i log

// ================================================================
// PARTE 3: TASK HEARTBEAT (AGGIUNTO PER FIX #4)
// ===============================================================
// Scopo: Mantenere stabile l'AP verificando che sia sempre attivo
//        Questo previene il timeout di inattività del WiFi
//        Il task "batte" ogni 30 secondi come un cuore

static void heartbeat_task(void *pvParameters)
{
    // Loop infinito: il task non deve mai terminare
    while (1)
    {
        // PASSO 1: Aspetta 30 secondi
        // Il task si "addormenta" per 30 secondi       
        // Durante questo tempo non consuma CPU
        vTaskDelay(pdMS_TO_TICKS(HEARTBEAT_INTERVAL_MS));
        
        // PASSO 2: Verifica che l'AP sia ancora attivo
        // Chiede al driver WiFi: "Che modalità sei?"
        // WIFI_MODE_AP = Access Point (lo slave)
        wifi_mode_t mode;
        esp_err_t err = esp_wifi_get_mode(&mode);
        
        // PASSO 3: Controlla se l'AP è attivo
        // Se la funzione fallisce (err != ESP_OK) OPPURE
        // Se il WiFi non è in modalità AP (mode != WIFI_MODE_AP)
        // → L'AP non è attivo!
        if (err != ESP_OK || mode != WIFI_MODE_AP)
        {
            // AP non attivo! Riavvia il WiFi
            ESP_LOGW(TAG, "⚠️ AP inattivo, riavvio...");
            
            // PASSO 4: Riavvia l'AP
            // Come quando il telefono non funziona e lo riavvii
            esp_wifi_stop();                // Spegni WiFi
            vTaskDelay(pdMS_TO_TICKS(2000)); // Aspetta 1 secondo
            esp_wifi_start();               // Riavvia WiFi
            
            ESP_LOGI(TAG, "✅ AP riavviato!");
        }
        else
        {
            // AP attivo: tutto ok!
            // ESP_LOGD è per debug (non viene stampato di default)
            ESP_LOGD(TAG, "💓 Heartbeat - AP OK");
        }
    }
}

// ================================================================
//  PARTE 4: FUNZIONI PER EBYTE
// ================================================================

void init_ebyte() {
    ESP_LOGI(TAG, "📡 Inizializzazione modulo Ebyte...");
    
    // Configura UART per Ebyte
    uart_config_t uart_config = {
        .baud_rate = 57600,
        .data_bits = UART_DATA_8_BITS,
        .parity = UART_PARITY_DISABLE,
        .stop_bits = UART_STOP_BITS_1,
        .flow_ctrl = UART_HW_FLOWCTRL_DISABLE,
    };


    //DEFINIZIONE DELLE PORTE SERIALI 
    uart_driver_install(E22_UART_NUM, 1024, 1024, 0, NULL, 0);
    uart_param_config(E22_UART_NUM, &uart_config);
    uart_set_pin(E22_UART_NUM, E22_TX, E22_RX, UART_PIN_NO_CHANGE, UART_PIN_NO_CHANGE);
    
    // Configura GPIO per M0 e M1 (modalità normale)
    gpio_config_t io_conf = {
        .pin_bit_mask = (1ULL << E22_M0) | (1ULL << E22_M1),
        .mode = GPIO_MODE_OUTPUT,
        .pull_up_en = GPIO_PULLUP_DISABLE,
        .pull_down_en = GPIO_PULLDOWN_DISABLE,
        .intr_type = GPIO_INTR_DISABLE,
    };
    gpio_config(&io_conf);
    
    gpio_set_level(E22_M0, 0);  // M0 = 0
    gpio_set_level(E22_M1, 0);  // M1 = 0 (modalità normale)
    
    ESP_LOGI(TAG, "✅ Modulo Ebyte inizializzato!");
}

// ================================================================
// PARTE 5: FUNZIONI MAVLINK
// ================================================================

void send_mavlink_heartbeat() {
    mavlink_message_t msg;
    mavlink_msg_heartbeat_pack(
        1,                          // System ID (drone)
        1,                          // Component ID
        &msg,
        MAV_TYPE_QUADROTOR,         // Tipo di drone
        MAV_AUTOPILOT_GENERIC,      // Autopilota
        0,                          // Base mode
        0,                          // Custom mode
        MAV_STATE_STANDBY           // Stato
    );
    
    uint8_t buf[MAVLINK_MAX_PACKET_LEN];
    uint16_t len = mavlink_msg_to_send_buffer(buf, &msg);
    uart_write_bytes(E22_UART_NUM, (const char*)buf, len);
}

void send_mavlink_distance(float distance) {
    mavlink_message_t msg;
    
    mavlink_msg_distance_sensor_pack(
        1,                          // system_id
        1,                          // component_id
        &msg,                       // msg
        0,                          // time_boot_ms
        0,                          // min_distance (cm)
        0,                          // max_distance (cm)
        (uint16_t)(distance * 100), // current_distance (cm)
        MAV_DISTANCE_SENSOR_LASER,  // type
        0,                          // id
        MAV_SENSOR_ROTATION_NONE,   // orientation
        0,                          // covariance
        0.0f,                       // horizontal_fov
        0.0f,                       // vertical_fov
        NULL,                       // quaternion
        0                          // signal_quality

    );
    
    uint8_t buf[MAVLINK_MAX_PACKET_LEN];
    uint16_t len = mavlink_msg_to_send_buffer(buf, &msg);
    uart_write_bytes(E22_UART_NUM, (const char*)buf, len);
}

// ================================================================
// PARTE 6: MAVLINK SEND TASK (invia heartbeat e distanza)
// ================================================================

static void mavlink_send_task(void *pvParameters) {
    while (1) {
        // Invia heartbeat MAVLink (ogni 2 secondi)
        send_mavlink_heartbeat();
        
        // Invia distanza (esempio: 2.5 metri)
        send_mavlink_distance(2.5f);
        
        ESP_LOGI(TAG, "📡 MAVLink inviato: heartbeat + distanza");
        
        vTaskDelay(pdMS_TO_TICKS(2000));
    }
}

// ================================================================
// PARTE 7: FUNZIONE PRINCIPALE - app_main()
// ================================================================

void app_main(void)
{
    ESP_LOGI(TAG, "🚀 Avvio FTM Responder con MAVLink + Ebyte");

    // ------------------------------------------------------------------
    // PASSO 1: Inizializzazione della memoria NVS
    // ------------------------------------------------------------------
    // NVS è la memoria dove ESP32 salva le configurazioni WiFi
    // Tra un riavvio e l'altro
    
    esp_err_t ret = nvs_flash_init();
    
    // Se la memoria è corrotta o c'è una nuova versione
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES || ret == ESP_ERR_NVS_NEW_VERSION_FOUND)
    {
        // Cancella tutto il contenuto di NVS
        ESP_ERROR_CHECK(nvs_flash_erase());
        
        // Reinizializza NVS dopo la cancellazione
        ret = nvs_flash_init();
    }
    
    // Se nvs_flash_init() fallisce ancora, blocca il programma
    ESP_ERROR_CHECK(ret);

    // ------------------------------------------------------------------
    // PASSO 2: Inizializzazione dell'interfaccia di rete
    // ------------------------------------------------------------------
    // Preparazione del stack TCP/IP per la comunicazione di rete
    
    ESP_ERROR_CHECK(esp_netif_init());              // Stack TCP/IP
    ESP_ERROR_CHECK(esp_event_loop_create_default()); // Sistema eventi
    esp_netif_create_default_wifi_ap();            // Crea interfaccia AP

    // ------------------------------------------------------------------
    // PASSO 3: Inizializzazione del driver WiFi
    // ------------------------------------------------------------------
    // Prepara il chip WiFi con le configurazioni standard
    
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    // ------------------------------------------------------------------
    // PASSO 4: Configurazione dell'Access Point (AP)
    // ------------------------------------------------------------------
    // Questa è la parte più importante per lo SLAVE:
    // configuriamo il WiFi come Access Point con FTM responder abilitato
    
    wifi_config_t wifi_config = {
        .ap = {
            .ssid = AP_SSID,                // Nome rete
            .password = AP_PASS,            // Password
            .ssid_len = strlen(AP_SSID),    // Lunghezza nome
            .channel = 6,                   // Canale WiFi
            .authmode = WIFI_AUTH_WPA2_PSK, // Sicurezza WPA2
            .ssid_hidden = 0,
            .max_connection = MAX_STA_CONN, // Max 4 client
            .beacon_interval = 50,
            .ftm_responder = true           // ABILITA FTM!
        },
    };

    // ------------------------------------------------------------------
    // PASSO 5: Applicazione configurazione e avvio WiFi
    // ------------------------------------------------------------------
    
    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_AP));  // Modalità AP
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_AP, &wifi_config)); // Applica
    ESP_ERROR_CHECK(esp_wifi_start());                  // Avvia WiFi

    // ------------------------------------------------------------------
    // PASSO 6: INIZIALIZZA EBYTE => DA TOGLIERE 
    // ------------------------------------------------------------------
    init_ebyte();
 

    // ------------------------------------------------------------------
    // PASSO 7: AVVIA HEARTBEAT TASK (FIX #4) => VIENE ESEGUITO IN MODO PARALLELO CON LA FUNZIONE mavlink_send_task
    // ------------------------------------------------------------------
    // Questo task mantiene stabile l'AP nel tempo
    // Senza heartbeat, la connessione cadrebbe dopo 4 minuti
    xTaskCreate(
        heartbeat_task,          // Funzione da eseguire
        "heartbeat_task",        // Nome del task
        4096,                    // Stack size (memoria allocata)
        NULL,                    // Parametri (nessuno)
        5,                       // Priorità (5 = alta)
        NULL                     // Handle (non ci serve)
    );
    
    ESP_LOGI(TAG, "💓 Heartbeat task avviato (ogni %d ms)", HEARTBEAT_INTERVAL_MS);

    // ------------------------------------------------------------------
    //  PASSO 8: AVVIA MAVLINK SEND TASK => VIENE ESEGUITO IN MODO PARALLELO CON LA FUNZIONE heartbeat_task
    // ------------------------------------------------------------------
    xTaskCreate(
        mavlink_send_task,       // Funzione da eseguire
        "mavlink_task",          // Nome del task
        4096,                    // Stack size
        NULL,                    // Parametri
        5,                       // Priorità del task è su 5
        NULL                     // Handle
    );
    
    ESP_LOGI(TAG, "📡 MAVLink send task avviato (heartbeat ogni 2 secondi)");

    // ------------------------------------------------------------------
    // PASSO 9: Messaggi di conferma
    // ------------------------------------------------------------------
    
    ESP_LOGI(TAG, "✅ FTM Responder AP avviato!");
    ESP_LOGI(TAG, "   📶 SSID: %s", AP_SSID);
    ESP_LOGI(TAG, "   🔑 Password: %s", AP_PASS);
    ESP_LOGI(TAG, "   📏 FTM responder: ATTIVO (in attesa di richieste)");
    ESP_LOGI(TAG, "   📡 Ebyte: attivo su canale 23");
    ESP_LOGI(TAG, "   📡 MAVLink: attivo (heartbeat ogni 2 secondi)");

    // ------------------------------------------------------------------
    // PASSO 10: LOOP INFINITO (FIX #4)
    // ------------------------------------------------------------------
    // Mantiene attivo il task principale
    // Senza questo loop, il programma terminerebbe
    
    while (1)
    {
        vTaskDelay(pdMS_TO_TICKS(10000));  // Aspetta 10 secondi
        // Il loop non fa nulla, ma mantiene attivo il programma
    }
    
    // ------------------------------------------------------------------
    // NOTA: Il codice non arriva mai qui perché il loop è infinito!
    // Lo slave rimane in attesa passiva. Il driver WiFi gestisce 
    // automaticamente le richieste FTM in background.
    // Non appena il master invia una richiesta, lo slave risponde.
    // ================================================================
}