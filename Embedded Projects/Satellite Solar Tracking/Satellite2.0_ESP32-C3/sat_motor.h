#ifndef SAT_MOTOR_H
#define SAT_MOTOR_H

#include <Arduino.h>
#include "sat_config.h"

/*
 * Port di Core/User/user_motor.c (parte motori) e di control.c (assi X/Y/Z).
 *
 * Sul STM32 il PWM era TIM3 con OCPolarity = LOW: con CCR = 0 il pin restava
 * ALTO, che per il driver significa "fermo". LEDC non ha inversione hardware,
 * quindi l'inversione viene fatta qui: duty fisico = MOTOR_DUTY_MAX - duty logico.
 */

/* Numero massimo di passi del duty logico (era il periodo TIM3: 1000). */
#define MOTOR_PWM_MAX 999

/* Configura i 3 canali LEDC dei motori. I pin sono parametri. */
void Motor_Init(uint8_t pin[3], uint8_t ch[3], uint8_t dir_pin[3]);

/* Velocita' logica 0..MOTOR_PWM_MAX. */
void Motor_PWM_set(uint8_t motor, uint8_t direct, uint16_t pwm);

/* Ferma il motore (duty logico 0 -> pin alto). */
void Motor_Stop(uint8_t motor);

/* Direzione: 0 o 1, scritto sul pin DIR del motore. */
void Motor_TurnSet(uint8_t motor, uint8_t dir);

/* ---- Assi X/Y/Z (stessa interfaccia dell'originale) ---- */
void MotorX_Run(uint16_t pwm);
void MotorX_Stop(void);
void MotorY_Run(uint16_t pwm);
void MotorY_Stop(void);
void MotorZ_Run(uint16_t pwm);
void MotorZ_Stop(void);

void DirX_Left(void);
void DirX_Right(void);
void DirY_Up(void);
void DirY_Down(void);
void DirZ_Forward(void);
void DirZ_Backward(void);

#endif /* SAT_MOTOR_H */
