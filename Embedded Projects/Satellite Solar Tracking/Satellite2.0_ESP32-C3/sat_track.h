#ifndef SAT_TRACK_H
#define SAT_TRACK_H

#include <Arduino.h>
#include "sat_config.h"
#include "sat_servo.h"
#include "sat_motor.h"

/*
 * Port di Core/User/TCA9548A.c (parte inseguimento).
 * La matematica e' identica; l'acquisizione dei sensori e' delegata a un
 * modulo esterno (sat_sensors), perche' dipende dal bus I2C e non dai pin.
 */

/* Medie derivate dalle 4 letture: stesse formule dell'originale. */
extern float lux_left;      /* (L0 + L1) / 2 */
extern float lux_right;     /* (L2 + L3) / 2 */
extern float lux_up;        /* (L0 + L3) / 2 */
extern float lux_down;      /* (L1 + L2) / 2 */
extern float lux_moyenne;   /* media delle 4 */

/* ---- Interfaccia che il modulo sensori deve fornire ---- */
/* Esegue un passo della macchina a stati; true quando un giro di 4 letture e' completo. */
bool  Sensors_Process(void);
/* Ultima lettura del canale 0..3, in lux. */
float Sensors_Lux(uint8_t index);
/* Ricalcola lux_left/right/up/down/moyenne dalle 4 letture. */
void  Sensors_UpdateDerived(void);

/* ---- Inseguimento ---- */
/* Da chiamare una volta sul fronte 0 -> 1 della modalita' inseguimento. */
void Track_OnEnter(void);

/* Da chiamare a ogni giro quando la modalita' inseguimento e' attiva. */
void Track_Process(void);

/* Regola proporzionale applicata alle 4 letture. */
void TrackByLight(float L0, float L1, float L2, float L3);

/* Traduce lo sbilanciamento in comandi ai 3 motori 24 V. */
void Track_MotorsByLight(void);

#endif /* SAT_TRACK_H */
