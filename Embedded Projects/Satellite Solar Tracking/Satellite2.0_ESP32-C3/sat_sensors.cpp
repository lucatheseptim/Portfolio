#include "sat_sensors.h"
#include <Wire.h>

/*
 * Port di Core/User/TCA9548A.c: acquisizione dei 4 BH1750 tramite TCA9548A.
 *
 * La macchina a stati e' la stessa: IDLE -> SELECT_CH -> START_MEASURE ->
 * WAIT_MEASURE -> READ_DATA -> DONE, con lo stesso numero di passi non bloccanti
 * e la stessa attesa di 24 ms. Una differenza: il "recovery" del bus su ESP32
 * si fa con Wire.end() + Wire.begin(), non con HAL_I2C_DeInit + reset RCC.
 */

static const int SAT_SDA_DEFAULT = 8;
static const int SAT_SCL_DEFAULT = 9;

static int  sda_pin = SAT_SDA_DEFAULT;
static int  scl_pin = SAT_SCL_DEFAULT;

typedef enum {
    ST_IDLE = 0,
    ST_SELECT_CH,
    ST_START_MEASURE,
    ST_WAIT_MEASURE,
    ST_READ_DATA,
    ST_DONE,
    ST_ERROR
} SatState_t;

static SatState_t state = ST_IDLE;

static uint8_t  current_ch = 0;
static uint32_t measure_start_ms = 0;
static uint8_t  round_ready = 0;
static uint32_t errors = 0;

static uint16_t raw_lux[4] = {0, 0, 0, 0};
static float    lux[4]     = {0.0f, 0.0f, 0.0f, 0.0f};

void Sensors_Init(int sda, int scl)
{
    sda_pin = sda;
    scl_pin = scl;

    Wire.begin(sda_pin, scl_pin);
    Wire.setClock(100000);   /* 100 kHz: piu' tollerante su fili lunghi */

    state = ST_IDLE;
    current_ch = 0;
    round_ready = 0;
    errors = 0;
}

bool Sensors_SelectChannel(uint8_t channel)
{
    if (channel > 7) return false;

    Wire.beginTransmission(TCA9548A_ADDR);
    Wire.write((uint8_t)(1u << channel));
    return (Wire.endTransmission() == 0);
}

static void Sensors_DisableAll(void)
{
    Wire.beginTransmission(TCA9548A_ADDR);
    Wire.write((uint8_t)0x00);
    Wire.endTransmission();
}

bool Sensors_Process(void)
{
    uint8_t cmd;
    uint16_t raw;

    switch (state) {
        case ST_IDLE:
            current_ch = 0;
            round_ready = 0;
            state = ST_SELECT_CH;
            break;

        case ST_SELECT_CH:
            state = Sensors_SelectChannel(current_ch) ? ST_START_MEASURE : ST_ERROR;
            break;

        case ST_START_MEASURE:
            cmd = BH1750_ONE_TIME_H;
            Wire.beginTransmission(BH1750_ADDR);
            Wire.write(cmd);
            if (Wire.endTransmission() == 0) {
                measure_start_ms = millis();
                state = ST_WAIT_MEASURE;
            } else {
                errors++;
                state = ST_ERROR;
            }
            break;

        case ST_WAIT_MEASURE:
            if ((uint32_t)(millis() - measure_start_ms) >= BH1750_WAIT_MS) {
                state = ST_READ_DATA;
            }
            break;

        case ST_READ_DATA:
            if (Wire.requestFrom((uint8_t)BH1750_ADDR, (uint8_t)2) == 2) {
                /* Due letture separate: l'ordine di valutazione degli operandi
                 * in una singola espressione non e' garantito dal C++. */
                uint8_t hi = (uint8_t)Wire.read();
                uint8_t lo = (uint8_t)Wire.read();
                raw = ((uint16_t)hi << 8) | (uint16_t)lo;
                raw_lux[current_ch] = raw;
                lux[current_ch] = (float)raw / 1.2f;   /* formula del datasheet */
                state = ST_DONE;
            } else {
                errors++;
                state = ST_ERROR;
            }
            break;

        case ST_DONE:
            current_ch++;
            if (current_ch >= 4) {
                Sensors_DisableAll();
                round_ready = 1;
                state = ST_IDLE;
            } else {
                state = ST_SELECT_CH;
            }
            break;

        case ST_ERROR:
            /* Recovery: si riavvia il bus e si riparte da capo. */
            Wire.end();
            delay(5);
            Wire.begin(sda_pin, scl_pin);
            Wire.setClock(100000);
            Sensors_DisableAll();
            state = ST_IDLE;
            break;
    }

    if (round_ready) {
        round_ready = 0;
        return true;
    }
    return false;
}

float Sensors_Lux(uint8_t index)
{
    if (index > 3) return 0.0f;
    return lux[index];
}

void Sensors_UpdateDerived(void)
{
    /* Le stesse quattro formule dell'originale (BH1750_MESURE_MOYENNE). */
    extern float lux_left, lux_right, lux_up, lux_down, lux_moyenne;

    lux_left    = (lux[0] + lux[1]) / 2.0f;
    lux_right   = (lux[2] + lux[3]) / 2.0f;
    lux_up      = (lux[0] + lux[3]) / 2.0f;
    lux_down    = (lux[1] + lux[2]) / 2.0f;
    lux_moyenne = (lux[0] + lux[1] + lux[2] + lux[3]) / 4.0f;
}

uint32_t Sensors_ErrorCount(void)
{
    return errors;
}

void Sensors_ScanBus(void)
{
    Serial.println("[I2C] scansione bus...");
    for (uint8_t addr = 1; addr < 127; addr++) {
        Wire.beginTransmission(addr);
        if (Wire.endTransmission() == 0) {
            Serial.printf("[I2C] dispositivo trovato: 0x%02X\n", addr);
        }
    }
    Serial.println("[I2C] fine scansione");
}
