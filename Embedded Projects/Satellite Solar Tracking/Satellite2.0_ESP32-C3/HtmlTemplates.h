// HtmlTemplates.h
#ifndef HTML_TEMPLATES_H
#define HTML_TEMPLATES_H

#include <Arduino.h>
#include "config.h"

class HtmlTemplates {
public:
    // ======================== 主页面（极简控制台） ========================
    static String getIndexPage(const String& ip, int messageCount, String* messageHistory, int historySize) {
        (void)ip;
        (void)messageCount;
        (void)messageHistory;
        (void)historySize;

        String html = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=yes'>
    <title>控制台</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, "Microsoft YaHei", sans-serif;
            margin: 0;
            padding: 8px 8px 72px 8px;
            background: #eef2f6;
            color: #1f2933;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(15,23,42,0.08);
        }
        h1 {
            text-align: center;
            font-size: 1.35rem;
            margin: 2px 0 10px 0;
            color: #102a43;
        }
        .panel {
            background: #f8fafc;
            border: 1px solid #d9e2ec;
            border-radius: 14px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .mode-panel { background: #eef2ff; border-color: #c7d2fe; }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .label-title {
            font-weight: 700;
            color: #243b53;
            white-space: nowrap;
        }
        .button-group {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        button {
            border: none;
            border-radius: 999px;
            padding: 8px 13px;
            font-size: 0.88rem;
            cursor: pointer;
            color: #fff;
            background: #0d6efd;
            white-space: nowrap;
        }
        button:hover { opacity: 0.9; }
        button:disabled {
            background: #94a3b8 !important;
            cursor: not-allowed;
            opacity: 0.65;
        }
        input:disabled { opacity: 0.55; cursor: not-allowed; }
        .mode-btn { background: #64748b; }
        .mode-btn-active { background: #16a34a; }
        .dir-btn { background: #64748b; padding: 6px 10px; font-size: 0.8rem; }
        .dir-btn-active { background: #16a34a; }
        .send-btn { background: #6f42c1; font-weight: 700; }
        .danger-btn { background: #dc3545; font-weight: 700; }
        .secondary-btn { background: #6c757d; }
        .active-nav { background: #0d6efd; }
        .device-row {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 13px;
            padding: 9px 10px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 76px minmax(150px, 1fr) 76px 94px;
            align-items: center;
            gap: 8px;
        }
        .device-name {
            font-weight: 700;
            color: #0d6efd;
            white-space: nowrap;
        }
        .slider-container { min-width: 0; }
        input[type="range"] { width: 100%; display: block; }
        input[type="number"] {
            width: 66px;
            padding: 6px;
            border: 1px solid #bcccdc;
            border-radius: 9px;
            font-size: 0.9rem;
            text-align: center;
        }
        .value-box {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .unit { color: #64748b; font-size: 0.8rem; }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .actions button { width: 100%; padding: 10px 12px; }
        .status {
            min-height: 20px;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.4;
            margin-top: 8px;
        }
        .lock-note {
            display: none;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 12px;
            padding: 8px 10px;
            margin-bottom: 10px;
            font-size: 0.82rem;
        }
        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.97);
            border-top: 1px solid #d9e2ec;
            box-shadow: 0 -3px 12px rgba(15,23,42,0.08);
            padding: 8px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            z-index: 10;
        }
        .bottom-nav a { text-decoration: none; }
        .bottom-nav button { width: 100%; padding: 9px 8px; }
        @media (max-width: 620px) {
            .device-row {
                grid-template-columns: 62px 1fr 66px;
                gap: 7px;
            }
            .device-row .button-group { grid-column: 2 / 4; }
        }
        @media (max-width: 420px) {
            body { padding: 7px 7px 72px 7px; }
            .container { padding: 10px; border-radius: 14px; }
            h1 { font-size: 1.22rem; }
            .mode-panel .button-group { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
            .device-row { grid-template-columns: 1fr; }
            .device-row .button-group { grid-column: 1; }
            .value-box { justify-content: flex-start; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function getEl(id) { return document.getElementById(id); }

        function clampValue(value, min, max) {
            value = parseInt(value || 0);
            if (isNaN(value)) value = 0;
            return Math.min(Math.max(value, min), max);
        }

        function getCurrentMode() {
            return getEl('modeManual').classList.contains('mode-btn-active') ? 0 : 1;
        }

        function setMotorValue(axis, value) {
            value = clampValue(value, 0, 100);
            getEl('motor'+axis+'_slider').value = value;
            getEl('motor'+axis+'_val').value = value;
        }

        function setServoValue(axis, value) {
            /* 上下限直接读滑条元素自身的 min/max 属性，不再把 0/180 写死一遍：
             * 改量程只需要动 HTML 的 min/max（见 config.h 里 SERVO_ANGLE_* 的说明）。 */
            const slider = getEl('servo'+axis+'_slider');
            value = clampValue(value, parseInt(slider.min, 10), parseInt(slider.max, 10));
            slider.value = value;
            getEl('servo'+axis+'_val').value = value;
        }

        function setDefaultControlValues() {
            setMotorValue('X', 0);
            setMotorValue('Y', 0);
            setMotorValue('Z', 0);
            setServoValue('A', 90);
            setServoValue('B', 90);
            setDirection('x', 0, false);
            setDirection('y', 0, false);
            setDirection('z', 0, false);
        }

        function setControlLocked(locked) {
            const ids = [
                'motorX_slider','motorX_val','dirX_f','dirX_r',
                'motorY_slider','motorY_val','dirY_f','dirY_r',
                'motorZ_slider','motorZ_val','dirZ_f','dirZ_r',
                'servoA_slider','servoA_val','servoB_slider','servoB_val',
                'sendControlBtn'
            ];
            ids.forEach(function(id) {
                const el = getEl(id);
                if (el) el.disabled = locked;
            });
            const note = getEl('followLockNote');
            if (note) note.style.display = locked ? 'block' : 'none';
        }

        function setModeButtonHighlight(mode) {
            const manual = getEl('modeManual');
            const follow = getEl('modeFollow');
            if (mode == 0) {
                manual.classList.add('mode-btn-active');
                follow.classList.remove('mode-btn-active');
                setControlLocked(false);
            } else {
                manual.classList.remove('mode-btn-active');
                follow.classList.add('mode-btn-active');
                setControlLocked(true);
            }
        }

        function setDirection(motor, dir, needCheck = true) {
            if (needCheck && getCurrentMode() === 1) return;
            dir = (parseInt(dir || 0) === 1) ? 1 : 0;

            const upper = motor.toUpperCase();
            getEl('dir' + upper + '_f').classList.toggle('dir-btn-active', dir === 0);
            getEl('dir' + upper + '_r').classList.toggle('dir-btn-active', dir === 1);
            getEl('motor' + upper + '_dir_val').value = dir;
        }

        function updateNumber(sliderId, numberId) {
            if (getCurrentMode() === 1) return;
            getEl(numberId).value = getEl(sliderId).value;
        }

        function updateSlider(sliderId, numberId) {
            if (getCurrentMode() === 1) return;
            const slider = getEl(sliderId);
            const number = getEl(numberId);
            /* 上下限取滑条自己的 min/max，不再由调用方传字面量。
             * 原来调用方传的是写死的 100 / 180，与 HTML 属性是两份数字，
             * 改一处漏一处就会出现"滑条能拖到 180、输入框又弹回 150"这类不一致。 */
            number.value = clampValue(number.value,
                                      parseInt(slider.min, 10),
                                      parseInt(slider.max, 10));
            slider.value = number.value;
        }

        async function loadApiState() {
            try {
                const response = await fetch('/api/state?t=' + Date.now());
                const data = await response.json();
                if (data && data.control) setModeButtonHighlight(parseInt(data.control.mode || 0));
            } catch (err) {
                try {
                    const response = await fetch('/getmode?t=' + Date.now());
                    const data = await response.json();
                    setModeButtonHighlight(parseInt(data.mode || 0));
                } catch (e) {}
            }
        }

        async function setMode(mode) {
            setModeButtonHighlight(mode);
            const status = getEl('controlSendStatus');
            status.innerText = '正在切换模式...';
            try {
                const response = await fetch('/setmode?mode=' + mode + '&t=' + Date.now());
                if (!response.ok) throw new Error('mode failed');
                status.innerText = (mode == 0) ? '手动模式' : '随动模式，控制已锁定';
                await loadApiState();
            } catch (e) {
                status.innerText = '模式切换失败';
            }
        }

        function buildControlParams(source) {
            const params = new URLSearchParams();
            params.set('mode', getCurrentMode());
            params.set('x_val', getEl('motorX_val').value);
            params.set('x_dir', getEl('motorX_dir_val').value);
            params.set('y_val', getEl('motorY_val').value);
            params.set('y_dir', getEl('motorY_dir_val').value);
            params.set('z_val', getEl('motorZ_val').value);
            params.set('z_dir', getEl('motorZ_dir_val').value);
            params.set('a_val', getEl('servoA_val').value);
            params.set('b_val', getEl('servoB_val').value);
            params.set('source', source || 'web');
            return params;
        }

        function showStatus(text) {
            const status = getEl('controlSendStatus');
            status.innerText = text;
        }

        async function sendControl() {
            if (getCurrentMode() === 1) {
                showStatus('随动模式已锁定');
                return;
            }
            showStatus('发送中...');
            try {
                const response = await fetch('/api/control', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: buildControlParams('web').toString()
                });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.error || 'failed');
                showStatus('已发送');
            } catch (e) {
                showStatus('发送失败');
            }
        }

        async function emergencyStop() {
            showStatus('急停发送中...');
            try {
                const response = await fetch('/api/emergency_stop?t=' + Date.now(), { method: 'POST' });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.error || 'failed');
                setDefaultControlValues();
                setModeButtonHighlight(0);
                showStatus('已急停：飞轮 0，舵机 90°');
            } catch (e) {
                showStatus('急停失败');
            }
        }

        window.addEventListener('DOMContentLoaded', function() {
            setDefaultControlValues();
            setModeButtonHighlight(0);
            loadApiState();
        });
    </script>
</head>
<body>
<div class='container'>
    <h1>控制台</h1>

    <div class='panel mode-panel'>
        <div class='flex-between'>
            <div class='label-title'>模式</div>
            <div class='button-group'>
                <button id='modeManual' class='mode-btn mode-btn-active' onclick='setMode(0)'>手动</button>
                <button id='modeFollow' class='mode-btn' onclick='setMode(1)'>随动</button>
            </div>
        </div>
    </div>

    <div id='followLockNote' class='lock-note'>随动模式：飞轮和舵机控制已锁定，急停可用。</div>

    <div class='device-row'>
        <div class='device-name'>X 飞轮</div>
        <div class='slider-container'><input type='range' id='motorX_slider' min='0' max='100' value='0' oninput="updateNumber('motorX_slider','motorX_val')"></div>
        <div class='value-box'><input type='number' id='motorX_val' min='0' max='100' value='0' oninput="updateSlider('motorX_slider','motorX_val')" step='1'><span class='unit'>%</span></div>
        <div class='button-group'>
            <button id='dirX_f' class='dir-btn dir-btn-active' onclick="setDirection('x',0)">正</button>
            <button id='dirX_r' class='dir-btn' onclick="setDirection('x',1)">反</button>
        </div>
        <input type='hidden' id='motorX_dir_val' value='0'>
    </div>

    <div class='device-row'>
        <div class='device-name'>Y 飞轮</div>
        <div class='slider-container'><input type='range' id='motorY_slider' min='0' max='100' value='0' oninput="updateNumber('motorY_slider','motorY_val')"></div>
        <div class='value-box'><input type='number' id='motorY_val' min='0' max='100' value='0' oninput="updateSlider('motorY_slider','motorY_val')" step='1'><span class='unit'>%</span></div>
        <div class='button-group'>
            <button id='dirY_f' class='dir-btn dir-btn-active' onclick="setDirection('y',0)">正</button>
            <button id='dirY_r' class='dir-btn' onclick="setDirection('y',1)">反</button>
        </div>
        <input type='hidden' id='motorY_dir_val' value='0'>
    </div>

    <div class='device-row'>
        <div class='device-name'>Z 飞轮</div>
        <div class='slider-container'><input type='range' id='motorZ_slider' min='0' max='100' value='0' oninput="updateNumber('motorZ_slider','motorZ_val')"></div>
        <div class='value-box'><input type='number' id='motorZ_val' min='0' max='100' value='0' oninput="updateSlider('motorZ_slider','motorZ_val')" step='1'><span class='unit'>%</span></div>
        <div class='button-group'>
            <button id='dirZ_f' class='dir-btn dir-btn-active' onclick="setDirection('z',0)">正</button>
            <button id='dirZ_r' class='dir-btn' onclick="setDirection('z',1)">反</button>
        </div>
        <input type='hidden' id='motorZ_dir_val' value='0'>
    </div>

    <!-- 舵机角度窗口 30~150 度。演示机构只允许两个 MG90S 在这个区间内运动，
         超出部分 STM32 会固定夹到边界（control.h 的 SERVO_CMD_MIN/MAX_DEG）。
         这里的 min/max 是静态标记（HTML 属性引用不到 C 宏），必须与
         config.h 的 SERVO_ANGLE_MIN/ANGLE_MAX 保持一致；
         JS 侧不再重复写死数字，一律从这两个元素的 min/max 读取。 -->
    <div class='device-row'>
        <div class='device-name'>舵机 1</div>
        <div class='slider-container'><input type='range' id='servoA_slider' min='30' max='150' value='90' oninput="updateNumber('servoA_slider','servoA_val')"></div>
        <div class='value-box'><input type='number' id='servoA_val' min='30' max='150' value='90' oninput="updateSlider('servoA_slider','servoA_val')" step='1'><span class='unit'>°</span></div>
        <div></div>
    </div>

    <div class='device-row'>
        <div class='device-name'>舵机 2</div>
        <div class='slider-container'><input type='range' id='servoB_slider' min='30' max='150' value='90' oninput="updateNumber('servoB_slider','servoB_val')"></div>
        <div class='value-box'><input type='number' id='servoB_val' min='30' max='150' value='90' oninput="updateSlider('servoB_slider','servoB_val')" step='1'><span class='unit'>°</span></div>
        <div></div>
    </div>

    <div class='actions'>
        <button id='sendControlBtn' class='send-btn' onclick='sendControl()'>发送</button>
        <button id='emergencyStopBtn' class='danger-btn' onclick='emergencyStop()'>急停</button>
    </div>
    <div id='controlSendStatus' class='status'></div>
</div>

<div class='bottom-nav'>
    <a href='/'><button class='active-nav'>控制</button></a>
    <a href='/message'><button class='secondary-btn'>屏幕</button></a>
    <a href='/camera'><button class='secondary-btn'>摄像头</button></a>
</div>
</body>
</html>
)rawliteral";
        return html;
    }

    // ======================== S3 消息页面 ========================
    static String getMessagePage() {
        String html = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=yes'>
    <title>屏幕</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, "Microsoft YaHei", sans-serif;
            margin: 0;
            padding: 8px 8px 72px 8px;
            background: #eef2f6;
            color: #1f2933;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(15,23,42,0.08);
        }
        h1 {
            text-align: center;
            font-size: 1.35rem;
            margin: 2px 0 12px 0;
            color: #102a43;
        }
        textarea {
            width: 100%;
            min-height: 160px;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid #bcccdc;
            font-size: 1rem;
            resize: vertical;
            font-family: Arial, "Microsoft YaHei", sans-serif;
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        button {
            border: none;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.9rem;
            cursor: pointer;
            color: #fff;
            background: #0d6efd;
            white-space: nowrap;
        }
        button:hover { opacity: 0.9; }
        .send-btn { background: #198754; font-weight: 700; }
        .secondary-btn { background: #6c757d; }
        .active-nav { background: #0d6efd; }
        .status {
            min-height: 20px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.4;
            margin-top: 8px;
        }
        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.97);
            border-top: 1px solid #d9e2ec;
            box-shadow: 0 -3px 12px rgba(15,23,42,0.08);
            padding: 8px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            z-index: 10;
        }
        .bottom-nav a { text-decoration: none; }
        .bottom-nav button { width: 100%; padding: 9px 8px; }
        @media (max-width: 420px) {
            .container { padding: 10px; border-radius: 14px; }
            h1 { font-size: 1.22rem; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function getEl(id) { return document.getElementById(id); }

        async function sendS3Message() {
            const input = getEl('s3MsgInput');
            const status = getEl('s3MsgStatus');
            const msg = (input.value || '').trim();
            if (!msg) {
                status.innerText = '消息不能为空';
                return;
            }
            try {
                status.innerText = '发送中...';
                const response = await fetch('/api/message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'msg=' + encodeURIComponent(msg)
                });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.error || 'failed');
                status.innerText = '已发送';
            } catch (e) {
                status.innerText = '发送失败';
            }
        }



        const LCD_WIDTH = 400;
        const LCD_HEIGHT = 800;

        function updateImageName(){
            const file = getEl('imageFile').files[0];
            const name = getEl('imageName');
            if(file){
                name.innerText = '已选择: ' + file.name;
            }else{
                name.innerText = '未选择图片';
            }
        }

        function compressImage(file){
            return new Promise((resolve)=>{

                const img = new Image();

                img.onload = function(){

                    const maxW = 360;
                    const maxH = 640;

                    let width = img.width;
                    let height = img.height;

                    // 竖屏LCD适配：保持比例缩放
                    let scale = Math.min(
                        maxW / width,
                        maxH / height,
                        1
                    );

                    width = Math.round(width * scale);
                    height = Math.round(height * scale);


                    const canvas = document.createElement('canvas');

                    canvas.width = width;
                    canvas.height = height;


                    const ctx = canvas.getContext('2d');


                    // JPEG不支持透明，透明区域填白色
                    ctx.fillStyle = "#FFFFFF";
                    ctx.fillRect(
                        0,
                        0,
                        width,
                        height
                    );


                    ctx.drawImage(
                        img,
                        0,
                        0,
                        width,
                        height
                    );


                    // 统一输出JPEG
                    canvas.toBlob(
                        function(blob){
                            resolve(blob);
                        },
                        "image/jpeg",
                        0.75
                    );
                };


                img.onerror = function(){
                    resolve(null);
                };


                img.src = URL.createObjectURL(file);

            });
        }

        async function uploadImageToLCD() {
            const file = getEl('imageFile').files[0];
            const status = getEl('imageUploadStatus');

            if (!file) {
                status.innerText = '请选择图片';
                return;
            }

            status.innerText = '正在压缩图片...';

            try {
                const jpg = await compressImage(file);

                if (!jpg) throw new Error('compress failed');

                status.innerText =
                    '压缩完成，正在上传...';

                const form = new FormData();
                form.append('image', jpg, 'display.jpg');

                const response = await fetch('/upload', {
                    method: 'POST',
                    body: form
                });

                if (!response.ok)
                    throw new Error('upload failed');

                status.innerText =
                    '上传成功';

            } catch(e) {
                status.innerText = '上传失败';
            }
        }

        function clearS3MessageInput() {
            getEl('s3MsgInput').value = '';
            getEl('s3MsgStatus').innerText = '';
        }
    </script>
</head>
<body>
<div class='container'>
    <h1>屏幕</h1>
    <textarea id='s3MsgInput' maxlength='300' placeholder='输入要显示到屏幕的消息'></textarea>

    <div class='actions'>
        <button class='send-btn' onclick='sendS3Message()'>发送</button>
        <button class='secondary-btn' onclick='clearS3MessageInput()'>清空</button>
    </div>
    <div id='s3MsgStatus' class='status'></div>

    <div class='panel'>
        <h3>图片上传</h3>
        <input id='imageFile'
               type='file'
               accept='image/*'
               style='display:none'
               onchange='updateImageName()'>

        <div class='actions'>
            <button class='secondary-btn'
                    onclick="document.getElementById('imageFile').click()">
                选择图片
            </button>
            <button class='send-btn'
                    onclick='uploadImageToLCD()'>
                上传图片到屏幕
            </button>
        </div>

        <div style="
            font-size:12px;
            color:#888;
            margin-top:8px;
            text-align:center;">
            若需要上传图片，请使用手机浏览器输入设备网址 192.168.4.1 后操作
        </div>

        <div id='imageName' class='status'>
            未选择图片
        </div>
        <div id='imageUploadStatus' class='status'>等待选择图片</div>
    </div>
</div>

<div class='bottom-nav'>
    <a href='/'><button class='secondary-btn'>控制</button></a>
    <a href='/message'><button class='active-nav'>屏幕</button></a>
    <a href='/camera'><button class='secondary-btn'>摄像头</button></a>
</div>
</body>
</html>
)rawliteral";
        return html;
    }

    // ======================== 摄像头页面 ========================
    static String getCameraPage() {
        String camStream = String(CAM_STREAM_URL);
        String camCapture = String(CAM_CAPTURE_URL);

        String html = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=yes'>
    <title>摄像头</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, "Microsoft YaHei", sans-serif;
            margin: 0;
            padding: 8px 8px 72px 8px;
            background: #eef2f6;
            color: #1f2933;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(15,23,42,0.08);
            text-align: center;
        }
        h1 {
            font-size: 1.35rem;
            margin: 2px 0 12px 0;
            color: #102a43;
        }
        img {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #d9e2ec;
            background: #000;
            max-height: 68vh;
            object-fit: contain;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        button {
            border: none;
            border-radius: 999px;
            padding: 10px 12px;
            font-size: 0.88rem;
            cursor: pointer;
            color: #fff;
            background: #0d6efd;
            white-space: nowrap;
        }
        button:hover { opacity: 0.9; }
        .send-btn { background: #198754; }
        .secondary-btn { background: #6c757d; }
        .active-nav { background: #0d6efd; }
        .status {
            min-height: 20px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.4;
            margin-top: 8px;
        }
        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.97);
            border-top: 1px solid #d9e2ec;
            box-shadow: 0 -3px 12px rgba(15,23,42,0.08);
            padding: 8px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            z-index: 10;
        }
        .bottom-nav a { text-decoration: none; }
        .bottom-nav button { width: 100%; padding: 9px 8px; }
        @media (max-width: 420px) {
            .container { padding: 10px; border-radius: 14px; }
            h1 { font-size: 1.22rem; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        const CAM_STREAM = ')rawliteral" + camStream + R"rawliteral(';
        const CAM_CAPTURE = ')rawliteral" + camCapture + R"rawliteral(';
        let isStreaming = true;

        function getImg() { return document.getElementById('camImg'); }
        function setStatus(text) { document.getElementById('statusMsg').textContent = text; }

        function stopStream() {
            const img = getImg();
            if (img) img.src = '';
            isStreaming = false;
        }

        function setStreamMode() {
            const img = getImg();
            if (!img) return;
            img.src = '';
            img.src = CAM_STREAM + '?_=' + Date.now();
            isStreaming = true;
            setStatus('实时流');
            img.onerror = function() {
                if (isStreaming) {
                    setStatus('连接失败，5 秒后重试');
                    setTimeout(function() {
                        if (isStreaming) img.src = CAM_STREAM + '?_=' + Date.now();
                    }, 5000);
                }
            };
            img.onload = function() {
                if (isStreaming) setStatus('实时流正常');
            };
        }

        function setSnapshotMode(url) {
            const img = getImg();
            if (!img) return;
            img.src = '';
            img.src = url;
            isStreaming = false;
            setStatus('图像已冻结');
            img.onerror = null;
            img.onload = null;
        }

        function refreshStream() { setStreamMode(); }

        async function startS3Live() {
            try {
                setStatus('通知 S3 中...');
                stopStream();
                const response = await fetch('/s3_display_cmd?mode=live&t=' + Date.now());
                if (!response.ok) throw new Error('failed');
                setStatus('S3 实时显示已启动');
            } catch (e) {
                setStatus('S3 显示启动失败');
            }
        }

        async function stopS3Live() {
            try {
                setStatus('通知 S3 中...');
                const response = await fetch('/s3_display_cmd?mode=stop&t=' + Date.now());
                if (!response.ok) throw new Error('failed');
                setStatus('S3 显示已停止');
            } catch (e) {
                setStatus('S3 停止失败');
            }
        }

        async function captureSnapshot() {
            try {
                setStatus('拍照中...');
                const img = getImg();
                if (img) img.src = '';
                const response = await fetch(CAM_CAPTURE + '?_=' + Date.now());
                if (!response.ok) throw new Error('failed');
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                setSnapshotMode(url);
                try { await fetch('/snapshot_notify?t=' + Date.now()); } catch (e) {}
                setStatus('拍照完成');
            } catch (e) {
                setStatus('拍照失败');
                setStreamMode();
            }
        }

        /*
         * 两端抢流互斥（谁最后请求谁独占）。
         *
         * ESP32-CAM 的 HTTP 服务器是单任务的，/stream 的处理函数是个永不返回的
         * 死循环，所以**同一时刻只能服务一个观看端**。手机网页和 S3 屏幕同时开
         * 相机时，两边会抢这唯一一个槽位：抢到的那边出图，另一边每秒超时重连
         * 一次 —— 这是"网页和 S3 都很卡"的最大单一原因。
         *
         * 规则：打开本页 = 手机要自己看，先把 S3 的实时画面停掉；离开本页
         * （切后台/锁屏/关闭）= 把画面还给 S3。复用已有的 /s3_display_cmd
         * 机制，不新增协议。S3 每 500ms 轮询一次，所以最迟半秒内响应。
         *
         * 注意：S3 还没释放时，本页对摄像头的连接会在 CAM 那边排队等着
         * （httpd 只是接受连接、暂时不处理），表现为画面晚半秒出现，不会报错。
         */
        function claimStreamForWeb() {
            try {
                /* keepalive 让请求在页面卸载后也能发出去。不 await —— 卸载过程中
                 * 浏览器不会等这个 Promise。 */
                fetch('/s3_display_cmd?mode=stop&t=' + Date.now(), { keepalive: true });
            } catch (e) {}
        }

        function releaseStreamToS3() {
            try {
                fetch('/s3_display_cmd?mode=live&t=' + Date.now(), { keepalive: true });
            } catch (e) {}
        }

        /* 续租心跳：mode=keep 只刷新 C3 那边的租约计时，不改变状态、不递增
         * cmd_id（所以 S3 不会每 3 秒被重新执行一次"停止"）。
         *
         * 没有它的话，页面被系统回收 / 浏览器挂起 / Wi-Fi 掉线这几种情况
         * 都不会触发 pagehide，C3 会一直以为手机还在看，S3 就永久停在
         * "已停止"了。C3 端超时见 config.h 的 S3_STREAM_CLAIM_TIMEOUT_MS。 */
        function renewStreamClaim() {
            if (document.hidden) return;
            try {
                fetch('/s3_display_cmd?mode=keep&t=' + Date.now(), { keepalive: true });
            } catch (e) {}
        }
        setInterval(renewStreamClaim, 3000);

        window.onbeforeunload = function() { stopStream(); };

        /* pagehide 比 onbeforeunload 可靠：移动端浏览器不保证触发 onbeforeunload。
         * visibilitychange 覆盖"切到后台/锁屏"—— 那时手机其实也不看了，
         * 应该把流让出去，回来时再抢回来。 */
        window.addEventListener('pagehide', function() {
            stopStream();
            releaseStreamToS3();
        });
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopStream();
                releaseStreamToS3();
            } else {
                claimStreamForWeb();
                setStreamMode();
            }
        });

        window.onload = function() {
            claimStreamForWeb();
            setStreamMode();
        };
    </script>
</head>
<body>
<div class='container'>
    <h1>摄像头</h1>
    <img id='camImg' src='' alt='加载中'>
    <div id='statusMsg' class='status'>加载中...</div>
    <div class='actions'>
        <button class='secondary-btn' onclick='refreshStream()'>网页显示</button>
        <button class='send-btn' onclick='captureSnapshot()'>拍照</button>
    </div>
</div>

<div class='bottom-nav'>
    <a href='/'><button class='secondary-btn'>控制</button></a>
    <a href='/message'><button class='secondary-btn'>屏幕</button></a>
    <a href='/camera'><button class='active-nav'>摄像头</button></a>
</div>
</body>
</html>
)rawliteral";
        return html;
    }

    // ======================== 辅助页面 ========================
    static String getSuccessPage(const String& title, const String& message, int redirectDelay = 2) {
        String html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
        html += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        html += "<style>";
        html += "body{font-family:Arial,'Microsoft YaHei',sans-serif;padding:30px;text-align:center;background:#f8f9fa;color:#1f2933;}";
        html += ".success{background:#d4edda;color:#155724;padding:22px;border-radius:14px;max-width:520px;margin:20px auto;line-height:1.7;}";
        html += "a{display:inline-block;margin-top:20px;padding:10px 22px;background:#0d6efd;color:white;text-decoration:none;border-radius:999px;}";
        html += "</style>";
        html += "<meta http-equiv='refresh' content='" + String(redirectDelay) + ";url=/'>";
        html += "</head><body>";
        html += "<div class='success'><h1>" + title + "</h1><p>" + message + "</p></div>";
        html += "<a href='/'>返回主页</a>";
        html += "</body></html>";
        return html;
    }

    static String getStatusPage(const String& ssid, const String& ip, const String& mac,
                                unsigned long uptime, int messageCount, const String& lastMessage) {
        String html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
        html += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        html += "<style>";
        html += "body{font-family:Arial,'Microsoft YaHei',sans-serif;padding:20px;background:#f8f9fa;color:#1f2933;}";
        html += ".container{max-width:620px;margin:0 auto;background:white;padding:20px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,0.08);}";
        html += ".status-item{margin:10px 0;padding:12px;background:#f1f5f9;border-radius:8px;word-break:break-all;}";
        html += ".label{font-weight:bold;}";
        html += "a{display:inline-block;margin-top:20px;padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:999px;}";
        html += "</style>";
        html += "</head><body><div class='container'><h1>系统状态</h1>";
        html += "<div class='status-item'><span class='label'>AP 名称:</span> " + ssid + "</div>";
        html += "<div class='status-item'><span class='label'>IP 地址:</span> " + ip + "</div>";
        html += "<div class='status-item'><span class='label'>MAC 地址:</span> " + mac + "</div>";
        html += "<div class='status-item'><span class='label'>运行时间:</span> " + String(uptime) + " 秒</div>";
        html += "<div class='status-item'><span class='label'>总消息数:</span> " + String(messageCount) + " 条</div>";
        html += "<div class='status-item'><span class='label'>最后消息:</span> " + lastMessage + "</div>";
        html += "<br><a href='/'>返回主页</a></div></body></html>";
        return html;
    }

    static String getHistoryPage(int messageCount, String* messageHistory, int historySize) {
        String html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
        html += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        html += "<style>";
        html += "body{font-family:Arial,'Microsoft YaHei',sans-serif;padding:20px;background:#f8f9fa;color:#1f2933;}";
        html += ".container{max-width:620px;margin:0 auto;background:white;padding:20px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,0.08);}";
        html += ".history-item{margin:8px 0;padding:12px;background:#f1f5f9;border-left:4px solid #0d6efd;border-radius:6px;font-family:Consolas,monospace;word-break:break-all;}";
        html += ".empty{text-align:center;color:#6c757d;}";
        html += "a{display:inline-block;margin-top:20px;padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:999px;}";
        html += "</style>";
        html += "</head><body><div class='container'><h1>消息历史</h1>";
        if (messageCount == 0) {
            html += "<p class='empty'>暂无历史消息</p>";
        } else {
            html += "<p>共 " + String(messageCount) + " 条消息:</p>";
            int displayCount = (messageCount > historySize) ? historySize : messageCount;
            int startIndex = (messageCount > historySize) ? messageCount - historySize : 0;
            for (int i = 0; i < displayCount; i++) {
                int idx = (startIndex + i) % historySize;
                html += "<div class='history-item'><strong>" + String(i+1) + ".</strong> " + messageHistory[idx] + "</div>";
            }
        }
        html += "<br><a href='/'>返回主页</a></div></body></html>";
        return html;
    }
};

#endif