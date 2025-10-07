/**
 * src/components/IndicadorEstadoCaja.jsx
 * Indicador de estado de caja en tiempo real para la barra superior
 * Muestra estado actual, efectivo disponible y alertas importantes
 * RELEVANT FILES: src/contexts/CajaContext.jsx, src/components/App.jsx
 */

import React, { useState, useEffect } from 'react';
import { 
  DollarSign, 
  Lock, 
  Unlock, 
  AlertTriangle, 
  CheckCircle, 
  Clock,
  Wifi,
  WifiOff
} from 'lucide-react';
import CONFIG from '../config/config';

const IndicadorEstadoCaja = () => {
  const [estadoCaja, setEstadoCaja] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [ultimaActualizacion, setUltimaActualizacion] = useState(null);

  // 🔄 Obtener estado de caja
  const obtenerEstadoCaja = async () => {
    try {
      setError(false);
      const response = await fetch(`${CONFIG.API_URL}/api/pos_status.php?_t=${Date.now()}`);
      const data = await response.json();
      
      if (data.success) {
        setEstadoCaja(data);
        setUltimaActualizacion(new Date());
      } else {
        setError(true);
      }
    } catch (error) {
      console.error('Error obteniendo estado de caja:', error);
      setError(true);
    } finally {
      setLoading(false);
    }
  };

  // 🔄 Actualizar cada 15 segundos
  useEffect(() => {
    obtenerEstadoCaja();
    
    const interval = setInterval(obtenerEstadoCaja, 15000);
    
    return () => clearInterval(interval);
  }, []);

  // 🎨 Determinar color y estilo según estado
  const getEstiloEstado = () => {
    if (loading || error) {
      return {
        bg: 'bg-gray-100',
        text: 'text-gray-600',
        border: 'border-gray-300',
        icon: error ? WifiOff : Clock
      };
    }

    if (estadoCaja?.caja_abierta) {
      return {
        bg: 'bg-green-50',
        text: 'text-green-700',
        border: 'border-green-200',
        icon: Unlock
      };
    } else {
      return {
        bg: 'bg-red-50',
        text: 'text-red-700',
        border: 'border-red-200',
        icon: Lock
      };
    }
  };

  const estilo = getEstiloEstado();
  const IconoEstado = estilo.icon;

  if (loading) {
    return (
      <div className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg border border-gray-200">
        <Clock className="w-4 h-4 text-gray-500 animate-pulse" />
        <span className="text-sm text-gray-600">Cargando...</span>
      </div>
    );
  }

  return (
    <div className={`flex items-center gap-3 px-4 py-2 rounded-lg border ${estilo.bg} ${estilo.border} transition-all duration-300`}>
      {/* Icono de estado */}
      <div className="flex items-center gap-2">
        <IconoEstado className={`w-4 h-4 ${estilo.text}`} />
        <span className={`text-sm font-medium ${estilo.text}`}>
          {error ? 'Sin conexión' : 
           loading ? 'Cargando...' :
           estadoCaja?.caja_abierta ? 'Caja Abierta' : 'Caja Cerrada'}
        </span>
      </div>

      {/* Efectivo disponible */}
      {estadoCaja?.caja_abierta && (
        <div className="flex items-center gap-1 border-l border-green-300 pl-3">
          <DollarSign className="w-4 h-4 text-green-600" />
          <span className="text-sm font-bold text-green-700">
            ${parseFloat(estadoCaja.efectivo_disponible || 0).toLocaleString('es-AR')}
          </span>
        </div>
      )}

      {/* Indicador de conexión */}
      <div className="flex items-center gap-1">
        {error ? (
          <WifiOff className="w-3 h-3 text-red-500" />
        ) : (
          <Wifi className="w-3 h-3 text-green-500" />
        )}
        {ultimaActualizacion && (
          <span className="text-xs text-gray-500">
            {ultimaActualizacion.toLocaleTimeString('es-AR', { 
              hour: '2-digit', 
              minute: '2-digit',
              second: '2-digit'
            })}
          </span>
        )}
      </div>

      {/* Botón de actualización manual */}
      <button
        onClick={obtenerEstadoCaja}
        className="p-1 hover:bg-white hover:bg-opacity-50 rounded transition-colors"
        title="Actualizar estado"
      >
        <CheckCircle className="w-3 h-3 text-gray-500 hover:text-gray-700" />
      </button>
    </div>
  );
};

export default IndicadorEstadoCaja;














