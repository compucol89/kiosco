/**
 * WEB WORKER PARA PROCESAMIENTO AFIP NO-BLOQUEANTE
 * 
 * Maneja polling de estados AFIP en background sin bloquear UI
 * Optimiza performance y experiencia del usuario
 */

class AFIPWorker {
    constructor() {
        this.isPolling = false;
        this.pollingIntervals = new Map();
        this.cache = new Map();
        this.maxRetries = 5;
        this.baseDelay = 2000; // 2 segundos
    }

    /**
     * 🚀 INICIAR POLLING DE ESTADO AFIP
     */
    startPolling(ventaId, options = {}) {
        if (this.pollingIntervals.has(ventaId)) {
            this.stopPolling(ventaId);
        }

        const config = {
            interval: options.interval || 3000,
            maxAttempts: options.maxAttempts || 30,
            backoffMultiplier: options.backoffMultiplier || 1.2,
            ...options
        };

        let attempts = 0;
        let currentInterval = config.interval;

        const poll = async () => {
            try {
                attempts++;
                
                // Verificar límite de intentos
                if (attempts > config.maxAttempts) {
                    this.stopPolling(ventaId);
                    this.postMessage({
                        type: 'AFIP_TIMEOUT',
                        ventaId,
                        data: {
                            error: 'Tiempo límite de polling excedido',
                            attempts
                        }
                    });
                    return;
                }

                // Realizar consulta de estado
                const status = await this.checkAFIPStatus(ventaId);
                
                // Enviar actualización a UI
                this.postMessage({
                    type: 'AFIP_STATUS_UPDATE',
                    ventaId,
                    data: {
                        status,
                        attempts,
                        timestamp: Date.now()
                    }
                });

                // Verificar si debe continuar polling
                if (status.status === 'completed' || status.status === 'failed') {
                    this.stopPolling(ventaId);
                    
                    this.postMessage({
                        type: 'AFIP_FINAL_STATUS',
                        ventaId,
                        data: {
                            status,
                            totalAttempts: attempts,
                            duration: (Date.now() - this.getStartTime(ventaId)) / 1000
                        }
                    });
                } else {
                    // Programar siguiente poll con backoff
                    currentInterval = Math.min(
                        currentInterval * config.backoffMultiplier,
                        10000 // Máximo 10 segundos
                    );
                    
                    const timeoutId = setTimeout(poll, currentInterval);
                    this.pollingIntervals.set(ventaId, timeoutId);
                }

            } catch (error) {
                console.error('Error en polling AFIP:', error);
                
                // Enviar error pero continuar intentando
                this.postMessage({
                    type: 'AFIP_POLLING_ERROR',
                    ventaId,
                    data: {
                        error: error.message,
                        attempts,
                        willRetry: attempts < config.maxAttempts
                    }
                });

                if (attempts < config.maxAttempts) {
                    // Retry con delay incrementado
                    currentInterval = Math.min(currentInterval * 1.5, 8000);
                    const timeoutId = setTimeout(poll, currentInterval);
                    this.pollingIntervals.set(ventaId, timeoutId);
                } else {
                    this.stopPolling(ventaId);
                }
            }
        };

        // Guardar tiempo de inicio
        this.setStartTime(ventaId);
        
        // Iniciar polling inmediatamente
        poll();
    }

    /**
     * 🛑 DETENER POLLING ESPECÍFICO
     */
    stopPolling(ventaId) {
        const timeoutId = this.pollingIntervals.get(ventaId);
        if (timeoutId) {
            clearTimeout(timeoutId);
            this.pollingIntervals.delete(ventaId);
        }
        this.clearStartTime(ventaId);
    }

    /**
     * 🔍 CONSULTAR ESTADO AFIP
     */
    async checkAFIPStatus(ventaId) {
        // Verificar caché primero
        const cacheKey = `status_${ventaId}`;
        const cached = this.cache.get(cacheKey);
        
        if (cached && (Date.now() - cached.timestamp < 1000)) {
            return cached.data;
        }

        const response = await fetch(
            `/kiosco/api/afip_async_processor.php?action=status&venta_id=${ventaId}`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        // Cachear resultado brevemente
        this.cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });

        // Limpiar caché viejo
        this.cleanCache();

        return data;
    }

    /**
     * 📊 OBTENER MÉTRICAS DE PERFORMANCE
     */
    async getPerformanceMetrics() {
        try {
            const response = await fetch('/kiosco/api/afip_async_processor.php?action=metrics');
            return await response.json();
        } catch (error) {
            console.error('Error obteniendo métricas:', error);
            return null;
        }
    }

    /**
     * 🧹 LIMPIAR CACHÉ ANTIGUO
     */
    cleanCache() {
        const now = Date.now();
        const maxAge = 30000; // 30 segundos

        for (const [key, value] of this.cache.entries()) {
            if (now - value.timestamp > maxAge) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * ⏰ MANEJAR TIMESTAMPS
     */
    setStartTime(ventaId) {
        this.cache.set(`start_${ventaId}`, { timestamp: Date.now() });
    }

    getStartTime(ventaId) {
        const entry = this.cache.get(`start_${ventaId}`);
        return entry ? entry.timestamp : Date.now();
    }

    clearStartTime(ventaId) {
        this.cache.delete(`start_${ventaId}`);
    }

    /**
     * 📤 ENVIAR MENSAJE AL HILO PRINCIPAL
     */
    postMessage(message) {
        self.postMessage(message);
    }

    /**
     * 🛑 CLEANUP AL TERMINAR
     */
    terminate() {
        // Detener todos los pollings
        for (const ventaId of this.pollingIntervals.keys()) {
            this.stopPolling(ventaId);
        }
        
        // Limpiar caché
        this.cache.clear();
        
        this.postMessage({
            type: 'WORKER_TERMINATED',
            data: { message: 'Worker terminado correctamente' }
        });
    }
}

// ========== INSTANCIA DEL WORKER ==========
const afipWorker = new AFIPWorker();

// ========== MANEJADOR DE MENSAJES ==========
self.addEventListener('message', async (event) => {
    const { type, data } = event.data;

    try {
        switch (type) {
            case 'START_POLLING':
                afipWorker.startPolling(data.ventaId, data.options);
                break;

            case 'STOP_POLLING':
                afipWorker.stopPolling(data.ventaId);
                afipWorker.postMessage({
                    type: 'POLLING_STOPPED',
                    ventaId: data.ventaId
                });
                break;

            case 'CHECK_STATUS':
                const status = await afipWorker.checkAFIPStatus(data.ventaId);
                afipWorker.postMessage({
                    type: 'STATUS_RESPONSE',
                    ventaId: data.ventaId,
                    data: status
                });
                break;

            case 'GET_METRICS':
                const metrics = await afipWorker.getPerformanceMetrics();
                afipWorker.postMessage({
                    type: 'METRICS_RESPONSE',
                    data: metrics
                });
                break;

            case 'TERMINATE':
                afipWorker.terminate();
                break;

            default:
                afipWorker.postMessage({
                    type: 'ERROR',
                    data: { error: `Tipo de mensaje no reconocido: ${type}` }
                });
        }

    } catch (error) {
        afipWorker.postMessage({
            type: 'ERROR',
            data: {
                error: error.message,
                stack: error.stack,
                originalType: type
            }
        });
    }
});

// ========== MANEJO DE ERRORES ==========
self.addEventListener('error', (error) => {
    self.postMessage({
        type: 'WORKER_ERROR',
        data: {
            message: error.message,
            filename: error.filename,
            lineno: error.lineno,
            colno: error.colno
        }
    });
});

// ========== CLEANUP PERIÓDICO ==========
setInterval(() => {
    afipWorker.cleanCache();
}, 60000); // Cada minuto 