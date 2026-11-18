#ifndef SAT_SENSORS_H
#define SAT_SENSORS_H

#include <Arduino.h>
#include "sat_config.h"

/*
 * Port di Core/User/TCA9548A.c (parte I2C): 4 sensori BH1750 dietro un
 * multiplexer TCA9548A, letti uno alla volta con macchina a stati non bloccante.
 *
 * Differenza rispetto all'originale: HAL_I2C_Master_Transmit/Receive diventano
 * Wire.beginTransmission/endTransmission/requestFrom. La sequenza dei comandi e
 * i tempi sono identici.
 */

/* Indirizzi I2C a 7 bit (Arduino usa 7 bit, la HAL ST usava 8). */
#define TCA9548A_ADDR        0x70
#define BH1750_ADDR          0x23

/* Comando: misura singola in modalita' alta risoluzione. */
#define BH1750_ONE_TIME_H    0x23

/* Tempo di attesa dopo il comando di misura (come nell'originale). */
#define BH1750_WAIT_MS       24

/* Inizializza il bus e lo stato della macchina. */
void Sensors_Init(int sda_pin, int scl_pin);

/* Esegue UN passo della macchina a stati. Ritorna true quando un giro di
 * 4 letture e' completo (equivalente di bh1750_round_ready). */
bool Sensors_Process(void);

/* Ultima lettura del canale 0..3, in lux. */
float Sensors_Lux(uint8_t index);

/* Ricalcola left/right/up/down/media con le stesse formule dell'originale. */
void Sensors_UpdateDerived(void);

/* Selezione canale del multiplexer (1 << ch). */
bool Sensors_SelectChannel(uint8_t channel);

/* Scansione del bus: utile in fase di bring-up. Stampa gli indirizzi trovati. */
void Sensors_ScanBus(void);

/* Numero di errori I2C dall'avvio (diagnostica). */
uint32_t Sensors_ErrorCount(void);

#endif /* SAT_SENSORS_H */
