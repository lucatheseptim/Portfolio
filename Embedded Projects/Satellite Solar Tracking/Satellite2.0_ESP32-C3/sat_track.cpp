#include "sat_track.h"
#include <math.h>

/*
 * Port di Core/User/TCA9548A.c: inseguimento solare.
 * Le costanti e la formula sono identiche all'originale; cambia solo la
 * sorgente del tempo (millis() invece di HAL_GetTick()).
 */

float lux_left = 0.0f;
float lux_right = 0.0f;
float lux_up = 0.0f;
float lux_down = 0.0f;
float lux_moyenne = 0.0f;

static int      track_target = SERVO_CENTER_DEG;
static float    err_filt = 0.0f;
static uint8_t  err_primed = 0;
static uint8_t  dz_on = 0;
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

    /* Troppo buio: nessuna informazione utile, non muovere nulla. */
    if (sum < LIGHT_MIN_SUM_LUX) return;

    error = (up - down) / sum;

    now = millis();
    if (!err_primed) {
        err_filt = error;
        err_primed = 1;
    } else {
        dt_ms = now - light_tick;
        if (dt_ms < 1u) dt_ms = 1u;
        if (dt_ms > 300u) dt_ms = 300u;
        alpha = (float)dt_ms / (TRACK_FILTER_TAU_MS + (float)dt_ms);
        err_filt += alpha * (error - err_filt);
    }
    light_tick = now;

    /* Isteresi: entra sopra il 7%, esce sotto il 5%. */
    if (!dz_on) {
        if (fabsf(err_filt) > LIGHT_DEAD_ZONE_EXIT) dz_on = 1;
    } else {
        if (fabsf(err_filt) < LIGHT_DEAD_ZONE) dz_on = 0;
    }
    if (!dz_on) return;

    target_f = (float)SERVO_CENTER_DEG + err_filt * (float)LIGHT_KP;
    if (target_f < (float)SERVO_CMD_MIN_DEG) target_f = (float)SERVO_CMD_MIN_DEG;
    if (target_f > (float)SERVO_CMD_MAX_DEG) target_f = (float)SERVO_CMD_MAX_DEG;

    track_target = (int)target_f;
    Servo_Ramp_SetGoal(1, (uint16_t)track_target);
    Servo_Ramp_SetGoal(2, (uint16_t)(180 - track_target));
}

void Track_OnEnter(void)
{
    uint16_t deg = Servo_GetDeg(1);

    if (deg < (uint16_t)SERVO_CMD_MIN_DEG || deg > (uint16_t)SERVO_CMD_MAX_DEG) {
        deg = SERVO_CENTER_DEG;
    }
    track_target = (int)deg;

    Servo_Ramp_Hold();

    err_filt = 0.0f;
    err_primed = 0;
    dz_on = 0;
    light_tick = millis();
}

static uint16_t Trk_ClampPwm(float v)
{
    uint16_t pwm = (uint16_t)v;
    if (pwm < TRK_MOTOR_PWM_MIN) pwm = TRK_MOTOR_PWM_MIN;
    if (pwm > TRK_MOTOR_PWM_MAX) pwm = TRK_MOTOR_PWM_MAX;
    return pwm;
}

void Track_MotorsByLight(void)
{
    float dx = lux_left - lux_right;
    float dy = lux_up   - lux_down;

    /* Asse X */
    if (fabsf(dx) < TRK_MOTOR_DEADZONE) {
        MotorX_Stop();
    } else {
        uint16_t pwm = Trk_ClampPwm(fabsf(dx) * TRK_MOTOR_KP);
        if (dx > 0.0f) DirX_Left(); else DirX_Right();
        MotorX_Run(pwm);
    }

    /* Asse Y */
    if (fabsf(dy) < TRK_MOTOR_DEADZONE) {
        MotorY_Stop();
    } else {
        uint16_t pwm = Trk_ClampPwm(fabsf(dy) * TRK_MOTOR_KP);
        if (dy > 0.0f) DirY_Up(); else DirY_Down();
        MotorY_Run(pwm);
    }

    /* Asse Z: cerca la fascia di luce intermedia */
    if (lux_moyenne < TRK_Z_LUX_LOW) {
        DirZ_Forward();
        MotorZ_Run(TRK_MOTOR_PWM_MIN);
    } else if (lux_moyenne > TRK_Z_LUX_HIGH) {
        DirZ_Backward();
        MotorZ_Run(TRK_MOTOR_PWM_MIN);
    } else {
        MotorZ_Stop();
    }
}

void Track_Process(void)
{
    /* Un giro completo di 4 letture = una correzione. */
    if (!Sensors_Process()) return;

    Sensors_UpdateDerived();

    TrackByLight(Sensors_Lux(0), Sensors_Lux(1), Sensors_Lux(2), Sensors_Lux(3));
    Track_MotorsByLight();
}
