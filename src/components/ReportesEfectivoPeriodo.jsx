/**
 * src/components/ReportesEfectivoPeriodo.jsx
 * Reportes detallados de efectivo por día/semana/mes
 * Análisis de tendencias y estadísticas de flujo de caja
 * RELEVANT FILES: src/components/HistorialTurnosPage.jsx, api/gestion_caja_completa.php
 */

import React, { useState, useEffect } from 'react';
import { 
  Calendar, 
  DollarSign, 
  TrendingUp, 
  TrendingDown, 
  BarChart3,
  FileText,
  Download,
  RefreshCw,
  Filter,
  User,
  Clock,
  CheckCircle,
  AlertTriangle,
  Target
} from 'lucide-react';
import CONFIG from '../config/config';

// 📈 Componente de Análisis de Tendencias
const AnalisisTendencias = ({ grupos, periodo }) => {
  // Calcular tendencias
  const calcularTendencias = () => {
    if (grupos.length < 3) return null;

    // Ordenar cronológicamente (más antiguo primero)
    const gruposOrdenados = [...grupos].reverse();

    // Calcular cambios período a período
    const cambios = gruposOrdenados.slice(1).map((grupo, index) => {
      const anterior = gruposOrdenados[index];
      return {
        fecha: grupo.fecha,
        cambio_diferencias: grupo.diferenciasTotal - anterior.diferenciasTotal,
        cambio_precislon: ((grupo.cierres > 0 ? (grupo.cierres - (grupo.diferenciasTotal !== 0 ? 1 : 0)) / grupo.cierres : 1) * 100) - 
                         ((anterior.cierres > 0 ? (anterior.cierres - (anterior.diferenciasTotal !== 0 ? 1 : 0)) / anterior.cierres : 1) * 100),
        cambio_monto: grupo.montoFinalTotal - anterior.montoFinalTotal
      };
    });

    // Promedios de cambios
    const promedioCambioDiferencias = cambios.reduce((acc, c) => acc + c.cambio_diferencias, 0) / cambios.length;
    const promedioCambioPrecision = cambios.reduce((acc, c) => acc + c.cambio_precislon, 0) / cambios.length;
    const promedioCambioMonto = cambios.reduce((acc, c) => acc + c.cambio_monto, 0) / cambios.length;

    // Determinar tendencias
    const tendenciaDiferencias = promedioCambioDiferencias > 500 ? 'empeorando' : 
                                promedioCambioDiferencias < -500 ? 'mejorando' : 'estable';
    
    const tendenciaPrecision = promedioCambioPrecision > 5 ? 'mejorando' : 
                              promedioCambioPrecision < -5 ? 'empeorando' : 'estable';

    const tendenciaMonto = promedioCambioMonto > 10000 ? 'creciendo' :
                          promedioCambioMonto < -10000 ? 'decreciendo' : 'estable';

    return {
      diferencias: {
        tendencia: tendenciaDiferencias,
        promedio: promedioCambioDiferencias,
        ultimo: cambios[cambios.length - 1]?.cambio_diferencias || 0
      },
      precision: {
        tendencia: tendenciaPrecision,
        promedio: promedioCambioPrecision,
        ultimo: cambios[cambios.length - 1]?.cambio_precislon || 0
      },
      monto: {
        tendencia: tendenciaMonto,
        promedio: promedioCambioMonto,
        ultimo: cambios[cambios.length - 1]?.cambio_monto || 0
      },
      periodos_analizados: cambios.length
    };
  };

  const tendencias = calcularTendencias();

  if (!tendencias) {
    return <div className="text-gray-500">Datos insuficientes para análisis</div>;
  }

  // 🎨 Componente de tarjeta de tendencia
  const TarjetaTendencia = ({ titulo, tendencia, valor, icono: IconComponent, descripcion }) => {
    const obtenerColor = () => {
      switch (tendencia) {
        case 'mejorando':
        case 'creciendo':
          return 'text-green-600';
        case 'empeorando':
        case 'decreciendo':
          return 'text-red-600';
        default:
          return 'text-gray-600';
      }
    };

    const obtenerIconoTendencia = () => {
      switch (tendencia) {
        case 'mejorando':
        case 'creciendo':
          return <TrendingUp className="w-4 h-4 text-green-500" />;
        case 'empeorando':
        case 'decreciendo':
          return <TrendingDown className="w-4 h-4 text-red-500" />;
        default:
          return <Target className="w-4 h-4 text-gray-500" />;
      }
    };

    return (
      <div className="bg-gray-50 rounded-lg p-4">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-2">
            <IconComponent className="w-5 h-5 text-gray-400" />
            <span className="font-medium text-gray-700">{titulo}</span>
          </div>
          {obtenerIconoTendencia()}
        </div>
        
        <p className={`text-lg font-bold ${obtenerColor()} mb-1`}>
          {typeof valor === 'number' && Math.abs(valor) > 100 ? 
            `$${Math.abs(valor).toLocaleString('es-AR')}` : 
            `${valor.toFixed(1)}${titulo.includes('Precisión') ? '%' : ''}`}
        </p>
        
        <p className="text-sm text-gray-600">{descripcion}</p>
        
        <div className="mt-2 text-xs text-gray-500">
          Tendencia: <span className={`font-medium ${obtenerColor()}`}>
            {tendencia === 'mejorando' ? '📈 Mejorando' :
             tendencia === 'empeorando' ? '📉 Empeorando' :
             tendencia === 'creciendo' ? '📈 Creciendo' :
             tendencia === 'decreciendo' ? '📉 Decreciendo' :
             '➡️ Estable'}
          </span>
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <TarjetaTendencia
          titulo="Diferencias"
          tendencia={tendencias.diferencias.tendencia}
          valor={tendencias.diferencias.ultimo}
          icono={DollarSign}
          descripcion={`Promedio ${tendencias.diferencias.promedio >= 0 ? '+' : ''}$${tendencias.diferencias.promedio.toFixed(0)} por ${periodo}`}
        />
        
        <TarjetaTendencia
          titulo="Precisión"
          tendencia={tendencias.precision.tendencia}
          valor={tendencias.precision.ultimo}
          icono={Target}
          descripcion={`Cambio promedio ${tendencias.precision.promedio >= 0 ? '+' : ''}${tendencias.precision.promedio.toFixed(1)}% por ${periodo}`}
        />
        
        <TarjetaTendencia
          titulo="Volumen"
          tendencia={tendencias.monto.tendencia}
          valor={tendencias.monto.ultimo}
          icono={BarChart3}
          descripcion={`Promedio ${tendencias.monto.promedio >= 0 ? '+' : ''}$${tendencias.monto.promedio.toFixed(0)} por ${periodo}`}
        />
      </div>

      {/* 📊 Interpretación y recomendaciones */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 className="font-semibold text-blue-800 mb-2">💡 Interpretación y Recomendaciones</h4>
        <div className="space-y-2 text-sm text-blue-700">
          {tendencias.diferencias.tendencia === 'empeorando' && (
            <p>⚠️ Las diferencias están aumentando. Revisar procedimientos de conteo y capacitación de cajeros.</p>
          )}
          {tendencias.diferencias.tendencia === 'mejorando' && (
            <p>✅ Las diferencias están disminuyendo. Mantener las buenas prácticas actuales.</p>
          )}
          {tendencias.precision.tendencia === 'empeorando' && (
            <p>📉 La precisión está bajando. Considerar refuerzo en capacitación y supervisión.</p>
          )}
          {tendencias.precision.tendencia === 'mejorando' && (
            <p>📈 La precisión está mejorando. Excelente trabajo del equipo.</p>
          )}
          {tendencias.monto.tendencia === 'creciendo' && (
            <p>💰 El volumen de efectivo está creciendo. Considerar ajustes en políticas de caja.</p>
          )}
          {tendencias.diferencias.tendencia === 'estable' && tendencias.precision.tendencia === 'estable' && (
            <p>➡️ El sistema mantiene estabilidad. Continuar con monitoreo regular.</p>
          )}
        </div>
      </div>
      
      <div className="text-xs text-gray-500 text-center">
        Análisis basado en {tendencias.periodos_analizados} períodos de comparación
      </div>
    </div>
  );
};

const ReportesEfectivoPeriodo = () => {
  const [reportes, setReportes] = useState(null);
  const [loading, setLoading] = useState(false);
  const [filtros, setFiltros] = useState({
    periodo: 'semana', // dia, semana, mes
    fecha_inicio: '',
    fecha_fin: '',
    cajero_id: '',
    incluir_diferencias: true
  });

  // 📊 Cargar reportes
  const cargarReportes = async () => {
    try {
      setLoading(true);
      
      // Calcular fechas automáticamente si no están definidas
      const fechas = calcularFechasPorPeriodo(filtros.periodo);
      
      const params = new URLSearchParams({
        accion: 'historial_completo',
        usuario_id: 1,
        limite: 1000,
        fecha_inicio: filtros.fecha_inicio || fechas.inicio,
        fecha_fin: filtros.fecha_fin || fechas.fin,
        ...(filtros.cajero_id && { cajero_id: filtros.cajero_id }),
        _t: Date.now()
      });

      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?${params}`);
      const data = await response.json();

      if (data.success) {
        const reportesProcesados = procesarDatosParaReportes(data.historial || [], filtros.periodo);
        setReportes(reportesProcesados);
      }
    } catch (error) {
      console.error('Error cargando reportes:', error);
    } finally {
      setLoading(false);
    }
  };

  // 📅 Calcular fechas por período
  const calcularFechasPorPeriodo = (periodo) => {
    const hoy = new Date();
    let inicio, fin;

    switch (periodo) {
      case 'dia':
        inicio = new Date(hoy);
        inicio.setHours(0, 0, 0, 0);
        fin = new Date(hoy);
        fin.setHours(23, 59, 59, 999);
        break;
        
      case 'semana':
        const inicioSemana = new Date(hoy);
        inicioSemana.setDate(hoy.getDate() - hoy.getDay());
        inicioSemana.setHours(0, 0, 0, 0);
        
        const finSemana = new Date(inicioSemana);
        finSemana.setDate(inicioSemana.getDate() + 6);
        finSemana.setHours(23, 59, 59, 999);
        
        inicio = inicioSemana;
        fin = finSemana;
        break;
        
      case 'mes':
        inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        fin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        fin.setHours(23, 59, 59, 999);
        break;
        
      default:
        inicio = new Date(hoy);
        inicio.setDate(hoy.getDate() - 7);
        fin = hoy;
    }

    return {
      inicio: inicio.toISOString().split('T')[0],
      fin: fin.toISOString().split('T')[0]
    };
  };

  // 🔧 Procesar datos para reportes
  const procesarDatosParaReportes = (historial, periodo) => {
    const grupos = {};
    const resumenGeneral = {
      totalEventos: historial.length,
      totalAperturas: 0,
      totalCierres: 0,
      diferenciasPositivas: 0,
      diferenciasNegativas: 0,
      cajeros: new Set(),
      montoInicialTotal: 0,
      montoFinalTotal: 0
    };

    // Agrupar por período
    historial.forEach(evento => {
      const fecha = new Date(evento.fecha_hora);
      let clavePeriodo;

      switch (periodo) {
        case 'dia':
          clavePeriodo = fecha.toISOString().split('T')[0];
          break;
        case 'semana':
          const inicioSemana = new Date(fecha);
          inicioSemana.setDate(fecha.getDate() - fecha.getDay());
          clavePeriodo = `Semana del ${inicioSemana.toLocaleDateString('es-AR')}`;
          break;
        case 'mes':
          clavePeriodo = `${fecha.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' })}`;
          break;
        default:
          clavePeriodo = fecha.toISOString().split('T')[0];
      }

      if (!grupos[clavePeriodo]) {
        grupos[clavePeriodo] = {
          fecha: clavePeriodo,
          eventos: [],
          aperturas: 0,
          cierres: 0,
          montoInicialTotal: 0,
          montoFinalTotal: 0,
          diferenciasTotal: 0,
          diferenciasPositivas: 0,
          diferenciasNegativas: 0,
          cajeros: new Set()
        };
      }

      const grupo = grupos[clavePeriodo];
      grupo.eventos.push(evento);
      grupo.cajeros.add(evento.cajero_nombre);

      const monto = parseFloat(evento.monto_inicial || evento.efectivo_teorico || 0);
      const diferencia = parseFloat(evento.diferencia || 0);

      if (evento.tipo_evento === 'apertura') {
        grupo.aperturas++;
        grupo.montoInicialTotal += monto;
        resumenGeneral.totalAperturas++;
      } else if (evento.tipo_evento === 'cierre') {
        grupo.cierres++;
        grupo.montoFinalTotal += monto;
        grupo.diferenciasTotal += diferencia;
        
        if (diferencia > 0) {
          grupo.diferenciasPositivas += diferencia;
          resumenGeneral.diferenciasPositivas += diferencia;
        } else if (diferencia < 0) {
          grupo.diferenciasNegativas += Math.abs(diferencia);
          resumenGeneral.diferenciasNegativas += Math.abs(diferencia);
        }
        
        resumenGeneral.totalCierres++;
      }

      resumenGeneral.cajeros.add(evento.cajero_nombre);
      resumenGeneral.montoInicialTotal += evento.tipo_evento === 'apertura' ? monto : 0;
      resumenGeneral.montoFinalTotal += evento.tipo_evento === 'cierre' ? monto : 0;
    });

    // Convertir Sets a arrays para serialización
    Object.values(grupos).forEach(grupo => {
      grupo.cajeros = Array.from(grupo.cajeros);
    });
    resumenGeneral.cajeros = Array.from(resumenGeneral.cajeros);

    return {
      grupos: Object.values(grupos).sort((a, b) => new Date(b.fecha) - new Date(a.fecha)),
      resumen: resumenGeneral
    };
  };

  // 📤 Exportar reporte a CSV
  const exportarCSV = () => {
    if (!reportes) return;

    const encabezados = [
      'Período',
      'Aperturas',
      'Cierres',
      'Monto Inicial Total',
      'Monto Final Total',
      'Diferencias Total',
      'Sobrantes',
      'Faltantes',
      'Cajeros'
    ];

    const filas = reportes.grupos.map(grupo => [
      grupo.fecha,
      grupo.aperturas,
      grupo.cierres,
      grupo.montoInicialTotal.toFixed(2),
      grupo.montoFinalTotal.toFixed(2),
      grupo.diferenciasTotal.toFixed(2),
      grupo.diferenciasPositivas.toFixed(2),
      grupo.diferenciasNegativas.toFixed(2),
      grupo.cajeros.join('; ')
    ]);

    const csvContent = [encabezados, ...filas]
      .map(fila => fila.map(valor => `"${valor}"`).join(','))
      .join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `reporte_efectivo_${filtros.periodo}_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
  };

  useEffect(() => {
    cargarReportes();
  }, [filtros.periodo]);

  // 📊 Tarjeta de métricas
  const TarjetaMetrica = ({ titulo, valor, icono: IconComponent, color, cambio = null }) => (
    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{titulo}</p>
          <p className={`text-2xl font-bold mt-1 ${color}`}>{valor}</p>
          {cambio && (
            <p className={`text-sm mt-1 ${cambio.positivo ? 'text-green-600' : 'text-red-600'}`}>
              {cambio.positivo ? '↗' : '↘'} {cambio.valor}
            </p>
          )}
        </div>
        <IconComponent className={`w-8 h-8 ${color}`} />
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      {/* 🎛️ Controles y filtros */}
      <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
        <div className="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-800">📈 Reportes de Efectivo</h2>
            <p className="text-gray-600">Análisis por período sin gráficos</p>
          </div>
          
          <div className="flex flex-wrap gap-3">
            {/* Período */}
            <select
              value={filtros.periodo}
              onChange={(e) => setFiltros(prev => ({ ...prev, periodo: e.target.value }))}
              className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="dia">Por Día</option>
              <option value="semana">Por Semana</option>
              <option value="mes">Por Mes</option>
            </select>

            {/* Botones de acción */}
            <button
              onClick={cargarReportes}
              disabled={loading}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              Actualizar
            </button>

            <button
              onClick={exportarCSV}
              disabled={!reportes}
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
            <span className="text-gray-600">Cargando reportes...</span>
          </div>
        </div>
      ) : reportes ? (
        <>
          {/* 📊 Resumen general */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <TarjetaMetrica
              titulo="Total de Eventos"
              valor={reportes.resumen.totalEventos}
              icono={BarChart3}
              color="text-blue-600"
            />
            
            <TarjetaMetrica
              titulo="Monto Inicial"
              valor={`$${reportes.resumen.montoInicialTotal.toLocaleString('es-AR')}`}
              icono={DollarSign}
              color="text-green-600"
            />
            
            <TarjetaMetrica
              titulo="Diferencias +"
              valor={`$${reportes.resumen.diferenciasPositivas.toLocaleString('es-AR')}`}
              icono={TrendingUp}
              color="text-orange-600"
            />
            
            <TarjetaMetrica
              titulo="Diferencias -"
              valor={`$${reportes.resumen.diferenciasNegativas.toLocaleString('es-AR')}`}
              icono={TrendingDown}
              color="text-red-600"
            />
          </div>

          {/* 📈 ANÁLISIS DE TENDENCIAS */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">📈 Análisis de Tendencias</h3>
            {reportes.grupos.length >= 3 ? (
              <AnalisisTendencias grupos={reportes.grupos} periodo={filtros.periodo} />
            ) : (
              <div className="text-center py-8 text-gray-500">
                <BarChart3 className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p>Se necesitan al menos 3 períodos para análisis de tendencias</p>
              </div>
            )}
          </div>

          {/* 📋 Detalles por período */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-800">
                📅 Detalle por {filtros.periodo}
              </h3>
            </div>
            
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Período
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Eventos
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Monto Inicial
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Monto Final
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Diferencias
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Cajeros
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {reportes.grupos.map((grupo, index) => (
                    <tr key={index} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {grupo.fecha}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div className="flex gap-4">
                          <span className="text-green-600">{grupo.aperturas} aperturas</span>
                          <span className="text-red-600">{grupo.cierres} cierres</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${grupo.montoInicialTotal.toLocaleString('es-AR')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${grupo.montoFinalTotal.toLocaleString('es-AR')}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <div className="space-y-1">
                          {grupo.diferenciasPositivas > 0 && (
                            <div className="text-orange-600">
                              +${grupo.diferenciasPositivas.toLocaleString('es-AR')}
                            </div>
                          )}
                          {grupo.diferenciasNegativas > 0 && (
                            <div className="text-red-600">
                              -${grupo.diferenciasNegativas.toLocaleString('es-AR')}
                            </div>
                          )}
                          {grupo.diferenciasTotal === 0 && (
                            <div className="text-green-600 flex items-center">
                              <CheckCircle className="w-4 h-4 mr-1" />
                              Sin diferencias
                            </div>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-500">
                        {grupo.cajeros.join(', ')}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      ) : (
        <div className="bg-white rounded-lg p-12 shadow-sm border border-gray-200 text-center">
          <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-600">No hay datos para mostrar</p>
          <button
            onClick={cargarReportes}
            className="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
          >
            Cargar reportes
          </button>
        </div>
      )}
    </div>
  );
};

export default ReportesEfectivoPeriodo;
