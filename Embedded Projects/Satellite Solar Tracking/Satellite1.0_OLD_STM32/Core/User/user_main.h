#ifndef __USER_MAIN_H
#define __USER_MAIN_H

/* 用户层公共头文件：集中包含用户模块常用的 HAL 外设头文件。 */
#include "main.h"
#include "spi.h"
#include "tim.h"
#include "gpio.h"

#include "stdio.h"
#include <string.h>
#include <stdlib.h>

/* 保留原工程的短整型别名，用户模块里大量使用 u8/u16/u32。 */
#define   u8     uint8_t
#define   u16    uint16_t
#define   u32    uint32_t

/* 用户层初始化和主循环入口，由 main.c 调用。 */
void User_Init(void);
void User_While(void);

/* 下面几个函数是原工程预留接口，当前主流程暂未调用。 */
void moto_go(void);
void moto_break(void);
void moto_jiaozhun(void);
void zhuche_1(void);
void zhuche_2(void);

#endif
