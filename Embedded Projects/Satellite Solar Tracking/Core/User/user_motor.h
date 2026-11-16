#ifndef __USER_MOTOR_H
#define __USER_MOTOR_H

#include "user_main.h"

/*
 * 电机编号说明
 * Motor_1: PWM = PA7 / TIM3_CH2, 方向 = PA4
 * Motor_2: PWM = PB0 / TIM3_CH3, 方向 = PA5
 * Motor_3: PWM = PB1 / TIM3_CH4, 方向 = PA6
 *
 * Servo_1 和 Servo_2 使用 TIM1 的两路 PWM 输出。
 */
#define Motor_1 01
#define Motor_2 02
#define Motor_3 03
#define Servo_1 04
#define Servo_2 0

/* 三路直流电机方向控制脚。 */
#define DIR_GPIO_1 GPIOA
#define DIR_Pin_1 GPIO_PIN_4
#define DIR_GPIO_2 GPIOA
#define DIR_Pin_2 GPIO_PIN_5
#define DIR_GPIO_3 GPIOA
#define DIR_Pin_3 GPIO_PIN_6

/* 启动 TIM3 三路电机 PWM 和 TIM1 两路舵机 PWM。 */
void motor_Init(void);

/*
 * 设置指定电机/舵机 PWM。
 * 直流电机：逻辑速度值，按 TIM3 ARR 限制在 0~999。
 * 舵机    ：原始比较值，会被夹进命令窗口（30~150 度对应约 83~217 计数）；
 *           按角度控制请用 Turn_Servo()。
 */
void motor_PWM_set(u8 motor, u8 direct, u16 PWM);

/*
 * 停止指定电机或舵机：把对应 PWM 比较值清零。
 * 舵机清零 = 输出无脉冲，舵机失力可自由扳动，不等于转到 0° 或停在中位。
 * 需要舵机停在安全位置请用 Servo_Center()。
 */
void motor_stop(char motor);

/* 设置指定直流电机方向脚电平。 */
void motor_turn_set(char motor, GPIO_PinState pinState);

/* 舵机简单测试函数，目前只扫 Servo_1/Servo_2。 */
void motor_test(void);

#endif
