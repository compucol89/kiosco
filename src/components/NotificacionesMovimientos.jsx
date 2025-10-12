/**
 * src/components/NotificacionesMovimientos.jsx
 * Sistema de notificaciones para movimientos importantes de caja
 * Muestra alertas para diferencias, cierres, aperturas y eventos críticos
 * RELEVANT FILES: src/App.jsx, src/components/IndicadorEstadoCaja.jsx, api/gestion_caja_completa.php
 */

import React, { useState, useEffect } from 'react';
import { 
  AlertTriangle, 
  CheckCircle, 
  Info, 
  X,
  DollarSign,
  TrendingUp,
  TrendingDown,
  Clock,
  User
} from 'lucide-react';
import CONFIG from '../config/config';

const NotificacionesMovimientos = () => {
  const [notificaciones, setNotificaciones] = useState([]);
  const [ultimaVerificacion, setUltimaVerificacion] = useState(Date.now());

  // 🔄 Verificar eventos nuevos
  const verificarEventosNuevos = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=historial_completo&usuario_id=1&limite=5&desde=${ultimaVerificacion}&_t=${Date.now()}`);
      const data = await response.json();

      if (data.success && data.historial) {
        const eventosNuevos = data.historial.filter(evento => 
          new Date(evento.fecha_hora).getTime() > ultimaVerificacion
        );

        // Crear notificaciones para eventos nuevos
        eventosNuevos.forEach(evento => {
          const notificacion = crearNotificacionDeEvento(evento);
          if (notificacion) {
            agregarNotificacion(notificacion);
          }
        });

        setUltimaVerificacion(Date.now());
      }
    } catch (error) {
      console.error('Error verificando eventos:', error);
    }
  };

  // 📋 Crear notificación basada en evento
  const crearNotificacionDeEvento = (evento) => {
    const diferencia = parseFloat(evento.diferencia || 0);
    const monto = parseFloat(evento.monto_inicial || evento.efectivo_teorico || 0);

    // 🚨 Diferencias significativas (más de $1000)
    if (evento.tipo_evento === 'cierre' && Math.abs(diferencia) > 1000) {
      return {
        id: `diferencia_${evento.id}_${Date.now()}`,
        tipo: diferencia > 0 ? 'warning' : 'error',
        titulo: diferencia > 0 ? '💰 Sobrante Detectado' : '⚠️ Faltante Detectado',
        mensaje: `${evento.cajero_nombre} reportó una diferencia de $${Math.abs(diferencia).toLocaleString('es-AR')} en el cierre del turno #${evento.numero_turno}`,
        timestamp: Date.now(),
        datos: { evento, diferencia, tipo: 'diferencia' }
      };
    }

    // ✅ Cierre exacto (sin diferencias)
    if (evento.tipo_evento === 'cierre' && diferencia === 0 && monto > 50000) {
      return {
        id: `cierre_exacto_${evento.id}_${Date.now()}`,
        tipo: 'success',
        titulo: '✅ Cierre Perfecto',
        mensaje: `${evento.cajero_nombre} cerró el turno #${evento.numero_turno} sin diferencias. Monto: $${monto.toLocaleString('es-AR')}`,
        timestamp: Date.now(),
        datos: { evento, tipo: 'cierre_exacto' }
      };
    }

    // 🔓 Apertura con monto alto
    if (evento.tipo_evento === 'apertura' && monto > 100000) {
      return {
        id: `apertura_alta_${evento.id}_${Date.now()}`,
        tipo: 'info',
        titulo: '🔓 Apertura con Monto Alto',
        mensaje: `${evento.cajero_nombre} abrió caja con $${monto.toLocaleString('es-AR')}`,
        timestamp: Date.now(),
        datos: { evento, monto, tipo: 'apertura_alta' }
      };
    }

    return null;
  };

  // ➕ Agregar notificación
  const agregarNotificacion = (notificacion) => {
    setNotificaciones(prev => {
      // Evitar duplicados
      if (prev.some(n => n.id === notificacion.id)) {
        return prev;
      }
      
      // Mantener máximo 5 notificaciones
      const nuevas = [notificacion, ...prev].slice(0, 5);
      return nuevas;
    });

    // Auto-eliminar después de 10 segundos para notificaciones de info/success
    if (notificacion.tipo === 'info' || notificacion.tipo === 'success') {
      setTimeout(() => {
        eliminarNotificacion(notificacion.id);
      }, 10000);
    }
  };

  // ❌ Eliminar notificación
  const eliminarNotificacion = (id) => {
    setNotificaciones(prev => prev.filter(n => n.id !== id));
  };

  // 🎨 Obtener estilo según tipo
  const obtenerEstiloNotificacion = (tipo) => {
    switch (tipo) {
      case 'error':
        return {
          bg: 'bg-red-50',
          border: 'border-red-200',
          text: 'text-red-800',
          icon: AlertTriangle,
          iconColor: 'text-red-600'
        };
      case 'warning':
        return {
          bg: 'bg-orange-50',
          border: 'border-orange-200', 
          text: 'text-orange-800',
          icon: AlertTriangle,
          iconColor: 'text-orange-600'
        };
      case 'success':
        return {
          bg: 'bg-green-50',
          border: 'border-green-200',
          text: 'text-green-800',
          icon: CheckCircle,
          iconColor: 'text-green-600'
        };
      case 'info':
      default:
        return {
          bg: 'bg-blue-50',
          border: 'border-blue-200',
          text: 'text-blue-800',
          icon: Info,
          iconColor: 'text-blue-600'
        };
    }
  };

  // 🔄 Verificar cada 30 segundos
  useEffect(() => {
    verificarEventosNuevos();
    
    const interval = setInterval(verificarEventosNuevos, 30000);
    
    return () => clearInterval(interval);
  }, [ultimaVerificacion]);

  // 🧪 Agregar notificación de prueba (solo para desarrollo)
  const agregarNotificacionPrueba = () => {
    const tipos = ['error', 'warning', 'success', 'info'];
    const tipo = tipos[Math.floor(Math.random() * tipos.length)];
    
    const mensajes = {
      error: 'Faltante de $2,500 detectado en cierre',
      warning: 'Sobrante de $1,200 en turno actual',
      success: 'Cierre perfecto sin diferencias',
      info: 'Nueva apertura con $85,000'
    };

    agregarNotificacion({
      id: `prueba_${Date.now()}`,
      tipo,
      titulo: `🧪 ${tipo.toUpperCase()} - Prueba`,
      mensaje: mensajes[tipo],
      timestamp: Date.now(),
      datos: { tipo: 'prueba' }
    });
  };

  if (notificaciones.length === 0) {
    return null;
  }

  return (
    <div className="fixed top-20 right-4 z-50 space-y-3 max-w-sm">
      {notificaciones.map(notificacion => {
        const estilo = obtenerEstiloNotificacion(notificacion.tipo);
        const IconoNotificacion = estilo.icon;

        return (
          <div
            key={notificacion.id}
            className={`${estilo.bg} ${estilo.border} border rounded-lg p-4 shadow-lg transform transition-all duration-300 animate-slide-in-right`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start gap-3">
                <IconoNotificacion className={`w-5 h-5 mt-0.5 ${estilo.iconColor}`} />
                <div className="flex-1">
                  <h4 className={`font-semibold ${estilo.text} text-sm`}>
                    {notificacion.titulo}
                  </h4>
                  <p className={`${estilo.text} text-sm mt-1 opacity-90`}>
                    {notificacion.mensaje}
                  </p>
                  <div className="flex items-center gap-2 mt-2 text-xs opacity-75">
                    <Clock className="w-3 h-3" />
                    <span>
                      {new Date(notificacion.timestamp).toLocaleTimeString('es-AR', {
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </span>
                  </div>
                </div>
              </div>
              
              <button
                onClick={() => eliminarNotificacion(notificacion.id)}
                className={`${estilo.iconColor} hover:opacity-70 transition-opacity`}
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </div>
        );
      })}

      {/* Botón de prueba (solo desarrollo) */}
      {process.env.NODE_ENV === 'development' && (
        <button
          onClick={agregarNotificacionPrueba}
          className="mt-4 px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded transition-colors"
        >
          🧪 Prueba
        </button>
      )}
    </div>
  );
};

export default NotificacionesMovimientos;
















