#ifndef __TEST_H
#define __TEST_H

#include "stm32f1xx_hal.h"
#include <stdint.h>

/* DMA 环形缓冲大小 */
#define JY61P_DMA_BUF_SIZE 256

/* 外部数据缓冲引用 */
extern uint8_t jy61p_dma_buf[JY61P_DMA_BUF_SIZE];
extern volatile uint16_t jy61p_parse_pos;

/*数据结构体*/
typedef struct
{
    float ax, ay, az;      // g
    float gx, gy, gz;      // °/s
    float roll, pitch, yaw; // °
} JY61P_Data_t;

extern JY61P_Data_t jy61p_data;


/* API */
void JY61P_DMA_Start(void);
static uint16_t jy61p_ring_available(uint16_t write_pos);
static uint8_t jy61p_buf_read(uint16_t pos);
static int16_t jy61p_get_int16(uint8_t l, uint8_t h);
void JY61P_HandleFrame(uint8_t *f);
void JY61P_ProcessDMA(void);

#endif
