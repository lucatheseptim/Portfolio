// Config.h
#ifndef CONFIG_H
#define CONFIG_H

// WiFi configuration
const char* AP_SSID = "卫星演示控制界面";
const char* AP_PASSWORD = "12345678";

// Server configuration
const int WEB_SERVER_PORT = 80;
const int MAX_HISTORY_SIZE = 10;

// S3 screen message configuration
#define SCREEN_MESSAGE_MAX_BYTES 300
#define S3_STATE_POLL_INTERVAL_MS 500

/*
 * 相机流"租约"超时。
 *
 * 手机相机页在打开期间每 3 秒续约一次（/s3_display_cmd?mode=stop），
 * 只要它不再续约超过这个时间，就认为手机已经不看了（页面被系统杀掉、
 * 断网、锁屏后浏览器挂起……这些情况都不会触发 pagehide），
 * 由 C3 主动把画面还给 S3。
 *
 * 没有这个兜底时，手机页面被系统回收一次，S3 就会永久停在"已停止"，
 * 只能在触摸屏上手动点一次摄像头才能恢复。
 */
#define S3_STREAM_CLAIM_TIMEOUT_MS 10000UL

// Camera stream URL (MJPEG)
#define CAM_STREAM_URL "http://192.168.4.2:81/stream"
// Camera static capture URL (usually /capture)
#define CAM_CAPTURE_URL "http://192.168.4.2/capture"

// LCD image display configuration
#define IMAGE_FILE "/display.jpg"
#define IMAGE_MAX_SIZE 600000
#define IMAGE_STATE_POLL_INTERVAL_MS 1000

// Servo angle command window
//
// 演示机构只允许两个 MG90S 在 30~150 度之间运动。STM32 侧 control.h 的
// SERVO_CMD_MIN_DEG / SERVO_CMD_MAX_DEG 会把所有角度入口夹到这个区间，
// 这里的作用是在**服务端**先把关（/api/control 的 a_val / b_val），
// 让网页滑条量程、服务端校验、STM32 裁剪三者口径一致。
//
// 改动时三处必须同步：
//   1) 本文件的 SERVO_ANGLE_MIN / SERVO_ANGLE_MAX
//   2) HtmlTemplates.h 里两个舵机滑条与数字框的 min/max 属性（静态标记，无法引用宏）
//   3) STM32 的 Core/User/control.h: SERVO_CMD_MIN_DEG / SERVO_CMD_MAX_DEG
#define SERVO_ANGLE_MIN 30
#define SERVO_ANGLE_MAX 150


#endif