import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useAuth } from './AuthContext';
import CONFIG from '../config/config';

const CajaContext = createContext();

export const useCaja = () => {
  const context = useContext(CajaContext);
  if (!context) {
    throw new Error('useCaja debe usarse dentro de CajaProvider');
  }
  return context;
};

export const CajaProvider = ({ children }) => {
  const { user } = useAuth();
  const [cajaAbierta, setCajaAbierta] = useState(false);
  const [turnoActivo, setTurnoActivo] = useState(null);
  const [loading, setLoading] = useState(true);
  const [lastCheck, setLastCheck] = useState(0);

  const verificarEstadoCaja = useCallback(async (forzarRecarga = false) => {
    // Evitar múltiples llamadas muy seguidas (cache de 10 segundos)
    const ahora = Date.now();
    if (!forzarRecarga && ahora - lastCheck < 10000) {
      return;
    }

    try {
      console.log('🔍 [CajaContext] Verificando estado de caja...');
      
      const response = await fetch(
        `${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=estado_caja&usuario_id=${user?.id || 1}&_t=${ahora}`,
        { 
          cache: 'no-cache',
          headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
          }
        }
      );
      
      const data = await response.json();
      console.log('📊 [CajaContext] Estado recibido:', data);
      
      if (data.success) {
        setCajaAbierta(data.caja_abierta);
        setTurnoActivo(data.turno);
        setLastCheck(ahora);
        
        // Guardar en localStorage para persistencia
        localStorage.setItem('caja_estado', JSON.stringify({
          abierta: data.caja_abierta,
          turno: data.turno,
          timestamp: ahora
        }));
      }
    } catch (error) {
      console.error('❌ [CajaContext] Error verificando estado:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.id, lastCheck]);

  const abrirCaja = useCallback(async (montoApertura, notas = '') => {
    try {
      console.log('🔓 [CajaContext] Abriendo caja...');
      
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
        setCajaAbierta(true);
        // Forzar recarga del estado
        await verificarEstadoCaja(true);
        return { success: true, data };
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('❌ [CajaContext] Error abriendo caja:', error);
      return { success: false, error: error.message };
    }
  }, [user?.id, verificarEstadoCaja]);

  const cerrarCaja = useCallback(async (montoCierre, notas = '') => {
    try {
      console.log('🔒 [CajaContext] Cerrando caja...');
      
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=cerrar_caja`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          monto_cierre: parseFloat(montoCierre),
          notas: notas
        })
      });

      const data = await response.json();
      
      if (data.success) {
        setCajaAbierta(false);
        setTurnoActivo(null);
        // Limpiar localStorage
        localStorage.removeItem('caja_estado');
        return { success: true, data };
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('❌ [CajaContext] Error cerrando caja:', error);
      return { success: false, error: error.message };
    }
  }, [user?.id]);

  const refrescarEstado = useCallback(() => {
    return verificarEstadoCaja(true);
  }, [verificarEstadoCaja]);

  // Verificar estado al inicializar
  useEffect(() => {
    if (user?.id) {
      // Intentar cargar desde localStorage primero
      const estadoGuardado = localStorage.getItem('caja_estado');
      if (estadoGuardado) {
        try {
          const estado = JSON.parse(estadoGuardado);
          const tiempoTranscurrido = Date.now() - estado.timestamp;
          
          // Si el estado guardado es reciente (menos de 5 minutos), usarlo
          if (tiempoTranscurrido < 300000) {
            setCajaAbierta(estado.abierta);
            setTurnoActivo(estado.turno);
            setLoading(false);
            console.log('📱 [CajaContext] Estado cargado desde localStorage');
          }
        } catch (error) {
          console.error('Error parseando estado guardado:', error);
        }
      }
      
      // Verificar estado actual del servidor
      verificarEstadoCaja();
    }
  }, [user?.id, verificarEstadoCaja]);

  // Verificar estado periódicamente cada 30 segundos
  useEffect(() => {
    if (user?.id && cajaAbierta) {
      const interval = setInterval(() => {
        verificarEstadoCaja();
      }, 30000);
      
      return () => clearInterval(interval);
    }
  }, [user?.id, cajaAbierta, verificarEstadoCaja]);

  const contextValue = {
    // Estado
    cajaAbierta,
    turnoActivo,
    loading,
    
    // Acciones
    abrirCaja,
    cerrarCaja,
    verificarEstadoCaja,
    refrescarEstado,
    
    // Métodos de utilidad
    esCajaRequerida: () => {
      // Aquí se puede agregar lógica para determinar si la página actual requiere caja abierta
      return true;
    }
  };

  return (
    <CajaContext.Provider value={contextValue}>
      {children}
    </CajaContext.Provider>
  );
};

export default CajaContext;
