import React, { useState, useEffect } from 'react';
import { Calendar, Filter, Search, BarChart3, Clock, DollarSign, User, AlertTriangle, Download, Eye, RefreshCw, FileText, Users } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';
import ReportesEfectivoPeriodo from './ReportesEfectivoPeriodo';
import ReportesDiferenciasCajero from './ReportesDiferenciasCajero';

const HistorialTurnosPage = () => {
  const { user } = useAuth();
  const [pestanaActiva, setPestanaActiva] = useState('historial'); // 'historial', 'reportes', 'cajeros'
  const [historial, setHistorial] = useState([]);
  const [estadisticas, setEstadisticas] = useState(null);
  const [filtrosDisponibles, setFiltrosDisponibles] = useState({ cajeros: [], tipos_evento: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Estados de filtros
  const [filtros, setFiltros] = useState({
    fecha_inicio: '',
    fecha_fin: '',
    tipo_evento: 'todos',
    cajero_id: 'todos',
    limite: 25,
    pagina: 1
  });
  
  // Estados de paginación
  const [paginacion, setPaginacion] = useState({
    total_registros: 0,
    pagina_actual: 1,
    limite_por_pagina: 25,
    total_paginas: 1,
    tiene_siguiente: false,
    tiene_anterior: false
  });

  const [mostrarFiltros, setMostrarFiltros] = useState(false);
  const [turnoDetalle, setTurnoDetalle] = useState(null);
  const [mostrarDetalle, setMostrarDetalle] = useState(false);
  const [resumenMovimientos, setResumenMovimientos] = useState(null);
  const [cargandoResumen, setCargandoResumen] = useState(false);

  // 🔧 OPTIMIZACIÓN: Debounce para evitar múltiples calls
  const [debounceTimeout, setDebounceTimeout] = useState(null);
  
  // 🔥 ESTADO PARA EFECTIVO REAL ACTUAL
  const [efectivoRealActual, setEfectivoRealActual] = useState(0);

  // 🔥 FUNCIÓN PARA OBTENER EFECTIVO REAL ACTUAL
  const obtenerEfectivoRealActual = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/pos_status.php?_t=${Date.now()}`);
      const data = await response.json();
      
      // POS status obtenido
      if (data.success && data.caja_abierta) {
        const efectivo = parseFloat(data.efectivo_disponible || 0);
        // Efectivo de caja abierta obtenido
        setEfectivoRealActual(efectivo);
        return efectivo;
      }
      // Caja cerrada
      setEfectivoRealActual(0);
      return 0;
    } catch (error) {
      console.error('Error obteniendo efectivo real:', error);
      setEfectivoRealActual(0);
      return 0;
    }
  };

  // Cargar datos del historial
  const cargarHistorial = async () => {
    // Cancelar timeout anterior si existe
    if (debounceTimeout) {
      clearTimeout(debounceTimeout);
    }
    
    setLoading(true);
    setError(null);
    
    try {
      const params = new URLSearchParams({
        usuario_id: user?.id || 1,
        ...filtros
      });
      
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=historial_completo&${params}&_t=${Date.now()}`);
      const data = await response.json();
      
      if (data.success) {
        setHistorial(data.historial || []);
        setEstadisticas(data.estadisticas || {});
        setPaginacion(data.paginacion || {});
        setFiltrosDisponibles(data.filtros_disponibles || { cajeros: [], tipos_evento: [] });
        
        // 🔥 ACTUALIZAR EFECTIVO REAL AL CARGAR HISTORIAL
        obtenerEfectivoRealActual();
      } else {
        throw new Error(data.error || 'Error al cargar historial');
      }
    } catch (error) {
      console.error('Error cargando historial:', error);
      setError('Error al cargar el historial de turnos');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    cargarHistorial();
  }, [user?.id, filtros.fecha_inicio, filtros.fecha_fin, filtros.tipo_evento, filtros.cajero_id, filtros.limite, filtros.pagina]); // 🔧 OPTIMIZACIÓN: Dependencias específicas

  // 🔥 OBTENER EFECTIVO REAL AL CARGAR COMPONENTE
  useEffect(() => {
    // Limpiar estado anterior
    setEfectivoRealActual(0);
    setHistorial([]);
    
    // Cargar datos frescos
    obtenerEfectivoRealActual();
    cargarHistorial();
    
    // Actualizar cada 30 segundos
    const interval = setInterval(() => {
      obtenerEfectivoRealActual();
      cargarHistorial();
    }, 30000);
    
    return () => clearInterval(interval);
  }, []);

  // Manejar cambios en filtros
  const handleFiltroChange = (campo, valor) => {
    setFiltros(prev => ({
      ...prev,
      [campo]: valor,
      pagina: 1 // Reset página al cambiar filtros
    }));
  };

  // Cambiar página
  const cambiarPagina = (nuevaPagina) => {
    setFiltros(prev => ({ ...prev, pagina: nuevaPagina }));
  };

  // Ver detalle de turno
  const verDetalleTurno = async (turno) => {
    setTurnoDetalle(turno);
    setMostrarDetalle(true);
    setResumenMovimientos(null);
    setCargandoResumen(true);
    
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=resumen_movimientos_turno&numero_turno=${turno.numero_turno}`);
      const data = await response.json();
      
      if (data.success) {
        setResumenMovimientos(data.resumen);
      } else {
        console.error('Error obteniendo resumen:', data.error);
      }
    } catch (error) {
      console.error('Error cargando resumen de movimientos:', error);
    } finally {
      setCargandoResumen(false);
    }
  };

  // 🏦 Calcular arqueo acumulativo (balance running)
  const calcularArqueoAcumulativo = (historialEvents) => {
    if (!historialEvents.length) return [];
    
    // Ordenar cronológicamente (más antiguo primero) para calcular correctamente
    const historialCronologico = [...historialEvents].sort((a, b) => 
      new Date(a.fecha_hora) - new Date(b.fecha_hora)
    );
    
    let balanceAcumulado = 0;
    let balanceAnterior = 0;
    
    const historialConArqueo = historialCronologico.map((evento, index) => {
      balanceAnterior = balanceAcumulado;
      
      if (evento.tipo_evento === 'apertura') {
        // En apertura: establecer el balance inicial
        balanceAcumulado = parseFloat(evento.monto_inicial || 0);
      } else if (evento.tipo_evento === 'cierre') {
        // En cierre: el balance es el efectivo contado real
        balanceAcumulado = parseFloat(evento.efectivo_contado || 0);
      }
      
      // Calcular el flujo neto (cambio respecto al evento anterior)
      const flujoNeto = index === 0 ? balanceAcumulado : balanceAcumulado - balanceAnterior;
      
      return {
        ...evento,
        balance_acumulado: balanceAcumulado,
        flujo_neto: flujoNeto,
        balance_anterior: balanceAnterior
      };
    });
    
    // Retornar en orden descendente (más reciente primero) para mostrar
    return historialConArqueo.reverse();
  };

  // Procesar historial con arqueo
  const historialConArqueo = calcularArqueoAcumulativo(historial);


  // 💰 Calcular análisis de efectivo real del período
  const calcularEfectivoRealPeriodo = () => {
    if (!historialConArqueo.length) return null;

    // Obtener eventos ordenados cronológicamente (más recientes primero)
    const eventosOrdenados = [...historialConArqueo];
    
    let efectivoQueDeberiaTener = 0;
    let eventoReferencia = null;
    let cajaRealmenteAbiertaLocal = efectivoRealActual > 0;
    
    if (cajaRealmenteAbiertaLocal) {
      // Caja abierta: usar efectivo real actual
      efectivoQueDeberiaTener = efectivoRealActual;
      eventoReferencia = eventosOrdenados.find(e => e.tipo_evento === 'apertura' && !e.fecha_cierre);
    } else {
      // Caja cerrada: buscar el último cierre válido
      const ultimoCierre = eventosOrdenados.find(e => 
        e.tipo_evento === 'cierre' && 
        parseFloat(e.efectivo_teorico || 0) > 0
      );
      
      if (ultimoCierre) {
        efectivoQueDeberiaTener = parseFloat(ultimoCierre.efectivo_teorico || 0);
        eventoReferencia = ultimoCierre;
      }
    }

    const efectivoInicialPeriodo = parseFloat(eventosOrdenados[eventosOrdenados.length - 1]?.monto_inicial || 0);
    const efectivoFinalReal = eventoReferencia && eventoReferencia.efectivo_contado ? parseFloat(eventoReferencia.efectivo_contado || 0) : efectivoQueDeberiaTener;
    
    // Sumar diferencias acumuladas de todos los cierres
    const cierres = eventosOrdenados.filter(e => e.tipo_evento === 'cierre');
    const diferenciasAcumuladas = cierres.reduce((acc, cierre) => {
      return acc + parseFloat(cierre.diferencia || 0);
    }, 0);

    // Calcular movimientos netos del período
    const movimientosNetoPeriodo = efectivoQueDeberiaTener - efectivoInicialPeriodo;
    
    // Días del período  
    const fechaInicio = new Date(eventosOrdenados[eventosOrdenados.length - 1]?.fecha_hora || new Date());
    const fechaFin = new Date(eventosOrdenados[0]?.fecha_hora || new Date());
    const diasPeriodo = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;

    // 🔥 DETERMINAR ESTADO REAL DE LA CAJA
    const cajaRealmenteAbierta = cajaRealmenteAbiertaLocal;
    
    // 🔥 USAR EL VALOR CORRECTO SEGÚN EL ESTADO REAL
    let efectivoFinalParaMostrar = efectivoQueDeberiaTener;

    const resultado = {
      efectivo_inicial_periodo: efectivoInicialPeriodo,
      efectivo_final_teorico: efectivoQueDeberiaTener,
      efectivo_final_real: efectivoFinalReal,
      diferencias_acumuladas: diferenciasAcumuladas,
      movimientos_neto_periodo: movimientosNetoPeriodo,
      dias_periodo: diasPeriodo,
      promedio_diferencia_diaria: diferenciasAcumuladas / Math.max(cierres.length, 1),
      total_turnos: cierres.length,
      efectivo_que_deberia_haber: efectivoFinalParaMostrar, // 🔥 OVERRIDE FORZADO
      estado_efectivo: diferenciasAcumuladas === 0 ? 'exacto' : (diferenciasAcumuladas > 0 ? 'sobrante' : 'faltante'),
      caja_abierta: cajaRealmenteAbierta // 🔥 CORREGIDO: Estado real basado en efectivoRealActual
    };
    
    return resultado;
  };
  
  const analisisEfectivoReal = calcularEfectivoRealPeriodo();

  // Limpiar filtros
  const limpiarFiltros = () => {
    setFiltros({
      fecha_inicio: '',
      fecha_fin: '',
      tipo_evento: 'todos',
      cajero_id: 'todos',
      limite: 25,
      pagina: 1
    });
  };

  if (loading && !historial.length) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <RefreshCw className="w-8 h-8 animate-spin text-blue-600 mx-auto mb-4" />
          <p className="text-gray-600">Cargando historial de turnos...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <h2 className="text-xl font-bold text-gray-800 mb-2">Error al cargar datos</h2>
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={cargarHistorial}
            className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-800 mb-2">📊 Historial de Turnos</h1>
              <p className="text-gray-600">Trazabilidad completa de apertura y cierre de caja</p>
            </div>
          </div>
          
          {/* 🔖 PESTAÑAS DE NAVEGACIÓN */}
          <div className="mt-6 border-b border-gray-200">
            <nav className="-mb-px flex space-x-8" aria-label="Tabs">
              <button
                onClick={() => setPestanaActiva('historial')}
                className={`${
                  pestanaActiva === 'historial'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                } whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2`}
              >
                <Clock className="w-4 h-4" />
                Historial de Turnos
              </button>
              <button
                onClick={() => setPestanaActiva('reportes')}
                className={`${
                  pestanaActiva === 'reportes'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                } whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2`}
              >
                <FileText className="w-4 h-4" />
                Reportes por Período
              </button>
              <button
                onClick={() => setPestanaActiva('cajeros')}
                className={`${
                  pestanaActiva === 'cajeros'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                } whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2`}
              >
                <Users className="w-4 h-4" />
                Diferencias por Cajero
              </button>
            </nav>
          </div>
        </div>

        {/* 📄 CONTENIDO DE LAS PESTAÑAS */}
        {pestanaActiva === 'historial' ? (
          <div>
            <div className="flex items-center justify-between mb-6">
              <div className="flex gap-3">
                <button
                  onClick={() => setMostrarFiltros(!mostrarFiltros)}
                  className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                >
                  <Filter className="w-4 h-4" />
                  Filtros
                </button>
                <button
                  onClick={() => {
                    cargarHistorial();
                    obtenerEfectivoRealActual();
                  }}
                  disabled={loading}
                  className="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg disabled:opacity-50"
                >
                  <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                  Actualizar
                </button>
              </div>
            </div>

        {/* Estadísticas Rápidas */}
        {estadisticas && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Eventos</p>
                  <p className="text-2xl font-bold text-gray-800">{estadisticas.total_eventos}</p>
                </div>
                <BarChart3 className="w-8 h-8 text-blue-500" />
              </div>
            </div>
            
            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Turnos Únicos</p>
                  <p className="text-2xl font-bold text-gray-800">{estadisticas.turnos_unicos}</p>
                </div>
                <Clock className="w-8 h-8 text-green-500" />
              </div>
            </div>
            
            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Cierres Exactos</p>
                  <p className="text-2xl font-bold text-green-600">{estadisticas.cierres_exactos}</p>
                  <p className="text-xs text-gray-500">de {estadisticas.total_cierres} cierres</p>
                </div>
                <DollarSign className="w-8 h-8 text-green-500" />
              </div>
            </div>
            
            <div className="bg-white rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Diferencia Total</p>
                  <p className={`text-2xl font-bold ${parseFloat(estadisticas.diferencia_total) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    ${parseFloat(estadisticas.diferencia_total || 0).toLocaleString('es-AR')}
                  </p>
                </div>
                <AlertTriangle className={`w-8 h-8 ${parseFloat(estadisticas.diferencia_total) >= 0 ? 'text-green-500' : 'text-red-500'}`} />
              </div>
            </div>
          </div>
        )}

        {/* Panel de Filtros */}
        {mostrarFiltros && (
          <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">🔍 Filtros de Búsqueda</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                <input
                  type="date"
                  value={filtros.fecha_inicio}
                  onChange={(e) => handleFiltroChange('fecha_inicio', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                <input
                  type="date"
                  value={filtros.fecha_fin}
                  onChange={(e) => handleFiltroChange('fecha_fin', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Tipo de Evento</label>
                <select
                  value={filtros.tipo_evento}
                  onChange={(e) => handleFiltroChange('tipo_evento', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  {filtrosDisponibles.tipos_evento.map(tipo => (
                    <option key={tipo.value} value={tipo.value}>{tipo.label}</option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Cajero</label>
                <select
                  value={filtros.cajero_id}
                  onChange={(e) => handleFiltroChange('cajero_id', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="todos">Todos los cajeros</option>
                  {filtrosDisponibles.cajeros.map(cajero => (
                    <option key={cajero.cajero_id} value={cajero.cajero_id}>
                      {cajero.cajero_nombre} ({cajero.cantidad_eventos} eventos)
                    </option>
                  ))}
                </select>
              </div>
            </div>
            
            <div className="flex gap-3 mt-4">
              <button
                onClick={limpiarFiltros}
                className="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Limpiar Filtros
              </button>
              <button
                onClick={() => setMostrarFiltros(false)}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
              >
                Aplicar Filtros
              </button>
            </div>
          </div>
        )}

        {/* Tabla de Historial */}
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-800">Registro de Eventos</h3>
              <p className="text-sm text-gray-600">
                {paginacion.total_registros} eventos encontrados
              </p>
            </div>
          </div>
          
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turno #</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cajero</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diferencia</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">🏦 Arqueo</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {historialConArqueo.map((evento) => (
                  <tr key={evento.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <span className="text-lg mr-2">{evento.icono_evento}</span>
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {evento.tipo_evento === 'apertura' ? 'Apertura' : 'Cierre'}
                          </div>
                          <div className="text-sm text-gray-500">
                            {evento.duracion_categoria && `Duración: ${evento.duracion_categoria}`}
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">#{evento.numero_turno}</div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">{evento.cajero_nombre}</div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">{evento.fecha_formateada}</div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">
                        {evento.tipo_evento === 'apertura' 
                          ? `$${parseFloat(evento.monto_inicial).toLocaleString('es-AR')}`
                          : `$${parseFloat(evento.efectivo_contado).toLocaleString('es-AR')}`
                        }
                      </div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      {evento.tipo_evento === 'cierre' ? (
                        <div className="flex items-center">
                          <span className="mr-1">{evento.icono_diferencia}</span>
                          <span className={`text-sm font-medium ${
                            parseFloat(evento.diferencia) === 0 ? 'text-green-600' :
                            parseFloat(evento.diferencia) > 0 ? 'text-blue-600' : 'text-red-600'
                          }`}>
                            ${parseFloat(evento.diferencia).toLocaleString('es-AR')}
                          </span>
                        </div>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    
                    {/* 🏦 COLUMNA DE ARQUEO - BALANCE ACUMULATIVO */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-right">
                        <div className={`text-sm font-bold ${
                          evento.balance_acumulado >= 0 ? 'text-green-600' : 'text-red-600'
                        }`}>
                          ${parseFloat(evento.balance_acumulado || 0).toLocaleString('es-AR')}
                        </div>
                        <div className="text-xs text-gray-500">
                          {evento.flujo_neto !== undefined && (
                            <span className={`${
                              evento.flujo_neto > 0 ? 'text-green-500' : 
                              evento.flujo_neto < 0 ? 'text-red-500' : 'text-gray-500'
                            }`}>
                              {evento.flujo_neto > 0 ? '+' : ''}{parseFloat(evento.flujo_neto || 0).toLocaleString('es-AR')}
                            </span>
                          )}
                        </div>
                      </div>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        evento.nivel_diferencia === 'Perfecto' ? 'bg-green-100 text-green-800' :
                        evento.nivel_diferencia === 'Aceptable' ? 'bg-yellow-100 text-yellow-800' :
                        evento.nivel_diferencia === 'Alto' ? 'bg-orange-100 text-orange-800' :
                        evento.nivel_diferencia === 'Crítico' ? 'bg-red-100 text-red-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {evento.nivel_diferencia || 'N/A'}
                      </span>
                    </td>
                    
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <button
                        onClick={() => verDetalleTurno(evento)}
                        className="text-blue-600 hover:text-blue-900 flex items-center gap-1"
                      >
                        <Eye className="w-4 h-4" />
                        Ver detalle
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          
          {/* Paginación */}
          {paginacion.total_paginas > 1 && (
            <div className="px-6 py-4 border-t border-gray-200">
              <div className="flex items-center justify-between">
                <div className="text-sm text-gray-700">
                  Mostrando {((paginacion.pagina_actual - 1) * paginacion.limite_por_pagina) + 1} a{' '}
                  {Math.min(paginacion.pagina_actual * paginacion.limite_por_pagina, paginacion.total_registros)} de{' '}
                  {paginacion.total_registros} resultados
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => cambiarPagina(paginacion.pagina_actual - 1)}
                    disabled={!paginacion.tiene_anterior}
                    className="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Anterior
                  </button>
                  <span className="px-3 py-1 text-sm bg-blue-600 text-white rounded">
                    {paginacion.pagina_actual} de {paginacion.total_paginas}
                  </span>
                  <button
                    onClick={() => cambiarPagina(paginacion.pagina_actual + 1)}
                    disabled={!paginacion.tiene_siguiente}
                    className="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Siguiente
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>


        {/* 💰 Análisis de Efectivo Real del Período */}
        {analisisEfectivoReal && (
          <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-lg font-semibold text-gray-800">💰 Análisis de Efectivo Real del Período</h3>
                <p className="text-gray-600">Cuánto efectivo físico deberías tener ({analisisEfectivoReal.dias_periodo} días, {analisisEfectivoReal.total_turnos} turnos)</p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-600">
                  {analisisEfectivoReal.caja_abierta ? '🔥 Efectivo Real Actual' : '💰 Efectivo del Último Cierre'}
                </p>
                <p className={`text-3xl font-bold ${
                  analisisEfectivoReal.estado_efectivo === 'exacto' ? 'text-green-600' : 
                  analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'text-blue-600' : 'text-red-600'
                }`}>
                  ${parseFloat(analisisEfectivoReal.efectivo_que_deberia_haber).toLocaleString('es-AR')}
                </p>
                <p className="text-xs text-gray-500">
                  {analisisEfectivoReal.caja_abierta ? '💰 Estado actual (caja abierta)' : '🔒 Último registro de cierre'}
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {/* Efectivo inicial del período */}
              <div className="bg-blue-50 rounded-xl p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-blue-600">💵 Efectivo Inicial</p>
                    <p className="text-xl font-bold text-blue-800">
                      ${parseFloat(analisisEfectivoReal.efectivo_inicial_periodo).toLocaleString('es-AR')}
                    </p>
                    <p className="text-xs text-blue-600">Primera apertura del período</p>
                  </div>
                  <div className="p-2 bg-blue-100 rounded-lg">
                    <Calendar className="w-5 h-5 text-blue-600" />
                  </div>
                </div>
              </div>

              {/* Movimientos netos del período */}
              <div className={`${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'bg-green-50' : 'bg-red-50'} rounded-xl p-4`}>
                <div className="flex items-center justify-between">
                  <div>
                    <p className={`text-sm font-medium ${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      📈 Movimientos Netos
                    </p>
                    <p className={`text-xl font-bold ${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'text-green-800' : 'text-red-800'}`}>
                      {analisisEfectivoReal.movimientos_neto_periodo >= 0 ? '+' : ''}${parseFloat(analisisEfectivoReal.movimientos_neto_periodo).toLocaleString('es-AR')}
                    </p>
                    <p className={`text-xs ${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      Ingresos - Egresos del período
                    </p>
                  </div>
                  <div className={`p-2 ${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'bg-green-100' : 'bg-red-100'} rounded-lg`}>
                    <BarChart3 className={`w-5 h-5 ${analisisEfectivoReal.movimientos_neto_periodo >= 0 ? 'text-green-600' : 'text-red-600'}`} />
                  </div>
                </div>
              </div>

              {/* Diferencias acumuladas */}
              <div className={`${analisisEfectivoReal.estado_efectivo === 'exacto' ? 'bg-green-50' : 
                                analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'bg-blue-50' : 'bg-red-50'} rounded-xl p-4`}>
                <div className="flex items-center justify-between">
                  <div>
                    <p className={`text-sm font-medium ${analisisEfectivoReal.estado_efectivo === 'exacto' ? 'text-green-600' : 
                                                          analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'text-blue-600' : 'text-red-600'}`}>
                      ⚖️ Diferencias Acumuladas
                    </p>
                    <p className={`text-xl font-bold ${analisisEfectivoReal.estado_efectivo === 'exacto' ? 'text-green-800' : 
                                                       analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'text-blue-800' : 'text-red-800'}`}>
                      {analisisEfectivoReal.diferencias_acumuladas >= 0 ? '+' : ''}${parseFloat(analisisEfectivoReal.diferencias_acumuladas).toLocaleString('es-AR')}
                    </p>
                    <p className={`text-xs ${analisisEfectivoReal.estado_efectivo === 'exacto' ? 'text-green-600' : 
                                              analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'text-blue-600' : 'text-red-600'}`}>
                      {analisisEfectivoReal.estado_efectivo === 'exacto' ? 'Perfecto' : 
                       analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'Sobrante' : 'Faltante'} total
                    </p>
                  </div>
                  <div className={`p-2 ${analisisEfectivoReal.estado_efectivo === 'exacto' ? 'bg-green-100' : 
                                          analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'bg-blue-100' : 'bg-red-100'} rounded-lg`}>
                    {analisisEfectivoReal.estado_efectivo === 'exacto' ? 
                      <DollarSign className="w-5 h-5 text-green-600" /> :
                      <AlertTriangle className={`w-5 h-5 ${analisisEfectivoReal.estado_efectivo === 'sobrante' ? 'text-blue-600' : 'text-red-600'}`} />
                    }
                  </div>
                </div>
              </div>

              {/* Efectivo real actual */}
              <div className="bg-purple-50 rounded-xl p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-purple-600">💳 Efectivo Real</p>
                    <p className="text-xl font-bold text-purple-800">
                      ${parseFloat(analisisEfectivoReal.efectivo_final_real).toLocaleString('es-AR')}
                    </p>
                    <p className="text-xs text-purple-600">
                      Último conteo físico
                    </p>
                  </div>
                  <div className="p-2 bg-purple-100 rounded-lg">
                    <User className="w-5 h-5 text-purple-600" />
                  </div>
                </div>
              </div>
            </div>

            {/* Explicación clara */}
            <div className="mt-6 p-4 bg-yellow-50 rounded-lg">
              <h4 className="text-sm font-semibold text-yellow-700 mb-2">🎯 Interpretación del Análisis:</h4>
              <div className="text-sm text-yellow-700 space-y-1">
                <p><strong>• Efectivo que Deberías Tener:</strong> ${parseFloat(analisisEfectivoReal.efectivo_que_deberia_haber).toLocaleString('es-AR')} es lo que deberías tener físicamente en caja</p>
                <p><strong>• Diferencias Acumuladas:</strong> {analisisEfectivoReal.diferencias_acumuladas >= 0 ? `Tienes $${Math.abs(analisisEfectivoReal.diferencias_acumuladas).toLocaleString('es-AR')} de más` : `Te faltan $${Math.abs(analisisEfectivoReal.diferencias_acumuladas).toLocaleString('es-AR')}`} según los cierres</p>
                <p><strong>• Promedio por Turno:</strong> ${Math.abs(analisisEfectivoReal.promedio_diferencia_diaria).toLocaleString('es-AR')} de diferencia promedio por turno</p>
                <p><strong>• Estado:</strong> {analisisEfectivoReal.estado_efectivo === 'exacto' ? '✅ Efectivo exacto' : 
                                              analisisEfectivoReal.estado_efectivo === 'sobrante' ? '📈 Hay sobrante de efectivo' : '📉 Falta efectivo en caja'}</p>
              </div>
            </div>
          </div>
        )}

        {/* Modal de Detalle */}
        {mostrarDetalle && turnoDetalle && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-2xl shadow-2xl max-w-4xl w-full p-8 max-h-[90vh] overflow-y-auto">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">
                  {turnoDetalle.icono_evento} Detalle Turno #{turnoDetalle.numero_turno}
                </h2>
                <button
                  onClick={() => setMostrarDetalle(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  ✕
                </button>
              </div>
              
              <div className="space-y-6">
                {/* Información básica del turno */}
                <div className="bg-gray-50 rounded-xl p-6">
                  <h3 className="text-lg font-semibold text-gray-800 mb-4">📋 Información del Turno</h3>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                      <label className="text-sm font-medium text-gray-600">Cajero</label>
                      <p className="text-lg font-semibold text-gray-800">{turnoDetalle.cajero_nombre}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-600">Fecha</label>
                      <p className="text-lg font-semibold text-gray-800">{turnoDetalle.fecha_solo}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-600">Hora</label>
                      <p className="text-lg font-semibold text-gray-800">{turnoDetalle.hora_solo}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-600">Tipo</label>
                      <p className="text-lg font-semibold text-gray-800">
                        {turnoDetalle.tipo_evento === 'apertura' ? '🔓 Apertura' : '🔒 Cierre'}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Resumen financiero del turno */}
                {cargandoResumen ? (
                  <div className="bg-blue-50 rounded-xl p-6 text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-2 border-blue-600 border-t-transparent mx-auto mb-4"></div>
                    <p className="text-blue-600">Cargando resumen de movimientos...</p>
                  </div>
                ) : resumenMovimientos ? (
                  <div className="space-y-6">
                    {/* Resumen de totales */}
                    <div className="bg-green-50 rounded-xl p-6">
                      <h3 className="text-lg font-semibold text-green-800 mb-4">💰 Resumen Financiero</h3>
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white rounded-lg p-4 border border-green-200">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm font-medium text-green-600">💵 Apertura</p>
                              <p className="text-xl font-bold text-green-800">
                                ${parseFloat(resumenMovimientos.turno.monto_apertura).toLocaleString('es-AR')}
                              </p>
                            </div>
                            <div className="p-2 bg-green-100 rounded-lg">
                              <DollarSign className="w-5 h-5 text-green-600" />
                            </div>
                          </div>
                        </div>
                        
                        <div className="bg-white rounded-lg p-4 border border-green-200">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm font-medium text-green-600">📈 Ingresos Totales</p>
                              <p className="text-xl font-bold text-green-800">
                                ${(resumenMovimientos.totales.ingresos_manuales + resumenMovimientos.totales.ventas_efectivo).toLocaleString('es-AR')}
                              </p>
                              <p className="text-xs text-green-600">
                                Manuales + Ventas
                              </p>
                            </div>
                            <div className="p-2 bg-green-100 rounded-lg">
                              <BarChart3 className="w-5 h-5 text-green-600" />
                            </div>
                          </div>
                        </div>
                        
                        <div className="bg-white rounded-lg p-4 border border-green-200">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm font-medium text-green-600">📉 Egresos Totales</p>
                              <p className="text-xl font-bold text-red-600">
                                ${resumenMovimientos.totales.egresos_totales.toLocaleString('es-AR')}
                              </p>
                              <p className="text-xs text-green-600">
                                {resumenMovimientos.totales.cantidad_egresos} movimiento(s)
                              </p>
                            </div>
                            <div className="p-2 bg-red-100 rounded-lg">
                              <AlertTriangle className="w-5 h-5 text-red-600" />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Desglose de movimientos */}
                    <div className="bg-blue-50 rounded-xl p-6">
                      <h3 className="text-lg font-semibold text-blue-800 mb-4">📊 Desglose de Movimientos</h3>
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {/* Ventas en efectivo */}
                        <div className="bg-white rounded-lg p-4 border border-blue-200">
                          <div className="text-center">
                            <div className="p-3 bg-blue-100 rounded-full inline-block mb-3">
                              <DollarSign className="w-6 h-6 text-blue-600" />
                            </div>
                            <p className="text-sm font-medium text-blue-600">Ventas en Efectivo</p>
                            <p className="text-2xl font-bold text-blue-800">
                              ${resumenMovimientos.totales.ventas_efectivo.toLocaleString('es-AR')}
                            </p>
                            <p className="text-xs text-blue-600">
                              {resumenMovimientos.totales.cantidad_ventas_efectivo} venta(s)
                            </p>
                          </div>
                        </div>

                        {/* Ingresos manuales */}
                        <div className="bg-white rounded-lg p-4 border border-blue-200">
                          <div className="text-center">
                            <div className="p-3 bg-green-100 rounded-full inline-block mb-3">
                              <BarChart3 className="w-6 h-6 text-green-600" />
                            </div>
                            <p className="text-sm font-medium text-green-600">Ingresos Manuales</p>
                            <p className="text-2xl font-bold text-green-800">
                              ${resumenMovimientos.totales.ingresos_manuales.toLocaleString('es-AR')}
                            </p>
                            <p className="text-xs text-green-600">
                              {resumenMovimientos.totales.cantidad_ingresos} ingreso(s)
                            </p>
                          </div>
                        </div>

                        {/* Egresos */}
                        <div className="bg-white rounded-lg p-4 border border-blue-200">
                          <div className="text-center">
                            <div className="p-3 bg-red-100 rounded-full inline-block mb-3">
                              <AlertTriangle className="w-6 h-6 text-red-600" />
                            </div>
                            <p className="text-sm font-medium text-red-600">Egresos/Salidas</p>
                            <p className="text-2xl font-bold text-red-800">
                              ${resumenMovimientos.totales.egresos_totales.toLocaleString('es-AR')}
                            </p>
                            <p className="text-xs text-red-600">
                              {resumenMovimientos.totales.cantidad_egresos} egreso(s)
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Detalle de Ingresos */}
                    {resumenMovimientos.ingresos_detallados && resumenMovimientos.ingresos_detallados.length > 0 && (
                      <div className="bg-green-50 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-green-800 mb-4">📥 Ingresos de Efectivo Detallados</h3>
                        <div className="space-y-3 max-h-64 overflow-y-auto">
                          {resumenMovimientos.ingresos_detallados.map((ingreso, index) => (
                            <div key={index} className="bg-white rounded-lg p-4 border border-green-200 shadow-sm">
                              <div className="flex items-center justify-between">
                                <div className="flex-1">
                                  <div className="flex items-center mb-2">
                                    <span className="w-3 h-3 rounded-full bg-green-500 mr-3"></span>
                                    <p className="font-medium text-green-800">{ingreso.categoria}</p>
                                    <span className="ml-2 text-xs bg-green-100 text-green-600 px-2 py-1 rounded">
                                      {ingreso.fecha_formateada}
                                    </span>
                                  </div>
                                  <p className="text-sm text-gray-700 font-medium ml-6">
                                    📝 {ingreso.descripcion || 'Sin descripción'}
                                  </p>
                                  {ingreso.referencia && (
                                    <p className="text-xs text-gray-500 ml-6">
                                      Ref: {ingreso.referencia}
                                    </p>
                                  )}
                                </div>
                                <div className="text-right">
                                  <p className="text-xl font-bold text-green-600">
                                    +${parseFloat(ingreso.monto).toLocaleString('es-AR')}
                                  </p>
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                        <div className="mt-4 p-3 bg-green-100 rounded-lg">
                          <p className="text-sm text-green-700">
                            <strong>Total Ingresos:</strong> +${resumenMovimientos.totales.ingresos_manuales.toLocaleString('es-AR')} 
                            ({resumenMovimientos.ingresos_detallados.length} movimiento{resumenMovimientos.ingresos_detallados.length !== 1 ? 's' : ''})
                          </p>
                        </div>
                      </div>
                    )}

                    {/* Detalle de Egresos */}
                    {resumenMovimientos.egresos_detallados && resumenMovimientos.egresos_detallados.length > 0 && (
                      <div className="bg-red-50 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-red-800 mb-4">📤 Egresos/Salidas de Efectivo Detallados</h3>
                        <div className="space-y-3 max-h-64 overflow-y-auto">
                          {resumenMovimientos.egresos_detallados.map((egreso, index) => (
                            <div key={index} className="bg-white rounded-lg p-4 border border-red-200 shadow-sm">
                              <div className="flex items-center justify-between">
                                <div className="flex-1">
                                  <div className="flex items-center mb-2">
                                    <span className="w-3 h-3 rounded-full bg-red-500 mr-3"></span>
                                    <p className="font-medium text-red-800">{egreso.categoria}</p>
                                    <span className="ml-2 text-xs bg-red-100 text-red-600 px-2 py-1 rounded">
                                      {egreso.fecha_formateada}
                                    </span>
                                  </div>
                                  <p className="text-sm text-gray-700 font-medium ml-6">
                                    📝 {egreso.descripcion || 'Sin descripción'}
                                  </p>
                                  {egreso.referencia && (
                                    <p className="text-xs text-gray-500 ml-6">
                                      Ref: {egreso.referencia}
                                    </p>
                                  )}
                                </div>
                                <div className="text-right">
                                  <p className="text-xl font-bold text-red-600">
                                    -${Math.abs(parseFloat(egreso.monto)).toLocaleString('es-AR')}
                                  </p>
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                        <div className="mt-4 p-3 bg-red-100 rounded-lg">
                          <p className="text-sm text-red-700">
                            <strong>Total Egresos:</strong> -${resumenMovimientos.totales.egresos_totales.toLocaleString('es-AR')} 
                            ({resumenMovimientos.egresos_detallados.length} movimiento{resumenMovimientos.egresos_detallados.length !== 1 ? 's' : ''})
                          </p>
                        </div>
                      </div>
                    )}

                    {/* Flujo neto final */}
                    <div className="bg-yellow-50 rounded-xl p-6">
                      <h3 className="text-lg font-semibold text-yellow-800 mb-4">🎯 Flujo Neto del Turno</h3>
                      <div className="text-center">
                        <p className="text-sm text-yellow-600 mb-2">
                          Apertura + Ingresos + Ventas - Egresos
                        </p>
                        <p className={`text-3xl font-bold ${
                          resumenMovimientos.flujo_neto >= 0 ? 'text-green-600' : 'text-red-600'
                        }`}>
                          ${resumenMovimientos.flujo_neto.toLocaleString('es-AR')}
                        </p>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="bg-red-50 rounded-xl p-6 text-center">
                    <AlertTriangle className="w-8 h-8 text-red-600 mx-auto mb-4" />
                    <p className="text-red-600">Error al cargar el resumen de movimientos</p>
                  </div>
                )}
              </div>
              
              <div className="mt-8 text-center">
                <button
                  onClick={() => setMostrarDetalle(false)}
                  className="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium"
                >
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        )}
          </div>
        ) : pestanaActiva === 'reportes' ? (
          /* 📈 PESTAÑA DE REPORTES POR PERÍODO */
          <ReportesEfectivoPeriodo />
        ) : (
          /* 👥 PESTAÑA DE DIFERENCIAS POR CAJERO */
          <ReportesDiferenciasCajero />
        )}
      </div>
    </div>
  );
};

export default HistorialTurnosPage;
