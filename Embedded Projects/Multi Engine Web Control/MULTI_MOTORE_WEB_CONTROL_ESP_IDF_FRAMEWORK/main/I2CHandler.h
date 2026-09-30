// I2CHandler.h
#ifndef I2C_HANDLER_H
#define I2C_HANDLER_H

#include <stdio.h>
#include "driver/i2c.h"
#include "Config.h"

static const char *TAG_I2C = "I2C_HANDLER";

// ============================================================
// INIZIALIZZAZIONE I²C
// ============================================================
void i2c_master_init(void) {
    i2c_config_t conf = {
        .mode = I2C_MODE_MASTER,
        .sda_io_num = I2C_MASTER_SDA_IO,
        .scl_io_num = I2C_MASTER_SCL_IO,
        .sda_pullup_en = GPIO_PULLUP_ENABLE,
        .scl_pullup_en = GPIO_PULLUP_ENABLE,
        .master.clk_speed = I2C_MASTER_FREQ_HZ,
    };
    i2c_param_config(I2C_NUM_0, &conf);
    i2c_driver_install(I2C_NUM_0, conf.mode, 0, 0, 0);
    ESP_LOGI(TAG_I2C, "✅ I2C inizializzato su SDA=%d, SCL=%d", 
             I2C_MASTER_SDA_IO, I2C_MASTER_SCL_IO);
}

// ============================================================
// LEGGE MPU6050 (ACCELEROMETRO)
// ============================================================
bool mpu6050_read(float *ax, float *ay, float *az) {
    // Verifica che il sensore sia presente
    uint8_t check = 0;
    i2c_master_write_to_device(I2C_NUM_0, MPU6050_ADDR, &check, 0, 1000 / portTICK_PERIOD_MS);
    
    // Legge i registri dell'accelerometro (0x3B-0x40)
    uint8_t reg = 0x3B;
    uint8_t data[6];
    esp_err_t err = i2c_master_write_to_device(I2C_NUM_0, MPU6050_ADDR, &reg, 1, 1000 / portTICK_PERIOD_MS);
    if (err != ESP_OK) return false;
    err = i2c_master_read_from_device(I2C_NUM_0, MPU6050_ADDR, data, 6, 1000 / portTICK_PERIOD_MS);
    if (err != ESP_OK) return false;
    
    int16_t rawX = (data[0] << 8) | data[1];
    int16_t rawY = (data[2] << 8) | data[3];
    int16_t rawZ = (data[4] << 8) | data[5];
    
    *ax = rawX / 16384.0 * 9.81;
    *ay = rawY / 16384.0 * 9.81;
    *az = rawZ / 16384.0 * 9.81;
    return true;
}

#endif