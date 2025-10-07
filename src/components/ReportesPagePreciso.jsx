import React, { useState, useEffect, useCallback } from 'react';
import { 
  Calculator, 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Package, 
  AlertTriangle,
  CheckCircle,
  BarChart3,
  PieChart,
  Clock,
  Target,
  Building2,
  CreditCard,
  Download,
  RefreshCw,
  Calendar,
  Filter,
  Activity,
  Zap
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import reportesService from '../services/reportesService';

// ========== COMPONENTE PRINCIPAL ==========
const ReportesPagePreciso = () => {
  const { currentUser } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [periodo, setPeriodo] = useState('hoy');
  const [fechaInicio, setFechaInicio] = useState('');
  const [fechaFin, setFechaFin] = useState('');
  const [datos, setDatos] = useState(null);
  const [vistaActiva, setVistaActiva] = useState('dashboard');

  // ========== CARGAR DATOS ==========
  const cargarDatos = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      
      const parametros = {
        periodo,
        ...(fechaInicio && { fechaInicio }),
        ...(fechaFin && { fechaFin })
      };
      
      const response = await reportesService.obtenerDatosContables(parametros);
      setDatos(response);
    } catch (error) {
      console.error('Error cargando datos:', error);
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }, [periodo, fechaInicio, fechaFin]);

  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

  // ========== FUNCIONES DE FORMATO ==========
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(amount || 0);
  };

  const formatPercentage = (value) => {
    const num = parseFloat(value) || 0;
    return `${num >= 0 ? '+' : ''}${num.toFixed(2)}%`;
  };

  // ========== COMPONENTE: TARJETAS DE MÉTRICAS ==========
  const MetricasCards = ({ resumen, gastos }) => (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      {/* Ingresos Netos */}
      <div className="bg-green-50 rounded-lg p-6 border border-green-200">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-green-800">Ingresos Netos</p>
            <p className="text-2xl font-bold text-green-900">
              {formatCurrency(resumen?.total_ingresos_netos)}
            </p>
            <p className="text-sm text-green-600">
              {resumen?.total_ventas} ventas
            </p>
          </div>
          <TrendingUp className="w-8 h-8 text-green-600" />
        </div>
      </div>

      {/* Utilidad Bruta */}
      <div className="bg-blue-50 rounded-lg p-6 border border-blue-200">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-blue-800">Utilidad Bruta</p>
            <p className="text-2xl font-bold text-blue-900">
              {formatCurrency(resumen?.total_utilidad_bruta)}
            </p>
            <p className="text-sm text-blue-600">
              Margen: {formatPercentage(resumen?.margen_bruto_porcentaje)}
            </p>
          </div>
          <Calculator className="w-8 h-8 text-blue-600" />
        </div>
      </div>

      {/* Gastos Fijos */}
      <div className="bg-purple-50 rounded-lg p-6 border border-purple-200">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-purple-800">Gastos Fijos</p>
            <p className="text-2xl font-bold text-purple-900">
              {formatCurrency(gastos?.periodo)}
            </p>
            <p className="text-sm text-purple-600">
              {formatCurrency(gastos?.diarios)}/día
            </p>
          </div>
          <Building2 className="w-8 h-8 text-purple-600" />
        </div>
      </div>

      {/* Utilidad Neta */}
      <div className={`rounded-lg p-6 border ${
        resumen?.utilidad_neta >= 0 
          ? 'bg-green-50 border-green-200' 
          : 'bg-red-50 border-red-200'
      }`}>
        <div className="flex items-center justify-between">
          <div>
            <p className={`text-sm font-medium ${
              resumen?.utilidad_neta >= 0 ? 'text-green-800' : 'text-red-800'
            }`}>
              Utilidad Neta
            </p>
            <p className={`text-2xl font-bold ${
              resumen?.utilidad_neta >= 0 ? 'text-green-900' : 'text-red-900'
            }`}>
              {formatCurrency(resumen?.utilidad_neta)}
            </p>
            <p className={`text-sm ${
              resumen?.utilidad_neta >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {resumen?.estado_negocio}
            </p>
          </div>
          {resumen?.utilidad_neta >= 0 ? (
            <TrendingUp className="w-8 h-8 text-green-600" />
          ) : (
            <TrendingDown className="w-8 h-8 text-red-600" />
          )}
        </div>
      </div>
    </div>
  );

  // ========== COMPONENTE: INDICADOR DE ESTADO DEL NEGOCIO ==========
  const EstadoNegocio = ({ resumen }) => {
    const getEstadoStyle = () => {
      switch (resumen?.estado_negocio) {
        case 'RENTABLE':
          return 'bg-green-50 border-green-200 text-green-800';
        case 'EN PÉRDIDAS':
          return 'bg-red-50 border-red-200 text-red-800';
        default:
          return 'bg-yellow-50 border-yellow-200 text-yellow-800';
      }
    };

    const getEstadoIcon = () => {
      switch (resumen?.estado_negocio) {
        case 'RENTABLE':
          return '🟢';
        case 'EN PÉRDIDAS':
          return '🔴';
        default:
          return '🟡';
      }
    };

    return (
      <div className={`p-6 rounded-lg border-2 mb-8 ${getEstadoStyle()}`}>
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold">
              {getEstadoIcon()} {resumen?.estado_negocio || 'EVALUANDO'}
            </h2>
            <p className="text-lg mt-2">
              <strong>Utilidad Neta:</strong> {formatCurrency(resumen?.utilidad_neta)}
            </p>
            <p className="text-sm mt-1">
              <strong>Margen Neto:</strong> {formatPercentage(resumen?.margen_neto_porcentaje)} | 
              <strong> ROI Neto:</strong> {formatPercentage(resumen?.roi_neto_porcentaje)}
            </p>
          </div>
          <div className="text-right">
            <div className="text-3xl font-bold text-blue-600">
              {formatCurrency(resumen?.utilidad_por_venta)}
            </div>
            <div className="text-sm text-gray-600">Utilidad por Venta</div>
          </div>
        </div>
      </div>
    );
  };

  // ========== COMPONENTE: ANÁLISIS FINANCIERO DETALLADO ==========
  const AnalisisFinanciero = ({ resumen, gastos }) => (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      {/* Flujo Financiero */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <Activity className="w-5 h-5 mr-2 text-blue-600" />
          Flujo Financiero Simplificado
        </h3>
        <div className="space-y-3">
          <div className="flex justify-between">
            <span className="text-gray-600">Ingresos Brutos:</span>
            <span className="font-semibold">{formatCurrency(resumen?.total_ingresos_brutos)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Descuentos:</span>
            <span className="font-semibold text-red-600">-{formatCurrency(resumen?.total_descuentos)}</span>
          </div>
          <div className="flex justify-between border-t pt-2">
            <span className="text-gray-800 font-semibold">Ingresos Netos:</span>
            <span className="font-bold text-green-600">{formatCurrency(resumen?.total_ingresos_netos)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Costos de Productos:</span>
            <span className="font-semibold text-red-600">-{formatCurrency(resumen?.total_costos)}</span>
          </div>
          <div className="flex justify-between border-t pt-2">
            <span className="text-gray-800 font-semibold">Utilidad Bruta:</span>
            <span className="font-bold text-blue-600">{formatCurrency(resumen?.total_utilidad_bruta)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Gastos Fijos (Simplificado):</span>
            <span className="font-semibold text-purple-600">-{formatCurrency(gastos?.periodo)}</span>
          </div>
          <div className="flex justify-between border-t-2 pt-2 bg-gray-50 px-3 py-2 rounded">
            <span className="text-gray-800 font-bold">UTILIDAD NETA:</span>
            <span className={`font-bold text-lg ${
              resumen?.utilidad_neta >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {formatCurrency(resumen?.utilidad_neta)}
            </span>
          </div>
        </div>
      </div>

      {/* Indicadores de Performance */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <Target className="w-5 h-5 mr-2 text-green-600" />
          Indicadores de Performance
        </h3>
        <div className="space-y-3">
          <div className="flex justify-between">
            <span className="text-gray-600">Total Ventas:</span>
            <span className="font-semibold">{resumen?.total_ventas}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Productos Vendidos:</span>
            <span className="font-semibold">{resumen?.total_productos_vendidos}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Ticket Promedio:</span>
            <span className="font-semibold">{formatCurrency(resumen?.ticket_promedio)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">ROI Bruto:</span>
            <span className="font-semibold">{formatPercentage(resumen?.roi_bruto_porcentaje)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">ROI Neto:</span>
            <span className={`font-semibold ${
              resumen?.roi_neto_porcentaje >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {formatPercentage(resumen?.roi_neto_porcentaje)}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Margen Bruto:</span>
            <span className="font-semibold">{formatPercentage(resumen?.margen_bruto_porcentaje)}</span>
          </div>
          <div className="flex justify-between border-t pt-2">
            <span className="text-gray-800 font-semibold">Margen Neto:</span>
            <span className={`font-bold ${
              resumen?.margen_neto_porcentaje >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {formatPercentage(resumen?.margen_neto_porcentaje)}
            </span>
          </div>
        </div>
      </div>
    </div>
  );

  // ========== COMPONENTE: FÓRMULAS IMPLEMENTADAS ==========
  const FormulasImplementadas = ({ gastos }) => (
    <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
      <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <Calculator className="w-5 h-5 mr-2 text-blue-600" />
        Fórmulas Matemáticas Aplicadas
      </h3>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-blue-50 p-4 rounded-lg">
          <h4 className="font-semibold text-blue-800">Utilidad por Producto</h4>
          <p className="text-sm text-blue-600 mt-1">
            <code>Utilidad = Precio_Venta - Costo_Producto</code>
          </p>
          <p className="text-xs text-blue-500 mt-2">
            Ejemplo: Costo $1,000 + 40% = Venta $1,400 → Utilidad $400
          </p>
        </div>
        <div className="bg-green-50 p-4 rounded-lg">
          <h4 className="font-semibold text-green-800">Gastos Fijos Diarios</h4>
          <p className="text-sm text-green-600 mt-1">
            <code>{gastos?.formula || 'Gastos_Mensuales ÷ Días_Mes'}</code>
          </p>
        </div>
        <div className="bg-purple-50 p-4 rounded-lg">
          <h4 className="font-semibold text-purple-800">Margen Porcentual</h4>
          <p className="text-sm text-purple-600 mt-1">
            <code>Margen = (Utilidad / Precio_Venta) × 100</code>
          </p>
        </div>
        <div className="bg-orange-50 p-4 rounded-lg">
          <h4 className="font-semibold text-orange-800">Utilidad Neta</h4>
          <p className="text-sm text-orange-600 mt-1">
            <code>Utilidad_Neta = Utilidad_Bruta - Gastos_Fijos</code>
          </p>
        </div>
      </div>
    </div>
  );

  // ========== COMPONENTE: ANÁLISIS POR PRODUCTO ==========
  const AnalisisProductos = ({ productos }) => (
    <div className="bg-white rounded-lg shadow-sm border mb-8">
      <div className="p-6 border-b">
        <h3 className="text-lg font-semibold text-gray-900 flex items-center">
          <Package className="w-5 h-5 mr-2 text-blue-600" />
          Análisis de Utilidades por Producto
        </h3>
        <p className="text-sm text-gray-600 mt-1">
          Filtros por día y fecha implementados - Fórmula: Precio_Venta - Costo_Producto
        </p>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingresos</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Costos</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilidad</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margen</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {productos?.slice(0, 10).map((producto, index) => (
              <tr key={index} className="hover:bg-gray-50">
                <td className="px-6 py-4">
                  <div>
                    <div className="text-sm font-medium text-gray-900">{producto.nombre}</div>
                    <div className="text-sm text-gray-500">{producto.categoria}</div>
                  </div>
                </td>
                <td className="px-6 py-4 text-sm text-gray-900">{producto.cantidad_vendida}</td>
                <td className="px-6 py-4 text-sm font-medium text-green-600">
                  {formatCurrency(producto.ingresos)}
                </td>
                <td className="px-6 py-4 text-sm font-medium text-red-600">
                  {formatCurrency(producto.costos)}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-blue-600">
                  {formatCurrency(producto.utilidad)}
                </td>
                <td className="px-6 py-4">
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    producto.margen_porcentaje > 30 ? 'bg-green-100 text-green-800' :
                    producto.margen_porcentaje > 15 ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                  }`}>
                    {formatPercentage(producto.margen_porcentaje)}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  // ========== COMPONENTE: MÉTODOS DE PAGO ==========
  const MetodosPago = ({ metodos, totalIngresos }) => (
    <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
      <h3 className="text-lg font-semibold text-gray-900 mb-6 flex items-center">
        <CreditCard className="w-5 h-5 mr-2 text-blue-600" />
        💳 Ingresos por Método de Pago
      </h3>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {Object.entries(metodos || {})
          .filter(([metodo, monto]) => metodo !== 'otros' && monto > 0)
          .map(([metodo, monto]) => (
          <div key={metodo} className="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600 capitalize mb-1">
                  {metodo === 'mercadopago' ? 'MercadoPago' : metodo}
                </p>
                <p className="text-2xl font-bold text-gray-900">{formatCurrency(monto)}</p>
              </div>
              <div className="text-right">
                <div className={`w-3 h-3 rounded-full mb-2 ${
                  metodo === 'efectivo' ? 'bg-green-500' :
                  metodo === 'tarjeta' ? 'bg-blue-500' :
                  metodo === 'transferencia' ? 'bg-purple-500' :
                  'bg-orange-500'
                }`}></div>
                <p className="text-sm font-medium text-gray-500">
                  {totalIngresos > 0 
                    ? formatPercentage((monto / totalIngresos) * 100)
                    : '0%'
                  }
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Resumen total */}
      <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div className="flex items-center justify-between">
          <span className="text-sm font-medium text-blue-800">Total Ingresos del Día:</span>
          <span className="text-lg font-bold text-blue-900">
            {formatCurrency(totalIngresos)}
          </span>
        </div>
      </div>
    </div>
  );

  // ========== COMPONENTE: TABLA DE VENTAS INDIVIDUALES ==========
  const TablaVentasIndividuales = ({ ventasDetalladas }) => {
    const [ordenarPor, setOrdenarPor] = useState('fecha');
    const [ordenAsc, setOrdenAsc] = useState(false);

    if (!ventasDetalladas || ventasDetalladas.length === 0) {
      return (
        <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
          <h3 className="text-lg font-semibold text-gray-900 mb-6 flex items-center">
            <Package className="w-5 h-5 mr-2 text-blue-600" />
            📊 Detalle de Ventas Individuales
          </h3>
          <div className="text-center py-8">
            <Package className="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <p className="text-gray-500 text-lg">No se encontraron ventas detalladas</p>
            <p className="text-gray-400 text-sm">No hay ventas registradas en el período consultado</p>
          </div>
        </div>
      );
    }

    // Procesar ventas para la tabla
    const ventasProcesadas = ventasDetalladas.map(venta => {
      const producto = venta.productos[0]; // Asumiendo 1 producto por venta
      const descuentoAplicado = venta.resumen.descuento_aplicado || 0;
      const precioFinal = venta.resumen.total_ingresos_netos;
      const ganancia = venta.resumen.utilidad_bruta;
      const margen = precioFinal > 0 ? (ganancia / precioFinal) * 100 : 0;

      return {
        id: venta.venta_id,
        fecha: new Date(venta.fecha),
        fechaTexto: new Date(venta.fecha).toLocaleDateString('es-AR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }),
        producto: producto.nombre,
        metodoPago: venta.metodo_pago,
        precioVenta: producto.precio_venta_unitario,
        costo: producto.costo_unitario,
        descuento: descuentoAplicado,
        precioFinal: precioFinal,
        ganancia: ganancia,
        margen: margen
      };
    });

    // Función de ordenamiento
    const ventasOrdenadas = [...ventasProcesadas].sort((a, b) => {
      let valorA = a[ordenarPor];
      let valorB = b[ordenarPor];
      
      if (ordenarPor === 'fecha') {
        valorA = a.fecha.getTime();
        valorB = b.fecha.getTime();
      }
      
      if (typeof valorA === 'string') {
        valorA = valorA.toLowerCase();
        valorB = valorB.toLowerCase();
      }
      
      if (ordenAsc) {
        return valorA > valorB ? 1 : -1;
      } else {
        return valorA < valorB ? 1 : -1;
      }
    });

    const manejarOrdenamiento = (campo) => {
      if (ordenarPor === campo) {
        setOrdenAsc(!ordenAsc);
      } else {
        setOrdenarPor(campo);
        setOrdenAsc(false);
      }
    };

    return (
      <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-gray-900 flex items-center">
            <Package className="w-5 h-5 mr-2 text-blue-600" />
            📊 Detalle de Ventas Individuales
          </h3>
          <div className="flex items-center space-x-4">
            <div className="text-sm text-blue-600">
              {ventasOrdenadas.length} ventas registradas
            </div>
            <button 
              className="text-sm text-gray-500 hover:text-gray-700 flex items-center"
              title="Exportar (próximamente)"
            >
              <Download className="w-4 h-4 mr-1" />
              Exportar
            </button>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200">
                <th 
                  className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => manejarOrdenamiento('fecha')}
                >
                  <div className="flex items-center">
                    Fecha y Hora
                    {ordenarPor === 'fecha' && (
                      <span className="ml-1">{ordenAsc ? '↑' : '↓'}</span>
                    )}
                  </div>
                </th>
                <th 
                  className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => manejarOrdenamiento('producto')}
                >
                  <div className="flex items-center">
                    Producto
                    {ordenarPor === 'producto' && (
                      <span className="ml-1">{ordenAsc ? '↑' : '↓'}</span>
                    )}
                  </div>
                </th>
                <th 
                  className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => manejarOrdenamiento('metodoPago')}
                >
                  <div className="flex items-center">
                    Método de Pago
                    {ordenarPor === 'metodoPago' && (
                      <span className="ml-1">{ordenAsc ? '↑' : '↓'}</span>
                    )}
                  </div>
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Precio Venta</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Descuento</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Precio Final</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Ganancia</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Margen %</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {ventasOrdenadas.map((venta) => (
                <tr key={venta.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm text-gray-900 font-medium">
                    {venta.fechaTexto}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-900 max-w-40 truncate" title={venta.producto}>
                    {venta.producto}
                  </td>
                  <td className="px-4 py-3 text-sm">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                      venta.metodoPago === 'efectivo' ? 'bg-green-100 text-green-800' :
                      venta.metodoPago === 'tarjeta' ? 'bg-blue-100 text-blue-800' :
                      venta.metodoPago === 'transferencia' ? 'bg-purple-100 text-purple-800' :
                      'bg-orange-100 text-orange-800'
                    }`}>
                      {venta.metodoPago === 'mercadopago' ? 'MercadoPago' : venta.metodoPago}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-sm font-medium text-blue-600">
                    {formatCurrency(venta.precioVenta)}
                  </td>
                  <td className="px-4 py-3 text-sm font-medium text-red-600">
                    {formatCurrency(venta.costo)}
                  </td>
                  <td className="px-4 py-3 text-sm">
                    {venta.descuento > 0 ? (
                      <span className="text-red-600 font-medium">
                        {formatCurrency(venta.descuento)}
                      </span>
                    ) : (
                      <span className="text-gray-400">$0</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-sm font-bold text-green-600">
                    {formatCurrency(venta.precioFinal)}
                  </td>
                  <td className={`px-4 py-3 text-sm font-bold ${venta.ganancia >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {formatCurrency(venta.ganancia)}
                  </td>
                  <td className={`px-4 py-3 text-sm font-bold ${venta.margen >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {venta.margen.toFixed(1)}%
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Totales */}
        <div className="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
          <div className="text-center">
            <div className="text-lg font-bold text-blue-600">
              {formatCurrency(ventasOrdenadas.reduce((sum, v) => sum + v.precioVenta, 0))}
            </div>
            <div className="text-xs text-gray-600">Total Ventas</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-bold text-red-600">
              {formatCurrency(ventasOrdenadas.reduce((sum, v) => sum + v.costo, 0))}
            </div>
            <div className="text-xs text-gray-600">Total Costos</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-bold text-orange-600">
              {formatCurrency(ventasOrdenadas.reduce((sum, v) => sum + v.descuento, 0))}
            </div>
            <div className="text-xs text-gray-600">Total Descuentos</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-bold text-green-600">
              {formatCurrency(ventasOrdenadas.reduce((sum, v) => sum + v.ganancia, 0))}
            </div>
            <div className="text-xs text-gray-600">Total Ganancias</div>
          </div>
        </div>
      </div>
    );
  };

  // ========== RENDER PRINCIPAL ==========
  if (loading) {
    return (
      <div className="p-6 max-w-7xl mx-auto">
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 max-w-7xl mx-auto">
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          <h3 className="font-bold">Error en Sistema de Reportes</h3>
          <p>Error: {error}</p>
          <button 
            onClick={cargarDatos}
            className="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  if (!datos || !datos.success) {
    return (
      <div className="p-6 max-w-7xl mx-auto">
        <div className="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
          No se pudieron cargar los datos financieros.
        </div>
      </div>
    );
  }

  const { 
    resumenGeneral, 
    gastosFijos, 
    metodosPago, 
    utilidadesProductos, 
    alertas,
    validaciones 
  } = datos;

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-8">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 flex items-center">
            <BarChart3 className="w-8 h-8 mr-3 text-blue-600" />
            📊 Reportes Financieros
          </h1>
          <p className="text-gray-600 mt-1">
            Sistema simplificado con gastos fijos unificados - Cálculos matemáticamente precisos
          </p>
        </div>
        
        <div className="flex items-center space-x-4">
          {/* Selector de período */}
          <select
            value={periodo}
            onChange={(e) => setPeriodo(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="hoy">HOY</option>
            <option value="ayer">AYER</option>
            <option value="semana">ESTA SEMANA</option>
            <option value="mes">ESTE MES</option>
            <option value="personalizado">PERSONALIZAR</option>
          </select>

          {periodo === 'personalizado' && (
            <div className="flex items-center space-x-2">
              <input
                type="date"
                value={fechaInicio}
                onChange={(e) => setFechaInicio(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-lg"
              />
              <span className="text-gray-500">-</span>
              <input
                type="date"
                value={fechaFin}
                onChange={(e) => setFechaFin(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-lg"
              />
            </div>
          )}

          <button
            onClick={cargarDatos}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <RefreshCw className="w-4 h-4 mr-2" />
            Actualizar
          </button>
        </div>
      </div>

      {/* Alertas */}
      {alertas && alertas.length > 0 && (
        <div className="space-y-3">
          {alertas.map((alerta, index) => (
            <div key={index} className={`p-4 rounded-lg border ${
              alerta.tipo === 'peligro' ? 'bg-red-50 border-red-200 text-red-800' :
              alerta.tipo === 'advertencia' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' :
              'bg-blue-50 border-blue-200 text-blue-800'
            }`}>
              <div className="flex items-center">
                <AlertTriangle className="w-5 h-5 mr-2" />
                <div>
                  <h4 className="font-semibold">{alerta.titulo}</h4>
                  <p className="text-sm">{alerta.mensaje}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Estado del Negocio */}
      <EstadoNegocio resumen={resumenGeneral} />

      {/* Métricas Principales */}
      <MetricasCards resumen={resumenGeneral} gastos={gastosFijos} />

      {/* Fórmulas Implementadas */}
      <FormulasImplementadas gastos={gastosFijos} />

      {/* Análisis Financiero */}
      <AnalisisFinanciero resumen={resumenGeneral} gastos={gastosFijos} />

      {/* 💳 MÉTODOS DE PAGO - SUBIDO SEGÚN PROMPT */}
      <MetodosPago 
        metodos={metodosPago} 
        totalIngresos={resumenGeneral?.total_ingresos_netos} 
      />

      {/* 📊 TABLA DE VENTAS INDIVIDUALES - REEMPLAZA ANÁLISIS POR PRODUCTO */}
      <TablaVentasIndividuales ventasDetalladas={datos?.ventasDetalladas} />

      {/* 🔍 VALIDACIONES DEL SISTEMA - CONDICIONALES SEGÚN PROMPT */}
      {validaciones && 
       validaciones.coherencia_general !== undefined && 
       resumenGeneral?.total_ventas > 0 && (
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <CheckCircle className="w-5 h-5 mr-2 text-green-600" />
            🔍 Validaciones del Sistema
          </h3>
          <div className={`p-4 rounded-lg border-l-4 ${
            validaciones.coherencia_general ? 'bg-green-50 border-green-400' : 'bg-red-50 border-red-400'
          }`}>
            <div className="flex items-center">
              {validaciones.coherencia_general ? (
                <CheckCircle className="w-5 h-5 text-green-600 mr-2" />
              ) : (
                <AlertTriangle className="w-5 h-5 text-red-600 mr-2" />
              )}
              <div>
                <h4 className={`font-semibold ${
                  validaciones.coherencia_general ? 'text-green-800' : 'text-red-800'
                }`}>
                  Coherencia Matemática: {validaciones.coherencia_general ? 'CORRECTA' : 'ERRORES DETECTADOS'}
                </h4>
                <p className={`text-sm mt-1 ${
                  validaciones.coherencia_general ? 'text-green-700' : 'text-red-700'
                }`}>
                  {validaciones.coherencia_general 
                    ? 'Todos los cálculos son matemáticamente consistentes'
                    : `Se detectaron ${validaciones.diferencias_detectadas} diferencias en los cálculos`
                  }
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ReportesPagePreciso; 