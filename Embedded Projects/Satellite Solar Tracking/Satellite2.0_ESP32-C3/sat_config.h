#ifndef SAT_CONFIG_H
#define SAT_CONFIG_H

/*
 * Costanti portate dal firmware STM32 (Core/User/control.h e TCA9548A.c).
 * NON cambiare i valori: sono tarati sul comportamento reale del prototipo.
 */

/* ---------------- Servo ---------------- */

/* Calibrazione: 500 us = 0°, 2500 us = 180° (MG90S) */
#define SERVO_CAL_MIN_DEG     0
#define SERVO_CAL_MAX_DEG     180
#define SERVO_MIN_PULSE_US    500
#define SERVO_MAX_PULSE_US    2500

/* Finestra di comando: il meccanismo accetta solo 30°..150° */
#define SERVO_CMD_MIN_DEG     30
#define SERVO_CMD_MAX_DEG     150
#define SERVO_CENTER_DEG      90

/* Periodo del segnale servo */
#define SERVO_PERIOD_US       20000u    /* 20 ms = 50 Hz */

/* 0 non e' nella finestra 30..150, quindi puo' indicare "posizione ignota" */
#define SERVO_DEG_UNKNOWN     0

/* Rampa: 90 °/s, passo 5 ms, buco massimo 20 ms */
#define SERVO_SLEW_DPS        90
#define SERVO_RAMP_TICK_MS    5
#define SERVO_RAMP_MAX_GAP_MS 20

/* ---------------- Inseguimento (TrackByLight) ---------------- */

#define LIGHT_DEAD_ZONE       0.05f     /* isteresi: sotto il 5% si ferma */
#define LIGHT_DEAD_ZONE_EXIT  0.07f     /* sopra il 7% ricomincia a muoversi */
#define LIGHT_KP              110
#define LIGHT_MIN_SUM_LUX     20.0f     /* sotto questa luce totale non agisce */
#define TRACK_FILTER_TAU_MS   250.0f    /* filtro passa-basso */

/* ---------------- Motori 24 V pilotati dalla luce ---------------- */

#define TRK_MOTOR_DEADZONE    30.0f
#define TRK_MOTOR_KP          8.0f
#define TRK_MOTOR_PWM_MIN     200
#define TRK_MOTOR_PWM_MAX     600
#define TRK_Z_LUX_LOW         50.0f
#define TRK_Z_LUX_HIGH        300.0f

/* ---------------- LEDC (PWM dell'ESP32) ---------------- */

/*
 * I servo usano 14 bit a 50 Hz: 20 ms / 16384 = 1.22 us per passo
 * (~0,06° di risoluzione), piu' che sufficiente per un MG90S.
 */
#define SERVO_LEDC_RES_BITS   14

/*
 * I motori usano 10 bit a 1 kHz, come il TIM3 dell'STM32.
 * ATTENZIONE: il driver motori e' attivo-basso (come su STM32, dove CCR=0
 * teneva il pin ALTO = fermo). LEDC non ha inversione hardware, quindi il
 * livello va invertito in software: duty = DUTY_MAX - duty_logico.
 */
#define MOTOR_LEDC_RES_BITS   10
#define MOTOR_LEDC_FREQ_HZ    1000
#define MOTOR_DUTY_MAX        ((1 << MOTOR_LEDC_RES_BITS) - 1)
#define MOTOR_ACTIVE_LOW      1

#endif /* SAT_CONFIG_H */
