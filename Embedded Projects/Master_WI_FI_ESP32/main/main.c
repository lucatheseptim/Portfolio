// ================================================================
// MASTER - FTM INITIATOR per ESP32-C3 (Stazione di Terra)
// Ruolo: Si connette all'AP dello slave, richiede misurazioni FTM,
//        calcola la distanza e applica filtri per stabilizzare i dati
//        Riceve MAVLink dallo slave via Ebyte
// In FreeRTOS, ogni task creato con xTaskCreate() viene eseguito in parallelo agli altri. 
// Ci sono due task principali che girano in parallelo, ma anche altri task di sistema che FreeRTOS gestisce automaticamente.
// ================================================================

// ================================================================
// PARTE 1: LIBRERIE INCLUSE
// ================================================================

#include <string.h>                  // Per strlen() - calcola lunghezza stringhe
#include <math.h>                    // Per fabs() - valore assoluto per float
#include <inttypes.h>                // Per PRIu32 - formato per uint32_t in printf

// FreeRTOS - Sistema operativo real-time per ESP32
#include "freertos/FreeRTOS.h"       // Sistema operativo real-time
#include "freertos/task.h"           // Per vTaskDelay() - pause

// ESP-IDF - Funzioni di sistema
#include "esp_system.h"              // Funzioni di sistema ESP-IDF
#include "esp_wifi.h"                // Driver WiFi principale
#include "esp_event.h"               // Sistema eventi asincroni
#include "nvs_flash.h"               // Memoria non volatile
#include "esp_log.h"                 // Logging (ESP_LOGI, ESP_LOGE)
#include "esp_netif.h"               // Interfacce di rete (STA/AP)

// ✅ AGGIUNTE PER EBYTE (come nel Server)
#include "driver/uart.h"             // UART - Stiamo passando da Serial1 (Arduino) a uart_driver (ESP-IDF) che fanno la stessa cosa
#include "driver/gpio.h"

// ✅ AGGIUNTE PER MAVLINK (ricezione)
#include "mavlink.h"
 
// ================================================================
// PARTE 2: DEFINIZIONI (configurazione)
// ================================================================

#define AP_SSID "ESP_FTM_AP"        // SSID dello slave (drone) - DEVE essere identico!
#define AP_PASS "12345678"          // Password dello slave - DEVE essere identica!
static const char *TAG = "FTM_MASTER"; // Tag per i log

// ================================================================
// DEFINIZIONI - EBYTE (UGUALI AL SERVER)
// ================================================================

#define E22_RX 4    // GPIO4 → RX del modulo (riceve dati dal modulo) => configurazione sulla "mother board"
#define E22_TX 5    // GPIO5 → TX del modulo (invia dati al modulo) => configurazione sulla "mother board"
#define E22_M0 1    // GPIO1 → M0 del modulo => configurazione sulla "mother board"
#define E22_M1 2    // GPIO2 → M1 del modulo => configurazione sulla "mother board"
#define E22_UART_NUM UART_NUM_1    // usa UART1

// ================================================================
// PARAMETRI DEI FILTRI - Per stabilizzare le misurazioni
// ================================================================

#define FILTER_WINDOW_SIZE 5        // Dimensione del filtro mediana (deve essere dispari)
#define FILTER_ALPHA_FAST 0.6f      // Alpha per movimento (risposta veloce)
#define FILTER_ALPHA_SLOW 0.2f      // Alpha per stabilità (fermo)
#define MOVEMENT_THRESHOLD 0.3f     // Soglia per decidere se c'è movimento (in metri)
#define CALIBRATION_SAMPLES 10      // Numero di campioni per la calibrazione
#define INITIAL_DISTANCE_ESTIMATE 2.0f // Valore iniziale per il filtro

// ================================================================
// STATO DEI FILTRI - Variabili Globali
// ================================================================

static float filter_buffer[FILTER_WINDOW_SIZE];     // Buffer circolare per le misurazioni
static int filter_idx = 0;                          // Indice di scrittura nel buffer
static int filter_count = 0;                        // Quante misurazioni sono state raccolte
static float last_ema_dist = -1.0f;                 // Ultimo valore filtrato (EMA)
static float previous_dist = -1.0f;                 // Misura precedente (per rilevare movimento)
static float calibration_offset = 0.0f;             // Offset di calibrazione
static bool calibration_done = false;               // Flag: calibrazione completata?
static float filter_alpha = FILTER_ALPHA_FAST;      // Alpha dinamico (cambia in base al movimento)
static volatile float last_raw_distance = -1.0f;    // Ultima distanza grezza ricevuta

// ================================================================
// PARTE 3: FUNZIONI PER EBYTE (COME NEL SERVER)
// ================================================================

void init_ebyte_master() {
    ESP_LOGI(TAG, "📡 Inizializzazione modulo Ebyte (Master)...");
    
    // Configura UART per Ebyte (stessi parametri del Server!)
    uart_config_t uart_config = {
        .baud_rate = 57600,
        .data_bits = UART_DATA_8_BITS,
        .parity = UART_PARITY_DISABLE,
        .stop_bits = UART_STOP_BITS_1,
        .flow_ctrl = UART_HW_FLOWCTRL_DISABLE,
    };

    // DEFINIZIONE DELLE PORTE SERIALI
    uart_driver_install(E22_UART_NUM, 2048, 1024, 0, NULL, 0);
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
    
    ESP_LOGI(TAG, "✅ Modulo Ebyte Master inizializzato!");
}

// ================================================================
// PARTE 4: FUNZIONI PER RICEVERE MAVLINK (NUOVE PER IL MASTER)
// ================================================================

static void process_mavlink_data(uint8_t *data, int len) {
    mavlink_message_t msg;
    mavlink_status_t status;
    
    for (int i = 0; i < len; i++) {
        if (mavlink_parse_char(MAVLINK_COMM_0, data[i], &msg, &status)) {
            // MAVLink packet ricevuto dallo slave!
            switch (msg.msgid) {
                case MAVLINK_MSG_ID_HEARTBEAT: {
                    mavlink_heartbeat_t hb;
                    mavlink_msg_heartbeat_decode(&msg, &hb);
                    ESP_LOGI(TAG, "💓 Heartbeat ricevuto dal drone! Tipo: %d, Autopilota : %d", 
                             hb.type, hb.autopilot);
                    break;
                }
                    
                case MAVLINK_MSG_ID_DISTANCE_SENSOR: {
                    mavlink_distance_sensor_t dist;
                    mavlink_msg_distance_sensor_decode(&msg, &dist);
                    float dist_m = dist.current_distance / 100.0f;
                    ESP_LOGI(TAG, "📏 Distanza MAVLink dal drone: %.2f m", dist_m);
                    break;
                }
                
                default:
                    ESP_LOGD(TAG, "📨 MAVLink msg ID: %d", msg.msgid);
                    break;
            }
        }
    }
}

// ================================================================
// TASK PER RICEVERE MAVLINK VIA EBYTE (NUOVO PER IL MASTER)
// ================================================================

static void ebyte_rx_task(void *pvParameters) {
    uint8_t buffer[256];
    int len;
    
    while (1) {
        len = uart_read_bytes(E22_UART_NUM, buffer, sizeof(buffer), pdMS_TO_TICKS(100));
        if (len > 0) {
            process_mavlink_data(buffer, len);
        }
        vTaskDelay(pdMS_TO_TICKS(10));
    }
}

// ================================================================
// PARTE 5: FUNZIONI DEL FILTRO
// ================================================================

// ================================================================
// FUNZIONE PER INIZIALIZZARE IL FILTRO
// ================================================================

static void init_filter_buffer(float initial_value)
{
    // Inizializza tutto il buffer con un valore ragionevole
    for (int i = 0; i < FILTER_WINDOW_SIZE; i++) {
        filter_buffer[i] = initial_value;
    }
    filter_count = FILTER_WINDOW_SIZE;  // Buffer considerato pieno
    filter_idx = 0;
    last_ema_dist = initial_value;
    previous_dist = initial_value;
    filter_alpha = FILTER_ALPHA_FAST;
    ESP_LOGI(TAG, "📊 Filtri inizializzati con distanza: %.2f m", initial_value);
}

// ================================================================
// FUNZIONE DI ORDINAMENTO - Per il filtro mediana
// ================================================================

static void sort_array(float *arr, int n)
{
    // Bubble sort - efficiente per array piccoli (N=5)
    for (int i = 0; i < n - 1; i++) {
        for (int j = 0; j < n - i - 1; j++) {
            if (arr[j] > arr[j + 1]) {
                float temp = arr[j];
                arr[j] = arr[j + 1];
                arr[j + 1] = temp;
            }
        }
    }
}

// ================================================================
// FUNZIONE DI FILTRAGGIO COMBINATO - Mediana + EMA con Alpha Adattivo
// ================================================================

static float apply_distance_filter(float new_dist)
{
    // ================================================================
    // PASSO 1: Sottrai l'offset di calibrazione (FIX #1)
    // ================================================================
    float calibrated_dist = new_dist - calibration_offset;
    if (calibrated_dist < 0.0f) calibrated_dist = 0.0f;

    // ================================================================
    // PASSO 2: Inserisci nel buffer circolare (FIX #2 - Alpha Adattivo)
    // ================================================================
    filter_buffer[filter_idx] = calibrated_dist;
    filter_idx = (filter_idx + 1) % FILTER_WINDOW_SIZE;
    if (filter_count < FILTER_WINDOW_SIZE) {
        filter_count++;
    }

    // ================================================================
    // PASSO 3: Calcola la mediana (per eliminare outlier)
    // ================================================================
    float temp_arr[FILTER_WINDOW_SIZE];
    for (int i = 0; i < filter_count; i++) {
        temp_arr[i] = filter_buffer[i];
    }
    sort_array(temp_arr, filter_count);
    float median = temp_arr[filter_count / 2];

    // ================================================================
    // PASSO 4: Alpha adattivo in base al movimento (FIX #2!)
    // ================================================================
    float dist_change = 0.0f;
    if (previous_dist >= 0.0f) {
        dist_change = fabs(median - previous_dist);  // fabs() = valore assoluto
    }
    previous_dist = median;

    // Se cambia più di 0.3m → movimento!
    if (dist_change > MOVEMENT_THRESHOLD) {
        filter_alpha = FILTER_ALPHA_FAST;   // 0.6 → risposta veloce
    } else {
        filter_alpha = FILTER_ALPHA_SLOW;   // 0.2 → stabilità
    }

    // ================================================================
    // PASSO 5: Applica EMA con l'alpha scelto
    // ================================================================
    if (last_ema_dist < 0.0f) {
        last_ema_dist = median;
    } else {
        last_ema_dist = (filter_alpha * median) + ((1.0f - filter_alpha) * last_ema_dist);
    }

    return last_ema_dist;
}

// ================================================================
// FUNZIONE DI CALIBRAZIONE (FIX #1)
// ================================================================

static float calibrate_offset(void)
{
    ESP_LOGI(TAG, "🔧 Avvio calibrazione offset...");
    ESP_LOGI(TAG, "📌 Posiziona il Master vicino al drone (< 0.5m)");
    
    float samples[CALIBRATION_SAMPLES];
    int valid_samples = 0;
    
    // Raccogli 10 campioni di distanza con i moduli a contatto
    while (valid_samples < CALIBRATION_SAMPLES) {
        wifi_ftm_initiator_cfg_t ftm_cfg = {
            .frm_count = 8,          // Meno frame per calibrazione veloce
            .burst_period = 0,
            .channel = 6,
        };
        
        esp_err_t err = esp_wifi_ftm_initiate_session(&ftm_cfg);
        
        if (err == ESP_OK) {
            vTaskDelay(pdMS_TO_TICKS(300));
            
            if (last_raw_distance > 0.0f && last_raw_distance < 20.0f) {
                samples[valid_samples] = last_raw_distance;
                valid_samples++;
                ESP_LOGI(TAG, "  📊 Campione %d: %.2f m", valid_samples, last_raw_distance);
                last_raw_distance = -1.0f;
            }
        }
        
        vTaskDelay(pdMS_TO_TICKS(100));
    }
    
    // Calcola la mediana (elimina outlier)
    sort_array(samples, valid_samples);
    float median = samples[valid_samples / 2];
    
    ESP_LOGI(TAG, "✅ Calibrazione completata! Offset: %.2f m", median);
    return median;
}

// ================================================================
// PARTE 6: HANDLER PER GLI EVENTI FTM - Riceve i report dal driver WiFi
// ================================================================

static void ftm_event_handler(void *arg, esp_event_base_t event_base,
                              int32_t event_id, void *event_data)
{
    if (event_id == WIFI_EVENT_FTM_REPORT) {
        wifi_event_ftm_report_t *ftm = (wifi_event_ftm_report_t *)event_data;

        if (ftm->status == FTM_STATUS_SUCCESS) {
            // Estrai la distanza grezza (in decimetri → metri)
            float raw_distance = (float)ftm->dist_est / 10.0f;
            last_raw_distance = raw_distance;
            
            // Applica i filtri (calibrazione + mediana + EMA)
            float filtered_dist = apply_distance_filter(raw_distance);

            // ================================================================
            // INVIA DATI AL PC VIA USB (SERIALE)
            // Questi dati possono essere letti da Mission Planner/QGroundControl
            // tramite un plugin o connessione seriale
            // ================================================================
            printf("{\"raw_dist\": %.2f, \"filtered_dist\": %.2f, \"rtt_ns\": %" PRIu32 "}\n",
                   raw_distance, filtered_dist, (uint32_t)ftm->rtt_est);
            
            // NOTA: I dati MAVLink vengono già inviati dallo SLAVE via Ebyte!
            // Non serve implementare MAVLink qui.
        } else {
            printf("{\"error\": \"FTM_STATUS_FAIL\", \"status\": %d}\n", ftm->status);
        }
    }
}

// ================================================================
// PARTE 7: HANDLER PER GLI EVENTI WiFi
// ================================================================

static void wifi_event_handler(void *arg, esp_event_base_t event_base, 
                               int32_t event_id, void *event_data)
{
    if (event_id == WIFI_EVENT_STA_START) {
        ESP_LOGI(TAG, "📡 WiFi started, connecting to AP del drone...");
        esp_wifi_connect();
    } else if (event_id == WIFI_EVENT_STA_CONNECTED) {
        ESP_LOGI(TAG, "✅ Connesso al drone!");
    } else if (event_id == WIFI_EVENT_STA_DISCONNECTED) {
        ESP_LOGI(TAG, "❌ Disconnesso, riconnetto...");
        esp_wifi_connect();
    }
}

// ================================================================
// PARTE 8: TASK HEARTBEAT - Mantiene viva la connessione (FIX #4)
// ================================================================
// Scopo: Mantenere stabile la connessione WiFi
//        Il task "batte" ogni 30 secondi come un cuore

static void heartbeat_task(void *pvParameters)
{
    // Loop infinito: il task non deve mai terminare
    while (1) {
        // PASSO 1: Aspetta 30 secondi
        // Il task si "addormenta" per 30 secondi       
        // Durante questo tempo non consuma CPU
        vTaskDelay(pdMS_TO_TICKS(30000));  // Ogni 30 secondi
        
        // PASSO 2: Verifica che la connessione WiFi sia ancora attiva
        wifi_ap_record_t ap_info;
        esp_err_t err = esp_wifi_sta_get_ap_info(&ap_info);
        
        // PASSO 3: Se la connessione è persa, riconnetti
        if (err != ESP_OK) {
            ESP_LOGW(TAG, "💔 Connessione persa, riconnetto...");
            esp_wifi_disconnect();
            vTaskDelay(pdMS_TO_TICKS(100));
            esp_wifi_connect();
        }
    }
}

// ================================================================
// PARTE 9: FUNZIONE PRINCIPALE - app_main()
// ================================================================

void app_main(void)
{
    ESP_LOGI(TAG, "🚀 Starting FTM Master (Stazione di Terra)...");

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
    // PASSO 2: Inizializzazione dell'interfaccia di rete (STA)
    // ------------------------------------------------------------------
    // Preparazione del stack TCP/IP per la comunicazione di rete
    
    ESP_ERROR_CHECK(esp_netif_init());              // Stack TCP/IP
    ESP_ERROR_CHECK(esp_event_loop_create_default()); // Sistema eventi
    esp_netif_create_default_wifi_sta();  // STA = Station (si connette all'AP)

    // ------------------------------------------------------------------
    // PASSO 3: Inizializzazione del driver WiFi
    // ------------------------------------------------------------------
    // Prepara il chip WiFi con le configurazioni standard
    
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    // ------------------------------------------------------------------
    // PASSO 4: Registrazione degli handler per gli eventi
    // ------------------------------------------------------------------
    ESP_ERROR_CHECK(esp_event_handler_instance_register(
        WIFI_EVENT, ESP_EVENT_ANY_ID, &wifi_event_handler, NULL, NULL));

    ESP_ERROR_CHECK(esp_event_handler_instance_register(
        WIFI_EVENT, WIFI_EVENT_FTM_REPORT, &ftm_event_handler, NULL, NULL));

    // ------------------------------------------------------------------
    // PASSO 5: Configurazione della connessione all'AP dello slave
    // ------------------------------------------------------------------
    wifi_config_t wifi_config = {
        .sta = {
            .ssid = AP_SSID,                    // Nome rete del drone
            .password = AP_PASS,                // Password del drone
            .threshold.authmode = WIFI_AUTH_WPA2_PSK,
        },
    };

    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_STA));
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_STA, &wifi_config));
    ESP_ERROR_CHECK(esp_wifi_start());

    // ------------------------------------------------------------------
    // PASSO 6: Attesa della connessione WiFi
    // ------------------------------------------------------------------
    ESP_LOGI(TAG, "⏳ In attesa di connessione al drone...");
    vTaskDelay(pdMS_TO_TICKS(10000));

    // Verifica connessione
    wifi_ap_record_t ap_info;
    if (esp_wifi_sta_get_ap_info(&ap_info) == ESP_OK) {
        ESP_LOGI(TAG, "✅ Connesso a %s, RSSI: %d dBm", AP_SSID, ap_info.rssi);
    } else {
        ESP_LOGW(TAG, "⚠️ Non connesso, tentativo...");
        esp_wifi_connect();
    }

    // ------------------------------------------------------------------
    // PASSO 7: INIZIALIZZA EBYTE (COME NEL SERVER!)
    // ------------------------------------------------------------------
    init_ebyte_master();
    ESP_LOGI(TAG, "📡 Ebyte Master inizializzato in modalità ricezione");

    // ------------------------------------------------------------------
    // PASSO 8: AVVIA TASK RICEZIONE MAVLINK VIA EBYTE
    // ------------------------------------------------------------------
    // Questo task riceve i dati MAVLink dal drone via Ebyte
    // L'ebyte_rx_task viene eseguito in parallelo con gli altri task
    xTaskCreate(ebyte_rx_task, "ebyte_rx_task", 8192, NULL, 5, NULL);
    ESP_LOGI(TAG, "📡 Task ricezione Ebyte avviato (in attesa di MAVLink)");

    // ------------------------------------------------------------------
    // PASSO 9: Calibrazione dell'offset (FIX #1)
    // ------------------------------------------------------------------
    ESP_LOGI(TAG, "🔧 Avvio calibrazione...");
    vTaskDelay(pdMS_TO_TICKS(2000));
    calibration_offset = calibrate_offset();
    calibration_done = true;

    // ------------------------------------------------------------------
    // PASSO 10: Inizializzazione dei filtri con valore calibrato
    // ------------------------------------------------------------------
    ESP_LOGI(TAG, "📊 Inizializzo filtri con offset: %.2fm", calibration_offset);
    init_filter_buffer(0.0f);

    // ------------------------------------------------------------------
    // PASSO 11: Avvia heartbeat task (FIX #4)
    // ------------------------------------------------------------------
    // Questo task mantiene stabile la connessione WiFi nel tempo
    // Senza heartbeat, la connessione potrebbe cadere
    // Il task "batte" ogni 30 secondi come un cuore
    xTaskCreate(heartbeat_task, "heartbeat_task", 4096, NULL, 5, NULL);
    ESP_LOGI(TAG, "💓 Heartbeat task avviato");

    // ------------------------------------------------------------------
    // PASSO 12: Loop principale - Richieste FTM
    // ------------------------------------------------------------------
    // Il master rimane in un loop infinito richiedendo misurazioni FTM
    // Ogni 0.5 secondi invia una richiesta al drone via WiFi
    while (1) {
        wifi_ftm_initiator_cfg_t ftm_cfg = {
            .frm_count = 16,        // Numero di frame per la misurazione
            .burst_period = 0,      // Periodo tra i burst
            .channel = 6,           // Canale WiFi (deve corrispondere allo slave)
        };
        
        esp_err_t err = esp_wifi_ftm_initiate_session(&ftm_cfg);
        if (err != ESP_OK) {
            ESP_LOGE(TAG, "❌ Errore FTM: %s", esp_err_to_name(err));
            esp_wifi_connect();
        }

        vTaskDelay(pdMS_TO_TICKS(500));  // 0.5 secondi tra una sessione e l'altra
    }
}