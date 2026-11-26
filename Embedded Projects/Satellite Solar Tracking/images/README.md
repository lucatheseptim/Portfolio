# Images / 图片

Visual documentation for the satellite solar-tracking cube.
卫星太阳能追踪立方体的图形资料。

| File / 文件 | What it shows / 内容 |
|---|---|
| `01-motor-driver-wiring.png` | How the ESP32-C3 drives a 24 V motor through the driver: PWM for speed, DIR for direction, 24 V for power. <br>ESP32-C3 如何通过驱动板控制 24V 电机：PWM 控制转速，DIR 控制方向，24V 提供动力。 |
| `02-web-console.png` | Control page: mode, three flywheels, two servos, send and emergency stop. <br>控制页：模式、三个飞轮、两个舵机、发送与急停。 |
| `03-web-screen-camera.png` | Screen page (message + image upload) and camera page (live stream + capture). <br>屏幕页（消息与图片上传）和摄像头页（实时流与拍照）。 |
| `04-web-overview.png` | All four pages side by side: console, screen, camera, system status. <br>四个页面并排：控制、屏幕、摄像头、系统状态。 |
| `05-follow-mode-before-after.png` | What changes when the user taps **随动 Follow**: the button turns green, an orange note appears, sliders, direction buttons and Send are disabled, **急停 stays active**. <br>点击**随动 Follow**后的变化：按钮变绿，出现橙色提示，滑块、方向按钮和发送被禁用，**急停仍可用**。 |

## About the web images / 关于网页图片

They are **reconstructions**, not screenshots: they were drawn from the real
CSS values of the firmware (`#eef2f6` background, `#16a34a` active buttons,
`#6f42c1` send, `#dc3545` emergency stop), so the layout and the colours match
the actual interface closely, but the exact rendering depends on the phone.
它们是**复原图**而非截图：依据固件中真实的 CSS 数值绘制
（背景 `#eef2f6`、激活按钮 `#16a34a`、发送 `#6f42c1`、急停 `#dc3545`），
因此布局与配色与真实界面高度一致，但精确渲染效果取决于手机。
