import React, { useState, useEffect, useCallback } from 'react';
import { 
  Calendar, 
  Download, 
  TrendingUp, 
  TrendingDown, 
  DollarSign,
  Percent,
  Building,
  Target,
  Banknote,
  ArrowRightLeft,
  CreditCard,
  QrCode,
  Settings,
  Save,
  Calculator,
  BarChart3,
  PieChart
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

// ========== COMPONENTE TARJETA INFORMATIVA ==========
const TarjetaInformativa = ({ titulo, valorPrincipal, subtitulo, icono, color, prefijo = '$' }) => {
  const IconComponent = {
    'trending-up': TrendingUp,
    'trending-down': TrendingDown,
    'dollar-sign': DollarSign,
    'percent': Percent,
    'building': Building,
    'calendar': Calendar,
    'target': Target,
    'banknote': Banknote,
    'arrow-right-left': ArrowRightLeft,
    'credit-card': CreditCard,
    'qr-code': QrCode
  }[icono] || DollarSign;

  const colorClasses = {
    'green': 'bg-green-50 border-green-200 text-green-800',
    'blue': 'bg-blue-50 border-blue-200 text-blue-800',
    'orange': 'bg-orange-50 border-orange-200 text-orange-800',
    'red': 'bg-red-50 border-red-200 text-red-800',
    'emerald': 'bg-emerald-50 border-emerald-200 text-emerald-800',
    'purple': 'bg-purple-50 border-purple-200 text-purple-800',
    'indigo': 'bg-indigo-50 border-indigo-200 text-indigo-800'
  }[color] || 'bg-gray-50 border-gray-200 text-gray-800';

  const iconColor = {
    'green': 'text-green-500',
    'blue': 'text-blue-500',
    'orange': 'text-orange-500',
    'red': 'text-red-500',
    'emerald': 'text-emerald-500',
    'purple': 'text-purple-500',
    'indigo': 'text-indigo-500'
  }[color] || 'text-gray-500';

  return (
    <div className={`rounded-xl border-2 p-6 ${colorClasses}`}>
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <p className="text-sm font-medium opacity-80 mb-1">{titulo}</p>
          <p className="text-3xl font-bold mb-2">
            {prefijo === '%' ? '' : prefijo}
            {typeof valorPrincipal === 'number' ? 
              valorPrincipal.toLocaleString('es-AR', {minimumFractionDigits: 2}) : 
              valorPrincipal
            }
            {prefijo === '%' ? '%' : ''}
          </p>
          <p className="text-xs opacity-70">{subtitulo}</p>
        </div>
        <IconComponent className={`w-10 h-10 ${iconColor}`} />
      </div>
    </div>
  );
};

// ========== COMPONENTE 1: VENTAS Y GANANCIAS ==========
const VentasGanancias = ({ datos }) => {
  if (!datos) {
    return (
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <TrendingUp className="w-6 h-6 mr-3 text-green-600" />
          Ventas y Ganancias
        </h2>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Cargando datos de ventas y ganancias...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <TrendingUp className="w-6 h-6 mr-3 text-green-600" />
        Ventas y Ganancias
      </h2>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <TarjetaInformativa
          titulo={datos.tarjeta_1_ganancia_neta.titulo}
          valorPrincipal={datos.tarjeta_1_ganancia_neta.valor_principal}
          subtitulo={datos.tarjeta_1_ganancia_neta.subtitulo}
          icono={datos.tarjeta_1_ganancia_neta.icono}
          color={datos.tarjeta_1_ganancia_neta.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_ventas_brutas.titulo}
          valorPrincipal={datos.tarjeta_2_ventas_brutas.valor_principal}
          subtitulo={datos.tarjeta_2_ventas_brutas.subtitulo}
          icono={datos.tarjeta_2_ventas_brutas.icono}
          color={datos.tarjeta_2_ventas_brutas.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_descuentos.titulo}
          valorPrincipal={datos.tarjeta_3_descuentos.valor_principal}
          subtitulo={datos.tarjeta_3_descuentos.subtitulo}
          icono={datos.tarjeta_3_descuentos.icono}
          color={datos.tarjeta_3_descuentos.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_resultado_operacional.titulo}
          valorPrincipal={datos.tarjeta_4_resultado_operacional.valor_principal}
          subtitulo={datos.tarjeta_4_resultado_operacional.subtitulo}
          icono={datos.tarjeta_4_resultado_operacional.icono}
          color={datos.tarjeta_4_resultado_operacional.color}
        />
      </div>
    </div>
  );
};

// ========== COMPONENTE 2: INFORMES GASTOS FIJOS ==========
const InformesGastosFijos = ({ datos, onGastosChange, gastosActuales }) => {
  const [editandoGastos, setEditandoGastos] = useState(false);
  const [gastosInput, setGastosInput] = useState(gastosActuales || 0);
  const [descripcionInput, setDescripcionInput] = useState('');

  const handleGuardarGastos = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/gastos_mensuales.php?accion=configurar`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          gastos_totales: parseFloat(gastosInput),
          descripcion: descripcionInput,
          mes_ano: new Date().toISOString().slice(0, 7),
          usuario_id: 1
        })
      });

      const data = await response.json();
      if (data.success) {
        onGastosChange(parseFloat(gastosInput));
        setEditandoGastos(false);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Error guardando gastos:', error);
      alert('Error al guardar gastos: ' + error.message);
    }
  };

  if (!datos) {
    return (
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <Building className="w-6 h-6 mr-3 text-purple-600" />
          Informes Gastos Fijos
        </h2>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Cargando informes de gastos fijos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold text-gray-800 flex items-center">
          <Building className="w-6 h-6 mr-3 text-purple-600" />
          Informes Gastos Fijos
        </h2>
        
        <button
          onClick={() => setEditandoGastos(!editandoGastos)}
          className="flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
        >
          <Settings className="w-4 h-4 mr-2" />
          Configurar Gastos
        </button>
      </div>
      
      {/* Formulario de configuración de gastos */}
      {editandoGastos && (
        <div className="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
          <h3 className="font-semibold text-purple-800 mb-3">Configurar Gastos Mensuales</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-purple-700 mb-1">
                Gastos Totales Mensuales
              </label>
              <input
                type="number"
                value={gastosInput}
                onChange={(e) => setGastosInput(e.target.value)}
                className="w-full px-3 py-2 border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500"
                placeholder="Ej: 5000000"
                step="0.01"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-purple-700 mb-1">
                Descripción (opcional)
              </label>
              <input
                type="text"
                value={descripcionInput}
                onChange={(e) => setDescripcionInput(e.target.value)}
                className="w-full px-3 py-2 border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500"
                placeholder="Descripción de gastos..."
              />
            </div>
          </div>
          <div className="flex justify-end mt-4 space-x-2">
            <button
              onClick={() => setEditandoGastos(false)}
              className="px-4 py-2 text-purple-600 border border-purple-300 rounded-md hover:bg-purple-50"
            >
              Cancelar
            </button>
            <button
              onClick={handleGuardarGastos}
              className="flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
            >
              <Save className="w-4 h-4 mr-2" />
              Guardar
            </button>
          </div>
        </div>
      )}
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <TarjetaInformativa
          titulo={datos.tarjeta_1_gastos_mensuales.titulo}
          valorPrincipal={datos.tarjeta_1_gastos_mensuales.valor_principal}
          subtitulo={datos.tarjeta_1_gastos_mensuales.subtitulo}
          icono={datos.tarjeta_1_gastos_mensuales.icono}
          color={datos.tarjeta_1_gastos_mensuales.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_gastos_diarios.titulo}
          valorPrincipal={datos.tarjeta_2_gastos_diarios.valor_principal}
          subtitulo={datos.tarjeta_2_gastos_diarios.subtitulo}
          icono={datos.tarjeta_2_gastos_diarios.icono}
          color={datos.tarjeta_2_gastos_diarios.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_saldo_faltante.titulo}
          valorPrincipal={datos.tarjeta_3_saldo_faltante.valor_principal}
          subtitulo={datos.tarjeta_3_saldo_faltante.subtitulo}
          icono={datos.tarjeta_3_saldo_faltante.icono}
          color={datos.tarjeta_3_saldo_faltante.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_roi.titulo}
          valorPrincipal={datos.tarjeta_4_roi.valor_principal}
          subtitulo={datos.tarjeta_4_roi.subtitulo}
          icono={datos.tarjeta_4_roi.icono}
          color={datos.tarjeta_4_roi.color}
          prefijo="%"
        />
      </div>
    </div>
  );
};

// ========== COMPONENTE 3: MÉTODOS DE PAGO ==========
const MetodosPago = ({ datos }) => {
  if (!datos) {
    return (
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <CreditCard className="w-6 h-6 mr-3 text-blue-600" />
          Informes por Método de Pago
        </h2>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Cargando métodos de pago...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <CreditCard className="w-6 h-6 mr-3 text-blue-600" />
        Informes por Método de Pago
      </h2>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <TarjetaInformativa
          titulo={datos.tarjeta_1_efectivo.titulo}
          valorPrincipal={datos.tarjeta_1_efectivo.valor_principal}
          subtitulo={datos.tarjeta_1_efectivo.subtitulo}
          icono={datos.tarjeta_1_efectivo.icono}
          color={datos.tarjeta_1_efectivo.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_transferencia.titulo}
          valorPrincipal={datos.tarjeta_2_transferencia.valor_principal}
          subtitulo={datos.tarjeta_2_transferencia.subtitulo}
          icono={datos.tarjeta_2_transferencia.icono}
          color={datos.tarjeta_2_transferencia.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_tarjeta.titulo}
          valorPrincipal={datos.tarjeta_3_tarjeta.valor_principal}
          subtitulo={datos.tarjeta_3_tarjeta.subtitulo}
          icono={datos.tarjeta_3_tarjeta.icono}
          color={datos.tarjeta_3_tarjeta.color}
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_qr.titulo}
          valorPrincipal={datos.tarjeta_4_qr.valor_principal}
          subtitulo={datos.tarjeta_4_qr.subtitulo}
          icono={datos.tarjeta_4_qr.icono}
          color={datos.tarjeta_4_qr.color}
        />
      </div>
    </div>
  );
};

// ========== COMPONENTE 4: DETALLE VENTAS INDIVIDUALES ==========
const DetalleVentasIndividuales = ({ datos }) => {
  if (!datos || !datos.ventas) {
    return (
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <BarChart3 className="w-6 h-6 mr-3 text-indigo-600" />
          Detalle de Ventas Individuales
        </h2>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Cargando detalle de ventas individuales...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <BarChart3 className="w-6 h-6 mr-3 text-indigo-600" />
        Detalle de Ventas Individuales
      </h2>
      
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-gray-50 border-b">
              <th className="px-4 py-3 text-left font-semibold text-gray-700">Fecha/Hora</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700">Método</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Total Venta</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Costo</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Descuento</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Precio Final</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Ganancia</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700">Margen %</th>
            </tr>
          </thead>
          <tbody>
            {datos.ventas.length === 0 ? (
              <tr>
                <td colSpan="9" className="px-4 py-8 text-center text-gray-500">
                  <div className="flex flex-col items-center">
                    <BarChart3 className="w-12 h-12 text-gray-300 mb-4" />
                    <p className="text-lg font-medium mb-2">No hay ventas en este período</p>
                    <p className="text-sm">Intenta seleccionar un período diferente o verifica que haya ventas registradas.</p>
                  </div>
                </td>
              </tr>
            ) : datos.ventas.map((venta, index) => (
              <tr key={index} className="border-b hover:bg-gray-50">
                <td className="px-4 py-3 text-gray-600">{venta.fecha_hora}</td>
                <td className="px-4 py-3 text-gray-600">#{venta.referencia}</td>
                <td className="px-4 py-3">
                  <span className="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                    {venta.metodo_pago}
                  </span>
                </td>
                <td className="px-4 py-3 text-right font-medium">
                  ${(venta.precio_total_venta || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td className="px-4 py-3 text-right text-red-600">
                  ${(venta.precio_costo || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td className="px-4 py-3 text-right text-orange-600">
                  ${(venta.descuento || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td className="px-4 py-3 text-right font-medium text-blue-600">
                  ${(venta.precio_final || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td className="px-4 py-3 text-right font-bold text-green-600">
                  ${(venta.ganancia_neta || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                </td>
                <td className="px-4 py-3 text-right font-medium text-purple-600">
                  {venta.margen_porcentual}%
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      
      {/* Totales */}
      <div className="bg-gray-50 rounded-lg p-4 mt-6">
        <h3 className="font-bold text-gray-800 mb-3">Totales del Período</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div className="text-center">
            <p className="text-gray-600">Total Ventas</p>
            <p className="font-bold text-blue-600">
              ${(datos.totales.total_ventas || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
            </p>
          </div>
          <div className="text-center">
            <p className="text-gray-600">Total Costos</p>
            <p className="font-bold text-red-600">
              ${(datos.totales.total_costos || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
            </p>
          </div>
          <div className="text-center">
            <p className="text-gray-600">Total Descuentos</p>
            <p className="font-bold text-orange-600">
              ${(datos.totales.total_descuentos || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
            </p>
          </div>
          <div className="text-center">
            <p className="text-gray-600">Total Ganancias</p>
            <p className="font-bold text-green-600">
              ${(datos.totales.total_ganancias || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

// ========== COMPONENTE PRINCIPAL ==========
const ModuloFinancieroCompleto = () => {
  const { user } = useAuth();
  const [datosFinancieros, setDatosFinancieros] = useState({});
  const [loading, setLoading] = useState(false);
  const [periodoSeleccionado, setPeriodoSeleccionado] = useState('hoy');
  const [fechaInicio, setFechaInicio] = useState(new Date().toISOString().split('T')[0]);
  const [fechaFin, setFechaFin] = useState(new Date().toISOString().split('T')[0]);
  const [gastosActuales, setGastosActuales] = useState(0);

  const cargarDatosFinancieros = useCallback(async () => {
    try {
      setLoading(true);
      
      const parametros = new URLSearchParams({
        periodo: periodoSeleccionado
      });
      
      if (periodoSeleccionado === 'personalizado') {
        parametros.append('fecha_inicio', fechaInicio);
        parametros.append('fecha_fin', fechaFin);
      }
      
      // Cache busting
      parametros.append('_t', Date.now().toString());
      
      const url = `${CONFIG.API_URL}/api/finanzas_completo.php?${parametros.toString()}`;
      
      console.log('🔄 Cargando módulo financiero completo:', url);
      
      const response = await fetch(url, {
        cache: 'no-cache'
      });
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      
      console.log('✅ Datos financieros completos recibidos:', data);
      
      if (data.success) {
        setDatosFinancieros(data);
        setGastosActuales(data.configuracion_gastos?.gastos_mensuales || 0);
      } else {
        throw new Error(data.error || 'Error al obtener datos financieros');
      }
    } catch (error) {
      console.error('❌ Error cargando datos financieros:', error);
      // Mostrar datos de ejemplo o mensaje de error más amigable
      setDatosFinancieros({
        error: true,
        mensaje: 'Error al cargar datos financieros. Verificando conexión...'
      });
    } finally {
      setLoading(false);
    }
  }, [periodoSeleccionado, fechaInicio, fechaFin]);

  const handleGastosChange = (nuevosGastos) => {
    setGastosActuales(nuevosGastos);
    // Recargar datos para reflejar el cambio
    setTimeout(() => cargarDatosFinancieros(), 1000);
  };

  useEffect(() => {
    cargarDatosFinancieros();
  }, [cargarDatosFinancieros]);

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Análisis de Ventas</h1>
          <p className="text-gray-600 mt-1">Análisis detallado de ventas, ganancias y gastos operacionales</p>
        </div>
        
        <div className="bg-green-100 border border-green-300 rounded-lg p-3">
          <p className="text-sm font-mono text-green-800">
            ✅ GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO
          </p>
        </div>
      </div>

      {/* Controles de período */}
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center space-x-2">
            <Calendar className="w-5 h-5 text-gray-500" />
            <span className="font-medium text-gray-700">Período:</span>
          </div>
          
          <select
            value={periodoSeleccionado}
            onChange={(e) => setPeriodoSeleccionado(e.target.value)}
            className="px-4 py-2 border-2 border-gray-300 rounded-lg font-medium bg-white focus:ring-2 focus:ring-blue-500"
          >
            <option value="hoy">Hoy</option>
            <option value="ayer">Ayer</option>
            <option value="semana">Esta Semana</option>
            <option value="mes">Este Mes</option>
            <option value="personalizado">Período Personalizado</option>
          </select>

          {periodoSeleccionado === 'personalizado' && (
            <div className="flex items-center space-x-2">
              <input
                type="date"
                value={fechaInicio}
                onChange={(e) => setFechaInicio(e.target.value)}
                className="px-3 py-2 border-2 border-gray-300 rounded-lg"
              />
              <span className="text-gray-500">-</span>
              <input
                type="date"
                value={fechaFin}
                onChange={(e) => setFechaFin(e.target.value)}
                className="px-3 py-2 border-2 border-gray-300 rounded-lg"
              />
            </div>
          )}

          <button
            onClick={() => window.print()}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
          >
            <Download className="w-4 h-4 mr-2" />
            Imprimir
          </button>
        </div>
      </div>

      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
        </div>
      ) : datosFinancieros.error ? (
        <div className="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
          <div className="text-red-600 mb-4">
            <svg className="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 className="text-xl font-bold text-red-800 mb-2">Error al cargar datos</h3>
          <p className="text-red-600 mb-4">{datosFinancieros.mensaje}</p>
          <button
            onClick={cargarDatosFinancieros}
            className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Reintentar
          </button>
        </div>
      ) : (
        <>
          {/* COMPONENTE 1: VENTAS Y GANANCIAS */}
          <VentasGanancias datos={datosFinancieros.componente_1_ventas_ganancias} />

          {/* COMPONENTE 2: INFORMES GASTOS FIJOS */}
          <InformesGastosFijos 
            datos={datosFinancieros.componente_2_gastos_fijos}
            onGastosChange={handleGastosChange}
            gastosActuales={gastosActuales}
          />

          {/* COMPONENTE 3: MÉTODOS DE PAGO */}
          <MetodosPago datos={datosFinancieros.componente_3_metodos_pago} />

          {/* COMPONENTE 4: DETALLE VENTAS INDIVIDUALES */}
          <DetalleVentasIndividuales datos={datosFinancieros.componente_4_detalle_ventas} />
        </>
      )}
    </div>
  );
};

export default ModuloFinancieroCompleto;
