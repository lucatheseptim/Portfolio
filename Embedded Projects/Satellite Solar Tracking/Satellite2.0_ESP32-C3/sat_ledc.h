#ifndef SAT_LEDC_H
#define SAT_LEDC_H

#include <Arduino.h>

/*
 * Compatibilita' LEDC tra le versioni del core Arduino-ESP32.
 *
 *  - core 2.x : ledcSetup(canale, freq, risoluzione) + ledcAttachPin(pin, canale)
 *               ledcWrite(canale, duty)
 *  - core 3.x : ledcAttach(pin, freq, risoluzione)  (il canale e' implicito)
 *               ledcWrite(pin, duty)
 *
 * Le due macro qui sotto nascondono la differenza: chi le usa passa sempre
 * sia il canale sia il pin, e funziona su entrambe le versioni.
 */

#if defined(ESP_ARDUINO_VERSION_MAJOR) && (ESP_ARDUINO_VERSION_MAJOR >= 3)

  #define SAT_LEDC_SETUP(ch, pin, freq, res)  ledcAttach((pin), (freq), (res))
  #define SAT_LEDC_WRITE(ch, pin, duty)       ledcWrite((pin), (duty))

#else

  #define SAT_LEDC_SETUP(ch, pin, freq, res)  do { \
                                                  ledcSetup((ch), (freq), (res)); \
                                                  ledcAttachPin((pin), (ch)); \
                                              } while (0)
  #define SAT_LEDC_WRITE(ch, pin, duty)       ledcWrite((ch), (duty))

#endif

#endif /* SAT_LEDC_H */
