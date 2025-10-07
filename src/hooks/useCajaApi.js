/**
 * src/hooks/useCajaApi.js
 * Hook personalizado para manejar todas las operaciones de la API de caja
 * Centraliza llamadas API y optimiza performance
 * RELEVANT FILES: src/components/GestionCajaMejorada.jsx, api/gestion_caja_completa.php
 */

import { useState, useCallback } from 'react';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

export const useCajaApi = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // 🔄 FUNCIÓN OPTIMIZADA: Una sola llamada para obtener todo el estado
  const obtenerEstadoCompleto = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      // Una sola llamada API que obtiene todo lo necesario
      const response = await fetch(
        `${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=estado_completo&usuario_id=${user?.id || 1}&_t=${Date.now()}`
      );
      
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.error || 'Error obteniendo estado de caja');
      }

      return {
        cajaAbierta: data.caja_abierta,
        turno: data.turno,
        movimientos: data.movimientos || [],
        ventasPorMetodo: data.ventas_por_metodo || {},
        estadisticas: data.estadisticas || {}
      };
    } catch (err) {
      console.error('Error en obtenerEstadoCompleto:', err);
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  // 🔓 FUNCIÓN OPTIMIZADA: Apertura de caja con verificación
  const abrirCaja = useCallback(async (datos) => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=abrir_caja`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          ...datos
        })
      });

      const data = await response.json();
      
      if (!data.success) {
        if (data.requiere_verificacion) {
          return { requiereVerificacion: true, ...data };
        }
        throw new Error(data.error || 'Error abriendo caja');
      }

      return data;
    } catch (err) {
      console.error('Error en abrirCaja:', err);
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  // 🔒 FUNCIÓN OPTIMIZADA: Cierre de caja
  const cerrarCaja = useCallback(async (datos) => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=cerrar_caja`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          ...datos
        })
      });

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.error || 'Error cerrando caja');
      }

      return data;
    } catch (err) {
      console.error('Error en cerrarCaja:', err);
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  // 💰 FUNCIÓN OPTIMIZADA: Registrar movimiento
  const registrarMovimiento = useCallback(async (movimiento) => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=registrar_movimiento`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: user?.id || 1,
          ...movimiento
        })
      });

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.error || 'Error registrando movimiento');
      }

      return data;
    } catch (err) {
      console.error('Error en registrarMovimiento:', err);
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  // 📊 FUNCIÓN OPTIMIZADA: Obtener último cierre
  const obtenerUltimoCierre = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(
        `${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=ultimo_cierre&usuario_id=${user?.id || 1}&_t=${Date.now()}`
      );
      
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.error || 'Error obteniendo último cierre');
      }

      return data.ultimo_cierre;
    } catch (err) {
      console.error('Error en obtenerUltimoCierre:', err);
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [user?.id]);

  return {
    loading,
    error,
    obtenerEstadoCompleto,
    abrirCaja,
    cerrarCaja,
    registrarMovimiento,
    obtenerUltimoCierre
  };
};














