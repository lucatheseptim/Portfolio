#ifndef __MPU6050_H
#define __MPU6050_H

#ifdef __cplusplus
extern "C" {
#endif

#include "main.h"
#include <stdint.h>
#include <stdbool.h>
#include <math.h>

/* ==================== MPU6050 I2C 地址 ==================== */
/* AD0 悬空：多数模块内部下拉，地址 0x68；若通信失败可尝试 0x69(AD0=1) */
#define MPU6050_ADDR             (0x68 << 1)

/* ==================== 关键寄存器地址 ==================== */
#define MPU6050_REG_SMPLRT_DIV   0x19    /* 采样率分频 */
#define MPU6050_REG_CONFIG       0x1A    /* 低通滤波器配置 */
#define MPU6050_REG_GYRO_CONFIG  0x1B    /* 陀螺仪量程 */
#define MPU6050_REG_ACCEL_CONFIG 0x1C    /* 加速度计量程 */
#define MPU6050_REG_ACCEL_XOUT_H 0x3B    /* 加速度数据起始 (共 6 字节) */
#define MPU6050_REG_TEMP_OUT_H   0x41    /* 温度数据起始 (共 2 字节) */
#define MPU6050_REG_GYRO_XOUT_H  0x43    /* 陀螺仪数据起始 (共 6 字节) */
#define MPU6050_REG_PWR_MGMT_1   0x6B    /* 电源管理 1 */
#define MPU6050_REG_PWR_MGMT_2   0x6C    /* 电源管理 2 */
#define MPU6050_REG_WHO_AM_I     0x75    /* 设备 ID，应返回 0x68 */
#define MPU6050_REG_SIGNAL_PATH_RESET 0x68 /* 信号路径复位 */

/* ==================== 默认配置参数 ==================== */
#define MPU6050_DLPF_BW_44HZ     0x03    /* CONFIG: ~44Hz 低通 */
#define MPU6050_GYRO_FS_500      0x08    /* GYRO_CONFIG: ±500°/s */
#define MPU6050_ACCEL_FS_4G      0x08    /* ACCEL_CONFIG: ±4g */
#define MPU6050_CLOCK_PLL_XGYRO  0x01    /* PWR_MGMT_1: PLL with X gyro */
#define MPU6050_SAMPLE_RATE_100HZ 9      /* SMPLRT_DIV: 1kHz/(9+1)=100Hz */

/* ==================== 量程换算系数 ==================== */
/* 当前配置：陀螺仪 ±500°/s → 16.4 LSB/(°/s)，加速度计 ±4g → 8192 LSB/g */
#define MPU6050_GYRO_SCALE        (500.0f / 32768.0f)    /* ±500°/s */
#define MPU6050_ACCEL_SCALE       (4.0f / 32768.0f)      /* ±4g */
#define MPU6050_TEMP_SCALE        (1.0f / 340.0f)        /* °C/LSB */
#define MPU6050_TEMP_OFFSET       36.53f                  /* 零度偏移 */

/* ==================== 数据结构 ==================== */
typedef struct {
    /* ---------- 原始值 ---------- */
    int16_t accel_raw[3];       /* ax, ay, az */
    int16_t gyro_raw[3];        /* gx, gy, gz */
    int16_t temp_raw;

    /* ---------- 物理量（已补偿） ---------- */
    float accel_g[3];           /* g */
    float gyro_dps[3];          /* °/s */
    float temperature;          /* °C */

    /* ---------- 校准参数 ---------- */
    float gyro_offset[3];       /* 陀螺仪零偏 */
    float accel_offset[3];      /* 加速度零偏 */
    uint8_t calibrated;         /* 校准完成标志 */

    /* ---------- 姿态角（软件融合） ---------- */
    float roll;                 /* 横滚角 ° */
    float pitch;                /* 俯仰角 ° */
    float yaw;                  /* 偏航角 ° */

    /* ---------- 内部状态 ---------- */
    uint32_t last_tick;         /* 上次更新时间戳 */
    float dt;                   /* 实际时间步长 (秒) */
} MPU6050_t;

/* ==================== API 函数声明 ==================== */

/**
 * @brief  初始化 MPU6050 传感器
 * @note   复位→唤醒→配置时钟→采样率→DLPF→量程→验证WHO_AM_I
 * @retval HAL_OK 成功，其他失败
 */
HAL_StatusTypeDef MPU6050_Init(void);

/**
 * @brief  测试 MPU6050 连接
 * @retval 1 连接正常，0 连接失败
 */
uint8_t MPU6050_TestConnection(void);

/**
 * @brief  一次性读取加速度 / 陀螺仪 / 温度共 14 字节
 * @param  dev  数据对象指针
 * @retval HAL_OK 成功
 */
HAL_StatusTypeDef MPU6050_ReadAll(MPU6050_t *dev);

/**
 * @brief  仅读取加速度计数据
 */
HAL_StatusTypeDef MPU6050_ReadAccel(MPU6050_t *dev);

/**
 * @brief  仅读取陀螺仪数据
 */
HAL_StatusTypeDef MPU6050_ReadGyro(MPU6050_t *dev);

/**
 * @brief  仅读取温度数据
 */
HAL_StatusTypeDef MPU6050_ReadTemp(MPU6050_t *dev);

/**
 * @brief  零偏校准
 * @param  dev     数据对象指针（校准结果写入 offset）
 * @param  samples 采样次数（建议 100~200 次）
 * @note   设备必须保持静止，校准过程约 samples×10ms
 */
void MPU6050_Calibrate(MPU6050_t *dev, uint16_t samples);

/**
 * @brief  互补滤波姿态解算
 * @param  dev  数据对象（需先调用 MPU6050_ReadAll 更新数据）
 * @note   融合加速度计和陀螺仪数据计算 roll/pitch
 *         yaw 无磁力计辅助，存在漂移
 */
void MPU6050_UpdateAngle(MPU6050_t *dev);

/**
 * @brief  设置采样率分频
 * @param  div  分频系数，实际采样率 = 1kHz / (div + 1)
 */
void MPU6050_SetSampleRateDiv(uint8_t div);

/**
 * @brief  设置陀螺仪量程
 * @param  fs_sel  0:±250  8:±500  16:±1000  24:±2000 °/s
 */
void MPU6050_SetGyroRange(uint8_t fs_sel);

/**
 * @brief  设置加速度计量程
 * @param  fs_sel  0:±2g  8:±4g  16:±8g  24:±16g
 */
void MPU6050_SetAccelRange(uint8_t fs_sel);

#ifdef __cplusplus
}
#endif

#endif /* __MPU6050_H */
