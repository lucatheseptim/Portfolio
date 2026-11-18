#include "test.h"
#include "usart.h"
#include <stdio.h>

/* DMA 环形缓冲 */
uint8_t jy61p_dma_buf[JY61P_DMA_BUF_SIZE];
volatile uint16_t jy61p_parse_pos = 0;

/*陀螺仪解析数据*/
JY61P_Data_t jy61p_data;

/* DMA 启动函数 */
void JY61P_DMA_Start(void)
{
    /* 启动 USART1 RX DMA */
    HAL_UART_Receive_DMA(&huart1, jy61p_dma_buf, JY61P_DMA_BUF_SIZE);

    /* 链接 DMA 句柄 */
    __HAL_LINKDMA(&huart1, hdmarx, hdma_usart2_rx);
}


/*环形缓冲剩余字节数*/
static uint16_t jy61p_ring_available(uint16_t write_pos)
{
    if (write_pos >= jy61p_parse_pos)
        return write_pos - jy61p_parse_pos;
    else
        return JY61P_DMA_BUF_SIZE - jy61p_parse_pos + write_pos;
}


/*安全读取环形缓冲（防跨界）*/
static uint8_t jy61p_buf_read(uint16_t pos)
{
    return jy61p_dma_buf[pos % JY61P_DMA_BUF_SIZE];
}


/*6 位有符号解析函数*/
static int16_t jy61p_get_int16(uint8_t l, uint8_t h)
{
    return (int16_t)((int16_t)h << 8 | l);
}


/*帧处理函数*/
void JY61P_HandleFrame(uint8_t *f)
{
    int16_t raw1, raw2, raw3;

    switch (f[1])
    {
    case 0x51: // 加速度
        raw1 = jy61p_get_int16(f[2], f[3]);
        raw2 = jy61p_get_int16(f[4], f[5]);
        raw3 = jy61p_get_int16(f[6], f[7]);

        jy61p_data.ax = raw1 / 32768.0f * 16.0f;
        jy61p_data.ay = raw2 / 32768.0f * 16.0f;
        jy61p_data.az = raw3 / 32768.0f * 16.0f;
        break;

    case 0x52: // 角速度
        raw1 = jy61p_get_int16(f[2], f[3]);
        raw2 = jy61p_get_int16(f[4], f[5]);
        raw3 = jy61p_get_int16(f[6], f[7]);

        jy61p_data.gx = raw1 / 32768.0f * 2000.0f;
        jy61p_data.gy = raw2 / 32768.0f * 2000.0f;
        jy61p_data.gz = raw3 / 32768.0f * 2000.0f;
        break;

    case 0x53: // 角度
        raw1 = jy61p_get_int16(f[2], f[3]);
        raw2 = jy61p_get_int16(f[4], f[5]);
        raw3 = jy61p_get_int16(f[6], f[7]);

        jy61p_data.roll  = raw1 / 32768.0f * 180.0f;
        jy61p_data.pitch = raw2 / 32768.0f * 180.0f;
        jy61p_data.yaw   = raw3 / 32768.0f * 180.0f;
        break;

    default:
        break;
    }
}

/* 数据处理函数（循环缓冲消费） */
void JY61P_ProcessDMA(void)
{
    uint16_t write_pos = JY61P_DMA_BUF_SIZE - __HAL_DMA_GET_COUNTER(&hdma_usart2_rx);

    /* 只要缓冲区里 >= 11 字节，就尝试解析 */
    while (jy61p_ring_available(write_pos) >= 11)
    {
        /* 1. 查找帧头 0x55 */
        if (jy61p_buf_read(jy61p_parse_pos) != 0x55)
        {
            jy61p_parse_pos++;
            if (jy61p_parse_pos >= JY61P_DMA_BUF_SIZE)
                jy61p_parse_pos = 0;
            continue;
        }

        /* 2. 缓冲区里是否还有完整 11 字节 */
        if (jy61p_ring_available(write_pos) < 11)
            return;

        /* 3. 拷贝 11 字节（防止跨 DMA 回卷） */
        uint8_t frame[11];
        for (int i = 0; i < 11; i++)
        {
            frame[i] = jy61p_buf_read(jy61p_parse_pos + i);
        }

        /* 4. 再次确认帧头（稳） */
        if (frame[0] != 0x55)
        {
            jy61p_parse_pos++;
            if (jy61p_parse_pos >= JY61P_DMA_BUF_SIZE)
                jy61p_parse_pos = 0;
            continue;
        }

        /* 5. 处理这一帧 */
        JY61P_HandleFrame(frame);

        /* 6. 消费 11 字节 */
        jy61p_parse_pos += 11;
        if (jy61p_parse_pos >= JY61P_DMA_BUF_SIZE)
            jy61p_parse_pos %= JY61P_DMA_BUF_SIZE;
    }
		 
}



