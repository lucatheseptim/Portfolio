#include "mpu6050.h"
#include "i2c.h"
#include "usart.h"

static HAL_StatusTypeDef W(uint8_t r, uint8_t d) { return HAL_I2C_Mem_Write(&hi2c1, MPU6050_ADDR, r, I2C_MEMADD_SIZE_8BIT, &d, 1, 100); }
static HAL_StatusTypeDef R(uint8_t r, uint8_t *d) { return HAL_I2C_Mem_Read(&hi2c1, MPU6050_ADDR, r, I2C_MEMADD_SIZE_8BIT, d, 1, 100); }
static HAL_StatusTypeDef Rs(uint8_t r, uint8_t *b, uint8_t l) { return HAL_I2C_Mem_Read(&hi2c1, MPU6050_ADDR, r, I2C_MEMADD_SIZE_8BIT, b, l, 100); }

HAL_StatusTypeDef MPU6050_Init(void)
{
    HAL_StatusTypeDef ret; uint8_t w = 0;
    if (HAL_I2C_GetState(&hi2c1) != HAL_I2C_STATE_READY) { UART_Printf("[MPU6050] I2C1 not ready\r\n"); return HAL_ERROR; }
    ret = W(0x6B, 0x80); if (ret != HAL_OK) { UART_Printf("[MPU6050] Reset fail\r\n"); return ret; } HAL_Delay(100);
    ret = W(0x6B, 0x01); if (ret != HAL_OK) { UART_Printf("[MPU6050] Wake fail\r\n"); return ret; } HAL_Delay(10);
    ret = W(0x19, 9); if (ret != HAL_OK) return ret;
    ret = W(0x1A, 3); if (ret != HAL_OK) return ret;
    ret = W(0x1B, 8); if (ret != HAL_OK) return ret;
    ret = W(0x1C, 8); if (ret != HAL_OK) return ret;
    ret = W(0x6C, 0); if (ret != HAL_OK) return ret;
    ret = R(0x75, &w); if (ret != HAL_OK) { UART_Printf("[MPU6050] WHO_AM_I fail\r\n"); return ret; }
    UART_Printf("[MPU6050] WHO_AM_I=0x%02X\r\n", w);
    UART_Printf("[MPU6050] Init OK\r\n"); return HAL_OK;
}

uint8_t MPU6050_TestConnection(void) { uint8_t w; return (R(0x75, &w) == HAL_OK && w == 0x68) ? 1 : 0; }

HAL_StatusTypeDef MPU6050_ReadAll(MPU6050_t *d)
{
    uint8_t b[14]; HAL_StatusTypeDef r = Rs(0x3B, b, 14); if (r != HAL_OK) return r;
    uint32_t n = HAL_GetTick(); d->dt = (d->last_tick) ? (float)(n-d->last_tick)/1000.0f : 0.01f; d->last_tick = n;
    d->accel_raw[0]=(int16_t)((uint16_t)b[0]<<8|b[1]); d->accel_raw[1]=(int16_t)((uint16_t)b[2]<<8|b[3]); d->accel_raw[2]=(int16_t)((uint16_t)b[4]<<8|b[5]);
    for(int i=0;i<3;i++) d->accel_g[i]=(float)d->accel_raw[i]*MPU6050_ACCEL_SCALE-d->accel_offset[i];
    d->temp_raw=(int16_t)((uint16_t)b[6]<<8|b[7]); d->temperature=(float)d->temp_raw*MPU6050_TEMP_SCALE+MPU6050_TEMP_OFFSET;
    d->gyro_raw[0]=(int16_t)((uint16_t)b[8]<<8|b[9]); d->gyro_raw[1]=(int16_t)((uint16_t)b[10]<<8|b[11]); d->gyro_raw[2]=(int16_t)((uint16_t)b[12]<<8|b[13]);
    for(int i=0;i<3;i++) d->gyro_dps[i]=(float)d->gyro_raw[i]*MPU6050_GYRO_SCALE-d->gyro_offset[i];
    return HAL_OK;
}

HAL_StatusTypeDef MPU6050_ReadAccel(MPU6050_t *d)
{
    uint8_t b[6]; HAL_StatusTypeDef r = Rs(0x3B, b, 6); if (r != HAL_OK) return r;
    d->accel_raw[0]=(int16_t)((uint16_t)b[0]<<8|b[1]); d->accel_raw[1]=(int16_t)((uint16_t)b[2]<<8|b[3]); d->accel_raw[2]=(int16_t)((uint16_t)b[4]<<8|b[5]);
    for(int i=0;i<3;i++) d->accel_g[i]=(float)d->accel_raw[i]*MPU6050_ACCEL_SCALE-d->accel_offset[i];
    return HAL_OK;
}

HAL_StatusTypeDef MPU6050_ReadGyro(MPU6050_t *d)
{
    uint8_t b[6]; HAL_StatusTypeDef r = Rs(0x43, b, 6); if (r != HAL_OK) return r;
    d->gyro_raw[0]=(int16_t)((uint16_t)b[0]<<8|b[1]); d->gyro_raw[1]=(int16_t)((uint16_t)b[2]<<8|b[3]); d->gyro_raw[2]=(int16_t)((uint16_t)b[4]<<8|b[5]);
    for(int i=0;i<3;i++) d->gyro_dps[i]=(float)d->gyro_raw[i]*MPU6050_GYRO_SCALE-d->gyro_offset[i];
    return HAL_OK;
}

HAL_StatusTypeDef MPU6050_ReadTemp(MPU6050_t *d)
{
    uint8_t b[2]; HAL_StatusTypeDef r = Rs(0x41, b, 2); if (r != HAL_OK) return r;
    d->temp_raw=(int16_t)((uint16_t)b[0]<<8|b[1]); d->temperature=(float)d->temp_raw*MPU6050_TEMP_SCALE+MPU6050_TEMP_OFFSET;
    return HAL_OK;
}

void MPU6050_Calibrate(MPU6050_t *d, uint16_t n)
{
    int32_t gs[3]={0}, as[3]={0};
    UART_Printf("[MPU6050] Calibrating %u samples...\r\n", n);
    for(uint16_t i=0;i<n;i++) {
        uint8_t b[14];
        if(Rs(0x3B,b,14)==HAL_OK) {
            int16_t ax=(int16_t)((uint16_t)b[0]<<8|b[1]),ay=(int16_t)((uint16_t)b[2]<<8|b[3]),az=(int16_t)((uint16_t)b[4]<<8|b[5]);
            int16_t gx=(int16_t)((uint16_t)b[8]<<8|b[9]),gy=(int16_t)((uint16_t)b[10]<<8|b[11]),gz=(int16_t)((uint16_t)b[12]<<8|b[13]);
            as[0]+=ax;as[1]+=ay;as[2]+=az; gs[0]+=gx;gs[1]+=gy;gs[2]+=gz;
        }
        HAL_Delay(10);
    }
    for(int i=0;i<3;i++) d->gyro_offset[i]=(float)gs[i]/n*MPU6050_GYRO_SCALE;
    float a0=(float)as[0]/n*MPU6050_ACCEL_SCALE,a1=(float)as[1]/n*MPU6050_ACCEL_SCALE,a2=(float)as[2]/n*MPU6050_ACCEL_SCALE;
    d->accel_offset[0]=a0;d->accel_offset[1]=a1;d->accel_offset[2]=a2-1.0f; d->calibrated=1;
    UART_Printf("[MPU6050] Calibration done\r\n");
}

static void AccelAngle(MPU6050_t *d, float *r, float *p)
{
    float n=sqrtf(d->accel_g[0]*d->accel_g[0]+d->accel_g[1]*d->accel_g[1]+d->accel_g[2]*d->accel_g[2]);
    if(n<0.1f)return; *r=atan2f(d->accel_g[1],d->accel_g[2])*57.29578f; *p=atan2f(-d->accel_g[0],sqrtf(d->accel_g[1]*d->accel_g[1]+d->accel_g[2]*d->accel_g[2]))*57.29578f;
}
void MPU6050_UpdateAngle(MPU6050_t *d)
{
    float ar=0,ap=0; AccelAngle(d,&ar,&ap);
    d->roll=0.98f*(d->roll+d->gyro_dps[0]*d->dt)+0.02f*ar; d->pitch=0.98f*(d->pitch+d->gyro_dps[1]*d->dt)+0.02f*ap; d->yaw+=d->gyro_dps[2]*d->dt;
}

void MPU6050_SetSampleRateDiv(uint8_t v){W(0x19,v);}
void MPU6050_SetGyroRange(uint8_t v){W(0x1B,v&0x18);}
void MPU6050_SetAccelRange(uint8_t v){W(0x1C,v&0x18);}
