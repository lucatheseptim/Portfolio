#include "user_main.h"
#include "user_motor.h"
#include "user_spi.h"
#include "TCA9548A.h"
#include "usart.h"
#include "control.h"
#include "mpu6050.h"

MPU6050_t mpu6050 = {0};
static uint32_t mpu6050_last_read = 0;

void User_Init(void)
{
    SPI_ESP_Init();
    motor_Init();
    Servo_Center(1);
    Servo_Center(2);
    if (MPU6050_Init() == HAL_OK) {
        HAL_Delay(50);
        MPU6050_Calibrate(&mpu6050, 100);
    } else {
        UART_Printf("[MPU6050] Init FAILED! Check wiring.\r\n");
    }
}

static uint8_t track_mode_prev = 0;

void User_While(void)
{
    Servo_Ramp_Process();
    SPI_ESP_Process();

    if (Mode_Flag == 1) {
        if (track_mode_prev != 1) {
            Track_OnEnter();
        }
        Track_Process();

        if (HAL_GetTick() - mpu6050_last_read >= 10) {
            MPU6050_ReadAll(&mpu6050);
            MPU6050_UpdateAngle(&mpu6050);
            mpu6050_last_read = HAL_GetTick();
        }
        /* ★ Rimosse le 3 righe motor_stop(Motor_1/2/3) che bloccavano i motori */
    }
    track_mode_prev = Mode_Flag;
}

void HAL_SPI_TxRxCpltCallback(SPI_HandleTypeDef *hspi)
{
    if (hspi->Instance == SPI2) SPI_ESP_DMACompleteCallback();
}

void HAL_SPI_ErrorCallback(SPI_HandleTypeDef *hspi)
{
    if (hspi->Instance == SPI2) SPI_ESP_ErrorCallback();
}