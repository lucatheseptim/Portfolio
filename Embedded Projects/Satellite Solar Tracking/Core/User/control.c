#include "control.h"
#include "stm32f1xx_hal.h"
#include "tim.h"

/*
 * 轴控制层
 *
 * 这个文件提供更容易理解的 X/Y/Z 轴接口，内部仍然是直接写 TIM 比较寄存器。
 * 如果以后机械结构调整，只需要在这里调整“轴 -> 电机通道”的映射。
 */

/* X 轴 PWM 输出：Motor_1 / PA7 / TIM3_CH2。 */
void MotorX_Run(uint16_t pwm)
{
    motor_PWM_set(Motor_1, 0, pwm);
}

/* X 轴停止：比较值清零，占空比为 0。 */
void MotorX_Stop(void)
{
    motor_stop(Motor_1);
}

/* Y 轴 PWM 输出：Motor_2 / PB0 / TIM3_CH3。 */
void MotorY_Run(uint16_t pwm)
{
    motor_PWM_set(Motor_2, 0, pwm);
}

/* Y 轴停止：比较值清零，占空比为 0。 */
void MotorY_Stop(void)
{
    motor_stop(Motor_2);
}

/* Z 轴 PWM 输出：Motor_3 / PB1 / TIM3_CH4。 */
void MotorZ_Run(uint16_t pwm)
{
    motor_PWM_set(Motor_3, 0, pwm);
}

/* Z 轴停止：比较值清零，占空比为 0。 */
void MotorZ_Stop(void)
{
    motor_stop(Motor_3);
}

/* X 轴方向控制：PA4。 */
void DirX_Left(void)
{
    motor_turn_set(Motor_1, GPIO_PIN_SET);
}

/* X 轴方向控制：PA4。 */
void DirX_Right(void)
{
    motor_turn_set(Motor_1, GPIO_PIN_RESET);
}

/* Y 轴方向控制：PA5。 */
void DirY_Up(void)
{
    motor_turn_set(Motor_2, GPIO_PIN_RESET);
}

/* Y 轴方向控制：PA5。 */
void DirY_Down(void)
{
    motor_turn_set(Motor_2, GPIO_PIN_SET);
}

/* Z 轴方向控制：PA6。 */
void DirZ_Forward(void)
{
    motor_turn_set(Motor_3, GPIO_PIN_SET);
}

/* Z 轴方向控制：PA6。 */
void DirZ_Backward(void)
{
    motor_turn_set(Motor_3, GPIO_PIN_RESET);
}

/*
 * 舵机角度 -> TIM1 比较值
 *
 * TIM1 按 50Hz 舵机周期配置（100kHz 计数，1 计数 = 10us），所以
 *   比较值 = 脉宽(us) / 10。
 * 全程整数运算，避免在无 FPU 的 Cortex-M3 上引入浮点库。
 *
 * 这里有两个相互独立的概念，用的是两组不同的常量，不要混用：
 *   1) 命令窗口：角度先被夹到 SERVO_CMD_MIN_DEG ~ SERVO_CMD_MAX_DEG（30~150）。
 *      这是机构允许的行程，是**有意的限制**，不要当成 bug 删掉。
 *   2) 标定跨度：夹好的角度再按 SERVO_CAL_MIN/MAX_DEG（0~180，对应
 *      500~2500us）线性插值出脉宽。夹角度不会改变角度与物理位置的对应关系，
 *      命令 30° 就是物理 30°。
 *
 * 历史教训（改这里之前务必读一遍）：这个函数曾经把角度夹到 30~150 之后，
 * 又用 80~240 计数（即 800~2400us）去映射 0~180，两处收窄**独立叠加**，
 * 结果脉宽比例只有标准的 0.8 倍，0~30 和 150~180 完全不可控，
 * 120° 的输入被压成了约 96° 的实际行程。
 * 当时的错在于脉宽量程，**不是**在于 30~150 这道限制本身。
 */

/* 最近一次真正下发的角度，下标 0 = Servo_1/PA9，1 = Servo_2/PA10。 */
static uint16_t servo_last_deg[2] = { SERVO_CENTER_DEG, SERVO_CENTER_DEG };

/*
 * 把角度夹进命令窗口。
 *
 * 单独提出来是因为需要两个地方用同一套裁剪：换算脉宽时，以及记录"最近下发角度"时。
 * 裁剪是幂等的，所以 Servo_AngleToCompare() 反复夹不会改变结果。
 */
static uint16_t Servo_ClampDeg(uint16_t degree)
{
    if (degree > SERVO_CMD_MAX_DEG) {
        degree = SERVO_CMD_MAX_DEG;
    }
    if (degree < SERVO_CMD_MIN_DEG) {
        degree = SERVO_CMD_MIN_DEG;
    }

    return degree;
}

uint16_t Servo_AngleToCompare(uint16_t degree)
{
    uint32_t us;
    uint32_t ccr;
    uint32_t arr = __HAL_TIM_GET_AUTORELOAD(&htim1);

    /* 步骤 1：夹进命令窗口。 */
    degree = Servo_ClampDeg(degree);

    /* 步骤 2：按标定跨度插值出脉宽微秒，再换算计数；
       加半个跨度做四舍五入，减少截断误差。 */
    us = SERVO_MIN_PULSE_US +
         ((uint32_t)degree * (SERVO_MAX_PULSE_US - SERVO_MIN_PULSE_US) +
          (SERVO_CAL_MAX_DEG - SERVO_CAL_MIN_DEG) / 2U) / (SERVO_CAL_MAX_DEG - SERVO_CAL_MIN_DEG);

    ccr = (us + SERVO_TICK_US / 2U) / SERVO_TICK_US;

    /* 兜底：比较值不得超过 ARR，否则占空比会变成恒高，舵机收不到脉冲。 */
    if (ccr > arr) {
        ccr = arr;
    }

    return (uint16_t)ccr;
}

/* 按角度控制舵机：n_servo = 1 -> TIM1_CH2/PA9，2 -> TIM1_CH3/PA10。 */
void Turn_Servo(uint8_t n_servo, uint16_t degree)
{
    /*
     * 记录**夹紧后**的角度，不是传进来的原值。
     *
     * 手动模式的角度来自 SPI 帧里的 3 位 ASCII 数字，最大 999。如果记原值，
     * 追光模式进入时会拿 300 当初始位置，再从这个值开始一步步爬 —— 而 300 一路
     * 都被夹成 150，结果是先白爬 1.67 秒什么都不动。
     */
    uint16_t clamped = Servo_ClampDeg(degree);
    uint16_t ccr = Servo_AngleToCompare(clamped);

    switch (n_servo) {
        case 1:
            __HAL_TIM_SET_COMPARE(&htim1, TIM_CHANNEL_2, ccr);
            servo_last_deg[0] = clamped;
            break;
        case 2:
            __HAL_TIM_SET_COMPARE(&htim1, TIM_CHANNEL_3, ccr);
            servo_last_deg[1] = clamped;
            break;
        default:
            break;
    }
}

/*
 * ==================== 舵机斜坡（手动 / 随动共用） ====================
 *
 * 斜坡状态：[0] = Servo_1/PA9，[1] = Servo_2/PA10，与 servo_last_deg 同下标约定。
 *
 * 位置用**毫度**保存，保留亚度的余量。这是最容易踩的坑：如果 position 用整度
 * 保存，"90 度/秒 × 5ms = 0.45 度"会被整除截断成 0，斜坡永远走不出第一步，
 * 舵机一动不动。用毫度累积就没有这个问题。
 *
 * 顺带一个巧合：度/秒 在数值上**正好等于** 毫度/毫秒，所以步进量可以直接用
 * elapsed_ms * SERVO_SLEW_DPS 算出来，全程整数运算，不引入浮点库。
 *
 * 初值取中位，与 User_Init() 里 Servo_Center() 的就位位置一致；不过不依赖这个
 * 一致性 —— Servo_Center() 会显式把基准对齐过去。
 */
static int32_t  servo_goal_mdeg[2] = { SERVO_CENTER_DEG * 1000, SERVO_CENTER_DEG * 1000 };
static int32_t  servo_pos_mdeg[2]  = { SERVO_CENTER_DEG * 1000, SERVO_CENTER_DEG * 1000 };
static int      servo_pos_deg[2]   = { SERVO_CENTER_DEG, SERVO_CENTER_DEG };

/* 斜坡节拍。 */
static uint32_t servo_ramp_tick = 0;

/* Turn_Servo() 的 n_servo 约定 -> 数组下标；非法编号返回 -1。 */
static int Servo_Index(uint8_t n_servo)
{
    if (n_servo == 1) {
        return 0;
    }
    if (n_servo == 2) {
        return 1;
    }

    return -1;
}

/*
 * 立即就位到指定角度，并把斜坡基准对齐过去。
 *
 * 这是**绕过斜坡**的唯一入口（斜坡自己走 Servo_Ramp_Process()）。不对齐的话，
 * 斜坡下一拍会从"它以为的位置"起坡，在接管瞬间产生一次跳变。
 */
static void Servo_Relocate(uint8_t n_servo, uint16_t degree)
{
    int i = Servo_Index(n_servo);

    if (i < 0) {
        return;
    }

    /* Turn_Servo() 内部会按命令窗口夹紧，所以回读它的记录取真值，
     * 不要用传进来的 degree 算，否则基准会比实际位置多出被夹掉的那一截。 */
    Turn_Servo(n_servo, degree);

    servo_pos_deg[i]   = (int)Servo_GetDeg(n_servo);
    servo_pos_mdeg[i]  = (int32_t)servo_pos_deg[i] * 1000;
    servo_goal_mdeg[i] = servo_pos_mdeg[i];
}

/* 舵机回到标定中位，并立即就位（不经过斜坡）。上电初始化和需要安全停位时使用。 */
void Servo_Center(uint8_t n_servo)
{
    Servo_Relocate(n_servo, SERVO_CENTER_DEG);
}

/* 返回最近一次下发的角度。SERVO_DEG_UNKNOWN 表示位置不可知。 */
uint16_t Servo_GetDeg(uint8_t n_servo)
{
    switch (n_servo) {
        case 1:
            return servo_last_deg[0];
        case 2:
            return servo_last_deg[1];
        default:
            return SERVO_DEG_UNKNOWN;
    }
}

/* 角度记录作废（舵机掉力被扳动过之后，记录的位置不再可信）。 */
void Servo_ForgetDeg(uint8_t n_servo)
{
    switch (n_servo) {
        case 1:
            servo_last_deg[0] = SERVO_DEG_UNKNOWN;
            break;
        case 2:
            servo_last_deg[1] = SERVO_DEG_UNKNOWN;
            break;
        default:
            break;
    }
}

/*
 * 设置目标角。只更新目标，不写硬件 —— 硬件由 Servo_Ramp_Process() 按斜坡推进。
 *
 * "连续指令不打架"就落在这一句赋值上：新目标直接覆盖旧的，斜坡下一拍自然转向
 * 新目标，既不等上一条走完，也不会丢指令。**不要**在这里加"忙就拒收"或排队。
 */
void Servo_Ramp_SetGoal(uint8_t n_servo, uint16_t degree)
{
    int i = Servo_Index(n_servo);

    if (i < 0) {
        return;
    }

    /*
     * 位置不可知（这个舵机被 motor_stop 掉过电、可能已经被人扳动过）：
     * 没有可信的起点，起不了坡。硬从记录的假位置起坡会先朝错误方向走一段，
     * 所以这里直接就位，并借 Servo_Relocate() 重新建立基准。
     */
    if (Servo_GetDeg(n_servo) == SERVO_DEG_UNKNOWN) {
        Servo_Relocate(n_servo, degree);
        return;
    }

    servo_goal_mdeg[i] = (int32_t)Servo_ClampDeg(degree) * 1000;
}

/*
 * 目标冻结在当前位置，就地待命。
 * 进入随动模式的边沿上调用：目标先不定去向，等第一帧光照再说，所以此刻舵机不动。
 */
void Servo_Ramp_Hold(void)
{
    int i;

    for (i = 0; i < 2; i++) {
        if (Servo_GetDeg((uint8_t)(i + 1)) == SERVO_DEG_UNKNOWN) {
            /* 位置不可知就退回中位，顺便把基准建立起来。 */
            Servo_Relocate((uint8_t)(i + 1), SERVO_CENTER_DEG);
        } else {
            servo_goal_mdeg[i] = servo_pos_mdeg[i];   /* 就地待命，不调 Turn_Servo */
        }
    }
}

/*
 * 斜坡节拍：把两个舵机朝各自的目标角平滑地推进一步。
 *
 * 主循环的每个迭代都要调用（内部按 SERVO_RAMP_TICK_MS 分频），且**两种模式下
 * 都要**，所以调用点在 user_main.c 的模式分支外面。全程整数运算。
 */
void Servo_Ramp_Process(void)
{
    uint32_t now = HAL_GetTick();
    uint32_t elapsed = now - servo_ramp_tick;   /* 无符号相减，回绕安全 */
    int32_t  step_mdeg;
    int      i;

    if (elapsed < SERVO_RAMP_TICK_MS) {
        return;
    }
    servo_ramp_tick = now;

    /* 夹住阻塞造成的时间空洞，否则一次卡顿会变成一次猛跳。
     * 主循环里存在几处阻塞：SPI 帧里 motor_turn_set() 的换向延时（最多 1 s）、
     * BH1750 的 I2C 错误恢复（HAL_Delay(5) + 总线复位 + 打印）、MPU6050 的 I2C 读。 */
    if (elapsed > SERVO_RAMP_MAX_GAP_MS) {
        elapsed = SERVO_RAMP_MAX_GAP_MS;
    }

    /* 度/秒 在数值上等于 毫度/毫秒，所以这一乘直接得到本周期允许走的毫度。 */
    step_mdeg = (int32_t)elapsed * (int32_t)SERVO_SLEW_DPS;

    for (i = 0; i < 2; i++) {
        int deg;

        if (servo_pos_mdeg[i] < servo_goal_mdeg[i]) {
            servo_pos_mdeg[i] += step_mdeg;
            if (servo_pos_mdeg[i] > servo_goal_mdeg[i]) {
                servo_pos_mdeg[i] = servo_goal_mdeg[i];
            }
        } else if (servo_pos_mdeg[i] > servo_goal_mdeg[i]) {
            servo_pos_mdeg[i] -= step_mdeg;
            if (servo_pos_mdeg[i] < servo_goal_mdeg[i]) {
                servo_pos_mdeg[i] = servo_goal_mdeg[i];
            }
        } else {
            continue;   /* 已经停在目标角上，不用下发 */
        }

        /* 只在整数角真的变了才写比较寄存器：1 度 ≈ 1.11 个计数 ≈ 11us 脉宽，
         * 已经接近 TIM1 在这个周期下能表达的分辨率，再细分没有意义。
         * 另外 TIM1 是 50Hz，舵机每 20ms 才采一次样，所以 5ms 节拍下发出去的
         * 不均匀间隔（10/10/10/15ms）对舵机来说是不可见的。
         *
         * 注意 servo_pos_deg 记的是"已经下发过的角度"，也就是舵机实际停的位置；
         * 它必须一直等于 Servo_GetDeg()，否则 Track_OnEnter() 会拿到错的位置。 */
        deg = (int)(servo_pos_mdeg[i] / 1000);
        if (deg != servo_pos_deg[i]) {
            servo_pos_deg[i] = deg;
            Turn_Servo((uint8_t)(i + 1), (uint16_t)deg);
        }
    }
}
