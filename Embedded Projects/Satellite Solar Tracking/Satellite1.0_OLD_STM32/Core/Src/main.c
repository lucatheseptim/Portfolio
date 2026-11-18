/* USER CODE BEGIN Header */
/**
  ******************************************************************************
  * @file           : main.c
  * @brief          : Main program body
  ******************************************************************************
  * @attention
  *
  * Copyright (c) 2026 STMicroelectronics.
  * All rights reserved.
  *
  * This software is licensed under terms that can be found in the LICENSE file
  * in the root directory of this software component.
  * If no LICENSE file comes with this software, it is provided AS-IS.
  *
  ******************************************************************************
  */
/* USER CODE END Header */
/* Includes ------------------------------------------------------------------*/
#include "main.h"
#include "adc.h"
#include "dma.h"
#include "i2c.h"
#include "spi.h"
#include "tim.h"
#include "usart.h"
#include "gpio.h"
#include "TCA9548A.h"

/* Private includes ----------------------------------------------------------*/
/* USER CODE BEGIN Includes */
#include "user_main.h"
#include "control.h"

/* USER CODE END Includes */

/* Private typedef -----------------------------------------------------------*/
/* USER CODE BEGIN PTD */
/* USER CODE BEGIN PFP */

static void SPI2_LoopbackTest(void);

/* USER CODE END PTD */

/* Private define ------------------------------------------------------------*/
/* USER CODE BEGIN PD */
/* USER CODE END PD */

/* Private macro -------------------------------------------------------------*/
/* USER CODE BEGIN PM */

/* USER CODE END PM */

/* Private variables ---------------------------------------------------------*/

/* USER CODE BEGIN PV */

/* USER CODE END PV */

/* Private function prototypes -----------------------------------------------*/
void SystemClock_Config(void);
/* USER CODE BEGIN PFP */

/* USER CODE END PFP */

/* Private user code ---------------------------------------------------------*/
/* USER CODE BEGIN 0 */
/* USER CODE BEGIN 0 */
/*
	spi环路测试函数
*/
static void SPI2_LoopbackTest(void)
{
    /* 测试数据：任意 8 个字节，包含典型值和边界值 */
    uint8_t txBuffer[8] = {0x00, 0x55, 0xAA, 0xFF, 0x12, 0x34, 0x56, 0x78};
    uint8_t rxBuffer[8] = {0};
    HAL_StatusTypeDef status;

    UART_Printf("\r\n===== SPI2 Loopback Test (PB14-PB15 shorted) =====\r\n");

    /* 可选：临时降低波特率，确保稳定性（当前若已为低速可不做） */
    /* 注意：修改波特率需要先禁止 SPI 再重新初始化 */
    __HAL_SPI_DISABLE(&hspi2);
    hspi2.Init.BaudRatePrescaler = SPI_BAUDRATEPRESCALER_128; // 约 281 kHz
    if (HAL_SPI_Init(&hspi2) != HAL_OK) {
        UART_Printf("SPI2 reinit failed!\r\n");
        return;
    }
		/*串口打印发送前数据*/
		UART_Printf("TX: ");
    for (int i = 0; i < sizeof(txBuffer); i++) {
        UART_Printf("%02X ", txBuffer[i]);
    }
    UART_Printf("\r\nRX: ");
    for (int i = 0; i < sizeof(rxBuffer); i++) {
        UART_Printf("%02X ", rxBuffer[i]);
    }
    UART_Printf("\r\n");

    /* 拉低片选（假设 PB12 为片选，已初始化为推挽输出） */
    HAL_GPIO_WritePin(GPIOB, GPIO_PIN_12, GPIO_PIN_RESET);
    HAL_Delay(1);

    /* 执行收发 */
    status = HAL_SPI_TransmitReceive(&hspi2, txBuffer, rxBuffer, sizeof(txBuffer), 100);

    /* 拉高片选 */
    HAL_GPIO_WritePin(GPIOB, GPIO_PIN_12, GPIO_PIN_SET);

    /* 检查传输是否超时或错误 */
    if (status != HAL_OK) {
        UART_Printf("SPI2 transfer failed! Error code: %d\r\n", status);
        return;
    }

    /* 打印发送和接收数据 */
    UART_Printf("TX: ");
    for (int i = 0; i < sizeof(txBuffer); i++) {
        UART_Printf("%02X ", txBuffer[i]);
    }
    UART_Printf("\r\nRX: ");
    for (int i = 0; i < sizeof(rxBuffer); i++) {
        UART_Printf("%02X ", rxBuffer[i]);
    }
    UART_Printf("\r\n");

    /* 比较数据 */
    if (memcmp(txBuffer, rxBuffer, sizeof(txBuffer)) == 0) {
        UART_Printf("Result: PASS (loopback data matches)\r\n");
    } else {
        UART_Printf("Result: FAIL (data mismatch)\r\n");
    }

    
}
/* USER CODE END 0 */
/* USER CODE END 0 */

/**
  * @brief  The application entry point.
  * @retval int
  */
int main(void)
{

  /* USER CODE BEGIN 1 */

  /* USER CODE END 1 */

  /* MCU Configuration--------------------------------------------------------*/

  /* Reset of all peripherals, Initializes the Flash interface and the Systick. */
  HAL_Init();

  /* USER CODE BEGIN Init */

  /* USER CODE END Init */

  /* Configure the system clock */
  SystemClock_Config();

  /* USER CODE BEGIN SysInit */

  /* USER CODE END SysInit */

  /* Initialize all configured peripherals */
  MX_GPIO_Init();
  MX_DMA_Init();
  MX_ADC1_Init();
  MX_TIM1_Init();
  MX_TIM3_Init();
  
  
  MX_USART1_UART_Init();
  MX_USART2_UART_Init();
  UART_Printf("TCA RESET before pulse PA12=%d\r\n", HAL_GPIO_ReadPin(GPIOA, GPIO_PIN_12));
  HAL_GPIO_WritePin(GPIOA, GPIO_PIN_12, GPIO_PIN_RESET);
  HAL_Delay(2);
  HAL_GPIO_WritePin(GPIOA, GPIO_PIN_12, GPIO_PIN_SET);
  HAL_Delay(10);
  UART_Printf("TCA RESET after pulse PA12=%d\r\n", HAL_GPIO_ReadPin(GPIOA, GPIO_PIN_12));
	UART_Printf("[INIT] Starting I2C1...\r\n");
	MX_I2C1_Init();
	UART_Printf("[INIT] I2C1 done\r\n");
	I2C2_PrintLineStatus("before init");
	MX_I2C2_Init();
	I2C2_PrintLineStatus("after init");
	MX_SPI2_Init();
	/* SPI1 uses PA5/PA6/PA7, which are motor direction/PWM pins on this board. */
	/* MX_SPI1_Init(); */
  /* USER CODE BEGIN 2 */
	User_Init();
  /* USER CODE END 2 */
//	UART_Printf("motor_test!\r\n");
//	motor_test();
//	UART_Printf("motor_test end!\r\n");
//	SPI2_LoopbackTest();  
	UART_Printf("Init finish!\r\n");
	
	// 临时扫描函数（放在初始化之后，不影响原有流程）

    UART_Printf("I2C scan: PB10/SCL=%d PB11/SDA=%d state=%lu err=0x%08lX\r\n",
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_10),
                HAL_GPIO_ReadPin(GPIOB, GPIO_PIN_11),
                (uint32_t)HAL_I2C_GetState(&TCA9548A_I2C),
                HAL_I2C_GetError(&TCA9548A_I2C));
    uint8_t found_count = 0;
    for (uint8_t addr = 1; addr < 127; addr++)
    {
        HAL_StatusTypeDef ret = HAL_I2C_IsDeviceReady(&TCA9548A_I2C, (addr << 1), 1, 10);
        if (ret == HAL_OK)
        {
            found_count++;
            UART_Printf("Found device at 0x%02X\r\n", addr);
        }
    }
    HAL_StatusTypeDef tca_ret = HAL_I2C_IsDeviceReady(&TCA9548A_I2C, TCA9548A_ADDR, 3, 50);
    UART_Printf("TCA9548A ready ret=%d state=%lu err=0x%08lX\r\n",
                tca_ret,
                (uint32_t)HAL_I2C_GetState(&TCA9548A_I2C),
                HAL_I2C_GetError(&TCA9548A_I2C));
    UART_Printf("Scan done, found=%d.\r\n", found_count);

  /* Infinite loop */
  /* USER CODE BEGIN WHILE */
  while (1)
  {
    /* USER CODE END WHILE */
//		HAL_GPIO_WritePin(GPIOB, GPIO_PIN_0, GPIO_PIN_SET);
    /* USER CODE BEGIN 3 */
		User_While();
	//motor_test();
	
//		DirX_Right();  
//		Turn_Servo(1, 100);
		
		
//		MotorX_Run(40);   //舵机2%占空比   50hz 1ms  0°
//		HAL_Delay(1000);   
			
		
//		MotorX_Run(280);	
//		HAL_Delay(1000);

  }
  /* USER CODE END 3 */
}

/**
  * @brief System Clock Configuration
  * @retval None
  */
void SystemClock_Config(void)
{
  RCC_OscInitTypeDef RCC_OscInitStruct = {0};
  RCC_ClkInitTypeDef RCC_ClkInitStruct = {0};
  RCC_PeriphCLKInitTypeDef PeriphClkInit = {0};

  /** Initializes the RCC Oscillators according to the specified parameters
  * in the RCC_OscInitTypeDef structure.
  */
  RCC_OscInitStruct.OscillatorType = RCC_OSCILLATORTYPE_HSE;
  RCC_OscInitStruct.HSEState = RCC_HSE_ON;
  RCC_OscInitStruct.HSEPredivValue = RCC_HSE_PREDIV_DIV1;
  RCC_OscInitStruct.HSIState = RCC_HSI_ON;
  RCC_OscInitStruct.PLL.PLLState = RCC_PLL_ON;
  RCC_OscInitStruct.PLL.PLLSource = RCC_PLLSOURCE_HSE;
  RCC_OscInitStruct.PLL.PLLMUL = RCC_PLL_MUL9;
  if (HAL_RCC_OscConfig(&RCC_OscInitStruct) != HAL_OK)
  {
    Error_Handler();
  }

  /** Initializes the CPU, AHB and APB buses clocks
  */
  RCC_ClkInitStruct.ClockType = RCC_CLOCKTYPE_HCLK|RCC_CLOCKTYPE_SYSCLK
                              |RCC_CLOCKTYPE_PCLK1|RCC_CLOCKTYPE_PCLK2;
  RCC_ClkInitStruct.SYSCLKSource = RCC_SYSCLKSOURCE_PLLCLK;
  RCC_ClkInitStruct.AHBCLKDivider = RCC_SYSCLK_DIV1;
  RCC_ClkInitStruct.APB1CLKDivider = RCC_HCLK_DIV2;
  RCC_ClkInitStruct.APB2CLKDivider = RCC_HCLK_DIV1;

  if (HAL_RCC_ClockConfig(&RCC_ClkInitStruct, FLASH_LATENCY_2) != HAL_OK)
  {
    Error_Handler();
  }
  PeriphClkInit.PeriphClockSelection = RCC_PERIPHCLK_ADC;
  PeriphClkInit.AdcClockSelection = RCC_ADCPCLK2_DIV6;
  if (HAL_RCCEx_PeriphCLKConfig(&PeriphClkInit) != HAL_OK)
  {
    Error_Handler();
  }
}

/* USER CODE BEGIN 4 */

/* USER CODE END 4 */

/**
  * @brief  This function is executed in case of error occurrence.
  * @retval None
  */
void Error_Handler(void)
{
  /* USER CODE BEGIN Error_Handler_Debug */
  /* User can add his own implementation to report the HAL error return state */
  __disable_irq();
  while (1)
  {
  }
  /* USER CODE END Error_Handler_Debug */
}

#ifdef  USE_FULL_ASSERT
/**
  * @brief  Reports the name of the source file and the source line number
  *         where the assert_param error has occurred.
  * @param  file: pointer to the source file name
  * @param  line: assert_param error line source number
  * @retval None
  */
void assert_failed(uint8_t *file, uint32_t line)
{
  /* USER CODE BEGIN 6 */
  /* User can add his own implementation to report the file name and line number,
     ex: printf("Wrong parameters value: file %s on line %d\r\n", file, line) */
  /* USER CODE END 6 */
}
#endif /* USE_FULL_ASSERT */
