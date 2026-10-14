# For more information about build system see
# https://docs.espressif.com/projects/esp-idf/en/latest/api-guides/build-system.html

cmake_minimum_required(VERSION 3.5)

include($ENV{IDF_PATH}/tools/cmake/project.cmake)

# MAVLink è nella cartella components/mavlink
# Questo dice: "Ehi sistema, cerca i componenti anche in questa cartella"
set(EXTRA_COMPONENT_DIRS ${CMAKE_CURRENT_SOURCE_DIR}/components/mavlink)

project(SERVER_WI_FI_ESP32)