#ifndef __ESP32_SPI_SIMPLE_H
#define __ESP32_SPI_SIMPLE_H

#ifdef __cplusplus
extern "C" {
#endif

#include "user_main.h"
#include <stdint.h>
#include <stdbool.h>

/*
 * ESP32 SPI 通信协议说明
 *
 * STM32 作为 SPI Master，ESP32-C3 作为 SPI Slave。
 * STM32 发送 0xFF 作为查询字节，同时从 MISO 读取 ESP32 已准备好的 20 字节数据帧。
 *
 * READY 引脚：
 *   ESP32 GPIO9 -> STM32 PB5 外部中断（下降沿触发）
 *
 *   ESP32 发的是 **100us 宽度的窄脉冲**，不是电平：queue 好一帧后拉低 100us 再拉高。
 *   所以必须依赖 PB5 的下降沿中断把通知锁存到 esp_flag，不能在轮询时因为
 *   "READY 现在读到高电平"就把 esp_flag 清掉 —— 主循环在随动模式下比这 100us
 *   还慢，漏掉一次的后果是 ESP32 的事务永远悬着、再也不发新通知，SPI 彻底死掉。
 *   详见 user_spi.c 里 ShouldStartTransfer() 的说明。
 *
 * SPI 引脚：
 *   STM32 PB12 CS   -> ESP32 GPIO10
 *   STM32 PB13 SCK  -> ESP32 GPIO4
 *   STM32 PB15 MOSI -> ESP32 GPIO6
 *   STM32 PB14 MISO <- ESP32 GPIO5
 */

/* 主机查询字节。实际 MOSI 内容不重要，只用于产生 SCK 时钟。 */
#define ESP_QUERY_CMD       0xFF

/* 固定协议帧长度：20 字节。 */
#define SPI_BUF_SIZE        20

/* 初始化 SPI 用户状态机。 */
void SPI_ESP_Init(void);

/* 在主循环中周期调用，用于检查 READY 并发起 SPI 读帧。 */
void SPI_ESP_Process(void);

/* HAL SPI 回调转接函数，由 user_main.c 的 HAL 回调调用。 */
void SPI_ESP_DMACompleteCallback(void);
void SPI_ESP_ErrorCallback(void);

/* PB5 外部中断置位的通知标志。 */
extern volatile uint8_t esp_flag;

/* 模式标志：0=手动控制，1=自动追光。 */
extern volatile uint8_t Mode_Flag;

#ifdef __cplusplus
}
#endif

#endif
