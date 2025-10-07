import { useState, useEffect, useCallback, useRef } from 'react';
import cajaService from '../services/cajaService';

/**
 * 🔒 HOOK CRÍTICO PARA GESTIÓN DE ESTADO DE CAJA
 * 
 * Garantiza que:
 * - NO SE PUEDEN PROCESAR VENTAS SI LA CAJA ESTÁ CERRADA
 * - Estado sincronizado en tiempo real
 * - Validaciones automáticas antes de cualquier operación
 * - Notificaciones instantáneas de cambios de estado
 */

const useCajaStatus = (options = {}) => {
  const {
    autoRefresh = true,
    refreshInterval = 30000, // 30 segundos
    onStatusChange = null,
    enableNotifications = true
  } = options;

  // Estados principales
  const [cajaStatus, setCajaStatus] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lastUpdate, setLastUpdate] = useState(null);
  const [fallbackMode, setFallbackMode] = useState(false);
  
  // Estados derivados para validaciones críticas
  const [canProcessSales, setCanProcessSales] = useState(false);
  const [cashRegisterOpen, setCashRegisterOpen] = useState(false);
  const [currentCashBalance, setCurrentCashBalance] = useState(0);
  const [lastFetchTime, setLastFetchTime] = useState(0);
  const [circuitBreakerActive, setCircuitBreakerActive] = useState(false);
  
  // Función para cargar estado desde respaldo
  const loadFromBackup = useCallback(() => {
    try {
      const backup = localStorage.getItem('caja_status_backup');
      if (backup) {
        const backupData = JSON.parse(backup);
        const ahora = Date.now();
        const tiempoRespaldo = backupData.timestamp || 0;
        
        // Solo usar respaldo si es reciente (menos de 5 minutos)
        if (ahora - tiempoRespaldo < 300000) {
          console.log('📦 [useCajaStatus] Cargando desde respaldo local');
          setCajaStatus(backupData.estado);
          setCanProcessSales(backupData.canProcessSales || false);
          setCashRegisterOpen(backupData.cashRegisterOpen || false);
          setCurrentCashBalance(backupData.currentCashBalance || 0);
          setFallbackMode(true);
          return true;
        }
      }
    } catch (e) {
      console.warn('No se pudo cargar respaldo de estado de caja');
    }
    return false;
  }, []);
  
  // Referencias para limpieza
  const intervalRef = useRef(null);
  const abortControllerRef = useRef(null);

  // ========================================================================
  // 🔍 FUNCIÓN PRINCIPAL PARA OBTENER ESTADO DE CAJA
  // ========================================================================
  const fetchCajaStatus = useCallback(async (force = false) => {
    // CIRCUIT BREAKER: Si está activo, no hacer más llamadas
    if (circuitBreakerActive && !force) {
      console.log('🚫 [useCajaStatus] Circuit Breaker ACTIVO - Bloqueando llamadas automáticas');
      return cajaStatus;
    }
    
    // Debounce: evitar llamadas muy frecuentes (mínimo 5 segundos entre llamadas)
    const now = Date.now();
    if (!force && (now - lastFetchTime) < 5000) {
      console.log('🔄 [useCajaStatus] Llamada muy frecuente, ignorando...');
      return cajaStatus;
    }
    
    // Cancelar request anterior si existe
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    
    abortControllerRef.current = new AbortController();
    setLastFetchTime(now);
    
    try {
      if (!force && !isLoading) {
        setIsLoading(true);
      }
      
      setError(null);
      
      // Usar alias del servicio principal con manejo robusto de errores
      const estadoCaja = await cajaService.getEstadoCaja(1); // Solo 1 reintento para evitar delays
      
      // Validar estructura de respuesta
      if (!estadoCaja) {
        throw new Error('Respuesta inválida del servidor de caja');
      }
      
      // Determinar estado crítico: ¿Se pueden procesar ventas?
      const cajaAbierta = estadoCaja.estado === 'abierta';
      const tieneCajaActiva = estadoCaja.caja && estadoCaja.caja.id;
      const puedeVender = cajaAbierta && tieneCajaActiva;
      
      // Calcular balance actual
      let balanceActual = 0;
      if (estadoCaja.totales) {
        balanceActual = estadoCaja.totales.efectivo_fisico || 
                       estadoCaja.totales.efectivo_teorico || 
                       estadoCaja.totales.gran_total || 0;
      }
      
      // Estados anteriores para detectar cambios
      const statusAnterior = cajaStatus?.estado;
      const balanceAnterior = currentCashBalance;
      
      // Actualizar estados
      setCajaStatus(estadoCaja);
      setCanProcessSales(puedeVender);
      setCashRegisterOpen(cajaAbierta);
      setCurrentCashBalance(balanceActual);
      setLastUpdate(new Date());
      setIsLoading(false);
      
      // Activar circuit breaker después de primera carga exitosa
      if (!circuitBreakerActive) {
        setCircuitBreakerActive(true);
        console.log('🛡️ [useCajaStatus] Circuit Breaker ACTIVADO - Modo estable');
      }
      
      // ========================================================================
      // 🚨 NOTIFICACIONES AUTOMÁTICAS DE CAMBIOS CRÍTICOS
      // ========================================================================
      if (enableNotifications && statusAnterior && statusAnterior !== estadoCaja.estado) {
        const mensaje = estadoCaja.estado === 'abierta' 
          ? '✅ Caja abierta - Ventas habilitadas'
          : '🔒 Caja cerrada - Ventas bloqueadas';
          
        // Disparar notificación personalizada si existe
        if (onStatusChange) {
          onStatusChange({
            previous: statusAnterior,
            current: estadoCaja.estado,
            canProcessSales: puedeVender,
            balance: balanceActual,
            message: mensaje
          });
        }
        
        // Log para auditoría
        console.log(`🏦 CAMBIO DE ESTADO DE CAJA: ${statusAnterior} → ${estadoCaja.estado}`);
      }
      
      return estadoCaja;
      
    } catch (error) {
      // No setear error si fue cancelado intencionalmente
      if (error.name !== 'AbortError') {
        console.error('❌ Error al obtener estado de caja:', error);
        
        // Manejo inteligente de errores de conectividad
        const esErrorConectividad = error.message && (
          error.message.includes('conectar') || 
          error.message.includes('conexión') ||
          error.message.includes('fetch') ||
          error.message.includes('network')
        );
        
        if (esErrorConectividad && cajaStatus) {
          // Si tenemos un estado previo y es solo un error de conectividad, mantener el estado
          console.warn('⚠️ [useCajaStatus] Error de conectividad - activando modo respaldo');
          setError('Conexión intermitente - usando último estado conocido');
          setFallbackMode(true);
          
          // Guardar estado en localStorage como respaldo
          try {
            localStorage.setItem('caja_status_backup', JSON.stringify({
              estado: cajaStatus,
              timestamp: Date.now(),
              canProcessSales,
              cashRegisterOpen,
              currentCashBalance
            }));
          } catch (e) {
            console.warn('No se pudo guardar respaldo de estado de caja');
          }
          
          setIsLoading(false);
          // En modo respaldo, retornar el último estado conocido
          return cajaStatus;
        } else {
          // Solo para errores críticos, establecer el error
          setError(error.message || 'Error al conectar con el sistema de caja');
          
          // En caso de error crítico, bloquear ventas por seguridad
          setCanProcessSales(false);
          setCashRegisterOpen(false);
          
          setIsLoading(false);
          throw error;
        }
      }
      
      setIsLoading(false);
      throw error;
    }
  }, []); // DEPENDENCIAS VACÍAS - evitar re-creaciones constantes

  // ========================================================================
  // 🔄 CONFIGURAR AUTO-REFRESH
  // ========================================================================
  useEffect(() => {
    console.log('🚀 [useCajaStatus] Iniciando sistema...');
    
    // Intentar cargar desde respaldo primero
    const cargadoDesdeRespaldo = loadFromBackup();
    
    if (cargadoDesdeRespaldo) {
      setIsLoading(false);
      console.log('✅ [useCajaStatus] Sistema iniciado desde respaldo local');
    }
    
    // Siempre intentar actualizar con datos frescos (pero sin bloquear UI)
    fetchCajaStatus(true).catch(error => {
      console.warn('No se pudo obtener estado fresco, usando respaldo si está disponible');
    });
    
    // Configurar refresh automático si está habilitado
    if (autoRefresh && refreshInterval > 0) {
      intervalRef.current = setInterval(() => {
        fetchCajaStatus(false).catch(error => {
          console.warn('Error en refresh automático:', error.message);
        });
      }, refreshInterval);
    }
    
    // Limpieza al desmontar
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, []); // VACÍO - solo ejecutar una vez al montar

  // ========================================================================
  // 🛡️ VALIDACIONES CRÍTICAS
  // ========================================================================
  
  /**
   * Validación crítica antes de procesar venta
   * DEBE ser llamada antes de cualquier operación de venta
   */
  const validateSaleOperation = useCallback(async () => {
    try {
      // Forzar refresh del estado antes de validar
      await fetchCajaStatus(true);
      
      if (!canProcessSales) {
        const razon = !cashRegisterOpen 
          ? 'La caja está cerrada'
          : 'No hay una sesión de caja activa';
          
        throw new Error(`❌ NO SE PUEDE PROCESAR LA VENTA: ${razon}. Por favor, abra la caja primero.`);
      }
      
      // Validaciones adicionales
      if (!cajaStatus?.caja?.id) {
        throw new Error('❌ NO HAY SESIÓN DE CAJA ACTIVA. Contacte al administrador.');
      }
      
      return {
        valid: true,
        cajaId: cajaStatus.caja.id,
        balance: currentCashBalance,
        message: '✅ Validación exitosa - Puede procesar la venta'
      };
      
    } catch (error) {
      return {
        valid: false,
        error: error.message,
        message: error.message
      };
    }
  }, [canProcessSales, cashRegisterOpen, cajaStatus, currentCashBalance, fetchCajaStatus]);

  /**
   * Forzar actualización del estado
   */
  const refreshStatus = useCallback(() => {
    return fetchCajaStatus(true);
  }, [fetchCajaStatus]);

  /**
   * Obtener resumen formateado para UI
   */
  const getStatusSummary = useCallback(() => {
    if (!cajaStatus) return null;
    
    return {
      isOpen: cashRegisterOpen,
      canSell: canProcessSales,
      balance: currentCashBalance,
      cajaId: cajaStatus.caja?.id || null,
      lastUpdate: lastUpdate,
      totals: cajaStatus.totales || {},
      resumeMetodos: cajaStatus.resumen_metodos || [],
      status: cajaStatus.estado || 'unknown'
    };
  }, [cajaStatus, cashRegisterOpen, canProcessSales, currentCashBalance, lastUpdate]);

  // ========================================================================
  // 🎯 RETURN DEL HOOK
  // ========================================================================
  return {
    // Estados principales
    cajaStatus,
    isLoading,
    error,
    lastUpdate,
    
    // Estados críticos para validaciones
    canProcessSales,
    cashRegisterOpen,
    currentCashBalance,
    
    // Funciones principales
    validateSaleOperation,
    refreshStatus: () => {
      setCircuitBreakerActive(false); // Resetear circuit breaker
      return fetchCajaStatus(true); // Forzar actualización
    },
    getStatusSummary,
    
    // Funciones del servicio expuestas
    abrirCaja: cajaService.abrirCaja,
    cerrarCaja: cajaService.cerrarCaja,
    registrarMovimiento: cajaService.registrarMovimiento,
    
    // Información adicional
    isConnected: !error && !isLoading,
    needsRefresh: lastUpdate && (Date.now() - lastUpdate.getTime() > refreshInterval * 2)
  };
};

export default useCajaStatus;

