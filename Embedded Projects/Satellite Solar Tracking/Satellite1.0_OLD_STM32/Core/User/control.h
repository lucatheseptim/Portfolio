#ifndef __CONTROL_H
#define __CONTROL_H

#include "stm32f1xx_hal.h"
#include "user_motor.h"

/*
 * 轴控制封装
 *
 * 这里把三路电机按 X/Y/Z 轴来命名，便于自动追光算法调用。
 * 当前通道关系：
 * X 轴：TIM3_CH2 / PA7，方向 PA4
 * Y 轴：TIM3_CH3 / PB0，方向 PA5
 * Z 轴：TIM3_CH4 / PB1，方向 PA6
 */
void MotorX_Run(uint16_t pwm);
void MotorX_Stop(void);
void MotorY_Run(uint16_t pwm);
void MotorY_Stop(void);
void MotorZ_Run(uint16_t pwm);
void MotorZ_Stop(void);

/* 三个方向函数只负责设置 GPIO，不改变 PWM。 */
void DirX_Left(void);
void DirX_Right(void);
void DirY_Up(void);
void DirY_Down(void);
void DirZ_Forward(void);
void DirZ_Backward(void);

/*
 * 舵机 PWM 参数
 *
 * TIM1: PSC = 720-1 @72MHz -> 计数频率 100kHz，1 个计数 = 10us；
 *       ARR = 2000-1        -> PWM 周期 20ms，即 50Hz 舵机信号。
 *
 * 这里有三组常量，含义不同，千万不要互相代用：
 *
 *  1) 标定跨度 SERVO_CAL_*：脉宽与角度的线性关系，覆盖舵机的整条行程。
 *     MG90S 标准是 500us = 0°、2500us = 180°，即 11.111us/度。
 *
 *  2) 命令窗口 SERVO_CMD_*：机构实际只允许在这个角度区间内运动。
 *     所有角度入口都会被夹到这个区间，超出部分固定到下/上界。
 *     注意：夹紧的是「角度」，不会改变角度与物理位置的对应关系
 *     —— 命令 30° 就是物理 30°，对应的脉宽仍然由标定跨度算出来。
 *
 *  3) 如果实机在窗口两端（30°/150°）仍然撞限位、堵转或有异响，
 *     要改的是标定脉宽 SERVO_MIN/MAX_PULSE_US（例如先试 600 / 2400）
 *     把整个行程收窄，换算逻辑和角度语义都不用动；
 *     只有当机构允许的行程本身变了，才去动 SERVO_CMD_*。
 */
#define SERVO_TICK_US        10U    /* TIM1 一个计数对应的微秒数 */

/* 标定跨度：脉宽 <-> 角度的线性关系 */
#define SERVO_CAL_MIN_DEG    0U     /* 最小脉宽对应的角度 */
#define SERVO_CAL_MAX_DEG    180U   /* 最大脉宽对应的角度 */
#define SERVO_MIN_PULSE_US   500U   /* SERVO_CAL_MIN_DEG 对应的脉宽 */
#define SERVO_MAX_PULSE_US   2500U  /* SERVO_CAL_MAX_DEG 对应的脉宽 */

/* 命令窗口：机构只允许 30~150 度，超出的角度会被夹到下/上界 */
#define SERVO_CMD_MIN_DEG    30U
#define SERVO_CMD_MAX_DEG    150U

#define SERVO_CENTER_DEG     90U    /* 正好是 30~150 的中点 */

/* 50Hz 舵机周期，以及它对应的 TIM1 ARR（必须与 tim.c 的 TIM1.Init.Period 一致）。 */
#define SERVO_PERIOD_US      20000U
#define SERVO_TIM1_ARR       (SERVO_PERIOD_US / SERVO_TICK_US - 1U)

/*
 * 舵机角度控制：n_servo = 1 -> Servo_1/PA9/TIM1_CH2，2 -> Servo_2/PA10/TIM1_CH3。
 *
 * degree 用 uint16_t 而不是 uint8_t：SPI 帧里的角度是 3 位 ASCII 数字，
 * 解析结果最大 999，窄化成 uint8_t 会回绕（300 -> 44，256 -> 0），
 * 一个坏帧就会让舵机朝反方向满行程猛冲。这里保留 16 位并在内部夹到
 * SERVO_CMD_MIN_DEG ~ SERVO_CMD_MAX_DEG（30~150）。
 */
void Turn_Servo(uint8_t n_servo, uint16_t degree);

/*
 * 舵机回到标定中位，并**立即**就位（不经过斜坡）。
 * 用于上电初始化，以及需要安全停位的场合。它同时会把斜坡基准对齐到中位，
 * 所以之后第一帧角度指令是从中位起坡，而不是从斜坡内部的默认值起坡。
 */
void Servo_Center(uint8_t n_servo);

/*
 * ============================ 舵机斜坡 ============================
 *
 * 手动与随动**共用**同一套限速，观感因此完全一致。
 *
 * 分工：调用方只"设目标"，硬件由 Servo_Ramp_Process() 在主循环里按
 * SERVO_SLEW_DPS 把位置朝目标一格格推过去。位置按**毫度**累积，不能用整度 ——
 * 90°/s × 5ms = 0.45°，整度存储会被整除截断成 0，斜坡永远走不出第一步
 * （见 BUG修复记录.md 第四节）。
 *
 * 为什么手动也要走斜坡：手动原来是直接写 TIM1 比较寄存器，舵机以自己的机械
 * 速度猛冲到位；随动是 90°/s 的匀速滑行。两者驱动的是同一对舵机，观感不该打架。
 */
#define SERVO_SLEW_DPS        90    /* 度/秒；120° 全程约 1.33 s */
#define SERVO_RAMP_TICK_MS    5U    /* 斜坡节拍；<2 无意义（HAL_GetTick 是 1ms 分辨率） */
#define SERVO_RAMP_MAX_GAP_MS 20U   /* 夹住阻塞造成的时间空洞，否则一次卡顿会变成一次猛跳 */

/*
 * 设置某一路舵机的目标角（内部按 SERVO_CMD_MIN/MAX_DEG 夹紧），**不写硬件**。
 *
 * 连续指令不会打架：新目标直接**覆盖**旧的，斜坡下一拍自然转向新目标 ——
 * 既不等上一条走完，也不会丢弃任何一条。这一点是刻意的，不要改成"排队"或
 * "忙就拒收"：上游（ESP32-C3 的 queue[8]）本来就会攒帧，排队会让手动操作
 * 出现肉眼可见的滞后与积压。
 *
 * 例外：位置不可知（被 motor_stop 掉过电、可能已被自由扳动）时没有可信起点，
 * 起不了坡，本函数会直接就位并重新建立基准。
 */
void Servo_Ramp_SetGoal(uint8_t n_servo, uint16_t degree);

/*
 * 斜坡节拍：把两路舵机朝各自的目标角推进一步。**两种模式下都要调用**，
 * 所以要放在模式分支外面。
 */
void Servo_Ramp_Process(void);

/*
 * 把两路舵机的目标**冻结在当前位置**（就地待命，一次 Turn_Servo 都不调）。
 * 用于进入随动模式的边沿：目标先待命，等第一帧光照再决定去向，
 * 这样切进来的瞬间舵机不动。
 */
void Servo_Ramp_Hold(void);

/* 角度 -> TIM1 比较值（单位：计数）。调试时可打印它换算出的脉宽。 */
uint16_t Servo_AngleToCompare(uint16_t degree);

/*
 * 舵机最近一次真正下发的角度记录
 *
 * 用途：追光模式需要知道"舵机现在实际停在哪"，才能从一个正确的位置开始摆动，
 * 而不是从它自己上一次残留的内部角度开始 —— 手动模式会驱动同两个舵机，
 * 追光层完全不知道手动把它转到哪去了。见 TCA9548A.c 的 Track_OnEnter()。
 *
 * 记录在 Turn_Servo() 内部完成，所以自动覆盖所有调用方，调用方无需配合。
 *
 * 注意：n_servo 是 Turn_Servo() 的约定（1 = PA9/TIM1_CH2，2 = PA10/TIM1_CH3），
 * 和 user_motor.h 里 Motor_1/Servo_1 那套通道编号是两套不同的命名空间。
 */
#define SERVO_DEG_UNKNOWN   0U  /* 0 不在命令窗口 30~150 内，可安全表示"位置不可知" */

/* 返回最近一次下发的角度（总是落在 30~150 内）；SERVO_DEG_UNKNOWN 表示不可知。 */
uint16_t Servo_GetDeg(uint8_t n_servo);

/*
 * 清除角度记录。
 * 用于舵机失去保持力矩、位置变得不可信的场合 —— 目前只有 motor_stop() 的舵机
 * 分支会调它（CCR=0 = 无脉冲 = 舵机可以被自由扳动，此时记录就是假的了）。
 */
void Servo_ForgetDeg(uint8_t n_servo);

#endif
