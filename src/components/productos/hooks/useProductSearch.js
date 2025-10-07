// src/components/productos/hooks/useProductSearch.js
// Hook para búsqueda y filtrado optimizado de productos
// Implementa debounce y memoización para mejor rendimiento
// RELEVANT FILES: ProductosPage.jsx, useProductos.js

import { useState, useMemo, useCallback } from 'react';
import { useDebounce } from '../../../hooks/useDebounce';

export const useProductSearch = (productos) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [tipoVista, setTipoVista] = useState('list');
  const itemsPerPage = 12;

  // ⚡ OPTIMIZACIÓN: Búsqueda con debounce para evitar filtrado excesivo
  const debouncedSearchTerm = useDebounce(searchTerm, 300);

  // ⚡ OPTIMIZACIÓN: Filtrar productos con memoización
  const productosFiltrados = useMemo(() => {
    if (!debouncedSearchTerm) return productos;
    const searchLower = debouncedSearchTerm.toLowerCase();
    return productos.filter(producto => 
      producto.nombre.toLowerCase().includes(searchLower) ||
      producto.categoria.toLowerCase().includes(searchLower) ||
      producto.codigo.toLowerCase().includes(searchLower)
    );
  }, [productos, debouncedSearchTerm]);

  // ⚡ OPTIMIZACIÓN: Paginación memoizada
  const paginacionData = useMemo(() => {
    const totalPages = Math.ceil(productosFiltrados.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const productosPaginados = productosFiltrados.slice(startIndex, startIndex + itemsPerPage);
    
    return { totalPages, startIndex, productosPaginados };
  }, [productosFiltrados, currentPage, itemsPerPage]);

  // ⚡ OPTIMIZACIÓN: Resetear paginación cuando cambie la búsqueda
  const handleSearchChange = useCallback((newSearchTerm) => {
    setSearchTerm(newSearchTerm);
    setCurrentPage(1);
  }, []);

  // ⚡ OPTIMIZACIÓN: Cambiar vista
  const handleVistaChange = useCallback((nuevaVista) => {
    setTipoVista(nuevaVista);
  }, []);

  return {
    searchTerm,
    currentPage,
    tipoVista,
    productosFiltrados,
    paginacionData,
    setCurrentPage,
    handleSearchChange,
    handleVistaChange
  };
};

