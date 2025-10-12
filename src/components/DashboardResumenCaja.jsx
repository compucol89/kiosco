/**
 * src/components/DashboardResumenCaja.jsx
 * Dashboard de resumen de caja para la página principal
 * Muestra métricas clave, estado actual y alertas importantes
 * RELEVANT FILES: src/App.jsx, src/components/DashboardVentasCompleto.jsx, api/pos_status.php
 */

import React, { useState, useEffect } from 'react';
import { 
  DollarSign, 
  TrendingUp, 
  TrendingDown, 
  AlertTriangle, 
  CheckCircle, 
  Clock,
  Calendar,
  User,
  Eye,
  RefreshCw,
  CreditCard,
  Smartphone,
  QrCode,
  BarChart3,
  Target
} from 'lucide-react';
import CONFIG from '../config/config';

const DashboardResumenCaja = () => {
  const [datosResumen, setDatosResumen] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [metaDiaria, setMetaDiaria] = useState(() => {
    // Cargar meta desde localStorage o usar default
    return parseFloat(localStorage.getItem('metaDiaria') || '100000');
  });
  const [editandoMeta, setEditandoMeta] = useState(false);

  // 📊 Obtener datos de resumen
  const cargarDatosResumen = async () => {
    try {
      setLoading(true);
      setError(null);

      const hoy = new Date();
      const ayer = new Date(hoy);
      ayer.setDate(hoy.getDate() - 1);
      const fechaAyer = ayer.toISOString().split('T')[0];

      // Obtener todos los datos necesarios - USANDO API MODERNA PRECISA
      const [estadoCaja, historialReciente, reporteHoy, reporteAyer, productosTop] = await Promise.all([
        fetch(`${CONFIG.API_URL}/api/pos_status.php?_t=${Date.now()}`).then(r => r.json()),
        fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=historial_completo&usuario_id=1&limite=10&_t=${Date.now()}`).then(r => r.json()),
        fetch(`${CONFIG.API_URL}/api/reportes_financieros_precisos.php?periodo=hoy&_t=${Date.now()}`).then(r => r.json()),
        fetch(`${CONFIG.API_URL}/api/reportes_financieros_precisos.php?periodo=ayer&_t=${Date.now()}`).then(r => r.json()).catch(() => ({ success: false, ventas_detalladas: [] })),
        fetch(`${CONFIG.API_URL}/api/productos_pos_optimizado.php?_t=${Date.now()}`).then(r => r.json()).catch(() => ({ success: false, productos: [] }))
      ]);

      if (estadoCaja.success && historialReciente.success && reporteHoy.success) {
        // Convertir datos del reporte preciso al formato esperado por el Dashboard
        const ventasHoy = (reporteHoy.ventas_detalladas || []).map(venta => ({
          ...venta,
          // Mapear campos del formato nuevo al formato esperado
          monto_total: venta.resumen?.monto_total_registrado || venta.monto_total || 0,
          fecha: venta.fecha || venta.fecha_hora,
          metodo_pago: venta.metodo_pago,
          detalles_json: venta.detalles_json || JSON.stringify({ cart: venta.productos || [] }),
          productos: venta.productos || [],
          cart: venta.productos || []
        }));
        
        const ventasAyer = reporteAyer.success ? (reporteAyer.ventas_detalladas || []).map(venta => ({
          ...venta,
          monto_total: venta.resumen?.monto_total_registrado || venta.monto_total || 0,
          fecha: venta.fecha || venta.fecha_hora
        })) : [];
        
        console.log('📊 Ventas procesadas:', {
          cantidadHoy: ventasHoy.length,
          primeraVenta: ventasHoy[0],
          totalVentas: ventasHoy.reduce((sum, v) => sum + parseFloat(v.monto_total || 0), 0)
        });
        
        setDatosResumen({
          estadoActual: estadoCaja,
          historial: historialReciente.historial || [],
          estadisticas: historialReciente.estadisticas || {},
          ventasDelDia: ventasHoy,
          ventasAyer: ventasAyer,
          productosDisponibles: productosTop.success ? productosTop.productos || [] : []
        });
      } else {
        setError('Error al cargar datos del resumen');
      }
    } catch (error) {
      console.error('Error cargando resumen:', error);
      setError('Error de conexión');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    cargarDatosResumen();
    
    // Actualizar cada 60 segundos (reducido para mejor performance)
    const interval = setInterval(cargarDatosResumen, 60000);
    
    return () => clearInterval(interval);
  }, []);

  // 🎨 Tarjeta de métrica
  const TarjetaMetrica = ({ titulo, valor, icono: IconComponent, color, subtitulo, tendencia }) => (
    <div className={`bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow`}>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{titulo}</p>
          <p className={`text-2xl font-bold mt-1 ${color}`}>{valor}</p>
          {subtitulo && (
            <p className="text-sm text-gray-500 mt-1">{subtitulo}</p>
          )}
        </div>
        <div className={`p-3 rounded-lg ${color.replace('text-', 'bg-').replace('-600', '-100')}`}>
          <IconComponent className={`w-6 h-6 ${color}`} />
        </div>
      </div>
      {tendencia && (
        <div className="mt-4 flex items-center">
          {tendencia.tipo === 'positivo' ? (
            <TrendingUp className="w-4 h-4 text-green-500 mr-2" />
          ) : tendencia.tipo === 'negativo' ? (
            <TrendingDown className="w-4 h-4 text-red-500 mr-2" />
          ) : (
            <CheckCircle className="w-4 h-4 text-gray-500 mr-2" />
          )}
          <span className="text-sm text-gray-600">{tendencia.texto}</span>
        </div>
      )}
    </div>
  );

  // 🎯 Calcular métricas del día
  const calcularMetricasDelDia = () => {
    if (!datosResumen) return null;

    const { estadoActual, historial, estadisticas, ventasDelDia, ventasAyer, productosDisponibles } = datosResumen;
    const hoy = new Date().toDateString();
    
    // Eventos de hoy
    const eventosHoy = historial.filter(evento => 
      new Date(evento.fecha_hora).toDateString() === hoy
    );

    const aperturaHoy = eventosHoy.find(e => e.tipo_evento === 'apertura');
    const cierresHoy = eventosHoy.filter(e => e.tipo_evento === 'cierre');

    // 💰 Calcular total de ventas del día (todas las ventas, no solo del turno actual)
    const totalVentasCompleto = ventasDelDia.reduce((acc, venta) => {
      return acc + parseFloat(venta.monto_total || 0);
    }, 0);

    // 📊 Calcular ventas por método de pago
    const ventasPorMetodo = ventasDelDia.reduce((acc, venta) => {
      const monto = parseFloat(venta.monto_total || 0);
      switch (venta.metodo_pago) {
        case 'efectivo':
          acc.efectivo += monto;
          break;
        case 'tarjeta':
          acc.tarjeta += monto;
          break;
        case 'transferencia':
          acc.transferencia += monto;
          break;
        case 'qr':
          acc.qr += monto;
          break;
      }
      return acc;
    }, { efectivo: 0, tarjeta: 0, transferencia: 0, qr: 0 });

    // ⏰ Calcular métricas de tiempo
    const ahora = new Date();
    const ultimaVenta = ventasDelDia.length > 0 ? new Date(ventasDelDia[0].fecha || ventasDelDia[0].fecha_hora) : null;
    const minutosDesdeUltimaVenta = ultimaVenta ? Math.floor((ahora - ultimaVenta) / (1000 * 60)) : null;
    
    // 📈 Calcular ventas por hora
    const ventasPorHora = ventasDelDia.length > 0 ? (ventasDelDia.length / 
      ((ahora - new Date(ventasDelDia[ventasDelDia.length - 1].fecha)) / (1000 * 60 * 60))) : 0;

    // 💼 Calcular turno actual
    const turnoActual = eventosHoy.find(e => e.tipo_evento === 'apertura' && !eventosHoy.find(c => 
      c.tipo_evento === 'cierre' && new Date(c.fecha_hora) > new Date(e.fecha_hora)));
    const tiempoTurno = turnoActual ? Math.floor((ahora - new Date(turnoActual.fecha_hora)) / (1000 * 60)) : 0;

    // 📊 Calcular comparativas con ayer
    const totalVentasAyer = ventasAyer.reduce((acc, venta) => acc + parseFloat(venta.monto_total || 0), 0);
    const cambioVsAyer = totalVentasCompleto - totalVentasAyer;
    const porcentajeCambio = totalVentasAyer > 0 ? ((cambioVsAyer / totalVentasAyer) * 100) : 0;

    // 🏆 Calcular top productos del día
    const productosVendidos = {};
    ventasDelDia.forEach(venta => {
      try {
        // Intentar obtener productos desde varias fuentes posibles
        let productos = venta.productos || [];
        
        if (!productos.length && venta.detalles_json) {
          const detalles = JSON.parse(venta.detalles_json || '{}');
          productos = detalles.cart || detalles.productos || [];
        }
        
        if (!productos.length && venta.cart) {
          productos = venta.cart;
        }
        
        productos.forEach(item => {
          const id = item.id || item.producto_id;
          const nombre = item.nombre || item.producto_nombre || 'Producto';
          const cantidad = parseInt(item.cantidad || item.quantity || 1);
          const precio = parseFloat(item.precio_venta_unitario || item.price || 0);
          
          if (!productosVendidos[id]) {
            productosVendidos[id] = {
              id: id,
              nombre: nombre,
              cantidad: 0,
              monto: 0
            };
          }
          productosVendidos[id].cantidad += cantidad;
          productosVendidos[id].monto += precio * cantidad;
        });
      } catch (e) {
        console.error('Error procesando productos de venta:', e);
      }
    });

    const topProductos = Object.values(productosVendidos)
      .sort((a, b) => b.monto - a.monto)
      .slice(0, 3);

    // 👥 Performance por cajero actual
    const cajeroActual = turnoActual?.cajero_nombre || 'N/A';
    const ventasCajeroHoy = ventasDelDia.filter(v => {
      const fechaVenta = new Date(v.fecha || v.fecha_hora);
      return turnoActual && fechaVenta >= new Date(turnoActual.fecha_hora);
    });
    const performanceCajero = {
      nombre: cajeroActual,
      ventas: ventasCajeroHoy.length,
      monto: ventasCajeroHoy.reduce((acc, v) => acc + parseFloat(v.monto_total || 0), 0),
      tiempoTurno: tiempoTurno
    };

    return {
      cajaAbierta: estadoActual.caja_abierta,
      efectivoActual: parseFloat(estadoActual.efectivo_disponible || 0),
      totalVentasHoy: totalVentasCompleto, // 🔧 CORREGIDO: Todas las ventas del día
      ventasPorMetodo: ventasPorMetodo, // 📊 NUEVO: Desglose por método
      cantidadVentas: ventasDelDia.length, // 📊 NUEVO: Cantidad de ventas
      aperturaHoy: aperturaHoy?.monto_inicial ? parseFloat(aperturaHoy.monto_inicial) : 0,
      cierresHoy: cierresHoy.length,
      diferenciasHoy: cierresHoy.reduce((acc, c) => acc + parseFloat(c.diferencia || 0), 0),
      ultimaActividad: historial[0]?.fecha_hora || null,
      // ⏰ NUEVAS MÉTRICAS DE TIEMPO
      minutosDesdeUltimaVenta,
      ventasPorHora: parseFloat(ventasPorHora.toFixed(1)),
      tiempoTurnoMinutos: tiempoTurno,
      ticketPromedio: totalVentasCompleto / (ventasDelDia.length || 1),
      // 📊 COMPARATIVAS
      totalVentasAyer,
      cambioVsAyer,
      porcentajeCambio,
      // 🏆 TOP PRODUCTOS
      topProductos,
      // 👥 PERFORMANCE CAJERO
      performanceCajero
    };
  };

  const metricas = calcularMetricasDelDia();

  if (loading) {
    return (
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div className="flex items-center justify-center h-32">
          <RefreshCw className="w-8 h-8 text-gray-400 animate-spin" />
          <span className="ml-3 text-gray-600">Cargando resumen...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <div className="flex items-center justify-center h-32 text-red-600">
          <AlertTriangle className="w-8 h-8 mr-3" />
          <span>{error}</span>
          <button 
            onClick={cargarDatosResumen}
            className="ml-4 px-3 py-1 bg-red-100 hover:bg-red-200 rounded text-sm"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
        {/* 🎯 Header del Dashboard */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white mb-6">
          <div className="flex justify-between items-center">
            <div>
              <h2 className="text-3xl font-bold mb-2">📊 Dashboard de Caja</h2>
              <p className="text-blue-100">Monitor empresarial en tiempo real</p>
              <p className="text-xs text-blue-200 mt-1">
                Última actualización: {new Date().toLocaleString('es-AR')}
              </p>
            </div>
            <button
              onClick={cargarDatosResumen}
              className="flex items-center gap-2 px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-lg transition-all backdrop-blur-sm"
              disabled={loading}
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              {loading ? 'Actualizando...' : 'Actualizar'}
            </button>
          </div>
        </div>

        {/* 📊 SECCIÓN 1: MÉTRICAS PRINCIPALES */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <BarChart3 className="w-6 h-6 mr-3 text-blue-600" />
            Métricas Principales
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <TarjetaMetrica
              titulo="Estado Actual"
              valor={metricas?.cajaAbierta ? 'Abierta' : 'Cerrada'}
              icono={metricas?.cajaAbierta ? CheckCircle : AlertTriangle}
              color={metricas?.cajaAbierta ? 'text-green-600' : 'text-red-600'}
              subtitulo={metricas?.cajaAbierta ? 'Operativa' : 'Debe abrir caja'}
            />

            <TarjetaMetrica
              titulo="Efectivo Disponible"
              valor={`$${metricas?.efectivoActual?.toLocaleString('es-AR') || '0'}`}
              icono={DollarSign}
              color="text-blue-600"
              subtitulo="En caja física"
            />

            <TarjetaMetrica
              titulo="Ventas del Día"
              valor={`$${metricas?.totalVentasHoy?.toLocaleString('es-AR') || '0'}`}
              icono={TrendingUp}
              color="text-green-600"
              subtitulo={`${metricas?.cantidadVentas || 0} ventas - Todos los métodos`}
            />

            <TarjetaMetrica
              titulo="Ticket Promedio"
              valor={`$${((metricas?.totalVentasHoy || 0) / (metricas?.cantidadVentas || 1)).toLocaleString('es-AR')}`}
              icono={Target}
              color="text-purple-600"
              subtitulo={`${metricas?.cantidadVentas || 0} ventas realizadas`}
            />
          </div>
        </div>

      {/* Actividad Reciente */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">🕒 Actividad Reciente</h3>
        
        {datosResumen?.historial?.length > 0 ? (
          <div className="space-y-3">
            {datosResumen.historial.slice(0, 5).map((evento, index) => (
              <div key={evento.id} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                <div className="flex items-center gap-3">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-xs ${
                    evento.tipo_evento === 'apertura' ? 'bg-green-500' : 'bg-red-500'
                  }`}>
                    {evento.tipo_evento === 'apertura' ? '🔓' : '🔒'}
                  </div>
                  <div>
                    <p className="font-medium text-gray-800">
                      {evento.tipo_evento === 'apertura' ? 'Apertura' : 'Cierre'} de Turno #{evento.numero_turno}
                    </p>
                    <p className="text-sm text-gray-600">
                      {evento.cajero_nombre} • {new Date(evento.fecha_hora).toLocaleString('es-AR')}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-bold text-gray-800">
                    ${parseFloat(evento.monto_inicial || evento.efectivo_teorico || 0).toLocaleString('es-AR')}
                  </p>
                  {evento.diferencia && parseFloat(evento.diferencia) !== 0 && (
                    <p className={`text-sm ${parseFloat(evento.diferencia) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {parseFloat(evento.diferencia) >= 0 ? '+' : ''}${parseFloat(evento.diferencia).toLocaleString('es-AR')}
                    </p>
                  )}
                  <p className="text-xs text-gray-500 mt-1">
                    {evento.tipo_evento === 'apertura' ? 
                      `Duración: ${evento.duracion_turno_minutos ? Math.floor(evento.duracion_turno_minutos / 60) + 'h ' + (evento.duracion_turno_minutos % 60) + 'm' : 'En curso'}` :
                      `Transacciones: ${evento.cantidad_transacciones || 0}`
                    }
                  </p>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-500">
            <Calendar className="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>No hay actividad reciente</p>
          </div>
        )}
      </div>

        {/* ⏰ SECCIÓN 2: MÉTRICAS DE RENDIMIENTO */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <Clock className="w-6 h-6 mr-3 text-green-600" />
            Métricas de Rendimiento
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-blue-700">Ventas/Hora</p>
                  <p className="text-3xl font-bold text-blue-800 my-2">{metricas?.ventasPorHora || '0'}</p>
                  <p className="text-xs text-blue-600">Ritmo actual</p>
                </div>
                <BarChart3 className="w-10 h-10 text-blue-600 opacity-80" />
              </div>
            </div>

            <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-green-700">Tiempo Turno</p>
                  <p className="text-3xl font-bold text-green-800 my-2">
                    {Math.floor((metricas?.tiempoTurnoMinutos || 0) / 60)}h {((metricas?.tiempoTurnoMinutos || 0) % 60)}m
                  </p>
                  <p className="text-xs text-green-600">Turno actual</p>
                </div>
                <Clock className="w-10 h-10 text-green-600 opacity-80" />
              </div>
            </div>

            <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200 hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-purple-700">Última Venta</p>
                  <p className="text-3xl font-bold text-purple-800 my-2">
                    {metricas?.minutosDesdeUltimaVenta !== null ? 
                      `${metricas.minutosDesdeUltimaVenta}min` : 'N/A'}
                  </p>
                  <p className="text-xs text-purple-600">Hace</p>
                </div>
                <TrendingUp className="w-10 h-10 text-purple-600 opacity-80" />
              </div>
            </div>

            <div className="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 hover:shadow-md transition-shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-orange-700">Meta Diaria</p>
                  <p className="text-3xl font-bold text-orange-800 my-2">
                    {Math.round((metricas?.totalVentasHoy || 0) / metaDiaria * 100)}%
                  </p>
                  <div className="flex items-center justify-between">
                    <p className="text-xs text-orange-600">
                      ${(metaDiaria / 1000).toFixed(0)}K objetivo
                    </p>
                    <button 
                      onClick={() => setEditandoMeta(true)}
                      className="ml-1 text-orange-500 hover:text-orange-700 transition-colors"
                      title="Editar meta"
                    >
                      ✏️
                    </button>
                  </div>
                </div>
                <Target className="w-10 h-10 text-orange-600 opacity-80" />
              </div>
            </div>
          </div>
        </div>

        {/* 📱 SECCIÓN 3: GRID DE INFORMACIÓN DETALLADA */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          {/* 💳 Desglose de Ventas por Método de Pago */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
              <CreditCard className="w-6 h-6 mr-3 text-blue-600" />
              Ventas por Método de Pago
            </h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="text-center">
            <DollarSign className="w-8 h-8 text-green-600 mx-auto mb-2" />
            <p className="text-sm text-gray-600">Efectivo</p>
            <p className="text-xl font-bold text-green-600">
              ${metricas?.ventasPorMetodo?.efectivo?.toLocaleString('es-AR') || '0'}
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {((metricas?.ventasPorMetodo?.efectivo || 0) / (metricas?.totalVentasHoy || 1) * 100).toFixed(1)}%
            </p>
          </div>
          <div className="text-center">
            <CreditCard className="w-8 h-8 text-blue-600 mx-auto mb-2" />
            <p className="text-sm text-gray-600">Tarjeta</p>
            <p className="text-xl font-bold text-blue-600">
              ${metricas?.ventasPorMetodo?.tarjeta?.toLocaleString('es-AR') || '0'}
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {((metricas?.ventasPorMetodo?.tarjeta || 0) / (metricas?.totalVentasHoy || 1) * 100).toFixed(1)}%
            </p>
          </div>
          <div className="text-center">
            <Smartphone className="w-8 h-8 text-purple-600 mx-auto mb-2" />
            <p className="text-sm text-gray-600">Transferencia</p>
            <p className="text-xl font-bold text-purple-600">
              ${metricas?.ventasPorMetodo?.transferencia?.toLocaleString('es-AR') || '0'}
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {((metricas?.ventasPorMetodo?.transferencia || 0) / (metricas?.totalVentasHoy || 1) * 100).toFixed(1)}%
            </p>
          </div>
          <div className="text-center">
            <QrCode className="w-8 h-8 text-orange-600 mx-auto mb-2" />
            <p className="text-sm text-gray-600">QR</p>
            <p className="text-xl font-bold text-orange-600">
              ${metricas?.ventasPorMetodo?.qr?.toLocaleString('es-AR') || '0'}
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {((metricas?.ventasPorMetodo?.qr || 0) / (metricas?.totalVentasHoy || 1) * 100).toFixed(1)}%
            </p>
          </div>
            </div>
          </div>

          {/* 📊 Comparativas Hoy vs Ayer */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
              <Calendar className="w-6 h-6 mr-3 text-purple-600" />
              Comparativa con Ayer
            </h3>
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center">
                <p className="text-sm text-gray-600 mb-2">Hoy</p>
                <p className="text-2xl font-bold text-blue-600">
                  ${metricas?.totalVentasHoy?.toLocaleString('es-AR') || '0'}
                </p>
                <p className="text-xs text-gray-500">{metricas?.cantidadVentas || 0} ventas</p>
              </div>
              <div className="text-center">
                <p className="text-sm text-gray-600 mb-2">Ayer</p>
                <p className="text-2xl font-bold text-gray-600">
                  ${metricas?.totalVentasAyer?.toLocaleString('es-AR') || '0'}
                </p>
                <p className="text-xs text-gray-500">Comparación</p>
              </div>
              <div className="text-center">
                <p className="text-sm text-gray-600 mb-2">Cambio</p>
                <p className={`text-2xl font-bold ${metricas?.cambioVsAyer >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                  {metricas?.cambioVsAyer >= 0 ? '+' : ''}${Math.abs(metricas?.cambioVsAyer || 0).toLocaleString('es-AR')}
                </p>
                <p className={`text-xs ${metricas?.porcentajeCambio >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                  {metricas?.porcentajeCambio >= 0 ? '+' : ''}{metricas?.porcentajeCambio?.toFixed(1) || '0'}%
                </p>
              </div>
            </div>
          </div>

        </div>

        {/* 🎯 SECCIÓN 4: GRID DE ANÁLISIS Y PERFORMANCE */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          {/* 🏆 Top 3 Productos del Día */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
              <Target className="w-6 h-6 mr-3 text-yellow-600" />
              Top 3 Productos
            </h3>
            {metricas?.topProductos?.length > 0 ? (
              <div className="space-y-4">
                {metricas.topProductos.map((producto, index) => (
                  <div key={producto.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div className="flex items-center gap-3">
                      <div className={`w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg ${
                        index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : 'bg-orange-500'
                      }`}>
                        {index + 1}
                      </div>
                      <div>
                        <p className="font-semibold text-gray-800">{producto.nombre}</p>
                        <p className="text-sm text-gray-600">{producto.cantidad} unidades vendidas</p>
                      </div>
                    </div>
                    <p className="text-xl font-bold text-green-600">
                      ${producto.monto.toLocaleString('es-AR')}
                    </p>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <Target className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p>No hay productos vendidos hoy</p>
              </div>
            )}
          </div>

          {/* 👥 Performance del Cajero Actual */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
              <User className="w-6 h-6 mr-3 text-green-600" />
              Performance Cajero
            </h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center bg-blue-50 rounded-lg p-4">
                <User className="w-8 h-8 text-blue-600 mx-auto mb-2" />
                <p className="text-sm text-gray-600">Cajero</p>
                <p className="text-lg font-bold text-blue-600">{metricas?.performanceCajero?.nombre}</p>
              </div>
              <div className="text-center bg-green-50 rounded-lg p-4">
                <BarChart3 className="w-8 h-8 text-green-600 mx-auto mb-2" />
                <p className="text-sm text-gray-600">Ventas Turno</p>
                <p className="text-lg font-bold text-green-600">{metricas?.performanceCajero?.ventas || 0}</p>
              </div>
              <div className="text-center bg-purple-50 rounded-lg p-4">
                <DollarSign className="w-8 h-8 text-purple-600 mx-auto mb-2" />
                <p className="text-sm text-gray-600">Monto Turno</p>
                <p className="text-lg font-bold text-purple-600">
                  ${(metricas?.performanceCajero?.monto || 0).toLocaleString('es-AR')}
                </p>
              </div>
              <div className="text-center bg-orange-50 rounded-lg p-4">
                <Clock className="w-8 h-8 text-orange-600 mx-auto mb-2" />
                <p className="text-sm text-gray-600">Tiempo</p>
                <p className="text-lg font-bold text-orange-600">
                  {Math.floor((metricas?.performanceCajero?.tiempoTurno || 0) / 60)}h {((metricas?.performanceCajero?.tiempoTurno || 0) % 60)}m
                </p>
              </div>
            </div>
          </div>

        </div>

        {/* 📊 SECCIÓN 5: ESTADÍSTICAS RÁPIDAS */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <BarChart3 className="w-6 h-6 mr-3 text-indigo-600" />
            Estadísticas del Sistema
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center bg-blue-50 rounded-lg p-6">
              <BarChart3 className="w-12 h-12 text-blue-600 mx-auto mb-4" />
              <h4 className="font-semibold text-gray-800 mb-2">Total de Eventos</h4>
              <p className="text-3xl font-bold text-blue-600">{datosResumen?.estadisticas?.total_eventos || 0}</p>
              <p className="text-sm text-gray-600 mt-1">Aperturas y cierres</p>
            </div>

            <div className="text-center bg-green-50 rounded-lg p-6">
              <User className="w-12 h-12 text-green-600 mx-auto mb-4" />
              <h4 className="font-semibold text-gray-800 mb-2">Cajeros Únicos</h4>
              <p className="text-3xl font-bold text-green-600">{datosResumen?.estadisticas?.cajeros_unicos || 0}</p>
              <p className="text-sm text-gray-600 mt-1">Han operado la caja</p>
            </div>

            <div className="text-center bg-emerald-50 rounded-lg p-6">
              <CheckCircle className="w-12 h-12 text-emerald-600 mx-auto mb-4" />
              <h4 className="font-semibold text-gray-800 mb-2">Cierres Exactos</h4>
              <p className="text-3xl font-bold text-emerald-600">
                {datosResumen?.estadisticas?.cierres_exactos || 0}/{datosResumen?.estadisticas?.total_cierres || 0}
              </p>
              <p className="text-sm text-gray-600 mt-1">Sin diferencias</p>
            </div>
          </div>
        </div>

      {/* 🚨 Alertas y Recomendaciones Inteligentes */}
      {(() => {
        const alertas = [];
        
        // Alerta por inactividad
        if (metricas?.minutosDesdeUltimaVenta > 30) {
          alertas.push({
            tipo: 'warning',
            titulo: '⚠️ Inactividad Detectada',
            mensaje: `Sin ventas hace ${metricas.minutosDesdeUltimaVenta} minutos`,
            accion: 'Revisar productos destacados o promociones'
          });
        }
        
        // Alerta por rendimiento bajo
        if (metricas?.ventasPorHora < 2 && metricas?.tiempoTurnoMinutos > 60) {
          alertas.push({
            tipo: 'info',
            titulo: '📊 Ritmo de Ventas Bajo',
            mensaje: `Solo ${metricas.ventasPorHora} ventas/hora`,
            accion: 'Considerar estrategias de impulso'
          });
        }
        
        // Alerta por efectivo alto
        if (metricas?.efectivoActual > 80000) {
          alertas.push({
            tipo: 'warning',
            titulo: '💰 Efectivo Alto en Caja',
            mensaje: `$${metricas.efectivoActual.toLocaleString('es-AR')} en efectivo`,
            accion: 'Considerar hacer un arqueo intermedio'
          });
        }
        
        // Felicitación por buen rendimiento
        if (metricas?.ventasPorHora > 8) {
          alertas.push({
            tipo: 'success',
            titulo: '🎉 Excelente Ritmo!',
            mensaje: `${metricas.ventasPorHora} ventas/hora`,
            accion: 'Mantener el momentum'
          });
        }

        return alertas.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            {alertas.map((alerta, index) => (
              <div 
                key={index}
                className={`rounded-xl p-4 border ${
                  alerta.tipo === 'warning' ? 'bg-yellow-50 border-yellow-200' :
                  alerta.tipo === 'success' ? 'bg-green-50 border-green-200' :
                  'bg-blue-50 border-blue-200'
                }`}
              >
                <h4 className={`font-medium mb-2 ${
                  alerta.tipo === 'warning' ? 'text-yellow-800' :
                  alerta.tipo === 'success' ? 'text-green-800' :
                  'text-blue-800'
                }`}>
                  {alerta.titulo}
                </h4>
                <p className={`text-sm mb-2 ${
                  alerta.tipo === 'warning' ? 'text-yellow-700' :
                  alerta.tipo === 'success' ? 'text-green-700' :
                  'text-blue-700'
                }`}>
                  {alerta.mensaje}
                </p>
                <p className={`text-xs font-medium ${
                  alerta.tipo === 'warning' ? 'text-yellow-600' :
                  alerta.tipo === 'success' ? 'text-green-600' :
                  'text-blue-600'
                }`}>
                  💡 {alerta.accion}
                </p>
              </div>
            ))}
          </div>
        );
      })()}


      {/* 🎯 Modal para Editar Meta */}
      {editandoMeta && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 className="text-lg font-semibold mb-4">🎯 Configurar Meta Diaria</h3>
            <input
              type="number"
              value={metaDiaria}
              onChange={(e) => setMetaDiaria(parseFloat(e.target.value) || 0)}
              className="w-full p-3 border border-gray-300 rounded-lg mb-4"
              placeholder="Ej: 100000"
            />
            <div className="flex gap-3">
              <button
                onClick={() => setEditandoMeta(false)}
                className="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg"
              >
                Cancelar
              </button>
              <button
                onClick={() => {
                  localStorage.setItem('metaDiaria', metaDiaria.toString());
                  setEditandoMeta(false);
                }}
                className="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
              >
                Guardar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DashboardResumenCaja;
