#ifndef IMAGE_HANDLER_H
#define IMAGE_HANDLER_H

#include <Arduino.h>
#include <LittleFS.h>

class ImageHandler {
private:
    unsigned long imageId = 0;
    size_t imageSize = 0;

public:
    bool begin() {
        if (!LittleFS.begin(true)) {
            Serial.println("[IMAGE] LittleFS failed");
            return false;
        }
        Serial.println("[IMAGE] LittleFS ready");
        return true;
    }

    void startUpload() {
        imageSize = 0;
        File f = LittleFS.open(IMAGE_FILE, "w");
        if (f) f.close();
        imageId++;
    }

    void writeChunk(uint8_t *buf, size_t len) {
        File f = LittleFS.open(IMAGE_FILE, "a");
        if (f) {
            f.write(buf, len);
            f.close();
            imageSize += len;
        }
    }

    String stateJson() {
        String s="{";
        s += "\"id\":"+String(imageId)+",";
        s += "\"size\":"+String(imageSize)+",";
        s += "\"url\":\"/display.jpg\"";
        s += "}";
        return s;
    }
};

#endif
