// src/components/productos/hooks/useProductStats.js
// Hook para calcular estadísticas de productos optimizado
// Usa memoización para evitar cálculos innecesarios
// RELEVANT FILES: ProductosPage.jsx, useProductos.js

import { useMemo } from 'react';

export const useProductStats = (productos) => {
  // ⚡ OPTIMIZACIÓN: Estadísticas memoizadas
  const estadisticas = useMemo(() => {
    if (!productos || productos.length === 0) {
      return {
        totalProductos: 0,
        valorTotal: 0,
        sinStock: 0,
        bajoStock: 0,
        categorias: 0,
        stockPromedio: 0,
        margenPromedio: 0
      };
    }

    const totalProductos = productos.length;
    let valorTotal = 0;
    let sinStock = 0;
    let bajoStock = 0;
    let stockTotal = 0;
    let margenTotal = 0;
    const categoriasUnicas = new Set();

    productos.forEach(producto => {
      // Valor total del inventario
      valorTotal += (producto.precio_costo || 0) * producto.stock;
      
      // Contadores de stock
      if (producto.stock === 0) {
        sinStock++;
      } else if (producto.stock <= 5) {
        bajoStock++;
      }
      
      // Stock total para promedio
      stockTotal += producto.stock;
      
      // Margen de ganancia
      if (producto.precio_costo > 0) {
        const margen = ((producto.precio_venta - producto.precio_costo) / producto.precio_costo) * 100;
        margenTotal += margen;
      }
      
      // Categorías únicas
      if (producto.categoria) {
        categoriasUnicas.add(producto.categoria);
      }
    });

    // 🔥 NUEVAS MÉTRICAS INTELIGENTES
    const productosActivosStock = productos.filter(p => p.stock > 0).length;
    const stockCritico = productos.filter(p => p.stock > 0 && p.stock <= 3).length;
    const productosRentables = productos.filter(p => {
      const margen = p.precio_costo > 0 ? ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100 : 0;
      return margen >= 20; // Productos con margen >= 20%
    }).length;
    
    // Top categorías por valor
    const valorPorCategoria = {};
    productos.forEach(producto => {
      const categoria = producto.categoria || 'Sin categoría';
      const valorProducto = (producto.precio_costo || 0) * producto.stock;
      valorPorCategoria[categoria] = (valorPorCategoria[categoria] || 0) + valorProducto;
    });
    
    const topCategoria = Object.keys(valorPorCategoria).length > 0 
      ? Object.entries(valorPorCategoria).reduce((a, b) => valorPorCategoria[a[0]] > valorPorCategoria[b[0]] ? a : b)[0]
      : 'N/A';

    // Salud del inventario (porcentaje)
    const saludInventario = totalProductos > 0 
      ? Math.round(((productosActivosStock - stockCritico) / totalProductos) * 100)
      : 0;

    // Valor promedio por producto
    const valorPromedioPorProducto = totalProductos > 0 
      ? Math.round(valorTotal / totalProductos)
      : 0;

    return {
      // Métricas básicas
      totalProductos,
      valorTotal,
      sinStock,
      bajoStock,
      categorias: categoriasUnicas.size,
      stockPromedio: totalProductos > 0 ? Math.round(stockTotal / totalProductos) : 0,
      margenPromedio: totalProductos > 0 ? Math.round(margenTotal / totalProductos) : 0,
      
      // 🚀 NUEVAS MÉTRICAS AVANZADAS
      productosActivosStock,
      stockCritico,
      productosRentables,
      topCategoria,
      saludInventario,
      valorPromedioPorProducto,
      porcentajeActivosStock: totalProductos > 0 ? Math.round((productosActivosStock / totalProductos) * 100) : 0,
      porcentajeRentables: totalProductos > 0 ? Math.round((productosRentables / totalProductos) * 100) : 0
    };
  }, [productos]);

  return { estadisticas };
};
