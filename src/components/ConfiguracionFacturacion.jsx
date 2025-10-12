/**
 * src/components/ConfiguracionFacturacion.jsx
 * Configuración de facturación AFIP por método de pago
 * Permite seleccionar qué métodos requieren factura fiscal
 * RELEVANT FILES: api/config_facturacion.php, api/gestionar_configuracion_facturacion.php
 */

import React, { useState, useEffect } from 'react';
import { Receipt, CreditCard, Banknote, ArrowRightLeft, QrCode, Save, RefreshCw } from 'lucide-react';
import CONFIG from '../config/config';

const ConfiguracionFacturacion = () => {
  const [configuracion, setConfiguracion] = useState([]);
  const [loading, setLoading] = useState(true);
  const [guardando, setGuardando] = useState(false);

  // Cargar configuración actual
  const cargarConfiguracion = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${CONFIG.API_URL}/api/gestionar_configuracion_facturacion.php?_t=${Date.now()}`);
      const data = await response.json();
      
      if (data.success) {
        setConfiguracion(data.configuracion);
      }
    } catch (error) {
      console.error('Error cargando configuración:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    cargarConfiguracion();
  }, []);

  // Cambiar configuración de un método
  const toggleMetodo = async (metodo_pago) => {
    try {
      setGuardando(true);
      
      const configActual = configuracion.find(c => c.metodo_pago === metodo_pago);
      const nuevoEstado = !configActual.requiere_factura;
      
      const response = await fetch(`${CONFIG.API_URL}/api/gestionar_configuracion_facturacion.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          metodo_pago,
          requiere_factura: nuevoEstado
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setConfiguracion(data.configuracion);
        alert(`✅ ${metodo_pago.toUpperCase()}: ${nuevoEstado ? 'SÍ factura' : 'NO factura'}`);
      }
    } catch (error) {
      alert('❌ Error al actualizar: ' + error.message);
    } finally {
      setGuardando(false);
    }
  };

  const getIcono = (metodo) => {
    const iconos = {
      'efectivo': Banknote,
      'tarjeta': CreditCard,
      'transferencia': ArrowRightLeft,
      'qr': QrCode
    };
    return iconos[metodo] || Receipt;
  };

  const getNombre = (metodo) => {
    const nombres = {
      'efectivo': 'Efectivo',
      'tarjeta': 'Tarjeta',
      'transferencia': 'Transferencia',
      'qr': 'QR / Digital'
    };
    return nombres[metodo] || metodo;
  };

  if (loading) {
    return (
      <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div className="flex items-center justify-center py-8">
          <RefreshCw className="w-6 h-6 animate-spin text-blue-500 mr-3" />
          <span className="text-gray-600">Cargando configuración...</span>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-xl font-bold text-gray-800 flex items-center">
            <Receipt className="w-6 h-6 mr-3 text-blue-600" />
            Configuración de Facturación AFIP
          </h3>
          <p className="text-sm text-gray-600 mt-1">
            Selecciona qué métodos de pago requieren factura fiscal
          </p>
        </div>
        
        <button
          onClick={cargarConfiguracion}
          disabled={guardando}
          className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
        >
          <RefreshCw className={`w-4 h-4 mr-2 ${guardando ? 'animate-spin' : ''}`} />
          Actualizar
        </button>
      </div>

      {/* Información importante */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div className="flex items-start">
          <Receipt className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
          <div className="text-sm text-blue-800">
            <p className="font-semibold mb-2">🎯 Configuración Inteligente</p>
            <p className="mb-2">
              Activa/desactiva la facturación AFIP según el método de pago.
              Útil para controlar costos administrativos y cumplir con tus necesidades fiscales.
            </p>
            <p className="text-xs text-blue-600">
              ✅ Los métodos marcados generarán Factura C (Monotributo) automáticamente
            </p>
          </div>
        </div>
      </div>

      {/* Grid de métodos de pago */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {configuracion.map((config) => {
          const Icono = getIcono(config.metodo_pago);
          const factura = Boolean(config.requiere_factura);
          
          return (
            <div
              key={config.metodo_pago}
              className={`border-2 rounded-lg p-4 transition-all cursor-pointer ${
                factura 
                  ? 'border-green-500 bg-green-50 shadow-md' 
                  : 'border-gray-200 bg-white hover:border-gray-300'
              }`}
              onClick={() => toggleMetodo(config.metodo_pago)}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <Icono className={`w-8 h-8 mr-3 ${factura ? 'text-green-600' : 'text-gray-400'}`} />
                  <div>
                    <h4 className="font-semibold text-gray-800 text-lg">
                      {getNombre(config.metodo_pago)}
                    </h4>
                    <p className={`text-sm ${factura ? 'text-green-600' : 'text-gray-500'}`}>
                      {factura ? '✅ SÍ factura AFIP' : '❌ NO factura'}
                    </p>
                  </div>
                </div>
                
                {/* Toggle visual */}
                <div className={`w-14 h-8 rounded-full relative transition-colors ${
                  factura ? 'bg-green-500' : 'bg-gray-300'
                }`}>
                  <div className={`absolute top-1 w-6 h-6 bg-white rounded-full shadow-md transition-all ${
                    factura ? 'right-1' : 'left-1'
                  }`} />
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Resumen */}
      <div className="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 className="font-semibold text-gray-800 mb-2 flex items-center">
          <Save className="w-4 h-4 mr-2" />
          Resumen de Configuración
        </h4>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-gray-600">Métodos que facturan:</p>
            <p className="font-bold text-green-600">
              {configuracion.filter(c => c.requiere_factura).length} de {configuracion.length}
            </p>
          </div>
          <div>
            <p className="text-gray-600">Tipo de factura:</p>
            <p className="font-bold text-blue-600">Factura C (Monotributo)</p>
          </div>
        </div>
      </div>

      {/* Nota informativa */}
      <div className="mt-4 text-xs text-gray-500 text-center">
        💡 Los cambios se aplican inmediatamente. Click en cada método para activar/desactivar facturación.
      </div>
    </div>
  );
};

export default ConfiguracionFacturacion;








