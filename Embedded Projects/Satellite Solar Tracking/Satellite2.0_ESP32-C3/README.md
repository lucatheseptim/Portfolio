# Satellite2.0 — ESP32-C3 single-board controller

Second generation of the solar-tracking controller: the control logic that used
to run on an **STM32F103** now runs on the **ESP32-C3**, and the second
microcontroller — together with the SPI bridge that connected it — is gone.

The web interface is reused unchanged. Only the layer that used to talk to the
STM32 was replaced.

## Architecture

```
BEFORE                                    AFTER
┌──────────┐   SPI   ┌──────────┐         ┌────────────────────────────┐
│ ESP32-C3 │◄───────►│  STM32   │         │         ESP32-C3           │
│  web UI  │         │ control  │         │  web UI + control + sensors │
└──────────┘         └──────────┘         └────────────────────────────┘
```

The web layer still produces 20-character ASCII frames and calls the same three
methods (`begin`, `handleSPI`, `receiveFromWeb`). `SPIHandler.h` became a
drop-in replacement (`SatLink`) that applies the commands locally instead of
sending them over SPI. `WebServerHandler.h` was not modified.

## Migration summary

| Removed | Added |
|---|---|
| `user_spi.c` — 519 lines of SPI protocol and queueing | `sat_link.h` — local command parser and actuator dispatch |
| STM32 firmware, second power rail, second toolchain | — |
| SPI wiring (4 wires + CS) | — |

| Ported | From | To |
|---|---|---|
| servo ramp, angle limits, pulse conversion | `control.c` | `sat_servo.cpp` |
| motor PWM, direction, boot state | `user_motor.c` | `sat_motor.cpp` |
| tracking control law | `TCA9548A.c` | `sat_track.cpp` |
| BH1750 acquisition state machine | `TCA9548A.c` | `sat_sensors.cpp` |

## Modules

| File | Responsibility |
|---|---|
| `sat_pins.h` | pin assignment, taken from the wiring harness (connector H1) |
| `sat_config.h` | tuning constants (servo range, tracking, motor limits, timeouts) |
| `sat_ledc.h` | LEDC compatibility shim (Arduino-ESP32 core 2.x / 3.x) |
| `sat_servo.cpp` | angle → pulse → duty, 90 °/s ramp, 30–150° command window |
| `sat_motor.cpp` | three 24 V motors, 1 kHz PWM, direction pins |
| `sat_track.cpp` | tracking control law, deadband, motor commands |
| `sat_sensors.cpp` | TCA9548A multiplexing, BH1750 reads, bus recovery |
| `sat_link.h` | drop-in replacement for the SPI handler, plus command fail-safe |
| `legacy/SPIHandler_SPI.h` | previous SPI implementation, kept for reference |

## Control law

Servos balance light between two pairs of sensors:

```c
up   = L0 + L3;
down = L2 + L1;
error = (up - down) / sum;         /* normalised, -1..+1 */
target = 90 + error * KP;          /* clamped to 30..150 degrees */
```

| Parameter | Value | Purpose |
|---|---|---|
| `LIGHT_MIN_SUM_LUX` | 20 lx | below this total light nothing moves |
| `LIGHT_DEAD_ZONE_EXIT` | 7 % | imbalance needed to start moving |
| `LIGHT_DEAD_ZONE` | 5 % | imbalance at which motion stops |
| `TRACK_FILTER_TAU_MS` | 250 ms | low-pass filter on the error |
| `LIGHT_KP` | 110 | proportional gain |
| `SERVO_SLEW_DPS` | 90 °/s | ramp rate |

Motors use a separate law, because they are coarse actuators and must not
oscillate: a 30 lux deadband, gain 8, PWM clamped between 200 and 999. The Z axis
does not track a gradient — it seeks an intermediate light band (50–300 lux).

## Fail-safe

The first port kept motors at the last commanded speed forever: closing the web
page (or losing WiFi) left the 24 V motors running. That is not acceptable on a
motorised mechanism, so `sat_link.h` now stops every motor when no command has
arrived for 500 ms in manual mode.

```c
if ((uint32_t)(millis() - last_cmd_ms) > MANUAL_TIMEOUT_MS) {
    StopAllMotors();
}
```

## Pinout (LuatOS CORE ESP32-C3)

Pin assignment follows the demonstration-cube wiring harness (connector H1), not
free pin choice: the drivers are wired to these pins and any other assignment
leaves the motor inputs floating.

| Signal | GPIO | | Signal | GPIO |
|---|---|---|---|---|
| Motor 1 PWM | 2 | | Motor 1 DIR | 7 |
| Motor 2 PWM | 3 | | Motor 2 DIR | 11 |
| Motor 3 PWM | 10 | | Motor 3 DIR | 9 |
| Servo 1 | 6 | | I²C SDA | 4 |
| Servo 2 | 8 | | I²C SCL | 5 |
| Status LED | 12 | | | |

The direction pins are driven **high** at start-up, not low: `DIR3` sits on
`GPIO9`, a strapping pin, and holding it low across a reset would put the chip
into download mode instead of running the application.

`GPIO11` (`DIR2`) is the module's VDD_SPI pin. The harness uses it as a GPIO and
it works on this board, but it is the first suspect if flash errors appear.

## Build

Arduino IDE, board **AirM2M CORE ESP32C3** — the manufacturer of the LuatOS CORE
ESP32-C3 module. The default ESP32-C3 core settings apply (4 MB flash, 80 MHz,
default partition scheme).

```
Sketch uses 1 204 233 bytes (91%) of program storage space.
Global variables use 39 464 bytes (12%) of dynamic memory.
```

The PWM drivers are active-low, as on the original board: a logical duty of 0
means the pin stays high (motor stopped). LEDC has no hardware inversion, so the
duty is inverted in software.

## Status

| | |
|---|---|
| Compiles and flashes | ✅ |
| Servo initialisation (both axes to 90°) | ✅ verified on hardware |
| Web interface | ✅ unchanged from version 1.0 |
| 24 V motor drive | ✅ verified on hardware, all three axes |
| Command fail-safe | ✅ verified: motors stop within 500 ms |
| Lux sensor acquisition | ⏳ pending validation |
| Tracking mode | ⏳ pending validation |

## Notes

- The 24 V motor supply must be independent of the logic supply; only the PWM
  signal reaches the MCU.
- Pull-ups on the motor PWM lines are recommended: the ESP32-C3 takes longer than
  the STM32 to configure its pins at boot, so the power-on transient is wider.
- Flash is at 91%: little headroom, and OTA updates are not possible with this
  partition scheme.
