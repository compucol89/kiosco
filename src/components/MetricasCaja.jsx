/**
 * src/components/MetricasCaja.jsx
 * Componente modular para mostrar métricas principales de la caja
 * Reutilizable y fácil de mantener
 * RELEVANT FILES: src/components/GestionCajaMejorada.jsx, src/hooks/useCajaLogic.js
 */

import React from 'react';
import { 
  DollarSign, 
  TrendingUp, 
  Clock, 
  Calculator,
  CreditCard,
  Banknote,
  ArrowRightLeft,
  QrCode
} from 'lucide-react';

// 🎨 COMPONENTE: Tarjeta de métrica individual
const TarjetaMetrica = ({ titulo, valor, subtitulo, icono: IconComponent, color, prefijo = '$' }) => {
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
    if (prefijo === 'h') {
      return val;
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
        <p className="text-xs opacity-70 leading-relaxed">{subtitulo}</p>
      </div>
    </div>
  );
};

// 📊 COMPONENTE PRINCIPAL: Métricas de efectivo
export const MetricasEfectivo = ({ flujoEfectivo, efectivoEsperado, metricas }) => {
  if (!flujoEfectivo) return null;

  return (
    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <DollarSign className="w-6 h-6 mr-3 text-green-600" />
        Control de Efectivo
      </h2>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <TarjetaMetrica
          titulo="Efectivo Inicial"
          valor={flujoEfectivo.inicial}
          subtitulo="Monto de apertura del turno"
          icono={Calculator}
          color="blue"
        />
        
        <TarjetaMetrica
          titulo="Entradas (+)"
          valor={flujoEfectivo.entradas}
          subtitulo="Ventas + ingresos manuales"
          icono={TrendingUp}
          color="green"
        />
        
        <TarjetaMetrica
          titulo="Salidas (-)"
          valor={flujoEfectivo.salidas}
          subtitulo="Egresos y retiros del turno"
          icono={TrendingUp}
          color="red"
        />
        
        <TarjetaMetrica
          titulo="Efectivo Esperado"
          valor={efectivoEsperado}
          subtitulo="Lo que deberías tener en caja"
          icono={Calculator}
          color="purple"
        />
      </div>
    </div>
  );
};

// 💳 COMPONENTE PRINCIPAL: Métricas de métodos de pago
export const MetricosMetodosPago = ({ resumenVentas }) => {
  if (!resumenVentas) return null;

  return (
    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <CreditCard className="w-6 h-6 mr-3 text-blue-600" />
        Resumen de Métodos de Pago
      </h2>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <TarjetaMetrica
          titulo="Efectivo"
          valor={resumenVentas.efectivo}
          subtitulo={`${resumenVentas.porcentajes.efectivo.toFixed(1)}% del total`}
          icono={Banknote}
          color="green"
        />
        
        <TarjetaMetrica
          titulo="Transferencia"
          valor={resumenVentas.transferencia}
          subtitulo={`${resumenVentas.porcentajes.transferencia.toFixed(1)}% del total`}
          icono={ArrowRightLeft}
          color="blue"
        />
        
        <TarjetaMetrica
          titulo="Tarjeta"
          valor={resumenVentas.tarjeta}
          subtitulo={`${resumenVentas.porcentajes.tarjeta.toFixed(1)}% del total`}
          icono={CreditCard}
          color="purple"
        />
        
        <TarjetaMetrica
          titulo="Pago QR"
          valor={resumenVentas.qr}
          subtitulo={`${resumenVentas.porcentajes.qr.toFixed(1)}% del total`}
          icono={QrCode}
          color="orange"
        />
      </div>
    </div>
  );
};

// ⏰ COMPONENTE PRINCIPAL: Métricas de rendimiento
export const MetricasRendimiento = ({ metricas, tiempoTurno, resumenVentas }) => {
  if (!metricas || !tiempoTurno) return null;

  return (
    <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <Clock className="w-6 h-6 mr-3 text-indigo-600" />
        Rendimiento del Turno
      </h2>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <TarjetaMetrica
          titulo="Tiempo Transcurrido"
          valor={tiempoTurno.formateado}
          subtitulo="Duración del turno actual"
          icono={Clock}
          color="indigo"
          prefijo=""
        />
        
        <TarjetaMetrica
          titulo="Ventas/Hora"
          valor={metricas.ventasPorHora}
          subtitulo="Ritmo promedio de ventas"
          icono={TrendingUp}
          color="green"
        />
        
        <TarjetaMetrica
          titulo="Total Vendido"
          valor={resumenVentas?.total || 0}
          subtitulo="Ventas totales del turno"
          icono={Calculator}
          color="blue"
        />
      </div>
    </div>
  );
};














