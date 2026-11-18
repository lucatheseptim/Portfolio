#include "TCA9548A.h"
#include "control.h"
#include "i2c.h"
#include "stdio.h"
#include "usart.h"
#include "math.h"

extern I2C_HandleTypeDef TCA9548A_I2C;

#define BH1750_ADDR        (0x23 << 1)
#define BH1750_ONE_TIME_LMODE  0x23
#define BH1750_MEASURE_WAIT_MS 24U
#define I2C_TIMEOUT_MS     100

typedef enum {
    BH1750_STATE_IDLE = 0, BH1750_STATE_SELECT_CH, BH1750_STATE_START_MEASURE,
    BH1750_STATE_WAIT_MEASURE, BH1750_STATE_READ_DATA, BH1750_STATE_DONE, BH1750_STATE_ERROR
} BH1750_State_t;

BH1750_State_t bh1750_state = BH1750_STATE_IDLE;

typedef enum { TRACK_X_SCAN = 0, TRACK_Y_SCAN, TRACK_X_BALANCE, TRACK_Y_BALANCE, TRACK_DONE } TrackState_t;
TrackState_t track_state = TRACK_X_SCAN;

typedef enum { X_SCAN_RIGHT = 0, X_SCAN_LEFT } XScanDir_t;
static XScanDir_t scan_dir = X_SCAN_RIGHT;

typedef enum { Y_SCAN_UP = 0, Y_SCAN_DOWN } YScanDir_t;
static YScanDir_t scan_dir_y = Y_SCAN_UP;

uint8_t current_ch = 0;
uint32_t measure_start_tick = 0;
uint8_t bh1750_rx_buf[2];
static uint8_t bh1750_round_ready = 0;
static uint32_t bh1750_round_start_tick = 0;
static uint32_t bh1750_last_round_ms = 0;

uint16_t bh1750_raw[4];
float bh1750_lux[4];
float lux_left, lux_right, lux_up, lux_down;
float lux_moyenne;

static float last_lux = 0;
static float peak_lux = 0;
static uint8_t direction = 1;
static uint8_t down_count = 0;

HAL_StatusTypeDef TCA9548A_SelectChannel(uint8_t channel)
{
    if (channel > 7) return HAL_ERROR;
    uint8_t data = 1 << channel;
    HAL_StatusTypeDef ret = HAL_I2C_Master_Transmit(&TCA9548A_I2C, TCA9548A_ADDR, &data, 1, I2C_TIMEOUT_MS);
    if (ret != HAL_OK) {
        UART_Printf("[TCA9548A] select ch=%d failed ret=%d state=%lu err=0x%08lX\r\n",
                    channel, ret, (uint32_t)HAL_I2C_GetState(&TCA9548A_I2C), HAL_I2C_GetError(&TCA9548A_I2C));
    }
    return ret;
}

HAL_StatusTypeDef TCA9548A_DisableAll(void)
{
    uint8_t data = 0x00;
    return HAL_I2C_Master_Transmit(&TCA9548A_I2C, TCA9548A_ADDR, &data, 1, I2C_TIMEOUT_MS);
}

void BH1750_StateMachine_Process(void)
{
    uint8_t cmd;
    uint16_t raw;

    switch (bh1750_state)
    {
        case BH1750_STATE_IDLE:
            current_ch = 0;
            bh1750_round_ready = 0;
            bh1750_round_start_tick = HAL_GetTick();
            bh1750_state = BH1750_STATE_SELECT_CH;
            break;
        case BH1750_STATE_SELECT_CH:
            if (TCA9548A_SelectChannel(current_ch) == HAL_OK) bh1750_state = BH1750_STATE_START_MEASURE;
            else bh1750_state = BH1750_STATE_ERROR;
            break;
        case BH1750_STATE_START_MEASURE:
            cmd = BH1750_ONE_TIME_LMODE;
            if (HAL_I2C_Master_Transmit(&TCA9548A_I2C, BH1750_ADDR, &cmd, 1, I2C_TIMEOUT_MS) == HAL_OK) {
                measure_start_tick = HAL_GetTick();
                bh1750_state = BH1750_STATE_WAIT_MEASURE;
            } else {
                UART_Printf("[BH1750] measure command failed state=%lu err=0x%08lX\r\n",
                            (uint32_t)HAL_I2C_GetState(&TCA9548A_I2C), HAL_I2C_GetError(&TCA9548A_I2C));
                bh1750_state = BH1750_STATE_ERROR;
            }
            break;
        case BH1750_STATE_WAIT_MEASURE:
            if (HAL_GetTick() - measure_start_tick >= BH1750_MEASURE_WAIT_MS) bh1750_state = BH1750_STATE_READ_DATA;
            break;
        case BH1750_STATE_READ_DATA:
            if (HAL_I2C_Master_Receive(&TCA9548A_I2C, BH1750_ADDR, bh1750_rx_buf, 2, I2C_TIMEOUT_MS) == HAL_OK) {
                raw = (bh1750_rx_buf[0] << 8) | bh1750_rx_buf[1];
                bh1750_raw[current_ch] = raw;
                bh1750_lux[current_ch] = raw / 1.2f;
                bh1750_state = BH1750_STATE_DONE;
            } else {
                UART_Printf("[BH1750] read failed state=%lu err=0x%08lX\r\n",
                            (uint32_t)HAL_I2C_GetState(&TCA9548A_I2C), HAL_I2C_GetError(&TCA9548A_I2C));
                bh1750_state = BH1750_STATE_ERROR;
            }
            break;
        case BH1750_STATE_DONE:
            current_ch++;
            if (current_ch >= 4) {
                TCA9548A_DisableAll();
                bh1750_last_round_ms = HAL_GetTick() - bh1750_round_start_tick;
                bh1750_round_ready = 1;
                bh1750_state = BH1750_STATE_IDLE;
            } else {
                bh1750_state = BH1750_STATE_SELECT_CH;
            }
            break;
        case BH1750_STATE_ERROR:
            HAL_I2C_DeInit(&TCA9548A_I2C);
            HAL_Delay(5);
            I2C2_RecoverBus();
            __HAL_RCC_I2C2_FORCE_RESET();
            __HAL_RCC_I2C2_RELEASE_RESET();
            HAL_I2C_Init(&TCA9548A_I2C);
            bh1750_state = BH1750_STATE_IDLE;
            break;
    }
}

void BH1750_MESURE_MOYENNE(void)
{
    lux_left   = (bh1750_lux[0] + bh1750_lux[1]) / 2.0f;
    lux_right  = (bh1750_lux[2] + bh1750_lux[3]) / 2.0f;
    lux_up     = (bh1750_lux[0] + bh1750_lux[3]) / 2.0f;
    lux_down   = (bh1750_lux[1] + bh1750_lux[2]) / 2.0f;
    lux_moyenne = (bh1750_lux[0] + bh1750_lux[1] + bh1750_lux[2] + bh1750_lux[3]) / 4.0f;
}

/* ==================== 旧算法：光强扫掠 ==================== */
/* 保留不用，以兼容旧代码。现行逻辑在 Track_MotorsByLight()。 */
#define MOTOR_SPEED 300
#define MOTOR_SPEED_SLOW 200
#define LUX_THRESHOLD 2
#define LUX_THRESHOLD_MAX 15
#define DOWN_LIMIT 2
#define UP_LIMIT 2
#define REVERSE_DELAY_MS 500

void Track_X_Scan(void) { /* ... (omesso per brevità, non è usato) ... */ }
void Track_Y_Scan(void) { /* ... (omesso per brevità, non è usato) ... */ }
#define BALANCE_THRESHOLD 15
#define BALANCE_SPEED 200
#define STABLE_LIMIT 2
void Track_X_Balance(void) { /* ... (omesso per brevità, non è usato) ... */ }
void Track_Y_Balance(void) { /* ... (omesso per brevità, non è usato) ... */ }
#define RESCAN_DROP_THRESHOLD 70
#define RESCAN_DIFF_THRESHOLD 30
#define RESCAN_CONFIRM_LIMIT 3
static float locked_lux = 0;
static uint8_t locked_flag = 0;
void DONE(void) { /* ... (omesso per brevità, non è usato) ... */ }
void Track_Done_Check(void) { /* ... (omesso per brevità, non è usato) ... */ }


/* ==================== 舵机追光参数 ==================== */
#define LIGHT_DEAD_ZONE 0.05f
#define LIGHT_DEAD_ZONE_EXIT 0.07f
#define KP 110
#define LIGHT_MIN_SUM_LUX 20.0f
#define TRACK_FILTER_TAU_MS 250.0f

static int track_target = 90;
static float err_filt = 0.0f;
static uint8_t err_primed = 0;
static uint8_t dz_on = 0;
static uint32_t light_tick = 0;

void TrackByLight(float L0, float L1, float L2, float L3)
{
    float up   = L0 + L3;
    float down = L2 + L1;
    float sum  = up + down;
    float error;
    float alpha;
    float target_f;
    uint32_t now;
    uint32_t dt_ms;

    if (sum < LIGHT_MIN_SUM_LUX) return;
    error = (up - down) / sum;
    now = HAL_GetTick();
    if (!err_primed) { err_filt = error; err_primed = 1; }
    else {
        dt_ms = now - light_tick;
        if (dt_ms < 1U) dt_ms = 1U;
        if (dt_ms > 300U) dt_ms = 300U;
        alpha = (float)dt_ms / (TRACK_FILTER_TAU_MS + (float)dt_ms);
        err_filt += alpha * (error - err_filt);
    }
    light_tick = now;

    if (!dz_on) { if (fabsf(err_filt) > LIGHT_DEAD_ZONE_EXIT) dz_on = 1; }
    else { if (fabsf(err_filt) < LIGHT_DEAD_ZONE) dz_on = 0; }
    if (!dz_on) return;

    target_f = 90.0f + err_filt * (float)KP;
    if (target_f < (float)SERVO_CMD_MIN_DEG) target_f = (float)SERVO_CMD_MIN_DEG;
    if (target_f > (float)SERVO_CMD_MAX_DEG) target_f = (float)SERVO_CMD_MAX_DEG;
    track_target = (int)target_f;
    Servo_Ramp_SetGoal(1, (uint16_t)track_target);
    Servo_Ramp_SetGoal(2, (uint16_t)(180 - track_target));
}

void Track_OnEnter(void)
{
    uint16_t deg = Servo_GetDeg(1);
    if (deg < (uint16_t)SERVO_CMD_MIN_DEG || deg > (uint16_t)SERVO_CMD_MAX_DEG) deg = SERVO_CENTER_DEG;
    track_target = (int)deg;
    Servo_Ramp_Hold();
    err_filt = 0.0f;
    err_primed = 0;
    dz_on = 0;
    light_tick = HAL_GetTick();
}

/* ==================== ★ 光强 -> 3 个 24V 直流电机 ==================== */
#define TRK_MOTOR_DEADZONE   30.0f
#define TRK_MOTOR_KP          8.0f
#define TRK_MOTOR_PWM_MIN   200U
#define TRK_MOTOR_PWM_MAX   600U
#define TRK_Z_LUX_LOW        50.0f
#define TRK_Z_LUX_HIGH      300.0f

static uint16_t trk_clamp_pwm(float v)
{
    uint16_t pwm = (uint16_t)v;
    if (pwm < TRK_MOTOR_PWM_MIN) pwm = TRK_MOTOR_PWM_MIN;
    if (pwm > TRK_MOTOR_PWM_MAX) pwm = TRK_MOTOR_PWM_MAX;
    return pwm;
}

void Track_MotorsByLight(void)
{
    float dx = lux_left  - lux_right;
    float dy = lux_up    - lux_down;

    if (fabsf(dx) < TRK_MOTOR_DEADZONE) { MotorX_Stop(); }
    else {
        uint16_t pwm = trk_clamp_pwm(fabsf(dx) * TRK_MOTOR_KP);
        if (dx > 0) DirX_Left();  else DirX_Right();
        MotorX_Run(pwm);
    }
    if (fabsf(dy) < TRK_MOTOR_DEADZONE) { MotorY_Stop(); }
    else {
        uint16_t pwm = trk_clamp_pwm(fabsf(dy) * TRK_MOTOR_KP);
        if (dy > 0) DirY_Up();  else DirY_Down();
        MotorY_Run(pwm);
    }
    if (lux_moyenne < TRK_Z_LUX_LOW) { DirZ_Forward(); MotorZ_Run(TRK_MOTOR_PWM_MIN); }
    else if (lux_moyenne > TRK_Z_LUX_HIGH) { DirZ_Backward(); MotorZ_Run(TRK_MOTOR_PWM_MIN); }
    else { MotorZ_Stop(); }
}

/* ==================== 自动追光主流程 ==================== */
void Track_Process(void)
{
    BH1750_StateMachine_Process();
    if (bh1750_round_ready) {
        bh1750_round_ready = 0;
        BH1750_MESURE_MOYENNE();
        TrackByLight(bh1750_lux[0], bh1750_lux[1], bh1750_lux[2], bh1750_lux[3]);
        Track_MotorsByLight();
    }
}