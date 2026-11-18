#ifndef SAT_PINS_H
#define SAT_PINS_H

/*
 * Pinout per LuatOS CORE ESP32-C3 (la scheda che hai fotografato).
 *
 * VINCOLI DELLA SCHEDA (verificati sul pinout ufficiale):
 *   GPIO11        -> VDD_SPI, collegato alla flash: NON USARE
 *   GPIO2         -> strapping (FSPIQ/BOOT): deve essere alto al boot
 *   GPIO8 / GPIO9 -> strapping e pulsante BOOT; la scheda li etichetta SDA/SCL
 *   GPIO18/19     -> USB D+/D-
 *   GPIO20/21     -> UART0 (Serial)
 *
 * Serve: 5 uscite PWM (3 motori + 2 servo) + 3 direzioni + 2 I2C + 1 LED = 11 pin.
 * Il C3 ne ha 11 utilizzabili: l'assegnazione qui sotto li usa tutti.
 */

/* --- I2C: sensori BH1750 dietro TCA9548A ---
 * GPIO8/GPIO9 sono strapping: le resistenze di pull-up del bus li tengono ALTI
 * al boot, che e' proprio la condizione richiesta. Se il bus non ha pull-up,
 * aggiungerne due da 4,7k verso 3,3V. */
#define PIN_I2C_SDA        8
#define PIN_I2C_SCL        9

/* --- Servo (2 canali, 50 Hz) --- */
#define PIN_SERVO_1        3
#define PIN_SERVO_2        4

/* --- Motori 24 V: PWM + direzione --- */
#define PIN_MOTOR1_PWM     5     /* asse X */
#define PIN_MOTOR1_DIR     0

#define PIN_MOTOR2_PWM     6     /* asse Y */
#define PIN_MOTOR2_DIR     1

#define PIN_MOTOR3_PWM     7     /* asse Z */
#define PIN_MOTOR3_DIR     10

/* --- LED di stato (D4 sulla scheda LuatOS) --- */
#define PIN_STATUS_LED     12

/* --- Canali LEDC ---
 * Il C3 ha 6 canali LEDC (0..5). I servo ne usano 2, i motori 3: totale 5.
 * (Con il core Arduino 3.x il canale e' implicito e legato al pin.) */
#define LEDC_CH_SERVO_1    0
#define LEDC_CH_SERVO_2    1
#define LEDC_CH_MOTOR1     2
#define LEDC_CH_MOTOR2     3
#define LEDC_CH_MOTOR3     4

#endif /* SAT_PINS_H */
