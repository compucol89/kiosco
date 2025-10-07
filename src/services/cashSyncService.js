// import CONFIG from '../config/config'; // Comentado temporalmente - no utilizado
import cajaService from './cajaService';

/**
 * 🔄 SERVICIO DE SINCRONIZACIÓN EN TIEMPO REAL PARA CAJA
 * 
 * Garantiza que:
 * - Todas las ventas se registren automáticamente en caja
 * - Estado de caja esté siempre actualizado
 * - Movimientos de efectivo se procesen correctamente
 * - Auditoría completa de transacciones
 */

class CashSyncService {
  constructor() {
    this.syncQueue = [];
    this.isProcessing = false;
    this.listeners = [];
    this.retryAttempts = 3;
    this.retryDelay = 1000; // 1 segundo
  }

  // ========================================================================
  // 🔄 SINCRONIZACIÓN AUTOMÁTICA DE VENTAS
  // ========================================================================

  /**
   * Registrar venta automáticamente en el sistema de caja
   */
  async syncSaleToCache(saleData) {
    try {
      console.log('🔄 Sincronizando venta con sistema de caja...', saleData);

      // Validar datos de venta requeridos
      this.validateSaleData(saleData);

      // Preparar datos para registro en caja
      const cajaData = {
        venta_id: saleData.venta_id,
        metodo_pago: this.normalizarMetodoPago(saleData.paymentMethod || saleData.metodo_pago),
        monto_total: parseFloat(saleData.total || saleData.monto_total),
        numero_comprobante: saleData.numero_comprobante || null,
        usuario_id: saleData.usuario_id || this.getCurrentUserId()
      };

      // Registrar en sistema de caja
      const result = await cajaService.registrarVenta(cajaData);

      console.log('✅ Venta sincronizada exitosamente en caja:', result);

      // Notificar a listeners
      this.notifyListeners('sale_synced', {
        saleData: saleData,
        cajaResult: result,
        timestamp: new Date()
      });

      return result;

    } catch (error) {
      console.error('❌ Error sincronizando venta con caja:', error);
      
      // Agregar a cola de reintentos
      this.addToRetryQueue('sale_sync', saleData, error);
      
      throw error;
    }
  }

  /**
   * Registrar movimiento manual de caja
   */
  async syncCashMovement(movementData) {
    try {
      console.log('💰 Registrando movimiento de caja...', movementData);

      const result = await cajaService.registrarMovimiento(movementData);

      console.log('✅ Movimiento de caja registrado:', result);

      // Notificar a listeners
      this.notifyListeners('movement_synced', {
        movementData: movementData,
        cajaResult: result,
        timestamp: new Date()
      });

      return result;

    } catch (error) {
      console.error('❌ Error registrando movimiento de caja:', error);
      this.addToRetryQueue('movement_sync', movementData, error);
      throw error;
    }
  }

  // ========================================================================
  // 🔄 SISTEMA DE REINTENTOS Y COLA
  // ========================================================================

  addToRetryQueue(type, data, error) {
    const queueItem = {
      id: Date.now() + Math.random(),
      type: type,
      data: data,
      error: error,
      attempts: 0,
      maxAttempts: this.retryAttempts,
      timestamp: new Date()
    };

    this.syncQueue.push(queueItem);
    
    // Procesar cola en background
    setTimeout(() => this.processRetryQueue(), this.retryDelay);
  }

  async processRetryQueue() {
    if (this.isProcessing || this.syncQueue.length === 0) {
      return;
    }

    this.isProcessing = true;

    const item = this.syncQueue.shift();
    
    if (!item) {
      this.isProcessing = false;
      return;
    }

    try {
      if (item.attempts >= item.maxAttempts) {
        console.error('❌ Máximo de reintentos alcanzado para:', item);
        this.notifyListeners('sync_failed', item);
        return;
      }

      item.attempts++;
      console.log(`🔄 Reintentando sincronización (${item.attempts}/${item.maxAttempts}):`, item.type);

      // Procesar según tipo
      switch (item.type) {
        case 'sale_sync':
          await this.syncSaleToCache(item.data);
          break;
        case 'movement_sync':
          await this.syncCashMovement(item.data);
          break;
        default:
          console.warn('Tipo de sincronización desconocido:', item.type);
      }

      console.log('✅ Reintento exitoso:', item.type);

    } catch (error) {
      console.warn('⚠️  Reintento fallido:', error.message);
      // Volver a agregar a la cola si no se han agotado los intentos
      if (item.attempts < item.maxAttempts) {
        this.syncQueue.unshift(item);
      }
    } finally {
      this.isProcessing = false;
      
      // Continuar procesando si hay más elementos
      if (this.syncQueue.length > 0) {
        setTimeout(() => this.processRetryQueue(), this.retryDelay);
      }
    }
  }

  // ========================================================================
  // 🔧 FUNCIONES AUXILIARES
  // ========================================================================

  validateSaleData(saleData) {
    const required = ['venta_id', 'total'];
    for (const field of required) {
      if (!saleData[field]) {
        throw new Error(`Campo requerido faltante para sincronización: ${field}`);
      }
    }

    if (isNaN(parseFloat(saleData.total))) {
      throw new Error('El total de la venta debe ser un número válido');
    }
  }

  normalizarMetodoPago(metodo) {
    const normalizaciones = {
      'cash': 'efectivo',
      'credit_card': 'tarjeta',
      'debit_card': 'tarjeta',
      'transfer': 'transferencia',
      'mercadopago': 'mercadopago',
      'qr_code': 'qr'
    };

    return normalizaciones[metodo] || metodo || 'efectivo';
  }

  getCurrentUserId() {
    try {
      const userData = localStorage.getItem('currentUser');
      if (userData) {
        const user = JSON.parse(userData);
        return user.id || user.user_id || 1;
      }
    } catch (error) {
      console.warn('No se pudo obtener ID de usuario actual:', error);
    }
    return 1; // Usuario por defecto
  }

  // ========================================================================
  // 📡 SISTEMA DE LISTENERS/EVENTOS
  // ========================================================================

  addListener(callback) {
    this.listeners.push(callback);
    return () => {
      this.listeners = this.listeners.filter(listener => listener !== callback);
    };
  }

  notifyListeners(event, data) {
    this.listeners.forEach(listener => {
      try {
        listener(event, data);
      } catch (error) {
        console.error('Error en listener de sincronización:', error);
      }
    });
  }

  // ========================================================================
  // 🔍 ESTADO Y DIAGNÓSTICOS
  // ========================================================================

  getQueueStatus() {
    return {
      queueLength: this.syncQueue.length,
      isProcessing: this.isProcessing,
      pendingItems: this.syncQueue.map(item => ({
        type: item.type,
        attempts: item.attempts,
        maxAttempts: item.maxAttempts,
        timestamp: item.timestamp
      }))
    };
  }

  clearQueue() {
    this.syncQueue = [];
    this.isProcessing = false;
    console.log('🧹 Cola de sincronización limpiada');
  }

  // ========================================================================
  // 📊 SINCRONIZACIÓN DE ESTADO COMPLETO
  // ========================================================================

  /**
   * Sincronizar estado completo de caja con servidor
   */
  async syncFullCashState() {
    try {
      console.log('🔄 Sincronizando estado completo de caja...');
      
      const estadoCaja = await cajaService.getEstadoCaja();
      
      this.notifyListeners('full_sync_completed', {
        estadoCaja: estadoCaja,
        timestamp: new Date()
      });

      return estadoCaja;

    } catch (error) {
      console.error('❌ Error en sincronización completa:', error);
      this.notifyListeners('full_sync_failed', { error: error.message });
      throw error;
    }
  }

  /**
   * Obtener resumen de rendimiento de sincronización
   */
  getPerformanceMetrics() {
    const now = Date.now();
    const recentItems = this.syncQueue.filter(item => 
      now - item.timestamp.getTime() < 300000 // Últimos 5 minutos
    );

    return {
      totalPendingItems: this.syncQueue.length,
      recentFailures: recentItems.length,
      isHealthy: this.syncQueue.length < 10 && !this.isProcessing,
      avgRetryAttempts: recentItems.length > 0 
        ? recentItems.reduce((sum, item) => sum + item.attempts, 0) / recentItems.length 
        : 0
    };
  }
}

// Instancia singleton
const cashSyncService = new CashSyncService();

export default cashSyncService;
