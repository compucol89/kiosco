import CONFIG from '../config/config';

const API_URL = CONFIG.API_URL;
const CAJA_ENDPOINT = '/api/pos_status.php';

/**
 * Servicio unificado para gestión de caja
 * Utiliza la API optimizada con cálculos precisos
 */
const cajaService = {
  /**
   * Obtener el estado actual de la caja con cálculos precisos
   * Incluye reintentos automáticos y manejo robusto de errores
   */
  getEstadoCaja: async (reintentos = 3) => {
    for (let intento = 1; intento <= reintentos; intento++) {
      try {
        // Agregar timeout y cache busting
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos timeout
        
        const response = await fetch(`${API_URL}${CAJA_ENDPOINT}?_t=${Date.now()}`, {
          method: 'GET',
          signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
          // Intentar obtener detalles del error
          let errorMessage = `Error HTTP: ${response.status}`;
          try {
            const errorData = await response.json();
            errorMessage = errorData.error || errorMessage;
          } catch (e) {
            // Si no se puede parsear el error, usar el mensaje genérico
          }
          throw new Error(errorMessage);
        }
        
        const data = await response.json();
        
        // Verificar estructura de respuesta
        if (data.error) {
          throw new Error(data.error);
        }
        
        // El endpoint de emergencia ya devuelve el formato correcto
        if (!data.success) {
          throw new Error(data.error || 'Error desconocido del servidor');
        }
        
        console.log(`✅ [CajaService] Estado obtenido correctamente (intento ${intento})`);
        return data;
        
      } catch (error) {
        console.error(`❌ [CajaService] Error en intento ${intento}/${reintentos}:`, error);
        
        // Si es el último intento, lanzar el error
        if (intento === reintentos) {
          // Agregar contexto adicional al error
          const errorConContexto = new Error(`No se pudo conectar a la base de datos después de ${reintentos} intentos: ${error.message}`);
          errorConContexto.originalError = error;
          errorConContexto.intentos = reintentos;
          throw errorConContexto;
        }
        
        // Esperar antes del siguiente intento (backoff exponencial)
        const tiempoEspera = Math.min(1000 * Math.pow(2, intento - 1), 5000);
        console.log(`⏳ [CajaService] Esperando ${tiempoEspera}ms antes del siguiente intento...`);
        await new Promise(resolve => setTimeout(resolve, tiempoEspera));
      }
    }
  },

  /**
   * Abrir caja con validaciones mejoradas
   */
  abrirCaja: async (montoApertura, usuarioId, justificacionDiferencia = null) => {
    try {
      const requestData = {
        accion: 'abrir',
        monto_apertura: parseFloat(montoApertura),
        usuario_id: parseInt(usuarioId),
        descripcion: 'Apertura de caja'
      };
      
      // Agregar justificación si existe
      if (justificacionDiferencia) {
        requestData.justificacion_diferencia = justificacionDiferencia;
      }
      
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
      });
      
      const responseText = await response.text();
      let responseData;
      
      try {
        responseData = JSON.parse(responseText);
      } catch (e) {
        throw new Error(`Respuesta inválida del servidor: ${responseText}`);
      }
      
      if (!response.ok) {
        throw new Error(responseData.error || responseData.mensaje || 'Error al abrir la caja');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al abrir caja:', error);
      throw error;
    }
  },

  /**
   * Cerrar caja con cálculos automatizados
   */
  cerrarCaja: async (monto, usuarioId, diferencia = 0, justificacion = null, cajaId = null) => {
    try {
      const requestData = {
        accion: 'cerrar',
        monto_cierre: parseFloat(monto),
        usuario_id: parseInt(usuarioId),
        diferencia: parseFloat(diferencia),
        justificacion: justificacion || null
      };
      
      if (cajaId) {
        requestData.caja_id = parseInt(cajaId);
      }
      
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
      });
      
      const responseText = await response.text();
      let responseData;
      
      try {
        responseData = JSON.parse(responseText);
      } catch (e) {
        throw new Error(`Respuesta inválida del servidor: ${responseText}`);
      }
      
      if (!response.ok) {
        throw new Error(responseData.error || responseData.mensaje || 'Error al cerrar la caja');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al cerrar caja:', error);
      throw error;
    }
  },

  /**
   * Registrar movimiento en caja (entrada/salida manual)
   */
  registrarMovimiento: async (datos) => {
    try {
      const requestData = {
        accion: 'movimiento',
        tipo: datos.tipo, // 'entrada' o 'salida'
        monto: parseFloat(datos.monto),
        descripcion: datos.descripcion,
        usuario_id: parseInt(datos.usuario_id),
        metodo_pago: datos.metodo_pago || 'efectivo',
        tipo_transaccion: datos.tipo_transaccion || 'operacion',
        categoria: datos.categoria || 'manual',
        referencia: datos.referencia || null,
        observaciones: datos.observaciones || null
      };
      
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new Error(responseData.error || 'Error al registrar movimiento');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al registrar movimiento:', error);
      throw error;
    }
  },

  /**
   * Registrar venta en caja (llamado desde el punto de venta)
   */
  registrarVenta: async (datosVenta) => {
    try {
      const requestData = {
        accion: 'registrar_venta',
        venta_id: parseInt(datosVenta.venta_id),
        metodo_pago: datosVenta.metodo_pago,
        monto_total: parseFloat(datosVenta.monto_total),
        numero_comprobante: datosVenta.numero_comprobante || null,
        usuario_id: datosVenta.usuario_id || 1
      };
      
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new Error(responseData.error || 'Error al registrar venta en caja');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al registrar venta en caja:', error);
      throw error;
    }
  },

  /**
   * Obtener el último cierre de caja para cambios de turno
   */
  getUltimoCierre: async () => {
    try {
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}?accion=ultimo_cierre`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      
      const responseText = await response.text();
      
      // Intentar parsear como JSON
      let responseData;
      try {
        responseData = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Error al parsear JSON de último cierre:', parseError);
        throw new Error(`Respuesta inválida del servidor: ${responseText}`);
      }
      
      if (!response.ok) {
        throw new Error(responseData.error || `Error HTTP ${response.status}: ${response.statusText}`);
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al obtener último cierre:', error);
      throw error;
    }
  },

  /**
   * Obtener historial de cierres de caja
   */
  getHistorialCierres: async () => {
    try {
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}?accion=historial`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new Error(responseData.error || 'Error al obtener historial de cierres');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al obtener historial de cierres:', error);
      throw error;
    }
  },

  /**
   * Obtener movimientos de caja específicos
   */
  getMovimientos: async (cajaId = null) => {
    try {
      let url = `${API_URL}${CAJA_ENDPOINT}?accion=movimientos`;
      if (cajaId) {
        url += `&caja_id=${cajaId}`;
      }
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new Error(responseData.error || 'Error al obtener movimientos');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al obtener movimientos:', error);
      throw error;
    }
  },

  /**
   * Calcular efectivo físico disponible
   */
  calcularEfectivoFisico: (estadoCaja) => {
    if (!estadoCaja || !estadoCaja.totales) {
      return 0;
    }
    
    return estadoCaja.totales.efectivo_fisico || estadoCaja.totales.efectivo_teorico || 0;
  },

  /**
   * Calcular total digital (tarjetas + transferencias)
   */
  calcularTotalDigital: (estadoCaja) => {
    if (!estadoCaja || !estadoCaja.totales) {
      return 0;
    }
    
    return estadoCaja.totales.total_digital || 0;
  },

  /**
   * Obtener resumen por métodos de pago
   */
  getResumenMetodosPago: (estadoCaja) => {
    if (!estadoCaja || !estadoCaja.resumen_metodos) {
      return [];
    }
    
    return estadoCaja.resumen_metodos;
  },

  /**
   * Validar si hay caja abierta
   */
  hayCajaAbierta: (estadoCaja) => {
    return estadoCaja && estadoCaja.estado === 'abierta';
  },

  /**
   * Formatear resumen de caja para display
   */
  formatearResumen: (estadoCaja) => {
    if (!estadoCaja || !estadoCaja.totales) {
      return null;
    }
    
    const totales = estadoCaja.totales;
    
    return {
      apertura: totales.apertura || 0,
      efectivoFisico: totales.efectivo_fisico || 0,
      totalDigital: totales.total_digital || 0,
      granTotal: totales.gran_total || 0,
      totalVentas: totales.total_ventas || 0,
      numVentas: totales.num_ventas || 0,
      promedioVenta: totales.promedio_venta || 0,
      
      // Desglose por método
      efectivo: totales.ingresos_efectivo || 0,
      tarjeta: totales.ingresos_tarjeta || 0,
      transferencia: totales.ingresos_transferencia || 0,
      
      // Movimientos
      totalEntradas: totales.total_entradas || 0,
      totalSalidas: totales.total_salidas || 0,
      totalRetiros: totales.total_retiros || 0
    };
  },

  /**
   * Obtener estadísticas detalladas del sistema de caja
   */
  getEstadisticasDetalladas: async () => {
    try {
      const response = await fetch(`${API_URL}${CAJA_ENDPOINT}?accion=estadisticas`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new Error(responseData.error || 'Error al obtener estadísticas detalladas');
      }
      
      return responseData;
    } catch (error) {
      console.error('Error al obtener estadísticas detalladas:', error);
      throw error;
    }
  }
};

// Alias para compatibilidad con hooks
cajaService.obtenerEstadoCaja = cajaService.getEstadoCaja;

export default cajaService; 