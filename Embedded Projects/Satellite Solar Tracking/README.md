# Satellite Solar Tracking

Solar-tracking control system for a satellite demonstrator: a two-axis mechanism
that finds the brightest light source and orients a panel toward it, plus three
flywheel motors driven by the same irradiance measurements.

The project exists in two generations.

| Version | Architecture | Folder |
|---|---|---|
| **1.0** | Two boards: ESP32-C3 (web UI) + STM32F103 (control), linked over SPI | `Satellite1.0_OLD_STM32/` |
| **2.0** | Single board: ESP32-C3 runs control and web UI | `Satellite2.0_ESP32-C3/` |

## Why a second version

Version 1.0 split the work across two microcontrollers and a custom SPI bridge.
That bridge was the most fragile part of the system: 519 lines of protocol and
queue handling, with failure modes that surfaced only on the bench.

Version 2.0 removes the second board. The control logic was ported to the
ESP32-C3 and the SPI bridge was deleted, while the existing web interface was
reused unchanged. The result is one firmware, one board, one configuration.

## What the system does

- reads four BH1750 digital lux sensors through a TCA9548A I²C multiplexer
- computes a normalised light balance per axis and drives two servos toward it
- uses the same measurements to command three 24 V flywheel motors
- exposes a web interface (WiFi access point) for manual control and mode switching
- filters the error signal and applies hysteresis to avoid actuator hunting

## Contents

- `Satellite1.0_OLD_STM32/` — STM32F103 firmware (CubeMX project, HAL, Keil toolchain)
- `Satellite2.0_ESP32-C3/` — ESP32-C3 firmware (Arduino framework, single board)
- `images/` — wiring diagram and web interface previews (bilingual captions)
