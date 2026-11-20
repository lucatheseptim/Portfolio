#ifndef SAT_PINS_H
#define SAT_PINS_H

/*
 * Pinout REALE del cubo — ricavato dallo schema elettrico del cablaggio
 * (connettore H1 della scheda ESP32-C3).
 *
 *   H1 pin 3  -> PWM1 -> GPIO2    motore 1
 *   H1 pin 4  -> PWM2 -> GPIO3    motore 2
 *   H1 pin 5  -> PWM3 -> GPIO10   motore 3
 *   H1 pin 6  -> PWM4 -> GPIO6    servo 1
 *   H1 pin 7  -> DIR1 -> GPIO7    motore 1
 *   H1 pin 8  -> DIR2 -> GPIO11   motore 2
 *   H1 pin 11 -> SCL  -> GPIO5    sensori
 *   H1 pin 12 -> SDA  -> GPIO4    sensori
 *   H1 pin 13 -> PWM5 -> GPIO8    servo 2
 *   H1 pin 14 -> DIR3 -> GPIO9    motore 3
 *
 * ATTENZIONE — pin particolari dell'ESP32-C3:
 *   GPIO2, GPIO8, GPIO9 sono STRAPPING: al boot devono stare ALTI.
 *     - GPIO2  (PWM1) : tenuto alto da Motor_Init (fermo = alto)      OK
 *     - GPIO8  (PWM5) : servo, tenuto alto dal pull-up di strapping   OK
 *     - GPIO9  (DIR3) : Motor_Init lo mette ALTO (vedi sat_motor.cpp)
 *                       Se stesse basso al reset, il chip entrerebbe
 *                       in modalita' download e il firmware non partirebbe.
 *
 *   GPIO11 (DIR2) : su ESP32-C3 e' VDD_SPI. Su questa scheda e' portato
 *     fuori e usato come GPIO dal cablaggio esistente. Se si notano
 *     reset anomali o errori di flash, il primo sospetto e' questo pin.
 */

/* --- I2C: sensori BH1750 dietro TCA9548A --- */
#define PIN_I2C_SDA        4
#define PIN_I2C_SCL        5

/* --- Servo (PWM4 e PWM5 dello schema) --- */
#define PIN_SERVO_1        6
#define PIN_SERVO_2        8

/* --- Motori 24 V: PWM + direzione --- */
#define PIN_MOTOR1_PWM     2      /* PWM1 */
#define PIN_MOTOR1_DIR     7      /* DIR1 */

#define PIN_MOTOR2_PWM     3      /* PWM2 */
#define PIN_MOTOR2_DIR     11     /* DIR2 */

#define PIN_MOTOR3_PWM     10     /* PWM3 */
#define PIN_MOTOR3_DIR     9      /* DIR3 */

/* --- LED di stato (D4 sulla scheda) --- */
#define PIN_STATUS_LED     12

/* --- Canali LEDC ---
 * Il C3 ha 6 canali (0..5): 2 servo + 3 motori = 5. */
#define LEDC_CH_SERVO_1    0
#define LEDC_CH_SERVO_2    1
#define LEDC_CH_MOTOR1     2
#define LEDC_CH_MOTOR2     3
#define LEDC_CH_MOTOR3     4

#endif /* SAT_PINS_H */
