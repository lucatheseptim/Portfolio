#include "user_motor.h"
#include "control.h"   /* 舵机标定常量与 Turn_Servo()/Servo_AngleToCompare() */
#include "usart.h"

/*
 * TIM3 的 ARR 为 999，因此直流电机 PWM 有效比较值范围是 0~999。
 * 电机驱动调速脚为低电平有效，TIM3 已配置为反相 PWM：
 *   PWM=0   -> 引脚保持高电平，电机停止
 *   PWM=999 -> 引脚低电平占比最高，电机接近全速
 */
#define MOTOR_PWM_MAX 999U

/*
 * 舵机通道不能套用 MOTOR_PWM_MAX：999 计数 = 9.99ms，已经超过 20ms 周期的一半，
 * 会给出一个完全无效的舵机脉宽。
 * 舵机原始比较值的合法区间由命令窗口决定，见 servo_set_compare_raw()。
 */

/* 换向保护时间：先停 PWM，等待电机和驱动桥臂进入安全状态，再改变方向。 */
#define MOTOR_DIR_CHANGE_DELAY_MS 1000U

static u16 motor_current_pwm[3] = {0, 0, 0};
static GPIO_PinState motor_current_dir[3] = {GPIO_PIN_RESET, GPIO_PIN_RESET, GPIO_PIN_RESET};

static int8_t motor_index(char motor)
{
	switch (motor)
	{
		case Motor_1:
			return 0;
		case Motor_2:
			return 1;
		case Motor_3:
			return 2;
		default:
			return -1;
	}
}

static void motor_set_compare(char motor, u16 pwm)
{
	switch (motor)
	{
		case Motor_1:
			__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_2, pwm); /* PA7 */
			break;
		case Motor_2:
			__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_3, pwm); /* PB0 */
			break;
		case Motor_3:
			__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_4, pwm); /* PB1 */
			break;
		default:
			break;
	}
}

/*
 * 舵机原始比较值写入
 *
 * 这条路径**绕过** Servo_AngleToCompare() 的角度换算，直接写 TIM1 比较寄存器，
 * 所以必须在这里自己把值夹进舵机的命令窗口，否则它就成了唯一能走出 30~150 的漏洞。
 * 窗口对应的比较值由角度换算函数算出，保证和 Turn_Servo() 用的是同一套标定。
 *
 * 需要按角度控制舵机时请用 Turn_Servo()，不要走这里。
 */
static void servo_set_compare_raw(uint32_t channel, u16 ccr)
{
	u16 ccr_min = Servo_AngleToCompare(SERVO_CMD_MIN_DEG);
	u16 ccr_max = Servo_AngleToCompare(SERVO_CMD_MAX_DEG);

	if (ccr < ccr_min)
	{
		ccr = ccr_min;
	}
	if (ccr > ccr_max)
	{
		ccr = ccr_max;
	}

	__HAL_TIM_SET_COMPARE(&htim1, channel, ccr);
}

/*
 * 启动所有用户层 PWM 输出
 *
 * 三个直流电机：
 *   Motor_1 -> PA7 / TIM3_CH2
 *   Motor_2 -> PB0 / TIM3_CH3
 *   Motor_3 -> PB1 / TIM3_CH4
 *
 * 两个舵机：
 *   Servo_1 -> TIM1_CH2
 *   Servo_2 -> TIM1_CH3
 */
void motor_Init(void)
{
	__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_2, 0);
	__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_3, 0);
	__HAL_TIM_SET_COMPARE(&htim3, TIM_CHANNEL_4, 0);
	motor_current_pwm[0] = 0;
	motor_current_pwm[1] = 0;
	motor_current_pwm[2] = 0;
	motor_current_dir[0] = HAL_GPIO_ReadPin(DIR_GPIO_1, DIR_Pin_1);
	motor_current_dir[1] = HAL_GPIO_ReadPin(DIR_GPIO_2, DIR_Pin_2);
	motor_current_dir[2] = HAL_GPIO_ReadPin(DIR_GPIO_3, DIR_Pin_3);

	HAL_StatusTypeDef m1_ret = HAL_TIM_PWM_Start(&htim3, TIM_CHANNEL_2);
	HAL_StatusTypeDef m2_ret = HAL_TIM_PWM_Start(&htim3, TIM_CHANNEL_3);
	HAL_StatusTypeDef m3_ret = HAL_TIM_PWM_Start(&htim3, TIM_CHANNEL_4);
	HAL_StatusTypeDef s1_ret = HAL_TIM_PWM_Start(&htim1, TIM_CHANNEL_2);
	HAL_StatusTypeDef s2_ret = HAL_TIM_PWM_Start(&htim1, TIM_CHANNEL_3);

	UART_Printf("[MOTOR] PWM start M1/PA7=%d M2/PB0=%d M3/PB1=%d S1=%d S2=%d ARR3=%lu\r\n",
	            m1_ret,
	            m2_ret,
	            m3_ret,
	            s1_ret,
	            s2_ret,
	            __HAL_TIM_GET_AUTORELOAD(&htim3));

	/*
	 * 上电自检：TIM1 的 ARR 必须是 1999，也就是 10us x 2000 = 20ms 的 50Hz 舵机周期。
	 *
	 * tim.c 里的 TIM1.Init.Period 是手工改成 2000-1 的，Satellite1.0.ioc 里原本写的是
	 * 100-1。如果哪天用 CubeMX 重新生成代码把周期改回 1kHz，所有舵机比较值都会大于 ARR，
	 * 输出恒为高电平、舵机完全收不到脉冲——串口上这条警告会立刻报出来。
	 */
	if (__HAL_TIM_GET_AUTORELOAD(&htim1) != SERVO_TIM1_ARR)
	{
		/* 串口输出保持纯 ASCII：ARMCC 对字符串字面量按系统代码页校验，
		 * 直接写中文会产生 invalid multibyte character sequence 警告。 */
		UART_Printf("[MOTOR] WARN: TIM1 ARR=%lu, expected %lu (50Hz servo period)! "
		            "Check TIM1.Period in Satellite1.0.ioc\r\n",
		            (uint32_t)__HAL_TIM_GET_AUTORELOAD(&htim1),
		            (uint32_t)SERVO_TIM1_ARR);
	}

	/*
	 * 上电自检：TIM3 三路调速脚是低电平有效，CCR=0 时引脚必须保持高电平（停机）。
	 *
	 * tim.c 里 TIM3 的 OCPolarity 也是手工改成 LOW 的。极性一旦被 CubeMX 改回 HIGH，
	 * PWM 模式 1 下 CCR=0 会让引脚恒为低电平，motor_stop() 就从"停机"变成"满速"，
	 * 随动模式每轮循环调 motor_stop 会把三个飞轮直接冲到最高转速。
	 * 这里在 CCR 全为 0 的情况下读一次 PA7 就能发现：读到低电平说明极性反了。
	 */
	if (HAL_GPIO_ReadPin(GPIOA, GPIO_PIN_7) == GPIO_PIN_RESET)
	{
		UART_Printf("[MOTOR] WARN: PA7 is LOW while CCR=0, TIM3 polarity is active-high! "
		            "motor_stop() would mean FULL SPEED. Check TIM3 OCPolarity in Satellite1.0.ioc\r\n");
	}
}

/*
 * 设置电机或舵机 PWM
 *
 * motor : Motor_1/Motor_2/Motor_3/Servo_1/Servo_2
 * direct: 旧接口保留参数，当前方向由 motor_turn_set() 单独控制
 * PWM   : 电机 -> 逻辑速度值，0=停止，999=接近全速；底层 TIM3 输出低电平有效波形。
 *         舵机 -> **原始比较值**（TIM1 是 100kHz 计数），会被夹进命令窗口
 *                 （30~150 度对应约 83~217 计数）。需要按角度控制舵机请直接用
 *                 Turn_Servo()，不要走这个接口。
 */
void motor_PWM_set(u8 motor, u8 direct, u16 PWM)
{
	int8_t index = motor_index((char)motor);

	(void)direct;

	switch (motor)
	{
		case Motor_1:
		case Motor_2:
		case Motor_3:
			if (PWM > MOTOR_PWM_MAX)
			{
				PWM = MOTOR_PWM_MAX;
			}
			motor_set_compare((char)motor, PWM);
			if (index >= 0)
			{
				motor_current_pwm[index] = PWM;
			}
			break;
		case Servo_1:
			servo_set_compare_raw(TIM_CHANNEL_2, PWM);
			break;
		case Servo_2:
			servo_set_compare_raw(TIM_CHANNEL_3, PWM);
			break;
		default:
			break;
	}
}

/*
 * 停止指定电机/舵机。
 *
 * 电机：低电平有效 PWM，CCR=0 会让调速脚保持高电平，即停机。
 * 舵机：CCR=0 在 PWM1 模式下输出恒低，也就是**完全没有脉冲**，
 *       舵机失去保持力矩、可以被自由扳动。它不等价于"转到 0°"，
 *       也不是"停在中位"。需要舵机停在安全位置请用 Servo_Center()。
 *       这里保持 PWM 通道继续运行，所以之后调用 Turn_Servo() 会立即恢复控制。
 */
void motor_stop(char motor)
{
	int8_t index = motor_index(motor);

	switch (motor)
	{
		case Motor_1:
		case Motor_2:
		case Motor_3:
			motor_set_compare(motor, 0);
			if (index >= 0)
			{
				motor_current_pwm[index] = 0;
			}
			break;
		case Servo_1:
			__HAL_TIM_SET_COMPARE(&htim1, TIM_CHANNEL_2, 0);
			/* 无脉冲 = 舵机失力、可被自由扳动，记录的角度不再可信。 */
			Servo_ForgetDeg(1);
			break;
		case Servo_2:
			__HAL_TIM_SET_COMPARE(&htim1, TIM_CHANNEL_3, 0);
			Servo_ForgetDeg(2);
			break;
		default:
			break;
	}
}

/*
 * 设置三路直流电机方向
 *
 * 注意：这个函数只改变方向脚电平，不改变 PWM。
 * 调用时通常先设方向，再设置 PWM，避免电机方向和速度不同步。
 */
void motor_turn_set(char motor, GPIO_PinState pinState)
{
	int8_t index = motor_index(motor);

	if (index >= 0 && motor_current_dir[index] != pinState)
	{
		if (motor_current_pwm[index] > 0)
		{
			motor_stop(motor);
			HAL_Delay(MOTOR_DIR_CHANGE_DELAY_MS);
		}
		motor_current_dir[index] = pinState;
	}

	switch (motor)
	{
		case Motor_1:
			HAL_GPIO_WritePin(DIR_GPIO_1, DIR_Pin_1, pinState); /* PA4 */
			break;
		case Motor_2:
			HAL_GPIO_WritePin(DIR_GPIO_2, DIR_Pin_2, pinState); /* PA5 */
			break;
		case Motor_3:
			HAL_GPIO_WritePin(DIR_GPIO_3, DIR_Pin_3, pinState); /* PA6 */
			break;
		default:
			break;
	}
}

/*
 * 舵机扫动测试：每秒走一个标定点，覆盖命令窗口 30~150 全程。
 *
 * 打印出来的 ccr 和脉宽可以直接和示波器上 PA9/PA10 的波形对照：
 * 30°/60°/90°/120°/150° 应分别对应 833/1167/1500/1833/2167us。
 *
 * 取值必须落在 SERVO_CMD_MIN_DEG ~ SERVO_CMD_MAX_DEG 内：超出窗口的角度会被
 * 静默夹到边界，而下面的打印用的是表里的原值，会和实际波形对不上。
 */
void motor_test(void)
{
	static const u8 test_deg[5] = {30, 60, 90, 120, 150};
	u8 i;

	for (i = 0; i < 5; i++)
	{
		u16 ccr = Servo_AngleToCompare(test_deg[i]);

		Turn_Servo(1, test_deg[i]);
		Turn_Servo(2, test_deg[i]);

		UART_Printf("[SERVO] deg=%u ccr=%u pulse=%uus\r\n",
		            test_deg[i], ccr, (unsigned)(ccr * SERVO_TICK_US));

		HAL_Delay(1000);
	}
}
