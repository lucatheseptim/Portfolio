#ifndef SAT_LINK_H
#define SAT_LINK_H

#include <Arduino.h>
#include "sat_config.h"
#include "sat_pins.h"
#include "sat_motor.h"
#include "sat_servo.h"
#include "sat_track.h"
#include "sat_sensors.h"

/*
 * Sostituto locale dello SPIHandler.
 *
 * PERCHE' ESISTE
 * Il tuo WebServerHandler chiama, su una istanza di SPIHandler:
 *     begin(), receiveFromWeb(String), handleSPI(), isReady(), clearQueue()
 * e gli passa frame ASCII di 20 caratteri.
 *
 * Con l'ESP32-C3 unico non c'e' piu' nessun STM32 da raggiungere via SPI:
 * questo handler riceve gli stessi frame e li applica direttamente ai motori,
 * ai servo e alla modalita' di inseguimento.
 *
 * Il web server non cambia: basta che l'oggetto che espone questa interfaccia
 * sia questo qui invece dello SPIHandler.
 *
 * FORMATO DEL FRAME (identico a quello che l'STM32 si aspettava):
 *   0      'S'
 *   1      mode: '0' manuale, '1' inseguimento
 *   2..4   PWM asse X  000..999
 *   5      direzione X 0/1
 *   6..8   PWM asse Y
 *   9      direzione Y
 *   10..12 PWM asse Z
 *   13     direzione Z
 *   14..16 angolo servo 1 000..180
 *   17..19 angolo servo 2 000..180
 */
class SatLink {
public:
    SatLink() : initialized(false) {}

    /* Inizializza attuatori e sensori. Da chiamare in setup(). */
    bool begin()
    {
        uint8_t pwm_pin[3] = { PIN_MOTOR1_PWM, PIN_MOTOR2_PWM, PIN_MOTOR3_PWM };
        uint8_t ledc_ch[3] = { LEDC_CH_MOTOR1, LEDC_CH_MOTOR2, LEDC_CH_MOTOR3 };
        uint8_t dir_pin[3] = { PIN_MOTOR1_DIR, PIN_MOTOR2_DIR, PIN_MOTOR3_DIR };

        Servo_Init(PIN_SERVO_1, LEDC_CH_SERVO_1, PIN_SERVO_2, LEDC_CH_SERVO_2);
        Servo_Center(1);
        Servo_Center(2);

        Motor_Init(pwm_pin, ledc_ch, dir_pin);

        Sensors_Init(PIN_I2C_SDA, PIN_I2C_SCL);

        pinMode(PIN_STATUS_LED, OUTPUT);
        digitalWrite(PIN_STATUS_LED, LOW);

        Mode_Flag = 0;
        track_mode_prev = 0;

        initialized = true;
        return true;
    }

    /* Sempre pronto: non esiste piu' un link SPI da negoziare. */
    bool isReady() const { return initialized; }

    /* Nessuna coda: il frame viene applicato subito. */
    bool isTransactionPending() const { return false; }
    void clearQueue() {}

    /* Il ciclo di controllo sostituisce handleSPI(). */
    void handleSPI()
    {
        Servo_Ramp_Process();

        if (Mode_Flag == 1) {
            if (track_mode_prev != 1) {
                Track_OnEnter();
            }
            Track_Process();
        }
        track_mode_prev = Mode_Flag;
    }

    /* Applica un frame ASCII di 20 caratteri ricevuto dal web server. */
    bool receiveFromWeb(const String& message)
    {
        if (message.length() != FRAME_SIZE) {
            Serial.printf("[SAT] frame lungo %d invece di %d\n", message.length(), FRAME_SIZE);
            return false;
        }
        if (message[0] != 'S') {
            return false;
        }
        for (int i = 1; i < FRAME_SIZE; i++) {
            if (message[i] < '0' || message[i] > '9') {
                return false;
            }
        }

        uint8_t mode = (uint8_t)(message[1] - '0');

        if (mode != Mode_Flag) {
            Mode_Flag = mode;
        }

        /* Inseguimento: i comandi manuali vengono ignorati, come prima. */
        if (Mode_Flag == 1) {
            return true;
        }

        uint16_t x_pwm  = Parse3(message, 2) * 10;
        uint8_t  x_dir  = (uint8_t)(message[5] - '0');
        uint16_t y_pwm  = Parse3(message, 6) * 10;
        uint8_t  y_dir  = (uint8_t)(message[9] - '0');
        uint16_t z_pwm  = Parse3(message, 10) * 10;
        uint8_t  z_dir  = (uint8_t)(message[13] - '0');
        uint16_t s1_deg = Parse3(message, 14);
        uint16_t s2_deg = Parse3(message, 17);

        /* Frame di tutti zeri = comando di arresto / cambio modalita'. */
        if (x_pwm == 0 && y_pwm == 0 && z_pwm == 0 && s1_deg == 0 && s2_deg == 0) {
            Servo_Ramp_Hold();
            return true;
        }

        Motor_TurnSet(0, x_dir);
        Motor_PWM_set(0, 0, x_pwm);

        Motor_TurnSet(1, y_dir);
        Motor_PWM_set(1, 0, y_pwm);

        Motor_TurnSet(2, z_dir);
        Motor_PWM_set(2, 0, z_pwm);

        Servo_Ramp_SetGoal(1, s1_deg);
        Servo_Ramp_SetGoal(2, s2_deg);

        return true;
    }

    /* Modalita' corrente: 0 manuale, 1 inseguimento. */
    uint8_t mode() const { return Mode_Flag; }

private:
    static const int FRAME_SIZE = 20;

    bool initialized;
    volatile uint8_t Mode_Flag = 0;
    uint8_t track_mode_prev = 0;

    static uint16_t Parse3(const String& s, int offset)
    {
        return (uint16_t)((s[offset] - '0') * 100 + (s[offset + 1] - '0') * 10 + (s[offset + 2] - '0'));
    }
};

#endif /* SAT_LINK_H */
