// ProtocolDefinition.h
#ifndef PROTOCOL_DEFINITION_H
#define PROTOCOL_DEFINITION_H

#include <Arduino.h>

// Control mode definitions
enum ControlMode {
    MODE_MANUAL = 0,      // 00 - SPI manual control mode
    MODE_SUN_TRACK = 1,   // 01 - sun tracking mode
    MODE_EMERGENCY = 2,   // 10 - emergency stop / brake mode
    MODE_STATUS = 3       // 11 - status query / reserved
};

// Device address definitions
enum DeviceAddress {
    ADDR_X_AXIS = 0,      // 000 - X axis motor
    ADDR_Y_AXIS = 1,      // 001 - Y axis motor
    ADDR_Z_AXIS = 2,      // 010 - Z axis motor
    ADDR_SERVO_1 = 3,     // 011 - solar panel 1 servo
    ADDR_SERVO_2 = 4,     // 100 - solar panel 2 servo
    ADDR_GLOBAL = 5       // 101 - global control / configuration
};

// Global control sub-commands
enum GlobalCommand {
    CMD_SET_HOME = 0,     // 000 - save current position as home
    CMD_GO_HOME = 1,      // 001 - go to home
    CMD_SET_MAX_SPEED = 2,// 010 - set max speed limit
    CMD_SET_ACCEL = 3,    // 011 - set acceleration
    CMD_MOTOR_ENABLE = 4, // 100 - enable / disable motor
    CMD_SET_SUN_PARAM = 5,// 101 - set sun tracking parameters
    CMD_READ_SENSOR = 6,  // 110 - read sensor data
    CMD_RESET_FAULT = 7   // 111 - reset fault
};

class ProtocolDefinition {
public:
    // Returns the control mode name (bilingual).
    static String getControlModeName(uint8_t mode) {
        switch(mode) {
            case MODE_MANUAL: return "手动模式 (Manual)";
            case MODE_SUN_TRACK: return "太阳追随 (Sun tracking)";
            case MODE_EMERGENCY: return "急停模式 (Emergency)";
            case MODE_STATUS: return "状态查询 (Status)";
            default: return "未知模式 (Unknown mode)";
        }
    }
    
    // Returns the device name (bilingual).
    static String getDeviceName(uint8_t address) {
        switch(address) {
            case ADDR_X_AXIS: return "X轴电机 (X axis motor)";
            case ADDR_Y_AXIS: return "Y轴电机 (Y axis motor)";
            case ADDR_Z_AXIS: return "Z轴电机 (Z axis motor)";
            case ADDR_SERVO_1: return "太阳能板1舵机 (Panel 1 servo)";
            case ADDR_SERVO_2: return "太阳能板2舵机 (Panel 2 servo)";
            case ADDR_GLOBAL: return "全局控制 (Global)";
            default: return "未知设备 (Unknown device)";
        }
    }
    
    // Returns the global command name (bilingual).
    static String getGlobalCommandName(uint8_t cmd) {
        switch(cmd) {
            case CMD_SET_HOME: return "设置原点 (Set home)";
            case CMD_GO_HOME: return "回原点 (Go home)";
            case CMD_SET_MAX_SPEED: return "设置最大速度 (Set max speed)";
            case CMD_SET_ACCEL: return "设置加速度 (Set acceleration)";
            case CMD_MOTOR_ENABLE: return "电机使能 (Motor enable)";
            case CMD_SET_SUN_PARAM: return "设置太阳参数 (Set sun params)";
            case CMD_READ_SENSOR: return "读取传感器 (Read sensor)";
            case CMD_RESET_FAULT: return "故障复位 (Reset fault)";
            default: return "未知命令 (Unknown command)";
        }
    }
    
    // Validates the angle range.
    static bool isValidAngle(uint16_t angle, uint8_t address) {
        if (address <= ADDR_Z_AXIS) {
            // Motor angle range: 0-360°
            return angle <= 1023;
        } else if (address <= ADDR_SERVO_2) {
            // Servo angle range: 0-180°
            return angle <= 1023;
        }
        return false;
    }
    
    // Returns the protocol help text (bilingual).
    static String getProtocolHelp() {
        String help = "协议格式说明 (Protocol format):\n";
        help += "数据位宽 (Data width): 16 bit\n";
        help += "控制模式 (Control mode): [15:14] 00=手动(Manual), 01=太阳追随(Sun), 10=急停(Emergency), 11=状态(Status)\n";
        help += "设备地址 (Device address): [13:11] 000=X轴(X), 001=Y轴(Y), 010=Z轴(Z), 011=舵机1(Servo1), 100=舵机2(Servo2), 101=全局(Global)\n";
        help += "数据字段 (Data field): [10:0] 角度/速度值或命令参数 (angle/speed or command param)\n";
        return help;
    }
};

#endif