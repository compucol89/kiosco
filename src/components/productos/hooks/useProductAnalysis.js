// src/components/productos/hooks/useProductAnalysis.js
// Hook para análisis de ventas y rendimiento de productos
// Optimizado con memoización y carga bajo demanda
// RELEVANT FILES: ProductosPage.jsx, api/reportes_financieros_precisos.php

import { useState, useCallback } from 'react';
import axios from 'axios';
import CONFIG from '../../../config/config';

export const useProductAnalysis = () => {
  const [datosVentas, setDatosVentas] = useState({
    ventasUltimos7Dias: 0,
    ventasUltimos30Dias: 0,
    promedioMensual: 0,
    ultimoMovimiento: null,
    rotacionEstimada: 0,
    cargando: false
  });

  // ⚡ OPTIMIZACIÓN: Análisis de ventas con useCallback - USANDO API MODERNA
  const cargarAnalisisVentas = useCallback(async (productoId, nombreProducto) => {
    setDatosVentas(prev => ({ ...prev, cargando: true }));

    try {
      console.log(`🔍 Analizando ventas para producto: ${nombreProducto} (ID: ${productoId})`);
      
      // Cargar ventas del último mes usando API moderna precisa
      const response = await axios.get(`${CONFIG.API_URL}/api/reportes_financieros_precisos.php?periodo=mes`);
      
      if (response.data && response.data.success && Array.isArray(response.data.ventas_detalladas)) {
        const todasLasVentas = response.data.ventas_detalladas;
        
        // Filtrar ventas que contienen este producto específico
        const ventasDelProducto = [];
        todasLasVentas.forEach(venta => {
          // Buscar en productos de la venta
          let productos = venta.productos || [];
          
          // También revisar en cart si existe
          if (!productos.length && venta.cart) {
            productos = venta.cart;
          }
          
          // Verificar si este producto está en la venta
          const tieneProducto = productos.some(p => {
            const id = p.id || p.producto_id;
            const nombre = p.nombre || p.producto_nombre;
            return id === productoId || nombre === nombreProducto;
          });
          
          if (tieneProducto) {
            ventasDelProducto.push({
              fecha_venta: venta.fecha || venta.fecha_hora,
              productos: productos
            });
          }
        });

        // Calcular métricas temporales
        const ahora = new Date();
        const hace7Dias = new Date(ahora.getTime() - 7 * 24 * 60 * 60 * 1000);
        const hace30Dias = new Date(ahora.getTime() - 30 * 24 * 60 * 60 * 1000);

        const ventasUltimos7Dias = ventasDelProducto.filter(venta => {
          const fechaVenta = new Date(venta.fecha_venta);
          return fechaVenta >= hace7Dias;
        }).length;

        const ventasUltimos30Dias = ventasDelProducto.filter(venta => {
          const fechaVenta = new Date(venta.fecha_venta);
          return fechaVenta >= hace30Dias;
        }).length;

        const promedioMensual = ventasUltimos30Dias > 0 ? Math.round((ventasUltimos30Dias / 30) * 30) : 0;
        const ultimoMovimiento = ventasDelProducto.length > 0 ? ventasDelProducto[0].fecha_venta : null;
        const rotacionEstimada = promedioMensual > 0 ? (30 / promedioMensual).toFixed(1) : 0;

        setDatosVentas({
          ventasUltimos7Dias,
          ventasUltimos30Dias,
          promedioMensual,
          ultimoMovimiento,
          rotacionEstimada,
          cargando: false
        });

        console.log('📊 Análisis completado:', {
          ventasUltimos7Dias,
          ventasUltimos30Dias,
          promedioMensual,
          rotacionEstimada,
          totalVentasEncontradas: ventasDelProducto.length
        });
      } else {
        throw new Error('No se pudieron cargar los datos de ventas');
      }
    } catch (error) {
      console.error('❌ Error al cargar análisis de ventas:', error);
      setDatosVentas({
        ventasUltimos7Dias: 0,
        ventasUltimos30Dias: 0,
        promedioMensual: 0,
        ultimoMovimiento: null,
        rotacionEstimada: 0,
        cargando: false
      });
    }
  }, []);

  return {
    datosVentas,
    cargarAnalisisVentas
  };
};
