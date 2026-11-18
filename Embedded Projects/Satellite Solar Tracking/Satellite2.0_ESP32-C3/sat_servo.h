#ifndef SAT_SERVO_H
#define SAT_SERVO_H

#include <Arduino.h>
#include "sat_config.h"

/*
 * Port di Core/User/control.c dal firmware STM32.
 *
 * Differenza rispetto all'originale: la scrittura del registro di confronto
 * (__HAL_TIM_SET_COMPARE su TIM1) e' sostituita da ledcWrite() sul canale
 * indicato. Tutta la matematica (clamp, conversione angolo -> impulso,
 * rampa in milligradi) e' identica.
 */

/*
 * Configura i 2 canali LEDC dei servo. Da chiamare in setup().
 * I pin sono parametri: il modulo non conosce il pinout, lo decide chi lo usa.
 */
void Servo_Init(uint8_t pin1, uint8_t ch1, uint8_t pin2, uint8_t ch2);

/* Impulso in microsecondi per un angolo gia' clampsato (diagnostica). */
uint16_t Servo_AngleToPulseUs(uint16_t degree);

/* Comando diretto: n_servo 1 o 2, angolo in gradi (clampsato 30..150). */
void Turn_Servo(uint8_t n_servo, uint16_t degree);

/* Porta al centro senza rampa (usato all'avvio). */
void Servo_Center(uint8_t n_servo);

/* Ultimo angolo realmente inviato; SERVO_DEG_UNKNOWN se ignoto. */
uint16_t Servo_GetDeg(uint8_t n_servo);

/* Dimentica la posizione (dopo un taglio di alimentazione del servo). */
void Servo_ForgetDeg(uint8_t n_servo);

/* Imposta il target; non tocca l'hardware (ci pensa Servo_Ramp_Process). */
void Servo_Ramp_SetGoal(uint8_t n_servo, uint16_t degree);

/* Congela il target dove si trova (all'ingresso della modalita' inseguimento). */
void Servo_Ramp_Hold(void);

/* Avanza la rampa verso il target. Va chiamata a ogni giro di loop(). */
void Servo_Ramp_Process(void);

#endif /* SAT_SERVO_H */
