# Solar-Tracking Satellite Demonstrator — STM32F103

Firmware for a solar-tracking satellite demonstrator based on an **STM32F103C8T6**
(ARM Cortex-M3). The module orients a two-axis mechanism toward the strongest
light source and drives three flywheel motors, replacing fixed-angle commands
with autonomous, light-seeking behaviour.

## Overview

The board continuously samples ambient light through four digital lux sensors,
computes a normalised irradiance balance, and moves the actuators to equalise
the reading. A filtered proportional controller keeps the motion stable under
noisy indoor lighting, and a hysteresis deadband prevents oscillation around the
equilibrium point.

The STM32 communicates with an ESP32 module over SPI; the ESP32 hosts a web
interface (and optionally the camera and display nodes) that forwards operator
commands and switches between manual and tracking mode.

## Hardware

| Component | Role |
|---|---|
| STM32F103C8T6 | main controller (Cortex-M3, 72 MHz) |
| 4 × BH1750 | digital lux sensors (I²C) |
| TCA9548A | 8-channel I²C multiplexer for the sensors |
| 3 × 24 V DC motors | flywheels, PWM speed control |
| 2 × MG90S servos | two-axis pointing, 50 Hz PWM |
| ESP32-C3 | web interface and command bridge (SPI) |

## Firmware architecture

```
main.c            HAL init, super-loop, calls User_Init / User_While
Core/Src/         CubeMX-generated peripheral init (ADC, DMA, I2C, SPI, TIM, UART)
Core/User/        application layer
  user_main.c       entry points: User_Init(), User_While()
  TCA9548A.c        sensor multiplexing + tracking algorithm (TrackByLight)
  control.c         servo ramp, angle limits, command handling
  user_motor.c      motor/servo PWM, direction control, boot self-checks
  user_spi.c        SPI protocol with the ESP32, Mode_Flag (manual / tracking)
  mpu6050.c         IMU driver (I²C)
  test.c            bring-up helpers
```

### Tracking control

```c
up   = L0 + L3;                 /* two sensors, one side  */
down = L2 + L1;                 /* two sensors, other side */
error = (up - down) / sum;      /* normalised balance, -1..+1 */
target = 90 + error * KP;       /* servo angle */
```

Tuning parameters (see `TCA9548A.c`):

| Parameter | Value | Purpose |
|---|---|---|
| `LIGHT_MIN_SUM_LUX` | 20 lx | below this total light, no action |
| `LIGHT_DEAD_ZONE_EXIT` | 7 % | imbalance required to start moving |
| `LIGHT_DEAD_ZONE` | 5 % | imbalance at which motion stops (hysteresis) |
| `TRACK_FILTER_TAU_MS` | 250 ms | low-pass filter to reject flicker |
| `KP` | 110 | proportional gain |
| servo range | 30°–150° | mechanical limits enforced in software |

### PWM configuration

| Timer | Channel | Use | Frequency | Notes |
|---|---|---|---|---|
| TIM3 | CH2/3/4 | motor speed | 1 kHz | **active-low**: `CCR = 0` means stop |
| TIM1 | CH2/CH3 | servos | 50 Hz | `ARR = 1999` for 20 ms period |

`motor_Init()` performs two boot-time self-checks and reports over UART
(115200 baud) if the timer period or the PWM polarity is wrong — a wrong
polarity would turn "stop" into "full speed".

## Build

The project was generated with **STM32CubeMX** for the **MDK-ARM** toolchain, so
the Keil project files (`MDK-ARM/*.uvprojx`) are included.

Two build options:

1. **Keil MDK-ARM** — open `MDK-ARM/Satellite1.0.uvprojx`.
2. **GCC** — regenerate the project from `Satellite1.0.ioc` with
   *Toolchain/IDE = STM32CubeIDE*, or compile directly with
   `arm-none-eabi-gcc` (startup and linker script required).

> Note: since STM32CubeIDE 2.0 the CubeMX `.ioc` editor is no longer bundled with
> the IDE and requires a separate STM32CubeMX installation.

## Flashing

Via ST-LINK over SWD:

```
STM32_Programmer_CLI -c port=SWD -w Satellite1.0.hex -v -rst
```

Wiring: `SWDIO → PA13`, `SWCLK → PA14`, plus `GND` and `3V3`.

## Memory footprint

| Region | Used | Available |
|---|---|---|
| Flash | 60.4 KB | 64 KB (92 %) |
| RAM | 5.3 KB | 20 KB (26 %) |

The flash is nearly full: additional features require checking the map file
first.

## Notes and limitations

- Bare-metal super-loop architecture (no RTOS); all control is driven by
  non-blocking state machines and `HAL_GetTick()` timing.
- Motor driver inputs float while the MCU is in reset, which can cause a brief
  movement transient at power-on. Adding 10 kΩ pull-ups on the PWM lines (which
  are active-low) removes it.
- The 24 V motor supply must be independent of the logic supply; only the PWM
  signal is connected to the MCU.
