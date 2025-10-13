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
  PieChart,
  RefreshCw
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

// ========== COMPONENTE TARJETA INFORMATIVA ==========
const TarjetaInformativa = ({ titulo, valorPrincipal, subtitulo, icono, color, prefijo = '$', formula }) => {
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
          <p className="text-xs opacity-70 mb-2">{subtitulo}</p>
          {formula && (
            <div className="mt-3 pt-3 border-t border-current opacity-40">
              <p className="text-xs font-mono leading-relaxed">{formula}</p>
            </div>
          )}
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
          formula="📊 Cálculo: Total Vendido - Descuentos - Gastos Fijos Diarios = Tu ganancia real del día"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_ventas_brutas.titulo}
          valorPrincipal={datos.tarjeta_2_ventas_brutas.valor_principal}
          subtitulo={datos.tarjeta_2_ventas_brutas.subtitulo}
          icono={datos.tarjeta_2_ventas_brutas.icono}
          color={datos.tarjeta_2_ventas_brutas.color}
          formula="💰 Cálculo: Suma de todas las ventas (efectivo + transferencia + tarjeta + QR)"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_descuentos.titulo}
          valorPrincipal={datos.tarjeta_3_descuentos.valor_principal}
          subtitulo={datos.tarjeta_3_descuentos.subtitulo}
          icono={datos.tarjeta_3_descuentos.icono}
          color={datos.tarjeta_3_descuentos.color}
          formula="🏷️ Cálculo: Total de descuentos aplicados a clientes en este período"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_resultado_operacional.titulo}
          valorPrincipal={datos.tarjeta_4_resultado_operacional.valor_principal}
          subtitulo={datos.tarjeta_4_resultado_operacional.subtitulo}
          icono={datos.tarjeta_4_resultado_operacional.icono}
          color={datos.tarjeta_4_resultado_operacional.color}
          formula="📈 Cálculo: Total Vendido - Descuentos = Ingresos netos (sin contar gastos fijos)"
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
  const [guardando, setGuardando] = useState(false);

  // Calcular gastos diarios automáticamente
  const diasDelMes = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
  const gastosDiarios = gastosInput > 0 ? gastosInput / diasDelMes : 0;
  
  const handleGuardarGastos = async () => {
    if (parseFloat(gastosInput) < 0) {
      alert('⚠️ Los gastos no pueden ser negativos');
      return;
    }

    try {
      setGuardando(true);
      
      const response = await fetch(`${CONFIG.API_URL}/api/gastos_mensuales.php?accion=configurar`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          gastos_totales: parseFloat(gastosInput),
          descripcion: descripcionInput || 'Gastos fijos mensuales',
          mes_ano: new Date().toISOString().slice(0, 7),
          usuario_id: 1
        })
      });

      const data = await response.json();
      if (data.success) {
        onGastosChange(parseFloat(gastosInput));
        setEditandoGastos(false);
        alert(`✅ Gastos guardados correctamente!\n\n💰 Gastos mensuales: $${parseFloat(gastosInput).toLocaleString('es-AR')}\n📅 Gastos diarios: $${gastosDiarios.toLocaleString('es-AR', {maximumFractionDigits: 2})}\n📊 Días del mes: ${diasDelMes}`);
        
        // Recargar datos
        window.location.reload();
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Error guardando gastos:', error);
      alert('❌ Error al guardar gastos: ' + error.message);
    } finally {
      setGuardando(false);
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
        <div className="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-300 rounded-xl p-6 mb-6">
          <h3 className="font-bold text-purple-900 mb-4 text-lg flex items-center">
            <DollarSign className="w-5 h-5 mr-2" />
            💰 Configurar Gastos Fijos Mensuales
          </h3>
          
          {/* Banner explicativo */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <p className="text-sm text-blue-800 leading-relaxed">
              <strong>ℹ️ ¿Qué son gastos fijos?</strong> Alquiler, sueldos, servicios, impuestos, etc.
              El sistema los divide automáticamente entre los días del mes para calcular tu utilidad neta real.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-bold text-purple-800 mb-2">
                💵 Gastos Totales del Mes
              </label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-bold">$</span>
                <input
                  type="text"
                  inputMode="numeric"
                  value={gastosInput}
                  onChange={(e) => {
                    // Permitir solo números
                    const valor = e.target.value.replace(/[^0-9]/g, '');
                    setGastosInput(valor);
                  }}
                  className="w-full pl-8 pr-3 py-3 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg font-semibold"
                  placeholder="Ej: 6000000"
                />
              </div>
              <p className="text-xs text-purple-600 mt-1">
                Ingresa el total de gastos fijos mensuales (alquiler, sueldos, etc.)
              </p>
            </div>
            <div>
              <label className="block text-sm font-bold text-purple-800 mb-2">
                📝 Descripción (opcional)
              </label>
              <textarea
                value={descripcionInput}
                onChange={(e) => setDescripcionInput(e.target.value)}
                className="w-full px-3 py-3 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Ej: Alquiler $2M + Sueldos $3M"
                rows="3"
              />
            </div>
          </div>

          {/* Calculadora automática en tiempo real */}
          <div className="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl p-6 mt-4">
            <h4 className="font-bold mb-4 flex items-center">
              <Calculator className="w-5 h-5 mr-2" />
              🧮 Cálculo Automático
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="text-center">
                <p className="text-indigo-200 text-sm mb-1">💰 Gastos Mensuales</p>
                <p className="text-3xl font-bold">${parseFloat(gastosInput || 0).toLocaleString('es-AR')}</p>
                <p className="text-indigo-200 text-xs mt-1">Total del mes</p>
              </div>
              <div className="text-center bg-white bg-opacity-20 rounded-lg p-3">
                <p className="text-indigo-100 text-sm mb-1">📅 Días del Mes</p>
                <p className="text-3xl font-bold">{diasDelMes}</p>
                <p className="text-indigo-200 text-xs mt-1">{new Date().toLocaleDateString('es-AR', { month: 'long' })}</p>
              </div>
              <div className="text-center">
                <p className="text-indigo-200 text-sm mb-1">📊 Gasto Diario</p>
                <p className="text-3xl font-bold">${gastosDiarios.toLocaleString('es-AR', {maximumFractionDigits: 2})}</p>
                <p className="text-indigo-200 text-xs mt-1">Por día</p>
              </div>
            </div>
            
            {/* Fórmula visual */}
            <div className="bg-white bg-opacity-10 rounded-lg p-3 mt-4 text-center">
              <p className="text-sm text-indigo-100 mb-1">📐 Fórmula:</p>
              <p className="font-mono text-white font-bold">
                ${parseFloat(gastosInput || 0).toLocaleString('es-AR')} ÷ {diasDelMes} días = ${gastosDiarios.toLocaleString('es-AR', {maximumFractionDigits: 2})} por día
              </p>
            </div>
          </div>

          <div className="flex justify-end mt-6 space-x-3">
            <button
              onClick={() => setEditandoGastos(false)}
              disabled={guardando}
              className="px-6 py-2 text-purple-700 bg-white border-2 border-purple-300 rounded-lg hover:bg-purple-50 font-medium transition-colors"
            >
              Cancelar
            </button>
            <button
              onClick={handleGuardarGastos}
              disabled={guardando}
              className="flex items-center px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors disabled:opacity-50"
            >
              {guardando ? (
                <>
                  <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                  Guardando...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Guardar Gastos
                </>
              )}
            </button>
          </div>
        </div>
      )}
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <TarjetaInformativa
          titulo={datos.tarjeta_1_gastos_mensuales?.titulo || 'Gastos Mensuales'}
          valorPrincipal={datos.tarjeta_1_gastos_mensuales?.valor_principal || 0}
          subtitulo={datos.tarjeta_1_gastos_mensuales?.subtitulo || ''}
          icono={datos.tarjeta_1_gastos_mensuales?.icono || 'building'}
          color={datos.tarjeta_1_gastos_mensuales?.color || 'purple'}
          formula="💼 Total de gastos fijos del mes (alquiler + sueldos + servicios + impuestos)"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_gastos_diarios?.titulo || 'Gasto por Día'}
          valorPrincipal={datos.tarjeta_2_gastos_diarios?.valor_principal || 0}
          subtitulo={datos.tarjeta_2_gastos_diarios?.subtitulo || ''}
          icono={datos.tarjeta_2_gastos_diarios?.icono || 'calendar'}
          color={datos.tarjeta_2_gastos_diarios?.color || 'blue'}
          formula="📅 Cálculo: Gastos Mensuales ÷ Días del Mes = Gasto promedio por día"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_gastos_periodo?.titulo || 'Gastos del Período'}
          valorPrincipal={datos.tarjeta_3_gastos_periodo?.valor_principal || 0}
          subtitulo={datos.tarjeta_3_gastos_periodo?.subtitulo || ''}
          icono={datos.tarjeta_3_gastos_periodo?.icono || 'calendar'}
          color={datos.tarjeta_3_gastos_periodo?.color || 'orange'}
          formula="📊 Cálculo: Gasto Diario × Días del Período = Total de gastos en este período"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_utilidad_neta?.titulo || 'Utilidad Neta'}
          valorPrincipal={datos.tarjeta_4_utilidad_neta?.valor_principal || 0}
          subtitulo={datos.tarjeta_4_utilidad_neta?.subtitulo || ''}
          icono={datos.tarjeta_4_utilidad_neta?.icono || 'trending-up'}
          color={datos.tarjeta_4_utilidad_neta?.color || 'green'}
          formula="💰 Cálculo: (Ventas - Descuentos) - Gastos Fijos del Período = Ganancia real"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_5_progreso_mes?.titulo || 'Progreso Mes'}
          valorPrincipal={datos.tarjeta_5_progreso_mes?.valor_principal || 0}
          subtitulo={datos.tarjeta_5_progreso_mes?.subtitulo || ''}
          icono={datos.tarjeta_5_progreso_mes?.icono || 'target'}
          color={datos.tarjeta_5_progreso_mes?.color || 'orange'}
          prefijo="%"
          formula="📈 Cálculo: (Días transcurridos ÷ Total días del mes) × 100 = % del mes completado"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_6_roi_neto?.titulo || 'ROI Neto'}
          valorPrincipal={datos.tarjeta_6_roi_neto?.valor_principal || 0}
          subtitulo={datos.tarjeta_6_roi_neto?.subtitulo || ''}
          icono={datos.tarjeta_6_roi_neto?.icono || 'percent'}
          color={datos.tarjeta_6_roi_neto?.color || 'red'}
          prefijo="%"
          formula="💹 Cálculo: (Utilidad Neta ÷ Gastos Fijos) × 100 = % de retorno sobre inversión"
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
          formula="💵 Total de ventas cobradas en efectivo. Importante: debe estar en caja al cierre"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_2_transferencia.titulo}
          valorPrincipal={datos.tarjeta_2_transferencia.valor_principal}
          subtitulo={datos.tarjeta_2_transferencia.subtitulo}
          icono={datos.tarjeta_2_transferencia.icono}
          color={datos.tarjeta_2_transferencia.color}
          formula="🏦 Cobros por transferencia bancaria. Verificar que ingresaron a cuenta"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_3_tarjeta.titulo}
          valorPrincipal={datos.tarjeta_3_tarjeta.valor_principal}
          subtitulo={datos.tarjeta_3_tarjeta.subtitulo}
          icono={datos.tarjeta_3_tarjeta.icono}
          color={datos.tarjeta_3_tarjeta.color}
          formula="💳 Ventas con tarjeta de crédito/débito. Se acreditan en 24-48hs"
        />
        
        <TarjetaInformativa
          titulo={datos.tarjeta_4_qr.titulo}
          valorPrincipal={datos.tarjeta_4_qr.valor_principal}
          subtitulo={datos.tarjeta_4_qr.subtitulo}
          icono={datos.tarjeta_4_qr.icono}
          color={datos.tarjeta_4_qr.color}
          formula="📱 Cobros con QR (MercadoPago, etc). Acreditación instantánea"
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
