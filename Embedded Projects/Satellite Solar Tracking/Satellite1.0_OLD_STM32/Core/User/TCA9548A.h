#ifndef __TCA9548A_H
#define __TCA9548A_H

#include "stm32f1xx_hal.h"

/* TCA9548A 7 位地址为 0x70，HAL I2C API 需要左移 1 位。 */
#define TCA9548A_ADDR      (0x70 << 1)
#define TCA9548A_I2C       hi2c2

HAL_StatusTypeDef TCA9548A_SelectChannel(uint8_t channel);
HAL_StatusTypeDef TCA9548A_DisableAll(void);
void BH1750_StateMachine_Process(void); 
void Track_Process(void);
void Track_OnEnter(void);

/* ★ 新增：光强 -> 3 个 24V 直流电机 */
void Track_MotorsByLight(void);

#endif