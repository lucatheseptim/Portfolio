#include "sat_servo.h"
#include "sat_ledc.h"

/*
 * Port fedele di Core/User/control.c (STM32F103 -> ESP32-C3).
 * La matematica e' identica; cambia solo il modo di scrivere il PWM.
 */

/* Canali e pin LEDC associati ai due servo (impostati da Servo_Init). */
static uint8_t servo_ledc_ch[2] = {0, 1};
static uint8_t servo_pin[2]     = {255, 255};

/* Ultimo angolo realmente inviato (0 = posizione ignota). */
static uint16_t servo_last_deg[2] = { SERVO_CENTER_DEG, SERVO_CENTER_DEG };

/* Stato della rampa, in milligradi: evita il troncamento a 0 gradi. */
static int32_t  servo_goal_mdeg[2] = { SERVO_CENTER_DEG * 1000, SERVO_CENTER_DEG * 1000 };
static int32_t  servo_pos_mdeg[2]  = { SERVO_CENTER_DEG * 1000, SERVO_CENTER_DEG * 1000 };
static int      servo_pos_deg[2]   = { SERVO_CENTER_DEG, SERVO_CENTER_DEG };

static uint32_t servo_ramp_tick = 0;

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

static int Servo_Index(uint8_t n_servo)
{
    if (n_servo == 1) return 0;
    if (n_servo == 2) return 1;
    return -1;
}

void Servo_Init(uint8_t pin1, uint8_t ch1, uint8_t pin2, uint8_t ch2)
{
    servo_ledc_ch[0] = ch1;
    servo_ledc_ch[1] = ch2;
    servo_pin[0]    = pin1;
    servo_pin[1]    = pin2;

    SAT_LEDC_SETUP(ch1, pin1, 1000000u / SERVO_PERIOD_US, SERVO_LEDC_RES_BITS);
    SAT_LEDC_SETUP(ch2, pin2, 1000000u / SERVO_PERIOD_US, SERVO_LEDC_RES_BITS);
}

/*
 * Angolo -> impulso.
 * La finestra di comando (30..150) e la calibrazione (0..180 -> 500..2500 us)
 * sono due concetti distinti: qui si clampa l'angolo, poi si interpola la
 * pulsazione sulla calibrazione. Non confonderli (vedi i commenti originali).
 */
uint16_t Servo_AngleToPulseUs(uint16_t degree)
{
    uint32_t us;

    degree = Servo_ClampDeg(degree);

    us = SERVO_MIN_PULSE_US +
         ((uint32_t)degree * (SERVO_MAX_PULSE_US - SERVO_MIN_PULSE_US) +
          (SERVO_CAL_MAX_DEG - SERVO_CAL_MIN_DEG) / 2u) / (SERVO_CAL_MAX_DEG - SERVO_CAL_MIN_DEG);

    return (uint16_t)us;
}

/* Impulso in us -> duty LEDC a 14 bit su un periodo di 20 ms. */
static uint32_t Servo_PulseUsToDuty(uint16_t us)
{
    uint32_t full = (1u << SERVO_LEDC_RES_BITS);
    return ((uint32_t)us * full + (SERVO_PERIOD_US / 2u)) / SERVO_PERIOD_US;
}

void Turn_Servo(uint8_t n_servo, uint16_t degree)
{
    int i = Servo_Index(n_servo);
    uint16_t clamped;
    uint16_t us;

    if (i < 0) return;

    clamped = Servo_ClampDeg(degree);
    us = Servo_AngleToPulseUs(clamped);

    SAT_LEDC_WRITE(servo_ledc_ch[i], servo_pin[i], Servo_PulseUsToDuty(us));
    servo_last_deg[i] = clamped;
}

uint16_t Servo_GetDeg(uint8_t n_servo)
{
    switch (n_servo) {
        case 1:  return servo_last_deg[0];
        case 2:  return servo_last_deg[1];
        default: return SERVO_DEG_UNKNOWN;
    }
}

void Servo_ForgetDeg(uint8_t n_servo)
{
    switch (n_servo) {
        case 1:  servo_last_deg[0] = SERVO_DEG_UNKNOWN; break;
        case 2:  servo_last_deg[1] = SERVO_DEG_UNKNOWN; break;
        default: break;
    }
}

/* Posizionamento immediato, fuori rampa, con riallineamento del riferimento. */
static void Servo_Relocate(uint8_t n_servo, uint16_t degree)
{
    int i = Servo_Index(n_servo);

    if (i < 0) return;

    Turn_Servo(n_servo, degree);   /* il clamp avviene dentro Turn_Servo */

    servo_pos_deg[i]   = (int)Servo_GetDeg(n_servo);
    servo_pos_mdeg[i]  = (int32_t)servo_pos_deg[i] * 1000;
    servo_goal_mdeg[i] = servo_pos_mdeg[i];
}

void Servo_Center(uint8_t n_servo)
{
    Servo_Relocate(n_servo, SERVO_CENTER_DEG);
}

void Servo_Ramp_SetGoal(uint8_t n_servo, uint16_t degree)
{
    int i = Servo_Index(n_servo);

    if (i < 0) return;

    /* Posizione ignota: nessun punto di partenza credibile, si va diretti. */
    if (Servo_GetDeg(n_servo) == SERVO_DEG_UNKNOWN) {
        Servo_Relocate(n_servo, degree);
        return;
    }

    servo_goal_mdeg[i] = (int32_t)Servo_ClampDeg(degree) * 1000;
}

void Servo_Ramp_Hold(void)
{
    for (int i = 0; i < 2; i++) {
        if (Servo_GetDeg((uint8_t)(i + 1)) == SERVO_DEG_UNKNOWN) {
            Servo_Relocate((uint8_t)(i + 1), SERVO_CENTER_DEG);
        } else {
            servo_goal_mdeg[i] = servo_pos_mdeg[i];
        }
    }
}

void Servo_Ramp_Process(void)
{
    uint32_t now = millis();
    uint32_t elapsed = now - servo_ramp_tick;   /* sottrazione senza segno: sicura al wrap */
    int32_t  step_mdeg;

    if (elapsed < SERVO_RAMP_TICK_MS) return;
    servo_ramp_tick = now;

    /* Un blocco del loop non deve diventare un salto brusco del servo. */
    if (elapsed > SERVO_RAMP_MAX_GAP_MS) elapsed = SERVO_RAMP_MAX_GAP_MS;

    /* °/s vale numericamente come milligradi/ms. */
    step_mdeg = (int32_t)elapsed * (int32_t)SERVO_SLEW_DPS;

    for (int i = 0; i < 2; i++) {
        if (servo_pos_mdeg[i] < servo_goal_mdeg[i]) {
            servo_pos_mdeg[i] += step_mdeg;
            if (servo_pos_mdeg[i] > servo_goal_mdeg[i]) servo_pos_mdeg[i] = servo_goal_mdeg[i];
        } else if (servo_pos_mdeg[i] > servo_goal_mdeg[i]) {
            servo_pos_mdeg[i] -= step_mdeg;
            if (servo_pos_mdeg[i] < servo_goal_mdeg[i]) servo_pos_mdeg[i] = servo_goal_mdeg[i];
        } else {
            continue;
        }

        int deg = (int)(servo_pos_mdeg[i] / 1000);
        if (deg != servo_pos_deg[i]) {
            servo_pos_deg[i] = deg;
            Turn_Servo((uint8_t)(i + 1), (uint16_t)deg);
        }
    }
}
