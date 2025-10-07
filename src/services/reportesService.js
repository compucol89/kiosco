import CONFIG from '../config/config';

class ReportesService {
  
  constructor() {
    this.baseURL = CONFIG.API_URL;
  }

  // Obtener datos contables usando el sistema PRECISO con cálculos exactos
  async obtenerDatosContables(parametros = {}) {
    try {
      // Construir URL con parámetros de período
      const queryParams = new URLSearchParams();
      
      if (parametros.periodo) {
        queryParams.append('periodo', parametros.periodo);
      }
      
      if (parametros.fechaInicio) {
        queryParams.append('fecha_inicio', parametros.fechaInicio);
      }
      
      if (parametros.fechaFin) {
        queryParams.append('fecha_fin', parametros.fechaFin);
      }
      
      // Add cache busting parameter
      queryParams.append('_t', Date.now().toString());
      
      // *** USAR SISTEMA DE REPORTES FINANCIEROS PRECISOS ***
      const url = `${this.baseURL}/api/reportes_financieros_precisos.php?${queryParams.toString()}`;
      
      const response = await fetch(url, {
        cache: 'no-cache'
      });
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.success) {
        // *** MAPEAR DATOS PRECISOS AL FORMATO ESPERADO POR EL FRONTEND ***
        return this.mapearDatosPrecisos(data);
      } else {
        throw new Error(data.message || 'Error al obtener datos contables');
      }
    } catch (error) {
      console.error('Error en obtenerDatosContables:', error);
      throw error;
    }
  }

  // Mapear datos del sistema preciso al formato esperado por el frontend
  mapearDatosPrecisos(data) {
    const { 
      resumen_general, 
      gastos_fijos, 
      metodos_pago, 
      productos_analisis, 
      validaciones, 
      periodo 
    } = data;
    
    // Procesar datos de ventas para el dashboard
    const ventasData = {
      ingresos_totales: resumen_general.total_ingresos_netos || 0,
      cantidad_ventas: resumen_general.total_ventas || 0,
      ticket_promedio: resumen_general.ticket_promedio || 0,
      crecimiento: 0 // TODO: Calcular si es necesario
    };

    // Procesar trazabilidad financiera con cálculos precisos
    const trazabilidadFinanciera = {
      ingresos_totales: resumen_general.total_ingresos_netos || 0,
      desglose_ingresos: {
        ventas_efectivo: metodos_pago.efectivo || 0,
        ventas_tarjeta: metodos_pago.tarjeta || 0,
        ventas_transferencia: metodos_pago.transferencia || 0,
        ventas_mercadopago: metodos_pago.mercadopago || 0,
        ingresos_extra: metodos_pago.otros || 0
      },
      egresos_totales: gastos_fijos.periodo || 0,
      flujo_neto: (resumen_general.total_ingresos_netos || 0) - (gastos_fijos.periodo || 0)
    };

    // Procesar utilidades por productos con datos precisos
    const utilidadesProductos = {
      por_producto: productos_analisis.map(p => ({
        nombre: p.nombre,
        categoria: p.categoria,
        cantidad_vendida: p.cantidad_vendida,
        ingresos: p.ingresos_totales,
        costos: p.costos_totales,
        utilidad: p.utilidad_total,
        margen_porcentaje: p.margen_promedio
      })),
      por_rubro: this.agruparPorRubro(productos_analisis),
      resumen_general: {
        total_utilidad: resumen_general.total_utilidad_bruta || 0,
        utilidad_neta: resumen_general.utilidad_neta || 0,
        margen_promedio: resumen_general.margen_bruto_porcentaje || 0,
        margen_neto: resumen_general.margen_neto_porcentaje || 0,
        roi_bruto: resumen_general.roi_bruto_porcentaje || 0,
        roi_neto: resumen_general.roi_neto_porcentaje || 0,
        estado_negocio: resumen_general.estado_negocio || 'DESCONOCIDO'
      }
    };

    // Procesar arqueo de caja (simplificado por ahora)
    const arqueo = {
      esperado_efectivo: metodos_pago.efectivo || 0,
      real_efectivo: metodos_pago.efectivo || 0,
      diferencia: 0
    };

    // Datos de gastos fijos precisos
    const gastosFijosData = {
      distribucion_actual: {
        gastos_fijos_mensuales: gastos_fijos.mensuales || 0,
        gasto_fijo_diario: gastos_fijos.diarios || 0,
        formula: gastos_fijos.formula || '',
        mes: new Date().toLocaleDateString('es-AR', { month: 'long', year: 'numeric' }),
        dias_mes: gastos_fijos.dias_mes || 30
      },
      resumen_periodo: {
        gastos_fijos_totales: gastos_fijos.periodo || 0,
        dias_calculados: periodo.dias_periodo || 1
      }
    };

    return {
      success: true,
      ventas: ventasData,
      trazabilidadFinanciera: trazabilidadFinanciera,
      utilidadesProductos: utilidadesProductos,
      arqueo: arqueo,
      gastosFijosSimplificado: gastosFijosData,
      periodo: periodo || {},
      alertas: this.generarAlertas(resumen_general, validaciones),
      comparativas: {},
      egresos: [],
      ingresos: [],
      // *** DATOS ADICIONALES DEL SISTEMA PRECISO ***
      resumenGeneral: resumen_general,
      validaciones: validaciones,
      gastosFijos: gastos_fijos,
      metodosPago: metodos_pago,
      ventasDetalladas: (data.ventas_detalladas || []).map(venta => ({
        id: venta.venta_id,
        fecha: venta.fecha,
        cliente_nombre: venta.cliente,
        metodo_pago: venta.metodo_pago,
        monto_total: venta.resumen?.monto_total_registrado || venta.resumen?.total_ingresos_netos || 0,
        subtotal: venta.resumen?.total_ingresos_brutos || 0,
        descuento: venta.resumen?.descuento_aplicado || 0,
        total: venta.resumen?.monto_total_registrado || venta.resumen?.total_ingresos_netos || 0,
        numero_comprobante: `#V${String(venta.venta_id).padStart(5, '0')}`,
        estado: 'completado',
        // 💰 DATOS DE CAMBIO Y EFECTIVO
        cambio_entregado: parseFloat(venta.cambio_entregado || 0),
        efectivo_recibido: parseFloat(venta.efectivo_recibido || venta.resumen?.monto_total_registrado || 0),
        // Campos adicionales para compatibilidad con TicketProfesional
        change: parseFloat(venta.cambio_entregado || 0),
        changeDue: parseFloat(venta.cambio_entregado || 0),
        cashReceived: parseFloat(venta.efectivo_recibido || venta.resumen?.monto_total_registrado || 0),
        amountPaid: parseFloat(venta.efectivo_recibido || venta.resumen?.monto_total_registrado || 0),
        // 🧾 DATOS FISCALES AFIP
        datos_fiscales: venta.cae ? {
          cae: venta.cae,
          numero_comprobante_fiscal: venta.comprobante_fiscal || `#${String(venta.venta_id).padStart(8, '0')}-0001`,
          codigo_barras: null, // No disponible por ahora
          qr_data: `https://www.afip.gob.ar/fe/qr/?p=${btoa(JSON.stringify({
            ver: 1,
            fecha: venta.fecha?.split(' ')[0] || new Date().toISOString().split('T')[0],
            cuit: 30718850874,
            ptoVta: 1,
            tipoCmp: 81,
            nroCmp: venta.venta_id,
            importe: venta.resumen?.monto_total_registrado || 0,
            moneda: 'PES',
            ctz: 1,
            codAut: venta.cae
          }))}`,
          fecha_vencimiento_cae: null,
          estado_fiscal: 'AUTORIZADO', // Cambiar a AUTORIZADO para que el ticket lo reconozca
          tipo_comprobante: 'TICKET_FISCAL',
          punto_venta: '0001'
        } : null,
        // Mapear carrito de productos desde la estructura del backend
        cart: venta.productos?.map(producto => ({
          id: producto.producto_id,
          nombre: producto.nombre,
          quantity: producto.cantidad,
          price: producto.precio_venta_unitario,
          cantidad: producto.cantidad
        })) || [],
        // Detalles JSON para compatibilidad
        detalles_json: JSON.stringify({
          cart: venta.productos?.map(producto => ({
            id: producto.producto_id,
            nombre: producto.nombre,
            quantity: producto.cantidad,
            price: producto.precio_venta_unitario,
            cantidad: producto.cantidad
          })) || []
        })
      }))
    };
  }

  // Agrupar productos por rubro
  agruparPorRubro(productos) {
    const rubros = {};
    
    productos.forEach(producto => {
      const categoria = producto.categoria || 'Sin categoría';
      
      if (!rubros[categoria]) {
        rubros[categoria] = {
          nombre: categoria,
          productos_count: 0,
          ingresos: 0,
          costos: 0,
          utilidad: 0,
          cantidad_vendida: 0
        };
      }
      
      rubros[categoria].productos_count++;
      rubros[categoria].ingresos += producto.ingresos_totales;
      rubros[categoria].costos += producto.costos_totales;
      rubros[categoria].utilidad += producto.utilidad_total;
      rubros[categoria].cantidad_vendida += producto.cantidad_vendida;
    });
    
    // Calcular margen por rubro
    Object.values(rubros).forEach(rubro => {
      rubro.margen_porcentaje = rubro.ingresos > 0 ? 
        (rubro.utilidad / rubro.ingresos) * 100 : 0;
    });
    
    return Object.values(rubros);
  }

  // Generar alertas basadas en los datos
  generarAlertas(resumen, validaciones) {
    const alertas = [];
    
    // Alerta sobre estado del negocio
    if (resumen.estado_negocio === 'EN PÉRDIDAS') {
      alertas.push({
        tipo: 'peligro',
        titulo: 'Negocio en Pérdidas',
        mensaje: `La utilidad neta es negativa: ${this.formatCurrency(resumen.utilidad_neta)}. Revisar gastos fijos y márgenes.`,
        prioridad: 'alta'
      });
    } else if (resumen.estado_negocio === 'PUNTO DE EQUILIBRIO') {
      alertas.push({
        tipo: 'advertencia',
        titulo: 'Punto de Equilibrio',
        mensaje: 'El negocio está en punto de equilibrio. Considerar optimizar márgenes.',
        prioridad: 'media'
      });
    }
    
    // Alerta sobre coherencia de datos
    if (!validaciones.coherencia_general) {
      alertas.push({
        tipo: 'advertencia',
        titulo: 'Inconsistencias Detectadas',
        mensaje: `Se encontraron ${validaciones.diferencias_detectadas} diferencias en los cálculos. Revisar datos.`,
        prioridad: 'media'
      });
    }
    
    // Alerta sobre márgenes bajos
    if (resumen.margen_neto_porcentaje < 10 && resumen.margen_neto_porcentaje > 0) {
      alertas.push({
        tipo: 'advertencia',
        titulo: 'Margen Neto Bajo',
        mensaje: `El margen neto es solo del ${resumen.margen_neto_porcentaje.toFixed(1)}%. Considerar revisar precios.`,
        prioridad: 'media'
      });
    }
    
    return alertas;
  }

  // Exportar reporte
  async exportarReporte(formato, datos) {
    try {
      // Implementar lógica de exportación
      // Exportando reporte en formato especificado
      
      // Por ahora, crear un CSV simple
      if (formato === 'csv' || formato === 'excel') {
        this.exportarCSV(datos);
      } else if (formato === 'pdf') {
        this.exportarPDF(datos);
      }
    } catch (error) {
      console.error('Error exportando reporte:', error);
      throw error;
    }
  }

  exportarCSV(datos) {
    const csv = ['Fecha,Concepto,Tipo,Monto'];
    
    // Agregar productos si están disponibles
    if (datos.utilidadesProductos && datos.utilidadesProductos.por_producto) {
      datos.utilidadesProductos.por_producto.forEach(producto => {
        csv.push(`${new Date().toISOString().split('T')[0]},${producto.nombre},Producto,${producto.utilidad}`);
      });
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reporte_financiero_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  }

  exportarPDF(datos) {
    // Por ahora, abrir ventana de impresión
    window.print();
  }

  // Formatear moneda
  formatCurrency(amount) {
    if (typeof amount !== 'number') {
      amount = parseFloat(amount) || 0;
    }
    
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(amount);
  }

  // Formatear porcentaje
  formatPercentage(value) {
    return `${value >= 0 ? '+' : ''}${value.toFixed(1)}%`;
  }

  // Obtener icono de variación
  getVariacionIcon(valor) {
    if (valor > 0) return '↗️';
    if (valor < 0) return '↘️';
    return '➡️';
  }

  // Métodos para compatibilidad con el sistema anterior
  async crearEgreso(datosGasto) {
    try {
      const response = await fetch(`${this.baseURL}/api/egresos.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'create',
          ...datosGasto
        })
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Error al crear egreso');
      }

      return data;
    } catch (error) {
      console.error('Error creando egreso:', error);
      throw error;
    }
  }

  async actualizarEgreso(id, datosGasto) {
    try {
      const response = await fetch(`${this.baseURL}/api/egresos.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update',
          id: id,
          ...datosGasto
        })
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Error al actualizar egreso');
      }

      return data;
    } catch (error) {
      console.error('Error actualizando egreso:', error);
      throw error;
    }
  }

  async eliminarEgreso(id) {
    try {
      const response = await fetch(`${this.baseURL}/api/egresos.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete',
          id: id
        })
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Error al eliminar egreso');
      }

      return data;
    } catch (error) {
      console.error('Error eliminando egreso:', error);
      throw error;
    }
  }

  async obtenerEgresos(filtros = {}) {
    try {
      const queryParams = new URLSearchParams(filtros);
      const response = await fetch(`${this.baseURL}/api/egresos.php?${queryParams}`);
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      return data.success ? data.egresos : [];
    } catch (error) {
      console.error('Error obteniendo egresos:', error);
      return [];
    }
  }
}

const reportesService = new ReportesService();
export default reportesService; 