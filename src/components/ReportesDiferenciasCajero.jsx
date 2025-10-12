/**
 * src/components/ReportesDiferenciasCajero.jsx
 * Reportes de diferencias y performance por cajero
 * Análisis de precisión, faltantes y sobrantes por operador
 * RELEVANT FILES: src/components/ReportesEfectivoPeriodo.jsx, api/gestion_caja_completa.php
 */

import React, { useState, useEffect } from 'react';
import { 
  User, 
  DollarSign, 
  TrendingUp, 
  TrendingDown, 
  Target,
  AlertTriangle,
  CheckCircle,
  Star,
  Award,
  BarChart3,
  RefreshCw,
  Download,
  Calendar,
  Filter
} from 'lucide-react';
import CONFIG from '../config/config';

const ReportesDiferenciasCajero = () => {
  const [reportesCajeros, setReportesCajeros] = useState(null);
  const [loading, setLoading] = useState(false);
  const [filtros, setFiltros] = useState({
    fecha_inicio: '',
    fecha_fin: '',
    periodo_dias: 30, // últimos 30 días por defecto
    incluir_solo_cierres: true
  });

  // 📊 Cargar reportes de cajeros
  const cargarReportesCajeros = async () => {
    try {
      setLoading(true);
      
      // Calcular fechas si no están definidas
      const fechas = calcularFechasPeriodo(filtros.periodo_dias);
      
      const params = new URLSearchParams({
        accion: 'historial_completo',
        usuario_id: 1,
        limite: 1000,
        fecha_inicio: filtros.fecha_inicio || fechas.inicio,
        fecha_fin: filtros.fecha_fin || fechas.fin,
        tipo_evento: filtros.incluir_solo_cierres ? 'cierre' : 'todos',
        _t: Date.now()
      });

      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?${params}`);
      const data = await response.json();

      if (data.success) {
        const reportesProcesados = procesarDatosPorCajero(data.historial || []);
        setReportesCajeros(reportesProcesados);
      }
    } catch (error) {
      console.error('Error cargando reportes de cajeros:', error);
    } finally {
      setLoading(false);
    }
  };

  // 📅 Calcular fechas del período
  const calcularFechasPeriodo = (dias) => {
    const fin = new Date();
    const inicio = new Date();
    inicio.setDate(fin.getDate() - dias);
    
    return {
      inicio: inicio.toISOString().split('T')[0],
      fin: fin.toISOString().split('T')[0]
    };
  };

  // 🔧 Procesar datos por cajero
  const procesarDatosPorCajero = (historial) => {
    const cajeros = {};
    
    historial.forEach(evento => {
      const cajeraNombre = evento.cajero_nombre || 'Sin asignar';
      
      if (!cajeros[cajeraNombre]) {
        cajeros[cajeraNombre] = {
          nombre: cajeraNombre,
          cajero_id: evento.cajero_id,
          eventos: [],
          estadisticas: {
            total_eventos: 0,
            total_aperturas: 0,
            total_cierres: 0,
            total_diferencias: 0,
            diferencias_positivas: 0,
            diferencias_negativas: 0,
            cierres_exactos: 0,
            monto_total_manejado: 0,
            precision_porcentaje: 0,
            mayor_diferencia: 0,
            menor_diferencia: 0
          }
        };
      }

      const cajero = cajeros[cajeraNombre];
      cajero.eventos.push(evento);
      cajero.estadisticas.total_eventos++;

      const monto = parseFloat(evento.monto_inicial || evento.efectivo_teorico || 0);
      const diferencia = parseFloat(evento.diferencia || 0);

      if (evento.tipo_evento === 'apertura') {
        cajero.estadisticas.total_aperturas++;
        cajero.estadisticas.monto_total_manejado += monto;
      } else if (evento.tipo_evento === 'cierre') {
        cajero.estadisticas.total_cierres++;
        cajero.estadisticas.monto_total_manejado += monto;
        cajero.estadisticas.total_diferencias += Math.abs(diferencia);

        if (diferencia === 0) {
          cajero.estadisticas.cierres_exactos++;
        } else if (diferencia > 0) {
          cajero.estadisticas.diferencias_positivas += diferencia;
        } else {
          cajero.estadisticas.diferencias_negativas += Math.abs(diferencia);
        }

        // Actualizar mayor y menor diferencia
        if (Math.abs(diferencia) > Math.abs(cajero.estadisticas.mayor_diferencia)) {
          cajero.estadisticas.mayor_diferencia = diferencia;
        }
        
        if (cajero.estadisticas.menor_diferencia === 0 || Math.abs(diferencia) < Math.abs(cajero.estadisticas.menor_diferencia)) {
          cajero.estadisticas.menor_diferencia = diferencia;
        }
      }
    });

    // Calcular precisión de cada cajero
    Object.values(cajeros).forEach(cajero => {
      if (cajero.estadisticas.total_cierres > 0) {
        cajero.estadisticas.precision_porcentaje = 
          (cajero.estadisticas.cierres_exactos / cajero.estadisticas.total_cierres) * 100;
      }
    });

    // Ordenar por precisión (de mayor a menor)
    const cajerosArray = Object.values(cajeros).sort((a, b) => 
      b.estadisticas.precision_porcentaje - a.estadisticas.precision_porcentaje
    );

    return {
      cajeros: cajerosArray,
      resumenGeneral: calcularResumenGeneral(cajerosArray)
    };
  };

  // 📊 Calcular resumen general
  const calcularResumenGeneral = (cajeros) => {
    return cajeros.reduce((acc, cajero) => {
      acc.total_cajeros++;
      acc.total_eventos += cajero.estadisticas.total_eventos;
      acc.total_cierres += cajero.estadisticas.total_cierres;
      acc.total_diferencias += cajero.estadisticas.total_diferencias;
      acc.diferencias_positivas += cajero.estadisticas.diferencias_positivas;
      acc.diferencias_negativas += cajero.estadisticas.diferencias_negativas;
      acc.cierres_exactos += cajero.estadisticas.cierres_exactos;
      acc.monto_total += cajero.estadisticas.monto_total_manejado;
      
      return acc;
    }, {
      total_cajeros: 0,
      total_eventos: 0,
      total_cierres: 0,
      total_diferencias: 0,
      diferencias_positivas: 0,
      diferencias_negativas: 0,
      cierres_exactos: 0,
      monto_total: 0
    });
  };

  // 🎨 Obtener color según precisión
  const obtenerColorPrecision = (precision) => {
    if (precision >= 90) return 'text-green-600';
    if (precision >= 75) return 'text-yellow-600';
    if (precision >= 50) return 'text-orange-600';
    return 'text-red-600';
  };

  // 🏆 Obtener ícono según posición
  const obtenerIconoPosicion = (index) => {
    switch (index) {
      case 0: return <Award className="w-5 h-5 text-yellow-500" />;
      case 1: return <Star className="w-5 h-5 text-gray-400" />;
      case 2: return <Target className="w-5 h-5 text-orange-500" />;
      default: return <User className="w-5 h-5 text-gray-400" />;
    }
  };

  // 📤 Exportar reporte a CSV
  const exportarCSV = () => {
    if (!reportesCajeros) return;

    const encabezados = [
      'Cajero',
      'Total Eventos',
      'Total Cierres', 
      'Cierres Exactos',
      'Precisión (%)',
      'Diferencias Positivas',
      'Diferencias Negativas',
      'Total Diferencias',
      'Monto Total Manejado',
      'Mayor Diferencia'
    ];

    const filas = reportesCajeros.cajeros.map(cajero => [
      cajero.nombre,
      cajero.estadisticas.total_eventos,
      cajero.estadisticas.total_cierres,
      cajero.estadisticas.cierres_exactos,
      cajero.estadisticas.precision_porcentaje.toFixed(2),
      cajero.estadisticas.diferencias_positivas.toFixed(2),
      cajero.estadisticas.diferencias_negativas.toFixed(2),
      cajero.estadisticas.total_diferencias.toFixed(2),
      cajero.estadisticas.monto_total_manejado.toFixed(2),
      cajero.estadisticas.mayor_diferencia.toFixed(2)
    ]);

    const csvContent = [encabezados, ...filas]
      .map(fila => fila.map(valor => `"${valor}"`).join(','))
      .join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `reporte_diferencias_cajeros_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
  };

  useEffect(() => {
    cargarReportesCajeros();
  }, [filtros.periodo_dias, filtros.incluir_solo_cierres]);

  return (
    <div className="space-y-6">
      {/* 🎛️ Controles */}
      <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
        <div className="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-800">👥 Diferencias por Cajero</h2>
            <p className="text-gray-600">Análisis de precisión y performance</p>
          </div>
          
          <div className="flex flex-wrap gap-3">
            {/* Período en días */}
            <select
              value={filtros.periodo_dias}
              onChange={(e) => setFiltros(prev => ({ ...prev, periodo_dias: parseInt(e.target.value) }))}
              className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value={7}>Últimos 7 días</option>
              <option value={15}>Últimos 15 días</option>
              <option value={30}>Últimos 30 días</option>
              <option value={90}>Últimos 90 días</option>
            </select>

            {/* Solo cierres */}
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={filtros.incluir_solo_cierres}
                onChange={(e) => setFiltros(prev => ({ ...prev, incluir_solo_cierres: e.target.checked }))}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm text-gray-700">Solo cierres</span>
            </label>

            {/* Botones de acción */}
            <button
              onClick={cargarReportesCajeros}
              disabled={loading}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              Actualizar
            </button>

            <button
              onClick={exportarCSV}
              disabled={!reportesCajeros}
              className="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md disabled:opacity-50"
            >
              <Download className="w-4 h-4" />
              Exportar CSV
            </button>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="bg-white rounded-lg p-12 shadow-sm border border-gray-200">
          <div className="flex items-center justify-center">
            <RefreshCw className="w-8 h-8 text-blue-600 animate-spin mr-3" />
            <span className="text-gray-600">Cargando reportes de cajeros...</span>
          </div>
        </div>
      ) : reportesCajeros ? (
        <>
          {/* 📊 Resumen general */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Cajeros</p>
                  <p className="text-2xl font-bold text-blue-600">{reportesCajeros.resumenGeneral.total_cajeros}</p>
                </div>
                <User className="w-8 h-8 text-blue-600" />
              </div>
            </div>

            <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Cierres Exactos</p>
                  <p className="text-2xl font-bold text-green-600">{reportesCajeros.resumenGeneral.cierres_exactos}</p>
                  <p className="text-sm text-gray-500">
                    de {reportesCajeros.resumenGeneral.total_cierres} totales
                  </p>
                </div>
                <CheckCircle className="w-8 h-8 text-green-600" />
              </div>
            </div>

            <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Diferencias +</p>
                  <p className="text-2xl font-bold text-orange-600">
                    ${reportesCajeros.resumenGeneral.diferencias_positivas.toLocaleString('es-AR')}
                  </p>
                </div>
                <TrendingUp className="w-8 h-8 text-orange-600" />
              </div>
            </div>

            <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Diferencias -</p>
                  <p className="text-2xl font-bold text-red-600">
                    ${reportesCajeros.resumenGeneral.diferencias_negativas.toLocaleString('es-AR')}
                  </p>
                </div>
                <TrendingDown className="w-8 h-8 text-red-600" />
              </div>
            </div>
          </div>

          {/* 🏆 Ranking de cajeros */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-800">🏆 Ranking por Precisión</h3>
            </div>
            
            <div className="divide-y divide-gray-200">
              {reportesCajeros.cajeros.map((cajero, index) => (
                <div key={cajero.cajero_id} className="p-6 hover:bg-gray-50">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <div className="flex items-center gap-2">
                        {obtenerIconoPosicion(index)}
                        <span className="font-medium text-gray-900">{cajero.nombre}</span>
                      </div>
                      
                      <div className="flex items-center gap-4 text-sm text-gray-600">
                        <span>{cajero.estadisticas.total_cierres} cierres</span>
                        <span>{cajero.estadisticas.cierres_exactos} exactos</span>
                      </div>
                    </div>

                    <div className="flex items-center gap-4">
                      <div className="text-right">
                        <p className={`text-lg font-bold ${obtenerColorPrecision(cajero.estadisticas.precision_porcentaje)}`}>
                          {cajero.estadisticas.precision_porcentaje.toFixed(1)}%
                        </p>
                        <p className="text-sm text-gray-500">Precisión</p>
                      </div>

                      <div className="text-right">
                        <p className="text-lg font-bold text-gray-900">
                          ${cajero.estadisticas.total_diferencias.toLocaleString('es-AR')}
                        </p>
                        <p className="text-sm text-gray-500">Total diferencias</p>
                      </div>
                    </div>
                  </div>

                  {/* Detalles expandibles */}
                  <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p className="text-gray-500">Sobrantes</p>
                      <p className="font-medium text-orange-600">
                        ${cajero.estadisticas.diferencias_positivas.toLocaleString('es-AR')}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500">Faltantes</p>
                      <p className="font-medium text-red-600">
                        ${cajero.estadisticas.diferencias_negativas.toLocaleString('es-AR')}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500">Mayor diferencia</p>
                      <p className={`font-medium ${cajero.estadisticas.mayor_diferencia >= 0 ? 'text-orange-600' : 'text-red-600'}`}>
                        {cajero.estadisticas.mayor_diferencia >= 0 ? '+' : ''}${cajero.estadisticas.mayor_diferencia.toLocaleString('es-AR')}
                      </p>
                    </div>
                    <div>
                      <p className="text-gray-500">Monto manejado</p>
                      <p className="font-medium text-gray-900">
                        ${cajero.estadisticas.monto_total_manejado.toLocaleString('es-AR')}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </>
      ) : (
        <div className="bg-white rounded-lg p-12 shadow-sm border border-gray-200 text-center">
          <User className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-600">No hay datos de cajeros para mostrar</p>
          <button
            onClick={cargarReportesCajeros}
            className="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
          >
            Cargar reportes
          </button>
        </div>
      )}
    </div>
  );
};

export default ReportesDiferenciasCajero;
















