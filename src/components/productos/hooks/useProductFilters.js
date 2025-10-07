// src/components/productos/hooks/useProductFilters.js
// Hook para aplicar filtros avanzados a productos
// Memoizado para optimización de rendimiento
// RELEVANT FILES: ProductFilters.jsx, useProductSearch.js

import { useMemo } from 'react';

export const useProductFilters = (productos, filtros) => {
  const productosFiltrados = useMemo(() => {
    if (!productos || Object.keys(filtros).length === 0) {
      return productos;
    }

    return productos.filter(producto => {
      // Filtro por categoría
      if (filtros.categoria && filtros.categoria !== 'todos') {
        if (producto.categoria !== filtros.categoria) return false;
      }

      // Filtro por estado de stock
      if (filtros.estadoStock && filtros.estadoStock !== 'todos') {
        const stock = producto.stock;
        switch (filtros.estadoStock) {
          case 'sin_stock':
            if (stock !== 0) return false;
            break;
          case 'critico':
            if (stock < 1 || stock > 3) return false;
            break;
          case 'bajo':
            if (stock < 4 || stock > 10) return false;
            break;
          case 'normal':
            if (stock < 11 || stock > 50) return false;
            break;
          case 'alto':
            if (stock <= 50) return false;
            break;
        }
      }

      // Filtro por rentabilidad
      if (filtros.rentabilidad && filtros.rentabilidad !== 'todos') {
        const margen = producto.precio_costo > 0 
          ? ((producto.precio_venta - producto.precio_costo) / producto.precio_costo) * 100 
          : 0;
        
        switch (filtros.rentabilidad) {
          case 'perdida':
            if (margen > 0) return false;
            break;
          case 'baja':
            if (margen < 1 || margen > 10) return false;
            break;
          case 'media':
            if (margen < 11 || margen > 25) return false;
            break;
          case 'alta':
            if (margen < 26 || margen > 50) return false;
            break;
          case 'muy_alta':
            if (margen <= 50) return false;
            break;
        }
      }

      // Filtro por rango de precio
      if (filtros.rangoPrecio && filtros.rangoPrecio !== 'todos') {
        const precio = producto.precio_venta;
        switch (filtros.rangoPrecio) {
          case 'bajo':
            if (precio > 1000) return false;
            break;
          case 'medio':
            if (precio <= 1000 || precio > 5000) return false;
            break;
          case 'alto':
            if (precio <= 5000 || precio > 15000) return false;
            break;
          case 'premium':
            if (precio <= 15000) return false;
            break;
        }
      }

      // Filtros rápidos
      if (filtros.necesita_reposicion === 'true') {
        if (producto.stock > 5) return false;
      }

      if (filtros.alta_rentabilidad === 'true') {
        const margen = producto.precio_costo > 0 
          ? ((producto.precio_venta - producto.precio_costo) / producto.precio_costo) * 100 
          : 0;
        if (margen < 30) return false;
      }

      // Aquí podrías agregar más filtros como:
      // - sin_imagen: productos sin imagen
      // - nuevos_productos: productos agregados recientemente
      // etc.

      return true;
    });
  }, [productos, filtros]);

  return { productosFiltrados };
};
