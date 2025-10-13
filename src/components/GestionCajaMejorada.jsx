import React, { useState, useEffect, useCallback } from 'react';
import { 
  Calculator, 
  DollarSign, 
  TrendingUp, 
  TrendingDown,
  Plus,
  Minus,
  Save,
  Lock,
  Unlock,
  CreditCard,
  Banknote,
  ArrowRightLeft,
  QrCode,
  Clock,
  User,
  Receipt,
  CheckCircle,
  Info,
  RefreshCw,
  Eye,
  Settings,
  AlertTriangle,
  Calendar
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

// ========== COMPONENTE DE TARJETA MODERNA ==========
const TarjetaModerna = ({ titulo, valor, subtitulo, icono: IconComponent, color, prefijo = '$', formula }) => {
  const estilos = {
    'green': 'bg-gradient-to-br from-green-50 to-green-100 border-green-200 text-green-800',
    'blue': 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 text-blue-800',
    'red': 'bg-gradient-to-br from-red-50 to-red-100 border-red-200 text-red-800',
    'purple': 'bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 text-purple-800',
    'orange': 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 text-orange-800',
    'indigo': 'bg-gradient-to-br from-indigo-50 to-indigo-100 border-indigo-200 text-indigo-800'
  }[color] || 'bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200 text-gray-800';

  const iconColor = {
    'green': 'text-green-600',
    'blue': 'text-blue-600',
    'red': 'text-red-600',
    'purple': 'text-purple-600',
    'orange': 'text-orange-600',
    'indigo': 'text-indigo-600'
  }[color] || 'text-gray-600';

  const formatearValor = (val) => {
    if (prefijo === '%') {
      return `${val}%`;
    }
    return typeof val === 'number' ? `${prefijo}${val.toLocaleString('es-AR', {minimumFractionDigits: 2})}` : `${prefijo}0.00`;
  };

  return (
    <div className={`rounded-2xl border-2 p-6 transition-all duration-300 hover:shadow-lg hover:scale-105 ${estilos}`}>
      <div className="flex items-center justify-between mb-4">
        <div className="p-3 rounded-xl bg-white/50">
          <IconComponent className={`w-6 h-6 ${iconColor}`} />
        </div>
      </div>
      
      <div>
        <p className="text-sm font-semibold opacity-80 mb-2">{titulo}</p>
        <p className="text-3xl font-bold mb-2">
          {formatearValor(valor)}
        </p>
        <p className="text-xs opacity-70 leading-relaxed mb-2">{subtitulo}</p>
        {formula && (
          <div className="mt-3 pt-3 border-t border-current opacity-50">
            <p className="text-xs font-mono leading-relaxed">{formula}</p>
          </div>
        )}
      </div>
    </div>
  );
};

// ========== FORMULARIO DE APERTURA ELEGANTE ==========
const FormularioAperturaElegante = ({ onAperturaExitosa }) => {
  const { user } = useAuth();
  const [montoApertura, setMontoApertura] = useState('');
  const [notas, setNotas] = useState('');
  const [loading, setLoading] = useState(false);

  const handleAbrirCaja = async (e) => {
    e.preventDefault();
    
    if (!montoApertura || parseFloat(montoApertura) < 0) {
      alert('Debe ingresar un monto válido');
      return;
    }

    try {
      setLoading(true);
      
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=abrir_caja`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          monto_apertura: parseFloat(montoApertura),
          notas: notas
        })
      });

      const data = await response.json();
      
      if (data.success) {
        onAperturaExitosa(data);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Error abriendo caja:', error);
      alert('Error al abrir caja: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-6">
      <div className="bg-white rounded-3xl shadow-2xl border border-gray-100 p-10 w-full max-w-lg">
        
        {/* Header Elegante */}
        <div className="text-center mb-10">
          <div className="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
            <Calculator className="w-10 h-10 text-white" />
          </div>
          <h1 className="text-3xl font-bold text-gray-800 mb-3">Apertura de Caja</h1>
          <p className="text-gray-600">Inicia tu turno de trabajo</p>
        </div>

        {/* Instrucciones */}
        <div className="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8">
          <div className="flex items-start">
            <div className="p-2 bg-blue-100 rounded-lg mr-4">
              <Info className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-sm font-semibold text-blue-800 mb-2">Instrucciones</p>
              <p className="text-sm text-blue-700 leading-relaxed">
                Cuenta el efectivo disponible en caja y registra el monto exacto. 
                Este será tu punto de partida para el turno.
              </p>
            </div>
          </div>
        </div>

        {/* Formulario */}
        <form onSubmit={handleAbrirCaja} className="space-y-6">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-3">
              Monto Inicial en Efectivo
            </label>
            <div className="relative">
              <DollarSign className="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                value={montoApertura}
                onChange={(e) => {
                  const valor = e.target.value;
                  // Permitir solo números y punto decimal
                  if (valor === '' || /^\d*\.?\d*$/.test(valor)) {
                    setMontoApertura(valor);
                  }
                }}
                className="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg font-medium"
                placeholder="0.00"
                inputMode="decimal"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-3">
              Notas del Turno (opcional)
            </label>
            <textarea
              value={notas}
              onChange={(e) => setNotas(e.target.value)}
              className="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Observaciones del inicio del turno..."
              rows="3"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 px-6 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-xl"
          >
            {loading ? (
              <>
                <RefreshCw className="w-5 h-5 mr-3 animate-spin" />
                Abriendo Caja...
              </>
            ) : (
              <>
                <Unlock className="w-5 h-5 mr-3" />
                Abrir Caja
              </>
            )}
          </button>
        </form>

        {/* Botón de Resolución de Inconsistencias */}
        <div className="mt-6 pt-6 border-t border-gray-200">
          <button
            onClick={async () => {
              if (window.confirm('🔧 ¿Resolver inconsistencias de turnos?\n\nEsto cerrará automáticamente cualquier turno que haya quedado abierto incorrectamente.')) {
                try {
                  const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=cerrar_turno_emergencia`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario_id: user?.id || 1 })
                  });
                  const result = await response.json();
                  if (result.success) {
                    alert('✅ Inconsistencias resueltas. Ahora puedes abrir la caja normalmente.');
                    window.location.reload();
                  } else {
                    alert('❌ Error al resolver inconsistencias: ' + (result.error || 'Error desconocido'));
                  }
                } catch (error) {
                  alert('❌ Error de conexión: ' + error.message);
                }
              }
            }}
            className="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-medium text-sm transition-colors flex items-center justify-center"
          >
            <AlertTriangle className="w-4 h-4 mr-2" />
            Resolver Inconsistencias de Turno
          </button>
        </div>

        {/* Cajero */}
        <div className="mt-8 pt-6 border-t border-gray-200 text-center">
          <div className="flex items-center justify-center text-sm text-gray-600">
            <div className="p-2 bg-gray-100 rounded-lg mr-3">
              <User className="w-4 h-4" />
            </div>
            <span className="font-medium">Cajero: {user?.nombre || 'Usuario'}</span>
          </div>
        </div>
      </div>
    </div>
  );
};

// ========== DASHBOARD PRINCIPAL MEJORADO ==========
const DashboardPrincipal = ({ datosControl, resumenMetodos, movimientos, onRefresh, onNuevoMovimiento }) => {
  const { user } = useAuth();
  const cajeroNombre = datosControl?.cajero_nombre || user?.nombre || 'Usuario';
  
  // Estados para el modal de cierre de caja
  const [showModalCierre, setShowModalCierre] = useState(false);
  const [datoscierre, setDatosCierre] = useState({
    efectivoContado: '',
    observaciones: '',
    fecha: new Date().toLocaleDateString('es-AR'),
    hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
    cajero: cajeroNombre
  });

  const [procesandoCierre, setProcesandoCierre] = useState(false);

  // 🔒 FUNCIÓN ROBUSTA PARA CALCULAR EFECTIVO ESPERADO
  const calcularEfectivoEsperado = (datosControl) => {
    // Buscar efectivo_teorico del backend 
    const efectivoTeorico = parseFloat(
      datosControl?.turno?.efectivo_teorico || 
      datosControl?.efectivo_teorico || 
      0
    );
    
    // Cálculo manual CORREGIDO como fallback
    const apertura = parseFloat(datosControl?.monto_apertura || 0);
    const entradas = parseFloat(datosControl?.total_entradas_efectivo || 0);  
    const salidas = Math.abs(parseFloat(datosControl?.salidas_efectivo_reales || 0));
    
    // 🔧 CORRECCIÓN: total_entradas_efectivo ya incluye apertura + ingresos
    // Si no tenemos efectivo_teorico del backend, usar el total_entradas
    const fallbackCalculo = entradas - salidas;
    
    return efectivoTeorico > 0 ? efectivoTeorico : fallbackCalculo;
  };

  // Función para procesar el cierre de caja
  const procesarCierreCaja = async () => {
    if (!datoscierre.efectivoContado) {
      alert('❌ Debe ingresar el efectivo contado');
      return;
    }

    // MANTENER EXACTITUD DECIMAL - Solo validar sin convertir automáticamente
    const efectivoContado = datoscierre.efectivoContado;
    if (!efectivoContado || isNaN(Number(efectivoContado)) || Number(efectivoContado) < 0) {
      alert('❌ El monto debe ser un número válido mayor o igual a 0');
      return;
    }

    setProcesandoCierre(true);

    if (efectivoContado && efectivoContado >= 0) {
      try {
        // Calcular el efectivo esperado del sistema CORREGIDO - usar función robusta
        const efectivoEsperado = calcularEfectivoEsperado(datosControl);
        
        // Calcular diferencia exacta sin redondeo automático
        const diferencia = Number(efectivoContado) - efectivoEsperado;

        // 🚀 LLAMADA REAL A LA API PARA CERRAR LA CAJA
        const cierreData = {
          usuario_id: user?.id || datosControl?.usuario_id || 1,
          monto_cierre: efectivoContado, // ENVIAR COMO STRING PARA PRESERVAR EXACTITUD
          notas: datoscierre.observaciones || `Efectivo esperado: $${efectivoEsperado.toLocaleString('es-AR')} | Efectivo contado: $${Number(efectivoContado).toLocaleString('es-AR')} | Diferencia: $${diferencia.toLocaleString('es-AR')} | Cajero: ${datoscierre.cajero}`
        };
        
        // 🔐 USAR EL ENDPOINT PRINCIPAL DE GESTIÓN DE CAJA
        const apiUrl = `${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=cerrar_caja`;
        
        // Enviar los datos como JSON en el body (como espera la función cerrarCaja)
        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(cierreData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const resultado = await response.json();
        
        if (!resultado.success) {
          throw new Error(resultado.error || 'Error desconocido del servidor');
        }
        
        // 🎉 CIERRE EXITOSO
        alert(`✅ Caja cerrada exitosamente!\n\n📊 RESUMEN DEL CIERRE:\n💰 Efectivo esperado: $${efectivoEsperado.toLocaleString('es-AR')}\n💵 Efectivo contado: $${Number(efectivoContado).toLocaleString('es-AR')}\n📈 Diferencia: $${diferencia.toLocaleString('es-AR')}\n🕒 Hora de cierre: ${datoscierre.hora}\n👤 Cajero: ${datoscierre.cajero}\n📝 Observaciones: ${datoscierre.observaciones || 'Ninguna'}`);

        // Cerrar modal y reiniciar
        setShowModalCierre(false);
        setDatosCierre({
          efectivoContado: '',
          observaciones: '',
          cajero: user?.nombre || 'Usuario',
          fecha: new Date().toLocaleDateString('es-AR'),
          hora: new Date().toLocaleTimeString('es-AR')
        });
        
        // Recargar datos
        onRefresh();
        
      } catch (error) {
        console.error('❌ Error completo en cierre:', error);
        alert(`❌ Error al cerrar la caja:\n${error.message}`);
      } finally {
        setProcesandoCierre(false);
      }
    } else {
      alert('❌ Efectivo contado es requerido');
    }
  };

  const abrirModalCierre = () => {
    setDatosCierre({
      efectivoContado: '',
      observaciones: '',
      fecha: new Date().toLocaleDateString('es-AR'),
      hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
      cajero: cajeroNombre
    });
    setShowModalCierre(true);
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-800 mb-2">📊 Control de Caja</h1>
              <p className="text-gray-600">Gestión completa de efectivo y movimientos</p>
            </div>
            
            <div className="flex items-center space-x-4">
              <div className="flex items-center bg-green-100 text-green-800 px-4 py-2 rounded-full">
                <CheckCircle className="w-5 h-5 mr-2" />
                <span className="font-medium">Caja Abierta</span>
              </div>
              <button
                onClick={abrirModalCierre}
                className="flex items-center bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors"
              >
                <Lock className="w-5 h-5 mr-2" />
                Cerrar Caja
              </button>
              <button
                onClick={onRefresh}
                className="flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors"
              >
                <RefreshCw className="w-5 h-5 mr-2" />
                Actualizar
              </button>
            </div>
          </div>
        </div>

        {/* Métricas Principales */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mx-auto mb-4">
              <Unlock className="w-6 h-6 text-green-600" />
            </div>
            <p className="text-sm text-gray-600 mb-1">Efectivo de Apertura</p>
            <p className="text-2xl font-bold text-gray-800">${parseFloat(datosControl?.monto_apertura || 0).toLocaleString('es-AR')}</p>
            <p className="text-xs text-gray-500 mt-1">Monto inicial del turno</p>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mx-auto mb-4">
              <TrendingUp className="w-6 h-6 text-blue-600" />
            </div>
            <p className="text-sm text-gray-600 mb-1">Entradas en Efectivo</p>
            <p className="text-2xl font-bold text-gray-800">${parseFloat(datosControl?.total_entradas_efectivo || 0).toLocaleString('es-AR')}</p>
            <p className="text-xs text-gray-500 mt-1">Ventas + Ingresos en efectivo</p>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-red-100 rounded-lg mx-auto mb-4">
              <TrendingDown className="w-6 h-6 text-red-600" />
            </div>
            <p className="text-sm text-gray-600 mb-1">Salidas en Efectivo</p>
            <p className="text-2xl font-bold text-gray-800">${Math.abs(parseFloat(datosControl?.salidas_efectivo_reales || 0)).toLocaleString('es-AR')}</p>
            <p className="text-xs text-gray-500 mt-1">Egresos del turno</p>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div className="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mx-auto mb-4">
              <DollarSign className="w-6 h-6 text-purple-600" />
            </div>
            <p className="text-sm text-gray-600 mb-1">Efectivo Disponible</p>
            <p className="text-2xl font-bold text-gray-800">${(parseFloat(datosControl?.monto_apertura || 0) + parseFloat(datosControl?.total_entradas_efectivo || 0) - Math.abs(parseFloat(datosControl?.salidas_efectivo_reales || 0))).toLocaleString('es-AR')}</p>
            <p className="text-xs text-gray-500 mt-1">Apertura + Entradas - Salidas</p>
          </div>
        </div>

        {/* Resumen de Métodos de Pago - TURNO ACTUAL */}
        <div className="bg-white rounded-xl shadow-sm border-2 border-blue-300 p-6 mb-8">
          {/* Header con claridad total */}
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-bold text-gray-800 flex items-center">
              <CreditCard className="w-6 h-6 mr-3 text-blue-600" />
              💳 Ventas por Método de Pago
            </h2>
            <div className="bg-blue-100 border border-blue-300 px-4 py-2 rounded-lg">
              <p className="text-sm font-bold text-blue-800">⏰ SOLO TURNO ACTUAL</p>
            </div>
          </div>

          {/* Banner explicativo súper visible */}
          <div className="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 mb-6">
            <div className="flex items-start">
              <div className="p-2 bg-blue-100 rounded-lg mr-3">
                <Info className="w-5 h-5 text-blue-600" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-bold text-blue-900 mb-2">
                  📊 Importante: Estas ventas son SOLO del turno actual
                </p>
                <p className="text-xs text-blue-800 leading-relaxed">
                  • <strong>Turno abierto desde:</strong> {datosControl?.fecha_apertura ? new Date(datosControl.fecha_apertura).toLocaleString('es-AR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }) : 'N/A'}
                  <br />
                  • <strong>¿Por qué $0?</strong> No se han hecho ventas en este turno aún
                  <br />
                  • <strong>Ver ventas del día completo:</strong> Ir al Dashboard o Reportes de Ventas
                </p>
              </div>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="bg-green-50 rounded-xl border border-green-200 p-6 text-center">
              <div className="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mx-auto mb-4">
                <Banknote className="w-6 h-6 text-green-600" />
              </div>
              <p className="text-sm text-green-600 mb-1 font-medium">Efectivo</p>
              <p className="text-2xl font-bold text-green-800">${parseFloat(datosControl?.ventas_efectivo_reales || 0).toLocaleString('es-AR')}</p>
              <p className="text-xs text-green-600 mt-1">Del turno actual</p>
            </div>

            <div className="bg-blue-50 rounded-xl border border-blue-200 p-6 text-center">
              <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mx-auto mb-4">
                <ArrowRightLeft className="w-6 h-6 text-blue-600" />
              </div>
              <p className="text-sm text-blue-600 mb-1 font-medium">Transferencia</p>
              <p className="text-2xl font-bold text-blue-800">${parseFloat(datosControl?.ventas_transferencia_reales || 0).toLocaleString('es-AR')}</p>
              <p className="text-xs text-blue-600 mt-1">Del turno actual</p>
            </div>

            <div className="bg-purple-50 rounded-xl border border-purple-200 p-6 text-center">
              <div className="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mx-auto mb-4">
                <CreditCard className="w-6 h-6 text-purple-600" />
              </div>
              <p className="text-sm text-purple-600 mb-1 font-medium">Tarjeta</p>
              <p className="text-2xl font-bold text-purple-800">${parseFloat(datosControl?.ventas_tarjeta_reales || 0).toLocaleString('es-AR')}</p>
              <p className="text-xs text-purple-600 mt-1">Del turno actual</p>
            </div>

            <div className="bg-orange-50 rounded-xl border border-orange-200 p-6 text-center">
              <div className="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mx-auto mb-4">
                <QrCode className="w-6 h-6 text-orange-600" />
              </div>
              <p className="text-sm text-orange-600 mb-1 font-medium">Pago QR</p>
              <p className="text-2xl font-bold text-orange-800">${parseFloat(datosControl?.ventas_qr_reales || 0).toLocaleString('es-AR')}</p>
              <p className="text-xs text-orange-600 mt-1">Del turno actual</p>
            </div>
          </div>
        </div>

      {/* Layout de Historial y Formulario Mejorado */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Historial de Movimientos (2/3) */}
        <div className="lg:col-span-2">
          <HistorialMovimientosMejorado movimientos={movimientos} />
        </div>

        {/* Formulario de Movimientos (1/3) */}
        <div className="lg:col-span-1">
          <FormularioMovimientosMejorado onMovimientoRegistrado={onNuevoMovimiento} />
        </div>
      </div>

      {/* 🔒 MODAL DE CIERRE DE CAJA CON RESUMEN DETALLADO */}
      {showModalCierre && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl max-w-4xl w-full p-8 max-h-[90vh] overflow-y-auto">
            <div className="text-center mb-6">
              <div className="p-4 bg-red-100 rounded-full inline-block mb-4">
                <Lock className="w-8 h-8 text-red-600" />
              </div>
              <h2 className="text-2xl font-bold text-gray-800">Cerrar Turno de Caja</h2>
              <p className="text-gray-600">Resumen completo y cierre del turno</p>
            </div>

            {/* 📊 RESUMEN DETALLADO DEL TURNO */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
              
              {/* 📋 Información del Turno */}
              <div className="bg-blue-50 rounded-xl p-4">
                <h3 className="font-semibold text-blue-800 mb-3 flex items-center">
                  <Calendar className="w-5 h-5 mr-2" />
                  Información del Turno
                </h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-blue-700">Cajero:</span>
                    <span className="font-semibold text-blue-900">{datoscierre.cajero}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-blue-700">Fecha:</span>
                    <span className="font-semibold text-blue-900">{datoscierre.fecha}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-blue-700">Hora cierre:</span>
                    <span className="font-semibold text-blue-900">{datoscierre.hora}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-blue-700">Duración turno:</span>
                    <span className="font-semibold text-blue-900">
                      {/* Calcular duración basada en apertura */}
                      {datosControl?.fecha_apertura ? 
                        `${Math.round((new Date() - new Date(datosControl.fecha_apertura)) / (1000 * 60 * 60))}h ${Math.round(((new Date() - new Date(datosControl.fecha_apertura)) % (1000 * 60 * 60)) / (1000 * 60))}m` 
                        : 'N/A'}
                    </span>
                  </div>
                </div>
              </div>

              {/* 💰 Resumen Financiero */}
              <div className="bg-green-50 rounded-xl p-4">
                <h3 className="font-semibold text-green-800 mb-3 flex items-center">
                  <DollarSign className="w-5 h-5 mr-2" />
                  Resumen Financiero
                </h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-green-700">💵 Apertura:</span>
                    <span className="font-semibold text-green-900">
                      ${parseFloat(datosControl?.monto_apertura || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-green-700">🛒 Ventas efectivo:</span>
                    <span className="font-semibold text-green-900">
                      ${parseFloat(datosControl?.ventas_efectivo_reales || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-green-700">📈 Ingresos manuales:</span>
                    <span className="font-semibold text-green-900">
                      ${(parseFloat(datosControl?.total_entradas_efectivo || 0) - parseFloat(datosControl?.monto_apertura || 0) - parseFloat(datosControl?.ventas_efectivo_reales || 0)).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between border-t border-green-200 pt-2">
                    <span className="text-green-700 font-medium">💚 Total Entradas:</span>
                    <span className="font-bold text-green-900">
                      ${parseFloat(datosControl?.total_entradas_efectivo || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-red-700">📉 Salidas:</span>
                    <span className="font-semibold text-red-900">
                      ${Math.abs(parseFloat(datosControl?.salidas_efectivo_reales || 0)).toLocaleString('es-AR')}
                    </span>
                  </div>
                </div>
              </div>

              {/* 📊 Métodos de Pago */}
              <div className="bg-purple-50 rounded-xl p-4">
                <h3 className="font-semibold text-purple-800 mb-3 flex items-center">
                  <CreditCard className="w-5 h-5 mr-2" />
                  Ventas por Método
                </h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-purple-700">💵 Efectivo:</span>
                    <span className="font-semibold text-purple-900">
                      ${parseFloat(datosControl?.ventas_efectivo_reales || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-purple-700">💳 Tarjeta:</span>
                    <span className="font-semibold text-purple-900">
                      ${parseFloat(datosControl?.ventas_tarjeta_reales || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-purple-700">🏦 Transferencia:</span>
                    <span className="font-semibold text-purple-900">
                      ${parseFloat(datosControl?.ventas_transferencia_reales || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-purple-700">📱 QR:</span>
                    <span className="font-semibold text-purple-900">
                      ${parseFloat(datosControl?.ventas_qr_reales || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                  <div className="flex justify-between border-t border-purple-200 pt-2">
                    <span className="text-purple-700 font-medium">🛍️ Total Ventas:</span>
                    <span className="font-bold text-purple-900">
                      ${parseFloat(datosControl?.total_ventas_hoy || 0).toLocaleString('es-AR')}
                    </span>
                  </div>
                </div>
              </div>

              {/* 🎯 Efectivo Esperado vs Contado */}
              <div className="bg-yellow-50 rounded-xl p-4">
                <h3 className="font-semibold text-yellow-800 mb-3 flex items-center">
                  <Calculator className="w-5 h-5 mr-2" />
                  Cálculo Final
                </h3>
                <div className="space-y-3">
                  <div className="bg-yellow-100 rounded-lg p-3">
                    <div className="text-center">
                      <p className="text-yellow-700 text-sm mb-1">💰 Efectivo Esperado</p>
                      <p className="text-2xl font-bold text-yellow-900">
                        ${calcularEfectivoEsperado(datosControl).toLocaleString('es-AR')}
                      </p>
                      <p className="text-xs text-yellow-600 mt-1">
                        Total Entradas (${parseFloat(datosControl?.total_entradas_efectivo || 0).toLocaleString('es-AR')}) - 
                        Salidas (${Math.abs(parseFloat(datosControl?.salidas_efectivo_reales || 0)).toLocaleString('es-AR')})
                      </p>
                      <p className="text-xs text-yellow-500 mt-1">
                        (Total Entradas = Apertura $${parseFloat(datosControl?.monto_apertura || 0).toLocaleString('es-AR')} + Ingresos $${(parseFloat(datosControl?.total_entradas_efectivo || 0) - parseFloat(datosControl?.monto_apertura || 0)).toLocaleString('es-AR')})
                      </p>
                    </div>
                  </div>
                  
                  {datoscierre.efectivoContado && (
                    <div className="bg-white rounded-lg p-3 border-2 border-yellow-200">
                      <div className="text-center">
                        <p className="text-gray-700 text-sm mb-1">💵 Efectivo Contado</p>
                        <p className="text-2xl font-bold text-gray-900">
                          ${parseFloat(datoscierre.efectivoContado || 0).toLocaleString('es-AR')}
                        </p>
                        <div className="mt-2">
                          {(() => {
                            const esperado = calcularEfectivoEsperado(datosControl);
                            const contado = parseFloat(datoscierre.efectivoContado || 0);
                                                    // Calcular diferencia sin redondeo forzado
                        const diferencia = contado - esperado;
                            
                            return (
                              <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                                diferencia === 0 ? 'bg-green-100 text-green-800' :
                                diferencia > 0 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'
                              }`}>
                                {diferencia === 0 ? '✅ Exacto' :
                                 diferencia > 0 ? `📈 Sobrante: $${diferencia.toLocaleString('es-AR')}` :
                                 `📉 Faltante: $${Math.abs(diferencia).toLocaleString('es-AR')}`}
                              </div>
                            );
                          })()}
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* 📝 FORMULARIO DE CIERRE */}
            <div className="space-y-4 bg-gray-50 rounded-xl p-6">

              {/* Efectivo contado */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  💵 Efectivo Contado Físicamente *
                </label>
                <div className="relative">
                  <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type="text"
                    value={datoscierre.efectivoContado}
                    onChange={(e) => {
                      const valor = e.target.value;
                      // Permitir solo números y punto decimal
                      if (valor === '' || /^\d*\.?\d*$/.test(valor)) {
                        setDatosCierre({...datoscierre, efectivoContado: valor});
                      }
                    }}
                    className="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    placeholder="0.00"
                    inputMode="decimal"
                    required
                  />
                </div>
              </div>

              {/* Observaciones */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  📝 Observaciones
                </label>
                <textarea
                  value={datoscierre.observaciones}
                  onChange={(e) => setDatosCierre({...datoscierre, observaciones: e.target.value})}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                  placeholder="Observaciones sobre el turno..."
                  rows="3"
                />
              </div>

              {/* Botones */}
              <div className="flex space-x-4 pt-6">
                <button
                  onClick={() => setShowModalCierre(false)}
                  disabled={procesandoCierre}
                  className="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50 font-medium"
                >
                  Cancelar
                </button>
                <button
                  onClick={procesarCierreCaja}
                  disabled={procesandoCierre || !datoscierre.efectivoContado}
                  className="flex-1 px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center font-medium"
                >
                  {procesandoCierre ? (
                    <>
                      <RefreshCw className="w-5 h-5 mr-2 animate-spin" />
                      Cerrando Turno...
                    </>
                  ) : (
                    <>
                      <Lock className="w-5 h-5 mr-2" />
                      Confirmar Cierre
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      
      </div>
    </div>
  );
};

// ========== HISTORIAL MEJORADO ========== 
const HistorialMovimientosMejorado = ({ movimientos }) => {
  if (!movimientos || movimientos.length === 0) {
    return (
      <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
          <Receipt className="w-6 h-6 mr-3 text-indigo-600" />
          Historial de Movimientos
        </h2>
        <div className="text-center py-12">
          <div className="p-4 bg-gray-100 rounded-2xl inline-block mb-4">
            <Receipt className="w-12 h-12 text-gray-400" />
          </div>
          <p className="text-gray-500 text-lg">No hay movimientos registrados</p>
          <p className="text-gray-400 text-sm mt-2">Los movimientos aparecerán aquí cuando se registren</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <Receipt className="w-6 h-6 mr-3 text-indigo-600" />
        Historial de Movimientos
      </h2>
      
      <div className="overflow-hidden rounded-xl border border-gray-200">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Fecha/Hora</th>
              <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700">Descripción</th>
              <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700">Monto</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {movimientos.slice(0, 10).map((movimiento, index) => (
              <tr key={index} className="hover:bg-gray-50 transition-colors">
                <td className="px-6 py-4">
                  <div className="flex items-center">
                    <Clock className="w-4 h-4 mr-2 text-gray-400" />
                    <span className="text-sm text-gray-600">{movimiento.fecha_formateada}</span>
                  </div>
                </td>
                <td className="px-6 py-4">
                  <div>
                    <p className="font-medium text-gray-800">{movimiento.descripcion}</p>
                    <p className="text-xs text-gray-500 mt-1">{movimiento.categoria}</p>
                  </div>
                </td>
                <td className="px-6 py-4 text-right">
                  <span className={`text-lg font-bold ${
                    movimiento.tipo === 'ingreso' || movimiento.tipo === 'venta' || movimiento.tipo === 'apertura' || movimiento.monto > 0 
                      ? 'text-green-600' 
                      : 'text-red-600'
                  }`}>
                    {movimiento.tipo === 'ingreso' || movimiento.tipo === 'venta' || movimiento.tipo === 'apertura' || movimiento.monto > 0 ? '+' : ''}
                    ${Math.abs(movimiento.monto).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

// ========== FORMULARIO MEJORADO ==========
const FormularioMovimientosMejorado = ({ onMovimientoRegistrado }) => {
  const { user } = useAuth();
  const [tipoMovimiento, setTipoMovimiento] = useState('ingreso');
  const [categoria, setCategoria] = useState('');
  const [monto, setMonto] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [referencia, setReferencia] = useState('');
  const [loading, setLoading] = useState(false);

  const categorias = {
    ingreso: ['Depósito', 'Ajuste Positivo', 'Devolución', 'Ingresos Varios'],
    egreso: ['Mercadería', 'Retiro Efectivo', 'Pago Servicios', 'Gastos Varios', 'Otros Egresos']
  };

  const montosRapidos = [5000, 10000, 20000, 50000];

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!categoria || !monto || !descripcion) {
      alert('Complete todos los campos obligatorios');
      return;
    }

    try {
      setLoading(true);
      
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=registrar_movimiento`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          tipo: tipoMovimiento,
          categoria: categoria,
          monto: parseFloat(monto),
          descripcion: descripcion,
          referencia: referencia
        })
      });

      const data = await response.json();
      
      if (data.success) {
        // Limpiar formulario
        setMonto('');
        setDescripcion('');
        setReferencia('');
        setCategoria('');
        
        if (onMovimientoRegistrado) {
          onMovimientoRegistrado(data);
        }
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Error registrando movimiento:', error);
      alert('Error: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
      <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
        <Plus className="w-5 h-5 mr-2 text-green-600" />
        Registrar Movimiento
      </h3>
      
      <form onSubmit={handleSubmit} className="space-y-6">
        
        {/* Tipo de Movimiento - Mejorado */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-3">
            Tipo de Movimiento
          </label>
          <div className="grid grid-cols-2 gap-3">
            <button
              type="button"
              onClick={() => setTipoMovimiento('ingreso')}
              className={`p-4 rounded-xl border-2 font-semibold transition-all duration-300 ${
                tipoMovimiento === 'ingreso'
                  ? 'border-green-500 bg-green-50 text-green-700 shadow-lg'
                  : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
              }`}
            >
              <TrendingUp className="w-5 h-5 mx-auto mb-2" />
              Ingreso
            </button>
            <button
              type="button"
              onClick={() => setTipoMovimiento('egreso')}
              className={`p-4 rounded-xl border-2 font-semibold transition-all duration-300 ${
                tipoMovimiento === 'egreso'
                  ? 'border-red-500 bg-red-50 text-red-700 shadow-lg'
                  : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
              }`}
            >
              <TrendingDown className="w-5 h-5 mx-auto mb-2" />
              Egreso
            </button>
          </div>
        </div>

        {/* Categoría */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-3">
            Categoría
          </label>
          <select
            value={categoria}
            onChange={(e) => setCategoria(e.target.value)}
            className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">Seleccionar categoría...</option>
            {categorias[tipoMovimiento].map((cat) => (
              <option key={cat} value={cat}>{cat}</option>
            ))}
          </select>
        </div>

        {/* Monto */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-3">
            Monto
          </label>
          <div className="relative mb-3">
            <DollarSign className="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              value={monto}
              onChange={(e) => {
                const valor = e.target.value;
                // Permitir solo números y punto decimal
                if (valor === '' || /^\d*\.?\d*$/.test(valor)) {
                  setMonto(valor);
                }
              }}
              className="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0.00"
              inputMode="decimal"
              required
            />
          </div>
          
          <div className="grid grid-cols-4 gap-2">
            {montosRapidos.map((valor) => (
              <button
                key={valor}
                type="button"
                onClick={() => setMonto(valor.toString())}
                className="px-3 py-2 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                ${(valor/1000)}K
              </button>
            ))}
          </div>
        </div>

        {/* Descripción */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-3">
            Descripción
          </label>
          <textarea
            value={descripcion}
            onChange={(e) => setDescripcion(e.target.value)}
            className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
            placeholder="Describe el motivo del movimiento..."
            rows="3"
            required
          />
        </div>

        {/* Referencia */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-3">
            Referencia (opcional)
          </label>
          <input
            type="text"
            value={referencia}
            onChange={(e) => setReferencia(e.target.value)}
            className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Nº factura, comprobante..."
          />
        </div>

        {/* Botón de acción */}
        <button
          type="submit"
          disabled={loading}
          className={`w-full py-4 px-6 rounded-xl font-semibold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-lg ${
            tipoMovimiento === 'ingreso'
              ? 'bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800'
              : 'bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800'
          }`}
        >
          {loading ? (
            <>
              <RefreshCw className="w-5 h-5 mr-2 animate-spin" />
              Registrando...
            </>
          ) : (
            <>
              {tipoMovimiento === 'ingreso' ? <Plus className="w-5 h-5 mr-2" /> : <Minus className="w-5 h-5 mr-2" />}
              Registrar {tipoMovimiento === 'ingreso' ? 'Ingreso' : 'Egreso'}
            </>
          )}
        </button>
      </form>
    </div>
  );
};

// ========== MODAL DE APERTURA AMIGABLE ==========
const ModalAperturaAmigable = ({ datos, setDatos, ultimoCierre, procesando, onConfirmar, onCancelar }) => {
  const montosRapidos = [5000, 10000, 15000, 20000, 50000];

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto">
        <div className="text-center mb-6">
          <div className="p-4 bg-green-100 rounded-full inline-block mb-4">
            <Unlock className="w-8 h-8 text-green-600" />
          </div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Abrir Caja</h2>
          <p className="text-gray-600">Complete los datos para iniciar el turno</p>
        </div>

        <div className="space-y-6">
          {/* Información de fecha y cajero */}
          <div className="bg-blue-50 rounded-xl p-4">
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span className="font-semibold text-blue-800">📅 Fecha:</span>
                <p className="text-blue-700">{datos.fecha}</p>
              </div>
              <div>
                <span className="font-semibold text-blue-800">🕐 Hora:</span>
                <p className="text-blue-700">{datos.hora}</p>
              </div>
              <div className="col-span-2">
                <span className="font-semibold text-blue-800">👤 Cajero:</span>
                <p className="text-blue-700">{datos.cajero}</p>
              </div>
            </div>
          </div>

          {/* Referencia del último cierre */}
          {ultimoCierre && (
            <div className="bg-yellow-50 rounded-xl p-4">
              <h3 className="font-semibold text-yellow-800 mb-2">📋 Último Cierre</h3>
              <div className="text-sm text-yellow-700 space-y-1">
                <p><span className="font-medium">Turno:</span> #{ultimoCierre.id}</p>
                <p><span className="font-medium">Fecha:</span> {ultimoCierre.fecha_cierre}</p>
                <p><span className="font-medium">Efectivo final:</span> ${parseFloat(ultimoCierre.monto_cierre || 0).toLocaleString('es-AR')}</p>
                <p><span className="font-medium">Diferencia:</span> 
                  <span className={`ml-1 ${parseFloat(ultimoCierre.diferencia || 0) === 0 ? 'text-green-600' : 'text-red-600'}`}>
                    ${parseFloat(ultimoCierre.diferencia || 0).toLocaleString('es-AR')}
                  </span>
                </p>
              </div>
            </div>
          )}

          {/* Monto inicial */}
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-3">
              💵 Monto Inicial de Apertura *
            </label>
            <div className="relative mb-4">
              <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                value={datos.montoInicial}
                onChange={(e) => {
                  const valor = e.target.value;
                  // Permitir solo números y punto decimal
                  if (valor === '' || /^\d*\.?\d*$/.test(valor)) {
                    setDatos(prev => ({ ...prev, montoInicial: valor }));
                  }
                }}
                className="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg"
                placeholder="0.00"
                inputMode="decimal"
                required
              />
            </div>

            {/* Montos rápidos */}
            <div className="grid grid-cols-5 gap-2">
              {montosRapidos.map(monto => (
                <button
                  key={monto}
                  type="button"
                  onClick={() => setDatos(prev => ({ ...prev, montoInicial: monto.toString() }))}
                  className="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                >
                  ${(monto/1000)}K
                </button>
              ))}
            </div>
          </div>

          {/* Observaciones */}
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">
              📝 Observaciones (opcional)
            </label>
            <textarea
              value={datos.observaciones}
              onChange={(e) => setDatos(prev => ({ ...prev, observaciones: e.target.value }))}
              className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
              rows="3"
              placeholder="Notas sobre la apertura del turno..."
            />
          </div>

          {/* Resumen */}
          {datos.montoInicial && (
            <div className="bg-green-50 rounded-xl p-4">
              <h3 className="font-semibold text-green-800 mb-2">💰 Resumen de Apertura</h3>
              <p className="text-lg font-bold text-green-600">
                Efectivo inicial: ${parseFloat(datos.montoInicial || 0).toLocaleString('es-AR')}
              </p>
              <p className="text-sm text-green-700 mt-1">
                Cajero: {datos.cajero} | {datos.fecha} {datos.hora}
              </p>
            </div>
          )}
        </div>

        {/* Botones */}
        <div className="flex gap-4 mt-8">
          <button
            onClick={onCancelar}
            disabled={procesando}
            className="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            onClick={onConfirmar}
            disabled={procesando || !datos.montoInicial}
            className="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors disabled:opacity-50"
          >
            {procesando ? (
              <div className="flex items-center justify-center">
                <div className="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                Abriendo...
              </div>
            ) : (
              <div className="flex items-center justify-center">
                <Unlock className="w-5 h-5 mr-2" />
                Abrir Caja
              </div>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

// ========== COMPONENTE PRINCIPAL ==========
const GestionCajaMejorada = () => {
  const { user } = useAuth();
  const [cajaAbierta, setCajaAbierta] = useState(false);
  const [loading, setLoading] = useState(true);
  const [datosControl, setDatosControl] = useState(null);

  // 🆕 Estados del modal de apertura amigable
  const [showModalApertura, setShowModalApertura] = useState(false);
  const [datosApertura, setDatosApertura] = useState({
    montoInicial: '',
    observaciones: '',
    fecha: new Date().toLocaleDateString('es-AR'),
    hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
    cajero: user?.nombre || 'Usuario'
  });
  const [ultimoCierre, setUltimoCierre] = useState(null);
  const [loadingUltimoCierre, setLoadingUltimoCierre] = useState(false);
  const [procesandoApertura, setProcesandoApertura] = useState(false);

  const [resumenMetodos, setResumenMetodos] = useState(null);
  const [movimientos, setMovimientos] = useState([]);

  const cargarDatos = useCallback(async () => {
    try {
      setLoading(true);
      
      // Verificar estado básico
      const estadoResponse = await fetch(
        `${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=estado_caja&usuario_id=${user?.id || 1}&_t=${Date.now()}`
      );
      const estadoData = await estadoResponse.json();
      
      if (estadoData.success) {
        setCajaAbierta(estadoData.caja_abierta);
        
        // Solo si la caja está abierta, cargar datos
        if (estadoData.caja_abierta && estadoData.turno) {
          // Datos básicos del turno + datos completos del estado
          let datosCompletos = { 
            ...estadoData.turno,
            turno: estadoData.turno  // 🔒 MANTENER referencia al turno completo
          };
          
          // 🔄 OBTENER VENTAS REALES DEL TURNO ACTUAL (NO TODO EL DÍA)
          let ventasEfectivoTotal = 0;
          let ventasTransferenciaTotal = 0;
          let ventasTarjetaTotal = 0;
          let ventasQrTotal = 0;
          let ingresosManualesTotales = 0;
          
          try {
            // Obtener movimientos del turno activo
            const movResponse = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=historial_movimientos&usuario_id=${user?.id || 1}&limite=50`);
            const movData = await movResponse.json();
            
            if (movData.success && movData.movimientos) {
              // 🔧 CORRECCIÓN: Usar datos del turno para ventas (solo del turno actual)
              // Las ventas están en los totales del turno, no todo el día
              
              // VENTAS por método de pago desde el estado del turno (solo este turno)
              if (estadoData.turno) {
                ventasEfectivoTotal = parseFloat(estadoData.turno.ventas_efectivo || 0);
                ventasTransferenciaTotal = parseFloat(estadoData.turno.ventas_transferencia || 0);
                ventasTarjetaTotal = parseFloat(estadoData.turno.ventas_tarjeta || 0);
                ventasQrTotal = parseFloat(estadoData.turno.ventas_qr || 0);
              }
              
              // INGRESOS MANUALES desde movimientos
              movData.movimientos.forEach(mov => {
                const monto = parseFloat(mov.monto) || 0;
                
                if (mov.tipo === 'ingreso' && monto > 0) {
                  ingresosManualesTotales += monto;
                }
              });
              
              // Asignar valores calculados - SEPARANDO ventas de ingresos totales
              datosCompletos.ventas_efectivo_reales = ventasEfectivoTotal; // Solo ventas en efectivo
              datosCompletos.ventas_transferencia_reales = ventasTransferenciaTotal;
              datosCompletos.ventas_tarjeta_reales = ventasTarjetaTotal;
              datosCompletos.ventas_qr_reales = ventasQrTotal;
              datosCompletos.total_ventas_hoy = ventasEfectivoTotal + ventasTransferenciaTotal + ventasTarjetaTotal + ventasQrTotal;
              
              // 🏦 TOTAL DE ENTRADAS EN EFECTIVO (ventas + ingresos manuales)
              datosCompletos.total_entradas_efectivo = ventasEfectivoTotal + ingresosManualesTotales;
              
              // 🔢 CALCULAR SALIDAS REALES DE EFECTIVO (solo egresos, no ventas)
              const salidasEfectivo = movData.movimientos
                .filter(mov => mov.tipo === 'egreso')
                .reduce((total, mov) => total + Math.abs(parseFloat(mov.monto)), 0);
              datosCompletos.salidas_efectivo_reales = salidasEfectivo;
              
              // Preparar movimientos para mostrar
              const movimientosCompletos = movData.movimientos
                .filter(mov => mov.tipo === 'ingreso' || mov.tipo === 'egreso' || mov.tipo === 'venta' || mov.tipo === 'apertura')
                .map(mov => ({
                  tipo: mov.tipo,
                  categoria: mov.categoria,
                  monto: parseFloat(mov.monto),
                  descripcion: mov.descripcion,
                  referencia: mov.referencia || '',
                  fecha_formateada: mov.fecha_formateada,
                  usuario_nombre: mov.usuario_nombre || 'Usuario'
                }));
                
              setMovimientos(movimientosCompletos.slice(0, 15));
            }
          } catch (error) {
            console.error('Error obteniendo ventas reales:', error);
          }
          
          setDatosControl(datosCompletos);
        } else {
          // Limpiar datos si caja cerrada
          setDatosControl(null);
          setResumenMetodos(null);
          setMovimientos([]);
        }
      }
    } catch (error) {
      console.error('Error cargando datos:', error);
      setCajaAbierta(false);
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  const handleAperturaExitosa = (data) => {
    setCajaAbierta(true);
    cargarDatos();
  };

  // 🔓 Función para obtener último cierre y abrir modal de apertura
  const abrirModalApertura = async () => {
    setLoadingUltimoCierre(true);
    try {
      // Obtener último cierre para referencia
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=ultimo_cierre&usuario_id=${user?.id || 1}`);
      const data = await response.json();
      
      if (data.success && data.ultimo_cierre) {
        setUltimoCierre(data.ultimo_cierre);
        // Sugerir el efectivo del último cierre como monto inicial (como string sin modificar)
        setDatosApertura(prev => ({
          ...prev,
          montoInicial: (data.ultimo_cierre.monto_cierre || '').toString(),
          referenciaUltimoCierre: `Turno #${data.ultimo_cierre.id} - ${data.ultimo_cierre.fecha_cierre}`
        }));
      } else {
        setUltimoCierre(null);
      }
    } catch (error) {
      console.error('Error obteniendo último cierre:', error);
      setUltimoCierre(null);
    } finally {
      setLoadingUltimoCierre(false);
      setShowModalApertura(true);
    }
  };

  // 🔓 Función para procesar apertura de caja con verificación manual
  const procesarAperturaCaja = async () => {
    setProcesandoApertura(true);
    try {
      // 🔥 PRIMER LLAMADA: Verificar si requiere validación manual
      const responseInicial = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=abrir_caja`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          monto_apertura: parseFloat(datosApertura.montoInicial || 0),
          notas: datosApertura.observaciones || ''
        })
      });

      const dataInicial = await responseInicial.json();
      
      // 🔥 Si requiere verificación manual
      if (!dataInicial.success && dataInicial.requiere_verificacion) {
        const efectivoEsperado = dataInicial.efectivo_esperado;
        const ultimoCierreInfo = dataInicial.ultimo_cierre;
        
        // Mostrar modal de verificación manual
        const efectivoContado = prompt(
          `🔍 VERIFICACIÓN MANUAL DE EFECTIVO\n\n` +
          `💰 Según el último cierre (Turno #${ultimoCierreInfo.id}):\n` +
          `Efectivo esperado: $${parseFloat(efectivoEsperado).toLocaleString('es-AR')}\n\n` +
          `Por favor, cuenta físicamente el efectivo en la caja y ingresa el monto real:\n\n` +
          `💡 Esta verificación es importante para detectar faltantes o sobrantes desde el inicio del turno.`
        );
        
        if (efectivoContado === null) {
          alert('❌ Apertura cancelada');
          return;
        }
        
        const efectivoContadoNum = parseFloat(efectivoContado);
        if (isNaN(efectivoContadoNum) || efectivoContadoNum < 0) {
          alert('❌ Por favor ingrese un monto válido');
          return;
        }
        
        // 🔥 SEGUNDA LLAMADA: Enviar efectivo contado
        const responseVerificacion = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=abrir_caja`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            usuario_id: user?.id || 1,
            monto_apertura: parseFloat(datosApertura.montoInicial || 0),
            efectivo_contado: efectivoContado,
            notas: datosApertura.observaciones || ''
          })
        });
        
        const dataVerificacion = await responseVerificacion.json();
        
        if (dataVerificacion.success) {
          const diferencia = dataVerificacion.diferencia_apertura;
          let mensaje = '✅ Caja abierta exitosamente con verificación\n\n';
          mensaje += `💰 Efectivo esperado: $${parseFloat(efectivoEsperado).toLocaleString('es-AR')}\n`;
          mensaje += `💰 Efectivo contado: $${parseFloat(efectivoContado).toLocaleString('es-AR')}\n`;
          
          if (diferencia === 0) {
            mensaje += `✅ Diferencia: $0 (Exacto)`;
          } else if (diferencia > 0) {
            mensaje += `📈 Diferencia: +$${Math.abs(diferencia).toLocaleString('es-AR')} (Sobrante)`;
          } else {
            mensaje += `📉 Diferencia: -$${Math.abs(diferencia).toLocaleString('es-AR')} (Faltante)`;
          }
          
          alert(mensaje);
        } else {
          throw new Error(dataVerificacion.error || 'Error en la verificación');
        }
      } 
      // 🔥 Si es primera apertura (sin cierre anterior)
      else if (dataInicial.success) {
        alert('✅ Caja abierta exitosamente (primera apertura)');
      } 
      else {
        throw new Error(dataInicial.error || 'Error al abrir la caja');
      }
      
      // Cerrar modal y refrescar
      setShowModalApertura(false);
      setDatosApertura({
        montoInicial: '',
        observaciones: '',
        fecha: new Date().toLocaleDateString('es-AR'),
        hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
        cajero: user?.nombre || 'Usuario'
      });
      cargarDatos();
      
    } catch (error) {
      console.error('Error al abrir caja:', error);
      alert('❌ Error al abrir la caja: ' + error.message);
    } finally {
      setProcesandoApertura(false);
    }
  };

  const handleNuevoMovimiento = () => {
    // Pequeño retraso para asegurar que el backend procese el movimiento
    setTimeout(() => {
      cargarDatos();
    }, 500);
  };

  useEffect(() => {
    // Solo cargar una vez al montar el componente
    cargarDatos();
  }, [user?.id]); // CORREGIDO: Solo depender del user.id

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-lg text-gray-600">Cargando gestión de caja...</p>
        </div>
      </div>
    );
  }

  if (!cajaAbierta) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full">
          <div className="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div className="p-4 bg-green-100 rounded-full inline-block mb-6">
              <Unlock className="w-8 h-8 text-green-600" />
            </div>
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Caja Cerrada</h2>
            <p className="text-gray-600 mb-8">
              No hay un turno activo. Debe abrir la caja para comenzar a operar.
            </p>
            <button
              onClick={() => abrirModalApertura()}
              disabled={loadingUltimoCierre}
              className="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors disabled:opacity-50"
            >
              {loadingUltimoCierre ? (
                <div className="flex items-center justify-center">
                  <div className="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                  Preparando apertura...
                </div>
              ) : (
                <div className="flex items-center justify-center">
                  <Unlock className="w-5 h-5 mr-2" />
                  Abrir Caja
                </div>
              )}
            </button>
          </div>
        </div>

        {/* Modal de Apertura Amigable */}
        {showModalApertura && (
          <ModalAperturaAmigable
            datos={datosApertura}
            setDatos={setDatosApertura}
            ultimoCierre={ultimoCierre}
            procesando={procesandoApertura}
            onConfirmar={procesarAperturaCaja}
            onCancelar={() => setShowModalApertura(false)}
          />
        )}
      </div>
    );
  }

  return (
    <DashboardPrincipal 
      datosControl={datosControl}
      resumenMetodos={resumenMetodos}
      movimientos={movimientos}
      onRefresh={cargarDatos}
      onNuevoMovimiento={handleNuevoMovimiento}
    />
  );
};

export default GestionCajaMejorada;
