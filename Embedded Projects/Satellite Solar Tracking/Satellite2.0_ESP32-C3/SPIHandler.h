#ifndef SPI_HANDLER_H
#define SPI_HANDLER_H

#include "sat_link.h"

/*
 * SOSTITUTO DROP-IN del tuo SPIHandler.h.
 *
 * Il WebServerHandler dichiara:
 *     #include "SPIHandler.h"
 *     SPIHandler spiHandler;
 *     spiHandler.begin();
 *     spiHandler.handleSPI();
 *     spiHandler.receiveFromWeb(protocolMsg);
 *
 * Questo file espone esattamente la stessa classe con la stessa interfaccia,
 * ma invece di parlare in SPI con l'STM32 applica i comandi in locale.
 *
 * COME SI USA
 *   1. rinomina il tuo SPIHandler.h in SPIHandler_SPI_backup.h
 *   2. metti questo file al suo posto
 *   3. WebServerHandler.h NON va toccato
 */

typedef SatLink SPIHandler;

#endif /* SPI_HANDLER_H */
