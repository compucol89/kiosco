// src/components/productos/hooks/useProductos.js
// Hook personalizado para gestión de productos
// Centraliza la lógica de carga y estado de productos
// RELEVANT FILES: ProductosPage.jsx, api/productos.php

import { useState, useCallback } from 'react';
import axios from 'axios';
import CONFIG from '../../../config/config';
import { showSuccess, showError } from '../../../utils/toastNotifications';

export const useProductos = () => {
  const [productos, setProductos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // ⚡ OPTIMIZACIÓN: Cargar productos con useCallback
  const cargarProductos = useCallback(async () => {
    setLoading(true);
    try {
      // ⚡ CARGAR TODOS LOS PRODUCTOS para página de administración
      const response = await axios.get(`${CONFIG.API_URL}/api/productos.php?admin=true`);
      
      if (response.data && Array.isArray(response.data)) {
        const productosLimpios = [];
        
        response.data.forEach((item, index) => {
          if (item && typeof item === 'object') {
            const producto = {
              id: String(item.id || `temp_${index}`),
              nombre: String(item.nombre || 'Sin nombre'),
              categoria: String(item.categoria || 'Sin categoría'),
              precio_venta: parseFloat(item.precio_venta || 0),
              precio_costo: parseFloat(item.precio_costo || 0),
              stock: parseInt(item.stock_actual || item.stock || 0),
              codigo: String(item.barcode || item.codigo || ''),
              descripcion: String(item.descripcion || ''),
              aplica_descuento_forma_pago: Boolean(item.aplica_descuento_forma_pago !== undefined ? item.aplica_descuento_forma_pago : true)
            };
            
            productosLimpios.push(producto);
          }
        });
        
        setProductos(productosLimpios);
      } else {
        setProductos([]);
      }
    } catch (error) {
      console.error('Error al cargar productos:', error);
      setError('Error al cargar productos');
      setProductos([]);
    }
    setLoading(false);
  }, []);

  // ⚡ OPTIMIZACIÓN: Eliminar producto
  const eliminarProducto = useCallback(async (producto) => {
    if (!window.confirm(`¿Está seguro de eliminar "${producto.nombre}"?\n\nEsta acción no se puede deshacer.`)) {
      return false;
    }

    try {
      const response = await axios.delete(`${CONFIG.API_URL}/api/productos.php`, {
        data: { id: producto.id }
      });

      if (response.data && response.data.success) {
        await cargarProductos(); // Recargar lista
        showSuccess(`🗑️ ${producto.nombre} eliminado correctamente`);
        return true;
      } else {
        throw new Error(response.data?.message || 'Error al eliminar producto');
      }
    } catch (error) {
      console.error('Error al eliminar producto:', error);
      showError(`❌ Error al eliminar ${producto.nombre}: ${error.message}`);
      return false;
    }
  }, [cargarProductos]);

  return {
    productos,
    loading,
    error,
    cargarProductos,
    eliminarProducto
  };
};
