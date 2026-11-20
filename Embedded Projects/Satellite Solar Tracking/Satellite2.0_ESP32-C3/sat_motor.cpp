#include "sat_motor.h"
#include "sat_ledc.h"

/*
 * Port di Core/User/user_motor.c (motori) + control.c (assi X/Y/Z).
 *
 * Differenza sostanziale rispetto allo STM32: la TIM3 era configurata con
 * OCPolarity = LOW, quindi il "fermo" corrispondeva al pin ALTO e il duty
 * logico aumentava il tempo BASSO. LEDC non inverte: l'inversione e' qui.
 *
 * NOTA SUI PIN DI DIREZIONE
 * All'inizializzazione i DIR vengono messi ALTI, non bassi. Due motivi:
 *   1) e' quello che faceva il progetto di riferimento funzionante
 *      (motors_init: digitalWrite(DIRn, HIGH));
 *   2) DIR3 e' su GPIO9, che e' un pin di strapping: se resta basso durante
 *      un reset, l'ESP32-C3 entra in modalita' download e il firmware non
 *      parte. Tenerlo alto evita il problema.
 */

static uint8_t motor_ledc_ch[3]  = {0, 1, 2};
static uint8_t motor_pin[3]      = {255, 255, 255};
static uint8_t motor_dir_pin[3]  = {255, 255, 255};
static uint16_t motor_last_pwm[3] = {0, 0, 0};

static int Motor_Index(uint8_t motor)
{
    if (motor < 3) return (int)motor;
    return -1;
}

void Motor_Init(uint8_t pin[3], uint8_t ch[3], uint8_t dir_pin[3])
{
    for (int i = 0; i < 3; i++) {
        motor_ledc_ch[i] = ch[i];
        motor_pin[i]     = pin[i];
        motor_dir_pin[i] = dir_pin[i];

        /* Direzione: ALTA subito, prima di configurare il PWM. */
        pinMode(dir_pin[i], OUTPUT);
        digitalWrite(dir_pin[i], HIGH);

        SAT_LEDC_SETUP(ch[i], pin[i], MOTOR_LEDC_FREQ_HZ, MOTOR_LEDC_RES_BITS);

        /* Fermo = duty logico 0 = pin alto. */
        SAT_LEDC_WRITE(ch[i], pin[i], MOTOR_ACTIVE_LOW ? MOTOR_DUTY_MAX : 0);
        motor_last_pwm[i] = 0;
    }
}

/*
 * pwm: 0 = fermo, MOTOR_PWM_MAX = massima velocita' logica.
 * Il duty logico viene scalato su MOTOR_DUTY_MAX e poi invertito, perche' il
 * driver motori e' attivo-basso.
 */
void Motor_PWM_set(uint8_t motor, uint8_t direct, uint16_t pwm)
{
    int i = Motor_Index(motor);
    uint32_t duty;

    (void)direct;

    if (i < 0) return;

    if (pwm > MOTOR_PWM_MAX) pwm = MOTOR_PWM_MAX;

    duty = ((uint32_t)pwm * MOTOR_DUTY_MAX) / MOTOR_PWM_MAX;

#if MOTOR_ACTIVE_LOW
    duty = MOTOR_DUTY_MAX - duty;
#endif

    SAT_LEDC_WRITE(motor_ledc_ch[i], motor_pin[i], duty);
    motor_last_pwm[i] = pwm;
}

void Motor_Stop(uint8_t motor)
{
    int i = Motor_Index(motor);

    if (i < 0) return;

    SAT_LEDC_WRITE(motor_ledc_ch[i], motor_pin[i], MOTOR_ACTIVE_LOW ? MOTOR_DUTY_MAX : 0);
    motor_last_pwm[i] = 0;
}

void Motor_TurnSet(uint8_t motor, uint8_t dir)
{
    int i = Motor_Index(motor);

    if (i < 0) return;

    digitalWrite(motor_dir_pin[i], dir ? HIGH : LOW);
}

/* ---------------- Assi X/Y/Z ---------------- */

void MotorX_Run(uint16_t pwm) { Motor_PWM_set(0, 0, pwm); }
void MotorX_Stop(void)        { Motor_Stop(0); }
void MotorY_Run(uint16_t pwm) { Motor_PWM_set(1, 0, pwm); }
void MotorY_Stop(void)        { Motor_Stop(1); }
void MotorZ_Run(uint16_t pwm) { Motor_PWM_set(2, 0, pwm); }
void MotorZ_Stop(void)        { Motor_Stop(2); }

void DirX_Left(void)  { Motor_TurnSet(0, 1); }
void DirX_Right(void) { Motor_TurnSet(0, 0); }
void DirY_Up(void)    { Motor_TurnSet(1, 0); }
void DirY_Down(void)  { Motor_TurnSet(1, 1); }
void DirZ_Forward(void)  { Motor_TurnSet(2, 1); }
void DirZ_Backward(void) { Motor_TurnSet(2, 0); }
