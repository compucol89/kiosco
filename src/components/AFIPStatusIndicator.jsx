import React, { useState, useEffect, useCallback, useRef } from 'react';

/**
 * INDICADOR DE ESTADO AFIP - COMPONENTE DE PROGRESO
 * 
 * Muestra el estado de facturación electrónica en tiempo real
 * Mejora la UX con feedback visual del proceso asíncrono
 */
const AFIPStatusIndicator = ({ venta, onStatusUpdate }) => {
    const [facturacionStatus, setFacturacionStatus] = useState(null);
    const [isPolling, setIsPolling] = useState(false);
    const [pollingAttempts, setPollingAttempts] = useState(0);
    const [showDetails, setShowDetails] = useState(false);
    const [performanceMetrics, setPerformanceMetrics] = useState(null);
    const workerRef = useRef(null);
    const isWebWorkerSupported = useRef(typeof Worker !== 'undefined');

    // Estados posibles de facturación
    const statusConfig = {
        'PROCESSING': {
            icon: '⏳',
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
            borderColor: 'border-blue-200',
            message: 'Generando factura electrónica...',
            showSpinner: true
        },
        'AUTORIZADO': {
            icon: '✅',
            color: 'text-green-600',
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            message: 'Factura autorizada por AFIP',
            showSpinner: false
        },
        'ERROR': {
            icon: '❌',
            color: 'text-red-600',
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            message: 'Error en facturación',
            showSpinner: false
        },
        'QUEUE_ERROR': {
            icon: '⚠️',
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-50',
            borderColor: 'border-yellow-200',
            message: 'Error en cola de procesamiento',
            showSpinner: false
        }
    };

    /**
     * 🛠️ INICIALIZAR WEB WORKER
     */
    const initializeWorker = useCallback(() => {
        if (!isWebWorkerSupported.current || workerRef.current) return;

        try {
            workerRef.current = new Worker('/workers/afip-worker.js');
            
            workerRef.current.onmessage = (event) => {
                const { type, ventaId, data } = event.data;
                
                // Verificar que el mensaje es para esta venta
                if (ventaId && ventaId !== venta?.venta_id) return;
                
                switch (type) {
                    case 'AFIP_STATUS_UPDATE':
                        setFacturacionStatus(data.status);
                        setPollingAttempts(data.attempts);
                        
                        if (onStatusUpdate) {
                            onStatusUpdate(data.status);
                        }
                        break;
                        
                    case 'AFIP_FINAL_STATUS':
                        setIsPolling(false);
                        setFacturacionStatus(data.status);
                        
                        // Si está completado, actualizar datos fiscales
                        if (data.status.status === 'completed' && data.status.datos_fiscales) {
                            setFacturacionStatus({
                                ...data.status,
                                status: 'AUTORIZADO',
                                datos_fiscales: data.status.datos_fiscales
                            });
                        }
                        
                        if (onStatusUpdate) {
                            onStatusUpdate(data.status);
                        }
                        
                        // Obtener métricas finales
                        workerRef.current.postMessage({
                            type: 'GET_METRICS'
                        });
                        break;
                        
                    case 'AFIP_TIMEOUT':
                        setIsPolling(false);
                        setFacturacionStatus({
                            status: 'ERROR',
                            message: 'Tiempo límite de procesamiento excedido'
                        });
                        break;
                        
                    case 'AFIP_POLLING_ERROR':
                        console.warn('Error en polling AFIP:', data.error);
                        if (!data.willRetry) {
                            setIsPolling(false);
                            setFacturacionStatus({
                                status: 'ERROR',
                                message: data.error
                            });
                        }
                        break;
                        
                    case 'METRICS_RESPONSE':
                        setPerformanceMetrics(data);
                        break;
                        
                    case 'ERROR':
                        console.error('Error en Web Worker AFIP:', data.error);
                        // Fallback a polling tradicional
                        fallbackToTraditionalPolling();
                        break;
                }
            };
            
            workerRef.current.onerror = (error) => {
                console.error('Error en Web Worker:', error);
                fallbackToTraditionalPolling();
            };
            
        } catch (error) {
            console.error('Error inicializando Worker:', error);
            isWebWorkerSupported.current = false;
        }
    }, [venta?.venta_id, onStatusUpdate]);

    /**
     * 🔙 FALLBACK A POLLING TRADICIONAL
     */
    const fallbackToTraditionalPolling = useCallback(() => {
        console.log('Fallback a polling tradicional');
        isWebWorkerSupported.current = false;
        
        if (workerRef.current) {
            workerRef.current.terminate();
            workerRef.current = null;
        }
        
        // Implementar polling tradicional como backup
        checkFacturacionStatusTraditional();
    }, []);

    /**
     * 🔄 POLLING TRADICIONAL (FALLBACK)
     */
    const checkFacturacionStatusTraditional = useCallback(async () => {
        if (!venta?.venta_id || !isPolling) return;

        try {
            const response = await fetch(
                `/kiosco/api/afip_async_processor.php?action=status&venta_id=${venta.venta_id}`
            );
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            setFacturacionStatus(data);
            setPollingAttempts(prev => prev + 1);
            
            if (onStatusUpdate) {
                onStatusUpdate(data);
            }
            
            if (data.status === 'completed' || data.status === 'failed') {
                setIsPolling(false);
                
                if (data.status === 'completed' && data.datos_fiscales) {
                    setFacturacionStatus({
                        ...data,
                        status: 'AUTORIZADO',
                        datos_fiscales: data.datos_fiscales
                    });
                }
            }
            
        } catch (error) {
            console.error('Error verificando estado AFIP:', error);
            setPollingAttempts(prev => prev + 1);
            
            if (pollingAttempts >= 10) {
                setIsPolling(false);
                setFacturacionStatus({
                    status: 'ERROR',
                    message: 'Error de conectividad verificando estado'
                });
            }
        }
    }, [venta?.venta_id, onStatusUpdate, pollingAttempts, isPolling]);

    /**
     * 🎬 INICIALIZAR Y GESTIONAR POLLING
     */
    useEffect(() => {
        if (venta?.datos_fiscales?.estado_fiscal === 'PROCESSING' && !isPolling) {
            setIsPolling(true);
            setPollingAttempts(0);
            
            // Establecer estado inicial
            setFacturacionStatus({
                status: 'PROCESSING',
                message: venta.datos_fiscales.mensaje || 'Procesando factura...',
                tiempo_estimado: venta.datos_fiscales.tiempo_estimado
            });

            // Inicializar Worker si es compatible
            if (isWebWorkerSupported.current) {
                initializeWorker();
                
                // Iniciar polling con Worker
                if (workerRef.current) {
                    workerRef.current.postMessage({
                        type: 'START_POLLING',
                        data: {
                            ventaId: venta.venta_id,
                            options: {
                                interval: 2000,      // 2 segundos inicial
                                maxAttempts: 25,     // 25 intentos máximo
                                backoffMultiplier: 1.15  // Incremento gradual
                            }
                        }
                    });
                } else {
                    // Fallback inmediato si Worker falla
                    fallbackToTraditionalPolling();
                }
            } else {
                // Usar polling tradicional si no hay soporte para Workers
                checkFacturacionStatusTraditional();
            }
        }
    }, [venta?.datos_fiscales?.estado_fiscal, isPolling, initializeWorker, fallbackToTraditionalPolling, checkFacturacionStatusTraditional]);

    /**
     * ⏰ EFECTO DE POLLING TRADICIONAL (SOLO COMO FALLBACK)
     */
    useEffect(() => {
        let intervalId;

        // Solo usar polling tradicional si no hay Web Worker
        if (isPolling && 
            facturacionStatus?.status === 'PROCESSING' && 
            !isWebWorkerSupported.current) {
            
            intervalId = setInterval(checkFacturacionStatusTraditional, 3000);
            checkFacturacionStatusTraditional();
        }

        return () => {
            if (intervalId) {
                clearInterval(intervalId);
            }
        };
    }, [isPolling, facturacionStatus?.status, checkFacturacionStatusTraditional]);

    /**
     * 🧹 CLEANUP AL DESMONTAR COMPONENTE
     */
    useEffect(() => {
        return () => {
            if (workerRef.current) {
                workerRef.current.postMessage({
                    type: 'STOP_POLLING',
                    data: { ventaId: venta?.venta_id }
                });
                workerRef.current.terminate();
                workerRef.current = null;
            }
        };
    }, [venta?.venta_id]);

    /**
     * 🎨 COMPONENTE SPINNER ANIMADO
     */
    const Spinner = () => (
        <div className="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
    );

    /**
     * 📱 COMPONENTE DE PROGRESO CIRCULAR
     */
    const CircularProgress = ({ status }) => {
        const radius = 20;
        const strokeWidth = 3;
        const normalizedRadius = radius - strokeWidth * 2;
        const circumference = normalizedRadius * 2 * Math.PI;
        
        // Calcular progreso basado en intentos de polling
        const progress = Math.min((pollingAttempts / 10) * 100, 90);
        const strokeDasharray = `${circumference} ${circumference}`;
        const strokeDashoffset = circumference - (progress / 100) * circumference;

        return (
            <div className="relative inline-flex items-center justify-center">
                <svg
                    height={radius * 2}
                    width={radius * 2}
                    className="transform -rotate-90"
                >
                    <circle
                        stroke="currentColor"
                        fill="transparent"
                        strokeWidth={strokeWidth}
                        strokeDasharray={strokeDasharray}
                        style={{ strokeDashoffset }}
                        r={normalizedRadius}
                        cx={radius}
                        cy={radius}
                        className="text-blue-500 transition-all duration-300"
                    />
                    <circle
                        stroke="currentColor"
                        fill="transparent"
                        strokeWidth={strokeWidth}
                        r={normalizedRadius}
                        cx={radius}
                        cy={radius}
                        className="text-gray-200"
                    />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center">
                    <span className="text-xs font-bold text-blue-600">
                        {Math.round(progress)}%
                    </span>
                </div>
            </div>
        );
    };

    // No mostrar nada si no hay datos fiscales
    if (!venta?.datos_fiscales) {
        return null;
    }

    const currentStatus = facturacionStatus?.status || venta.datos_fiscales.estado_fiscal;
    const config = statusConfig[currentStatus] || statusConfig['PROCESSING'];

    return (
        <div className={`rounded-lg border ${config.borderColor} ${config.bgColor} p-3 mb-4`}>
            {/* HEADER DEL ESTADO */}
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3">
                    <div className={`text-lg ${config.color}`}>
                        {config.showSpinner ? <Spinner /> : config.icon}
                    </div>
                    
                    <div className="flex-1">
                        <div className={`font-medium text-sm ${config.color}`}>
                            {config.message}
                        </div>
                        
                        {/* INFORMACIÓN ADICIONAL */}
                        {currentStatus === 'PROCESSING' && (
                            <div className="text-xs text-gray-600 mt-1">
                                {facturacionStatus?.tiempo_estimado && (
                                    <span>Tiempo estimado: {facturacionStatus.tiempo_estimado}</span>
                                )}
                                {pollingAttempts > 0 && (
                                    <span className="ml-2">• Verificación #{pollingAttempts}</span>
                                )}
                            </div>
                        )}
                        
                        {currentStatus === 'AUTORIZADO' && facturacionStatus?.cae && (
                            <div className="text-xs text-gray-600 mt-1">
                                CAE: {facturacionStatus.cae}
                            </div>
                        )}
                        
                        {(currentStatus === 'ERROR' || currentStatus === 'QUEUE_ERROR') && facturacionStatus?.error && (
                            <div className="text-xs text-red-600 mt-1">
                                {facturacionStatus.error}
                            </div>
                        )}
                        
                        {/* INDICADOR DE WEB WORKER */}
                        {isWebWorkerSupported.current && currentStatus === 'PROCESSING' && (
                            <div className="text-xs text-blue-600 mt-1 flex items-center">
                                <span className="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span>
                                Procesamiento optimizado
                            </div>
                        )}
                        
                        {/* MÉTRICAS DE PERFORMANCE */}
                        {performanceMetrics && showDetails && (
                            <div className="text-xs text-gray-600 mt-1">
                                <span>Tasa éxito: {performanceMetrics.success_rate || 'N/A'}%</span>
                            </div>
                        )}
                    </div>
                </div>
                
                {/* PROGRESO CIRCULAR PARA ESTADO PROCESSING */}
                {currentStatus === 'PROCESSING' && (
                    <CircularProgress status={currentStatus} />
                )}
                
                {/* BOTÓN DE DETALLES */}
                <button
                    onClick={() => setShowDetails(!showDetails)}
                    className="text-xs text-gray-500 hover:text-gray-700 ml-2"
                >
                    {showDetails ? '▼' : '▶'}
                </button>
            </div>
            
            {/* DETALLES EXPANDIBLES */}
            {showDetails && facturacionStatus && (
                <div className="mt-3 pt-3 border-t border-gray-200">
                    <div className="text-xs text-gray-600 space-y-1">
                        <div>Estado: <span className="font-mono">{facturacionStatus.status}</span></div>
                        <div>Venta ID: <span className="font-mono">{venta.venta_id}</span></div>
                        {facturacionStatus.created_at && (
                            <div>Iniciado: <span className="font-mono">{facturacionStatus.created_at}</span></div>
                        )}
                        {facturacionStatus.processing_time && (
                            <div>Tiempo procesamiento: <span className="font-mono">{facturacionStatus.processing_time}s</span></div>
                        )}
                        {facturacionStatus.retry_count > 0 && (
                            <div>Reintentos: <span className="font-mono">{facturacionStatus.retry_count}</span></div>
                        )}
                        {isWebWorkerSupported.current && (
                            <div>Web Worker: <span className="font-mono text-green-600">✓ Activo</span></div>
                        )}
                        {performanceMetrics && (
                            <div>Métricas: <span className="font-mono">{performanceMetrics.total_processed || 0} proc.</span></div>
                        )}
                    </div>
                </div>
            )}
            
            {/* ACCIONES RÁPIDAS */}
            {currentStatus === 'ERROR' && (
                <div className="mt-3 pt-3 border-t border-gray-200">
                    <button
                        onClick={() => {
                            setIsPolling(true);
                            setPollingAttempts(0);
                            
                            if (isWebWorkerSupported.current && workerRef.current) {
                                workerRef.current.postMessage({
                                    type: 'START_POLLING',
                                    data: {
                                        ventaId: venta.venta_id,
                                        options: { interval: 2000, maxAttempts: 15 }
                                    }
                                });
                            } else {
                                checkFacturacionStatusTraditional();
                            }
                        }}
                        className="text-xs bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition-colors"
                    >
                        🔄 Reintentar verificación
                    </button>
                </div>
            )}
        </div>
    );
};

export default AFIPStatusIndicator; 