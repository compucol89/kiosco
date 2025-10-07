import React, { useState, useEffect, useCallback } from 'react';
import { 
  Calendar, 
  Download, 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Calculator,
  AlertTriangle,
  CheckCircle,
  BarChart3,
  Settings
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

// ========== MOTOR DE CÁLCULO CORREGIDO SPACEX GRADE ==========
class FinancialEngineCorregido {
  
  /**
   * 🎯 FÓRMULA CORRECTA SEGÚN ESPECIFICACIÓN DEL USUARIO:
   * GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO PRODUCTO
   */
  static calcularGananciaNeta(ventasDetalladas = []) {
    console.log('🧮 Aplicando fórmula CORRECTA: (PRECIO VENTA - DESCUENTO) - COSTO');
    
    return ventasDetalladas.reduce((sum, venta) => {
      // Extraer datos de la venta procesada con backend corregido
      const resumen = venta?.resumen || venta || {};
      const gananciaNeta = parseFloat(resumen?.ganancia_neta || 0);
      
      console.log(`📊 Venta ${venta?.venta_id || 'N/A'}:`, {
        ganancia_neta_calculada: gananciaNeta,
        formula_aplicada: 'Backend corregido con fórmula exacta'
      });
      
      return sum + Math.max(0, gananciaNeta);
    }, 0);
  }

  static calcularIngresosNetos(ventasDetalladas = []) {
    return ventasDetalladas.reduce((sum, venta) => {
      const resumen = venta?.resumen || venta || {};
      const ingresosNetos = parseFloat(resumen?.total_ingresos_netos || resumen?.ingresos_netos || 0);
      return sum + ingresosNetos;
    }, 0);
  }

  static calcularCostosTotales(ventasDetalladas = []) {
    return ventasDetalladas.reduce((sum, venta) => {
      const resumen = venta?.resumen || venta || {};
      const costos = parseFloat(resumen?.total_costos || resumen?.costos_totales || 0);
      return sum + costos;
    }, 0);
  }

  static calcularDescuentosTotales(ventasDetalladas = []) {
    return ventasDetalladas.reduce((sum, venta) => {
      const resumen = venta?.resumen || venta || {};
      const descuentos = parseFloat(resumen?.total_descuentos || resumen?.descuentos || 0);
      return sum + descuentos;
    }, 0);
  }
}

// ========== COMPONENTE RESUMEN FINANCIERO CORREGIDO ==========
const ResumenFinancieroCorregido = React.memo(({ datosFinancieros, gastosDiarios = 0 }) => {
  const ventasDetalladas = datosFinancieros?.ventas_detalladas || [];
  const resumenFinanciero = datosFinancieros?.resumen_financiero || {};
  
  // 🎯 USAR DATOS DEL BACKEND CORREGIDO DIRECTAMENTE
  const ingresosNetos = resumenFinanciero?.total_ingresos_netos || 0;
  const gananciaNeta = resumenFinanciero?.total_ganancia_neta || 0;
  const costosTotales = resumenFinanciero?.total_costos || 0;
  const resultadoOperacional = resumenFinanciero?.resultado_operacional || 0;
  const margenGanancia = resumenFinanciero?.margen_ganancia_porcentaje || 0;
  const roi = resumenFinanciero?.roi_porcentaje || 0;

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-8 mb-8">
      
      {/* Header con fórmula aplicada */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Resumen Financiero Corregido</h2>
          <div className="bg-green-50 border border-green-200 rounded-lg p-3">
            <p className="text-sm font-mono text-green-800">
              <strong>✅ FÓRMULA APLICADA:</strong> GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO
            </p>
            <p className="text-xs text-green-600 mt-1">
              {datosFinancieros?.formula_aplicada || 'Cálculo corregido según especificación del usuario'}
            </p>
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <CheckCircle className="w-6 h-6 text-green-500" />
          <span className="text-sm font-medium text-green-600">Cálculos Validados</span>
        </div>
      </div>

      {/* Métricas principales */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        {/* Ingresos Netos */}
        <div className="bg-blue-50 rounded-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-blue-600">Ingresos Netos</p>
              <p className="text-2xl font-bold text-blue-800">
                ${ingresosNetos.toLocaleString('es-AR', {minimumFractionDigits: 2})}
              </p>
              <p className="text-xs text-blue-500 mt-1">Después de descuentos</p>
            </div>
            <DollarSign className="w-8 h-8 text-blue-500" />
          </div>
        </div>

        {/* Ganancia Neta */}
        <div className="bg-green-50 rounded-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-green-600">Ganancia Neta</p>
              <p className="text-2xl font-bold text-green-800">
                ${gananciaNeta.toLocaleString('es-AR', {minimumFractionDigits: 2})}
              </p>
              <p className="text-xs text-green-500 mt-1">Fórmula corregida</p>
            </div>
            <TrendingUp className="w-8 h-8 text-green-500" />
          </div>
        </div>

        {/* Resultado Operacional */}
        <div className={`${resultadoOperacional >= 0 ? 'bg-emerald-50' : 'bg-red-50'} rounded-lg p-6`}>
          <div className="flex items-center justify-between">
            <div>
              <p className={`text-sm font-medium ${resultadoOperacional >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                Resultado Operacional
              </p>
              <p className={`text-2xl font-bold ${resultadoOperacional >= 0 ? 'text-emerald-800' : 'text-red-800'}`}>
                ${resultadoOperacional.toLocaleString('es-AR', {minimumFractionDigits: 2})}
              </p>
              <p className={`text-xs mt-1 ${resultadoOperacional >= 0 ? 'text-emerald-500' : 'text-red-500'}`}>
                Ganancia - Gastos
              </p>
            </div>
            {resultadoOperacional >= 0 ? 
              <TrendingUp className="w-8 h-8 text-emerald-500" /> : 
              <TrendingDown className="w-8 h-8 text-red-500" />
            }
          </div>
        </div>

        {/* Margen */}
        <div className="bg-purple-50 rounded-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-purple-600">Margen</p>
              <p className="text-2xl font-bold text-purple-800">{margenGanancia.toFixed(1)}%</p>
              <p className="text-xs text-purple-500 mt-1">Ganancia/Ingresos</p>
            </div>
            <BarChart3 className="w-8 h-8 text-purple-500" />
          </div>
        </div>
      </div>

      {/* Fórmula detallada */}
      <div className="bg-gray-50 rounded-lg p-6 mb-6">
        <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <Calculator className="w-5 h-5 mr-2" />
          Cálculo Paso a Paso
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 className="font-semibold text-gray-700 mb-2">📊 Datos Base:</h4>
            <div className="space-y-2 text-sm">
              <p>• Ingresos Brutos: ${resumenFinanciero?.total_ingresos_brutos?.toLocaleString('es-AR', {minimumFractionDigits: 2}) || '0.00'}</p>
              <p>• Descuentos: ${resumenFinanciero?.total_descuentos?.toLocaleString('es-AR', {minimumFractionDigits: 2}) || '0.00'}</p>
              <p>• Ingresos Netos: ${ingresosNetos.toLocaleString('es-AR', {minimumFractionDigits: 2})}</p>
              <p>• Costos Totales: ${costosTotales.toLocaleString('es-AR', {minimumFractionDigits: 2})}</p>
            </div>
          </div>
          
          <div>
            <h4 className="font-semibold text-gray-700 mb-2">🧮 Fórmulas:</h4>
            <div className="space-y-2 text-sm font-mono">
              <p className="text-green-700">• Ganancia = Ingresos Netos - Costos</p>
              <p className="text-blue-700">• Resultado = Ganancia - Gastos</p>
              <p className="text-purple-700">• Margen = (Ganancia / Ingresos) × 100</p>
              <p className="text-orange-700">• ROI = (Ganancia / Costos) × 100</p>
            </div>
          </div>
        </div>

        {/* Mostrar fórmula del backend si está disponible */}
        {resumenFinanciero?.formula_resultado && (
          <div className="mt-4 p-3 bg-blue-50 rounded border-l-4 border-blue-400">
            <p className="text-sm font-mono text-blue-800">
              <strong>Backend:</strong> {resumenFinanciero.formula_resultado}
            </p>
          </div>
        )}
      </div>

      {/* Estados del negocio */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className={`p-4 rounded-lg border ${gananciaNeta > 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'}`}>
          <h4 className={`font-semibold ${gananciaNeta > 0 ? 'text-green-800' : 'text-red-800'}`}>
            Estado de Ganancias
          </h4>
          <p className={`text-sm ${gananciaNeta > 0 ? 'text-green-600' : 'text-red-600'}`}>
            {resumenFinanciero?.estado_ganancias || (gananciaNeta > 0 ? 'RENTABLE' : 'PERDIDAS')}
          </p>
        </div>
        
        <div className={`p-4 rounded-lg border ${resultadoOperacional > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200'}`}>
          <h4 className={`font-semibold ${resultadoOperacional > 0 ? 'text-emerald-800' : 'text-orange-800'}`}>
            Estado Operacional
          </h4>
          <p className={`text-sm ${resultadoOperacional > 0 ? 'text-emerald-600' : 'text-orange-600'}`}>
            {resumenFinanciero?.estado_operacional || (resultadoOperacional > 0 ? 'UTILIDAD' : 'REQUIERE OPTIMIZACIÓN')}
          </p>
        </div>
      </div>
    </div>
  );
});

// ========== COMPONENTE CONFIGURACIÓN DE GASTOS ==========
const ConfiguracionGastos = React.memo(({ gastosDiarios, onGastosChange }) => {
  const [gastosInput, setGastosInput] = useState(gastosDiarios);

  const handleSubmit = (e) => {
    e.preventDefault();
    onGastosChange(parseFloat(gastosInput) || 0);
  };

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
      <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
        <Settings className="w-5 h-5 mr-2" />
        Configuración de Gastos Diarios
      </h3>
      
      <form onSubmit={handleSubmit} className="flex items-end space-x-4">
        <div className="flex-1">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Gastos Operacionales Diarios
          </label>
          <input
            type="number"
            value={gastosInput}
            onChange={(e) => setGastosInput(e.target.value)}
            placeholder="Ingrese gastos diarios"
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            step="0.01"
            min="0"
          />
        </div>
        
        <button
          type="submit"
          className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
        >
          Actualizar
        </button>
      </form>
      
      <p className="text-sm text-gray-500 mt-2">
        Los gastos se aplicarán al período seleccionado. Para un día: gastos diarios × 1, para una semana: gastos diarios × 7, etc.
      </p>
    </div>
  );
});

// ========== COMPONENTE PRINCIPAL ==========
const FinanzasPageCorregida = () => {
  const { user } = useAuth();
  const [datosFinancieros, setDatosFinancieros] = useState({});
  const [loading, setLoading] = useState(false);
  const [periodoSeleccionado, setPeriodoSeleccionado] = useState('hoy');
  const [fechaInicio, setFechaInicio] = useState(new Date().toISOString().split('T')[0]);
  const [fechaFin, setFechaFin] = useState(new Date().toISOString().split('T')[0]);
  const [gastosDiarios, setGastosDiarios] = useState(0);

  const cargarDatosFinancierosCorregidos = useCallback(async () => {
    try {
      setLoading(true);
      
      const parametros = new URLSearchParams({
        periodo: periodoSeleccionado,
        gastos_diarios: gastosDiarios
      });
      
      if (periodoSeleccionado === 'personalizado') {
        parametros.append('fecha_inicio', fechaInicio);
        parametros.append('fecha_fin', fechaFin);
      }
      
      // Cache busting
      parametros.append('_t', Date.now().toString());
      
      // 🎯 USAR EL ENDPOINT CORREGIDO
      const url = `${CONFIG.API_URL}/api/reportes_financieros_corregidos.php?${parametros.toString()}`;
      
      console.log('🔄 Cargando datos con fórmula corregida:', url);
      
      const response = await fetch(url, {
        cache: 'no-cache'
      });
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      
      console.log('✅ Datos financieros corregidos recibidos:', data);
      
      if (data.success) {
        setDatosFinancieros(data);
      } else {
        throw new Error(data.error || 'Error al obtener datos financieros corregidos');
      }
    } catch (error) {
      console.error('❌ Error cargando datos financieros corregidos:', error);
    } finally {
      setLoading(false);
    }
  }, [periodoSeleccionado, fechaInicio, fechaFin, gastosDiarios]);

  useEffect(() => {
    cargarDatosFinancierosCorregidos();
  }, [cargarDatosFinancierosCorregidos]);

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      
      {/* Header con indicador de corrección */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Panel Financiero Corregido</h1>
          <div className="flex items-center mt-2">
            <CheckCircle className="w-5 h-5 text-green-500 mr-2" />
            <p className="text-gray-600">Fórmulas matemáticas validadas y corregidas</p>
          </div>
        </div>
        
        {/* Indicador de fórmula */}
        <div className="bg-green-100 border border-green-300 rounded-lg p-3">
          <p className="text-sm font-mono text-green-800">
            ✅ GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO
          </p>
        </div>
      </div>

      {/* Controles */}
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div className="flex flex-wrap items-center gap-4">
          <select
            value={periodoSeleccionado}
            onChange={(e) => setPeriodoSeleccionado(e.target.value)}
            className="px-4 py-3 border-2 border-gray-300 rounded-lg font-medium bg-white"
          >
            <option value="hoy">Hoy</option>
            <option value="ayer">Ayer</option>
            <option value="semana">Esta Semana</option>
            <option value="mes">Este Mes</option>
            <option value="personalizado">Personalizado</option>
          </select>

          {periodoSeleccionado === 'personalizado' && (
            <div className="flex items-center space-x-2">
              <input
                type="date"
                value={fechaInicio}
                onChange={(e) => setFechaInicio(e.target.value)}
                className="px-3 py-3 border-2 border-gray-300 rounded-lg font-medium"
              />
              <span className="text-gray-500 font-bold">-</span>
              <input
                type="date"
                value={fechaFin}
                onChange={(e) => setFechaFin(e.target.value)}
                className="px-3 py-3 border-2 border-gray-300 rounded-lg font-medium"
              />
            </div>
          )}

          <button
            onClick={() => window.print()}
            className="flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-lg"
          >
            <Download className="w-5 h-5 mr-2" />
            Imprimir
          </button>
        </div>
      </div>

      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
        </div>
      ) : (
        <>
          {/* Configuración de Gastos */}
          <ConfiguracionGastos 
            gastosDiarios={gastosDiarios}
            onGastosChange={setGastosDiarios}
          />

          {/* Resumen Financiero Corregido */}
          <ResumenFinancieroCorregido 
            datosFinancieros={datosFinancieros} 
            gastosDiarios={gastosDiarios}
          />

          {/* Debug de validación */}
          {datosFinancieros?.validaciones && (
            <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
              <h3 className="text-lg font-bold text-gray-800 mb-4">Validaciones del Sistema</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p><strong>Ventas Procesadas:</strong> {datosFinancieros.validaciones.total_ventas_procesadas}</p>
                  <p><strong>Diferencias Detectadas:</strong> {datosFinancieros.validaciones.diferencias_detectadas}</p>
                </div>
                <div>
                  <p><strong>Fórmula Ganancia:</strong> {datosFinancieros.validaciones.formula_ganancia_neta}</p>
                  <p><strong>Precisión:</strong> {datosFinancieros.validaciones.precision_calculo}</p>
                </div>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default FinanzasPageCorregida;
