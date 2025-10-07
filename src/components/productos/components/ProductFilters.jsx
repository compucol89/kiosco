// src/components/productos/components/ProductFilters.jsx
// Filtros avanzados para análisis profundo de productos
// Filtrado por margen, stock, categoría y más
// RELEVANT FILES: ProductSearch.jsx, useProductSearch.js

import React, { useState } from 'react';
import { Filter, X, RotateCcw } from 'lucide-react';

const ProductFilters = ({ productos, onFilterChange, activeFilters = {} }) => {
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState(activeFilters);

  // Obtener opciones únicas para filtros
  const categorias = [...new Set(productos.map(p => p.categoria).filter(Boolean))].sort();
  
  const handleFilterChange = (key, value) => {
    const newFilters = { ...filters, [key]: value };
    if (!value || value === 'todos') {
      delete newFilters[key];
    }
    setFilters(newFilters);
    onFilterChange(newFilters);
  };

  const clearFilters = () => {
    setFilters({});
    onFilterChange({});
  };

  const activeFilterCount = Object.keys(filters).length;

  return (
    <div className="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
      {/* Header de filtros */}
      <div className="p-4 border-b border-gray-100">
        <div className="flex items-center justify-between">
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="flex items-center gap-2 text-gray-700 hover:text-gray-900 transition-colors"
          >
            <Filter className="w-5 h-5" />
            <span className="font-medium">Filtros Avanzados</span>
            {activeFilterCount > 0 && (
              <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                {activeFilterCount}
              </span>
            )}
          </button>
          
          {activeFilterCount > 0 && (
            <button
              onClick={clearFilters}
              className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors"
            >
              <RotateCcw className="w-4 h-4" />
              Limpiar
            </button>
          )}
        </div>
      </div>

      {/* Panel de filtros */}
      {showFilters && (
        <div className="p-4 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {/* Filtro por Categoría */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Categoría
              </label>
              <select
                value={filters.categoria || 'todos'}
                onChange={(e) => handleFilterChange('categoria', e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todas las categorías</option>
                {categorias.map(categoria => (
                  <option key={categoria} value={categoria}>{categoria}</option>
                ))}
              </select>
            </div>

            {/* Filtro por Estado de Stock */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Estado de Stock
              </label>
              <select
                value={filters.estadoStock || 'todos'}
                onChange={(e) => handleFilterChange('estadoStock', e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todos los estados</option>
                <option value="sin_stock">Sin Stock (0)</option>
                <option value="critico">Stock Crítico (1-3)</option>
                <option value="bajo">Stock Bajo (4-10)</option>
                <option value="normal">Stock Normal (11-50)</option>
                <option value="alto">Stock Alto (50+)</option>
              </select>
            </div>

            {/* Filtro por Rentabilidad */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Rentabilidad
              </label>
              <select
                value={filters.rentabilidad || 'todos'}
                onChange={(e) => handleFilterChange('rentabilidad', e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todas las rentabilidades</option>
                <option value="perdida">Con Pérdida (0%)</option>
                <option value="baja">Baja Rentabilidad (1-10%)</option>
                <option value="media">Media Rentabilidad (11-25%)</option>
                <option value="alta">Alta Rentabilidad (26-50%)</option>
                <option value="muy_alta">Muy Alta (50%+)</option>
              </select>
            </div>

            {/* Filtro por Rango de Precio */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Rango de Precio
              </label>
              <select
                value={filters.rangoPrecio || 'todos'}
                onChange={(e) => handleFilterChange('rangoPrecio', e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todos los precios</option>
                <option value="bajo">Bajo ($0 - $1,000)</option>
                <option value="medio">Medio ($1,001 - $5,000)</option>
                <option value="alto">Alto ($5,001 - $15,000)</option>
                <option value="premium">Premium ($15,000+)</option>
              </select>
            </div>
          </div>

          {/* Filtros rápidos */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Filtros Rápidos
            </label>
            <div className="flex flex-wrap gap-2">
              {[
                { key: 'necesita_reposicion', label: '⚠️ Necesita Reposición', value: 'true' },
                { key: 'alta_rentabilidad', label: '💰 Alta Rentabilidad', value: 'true' },
                { key: 'nuevos_productos', label: '🆕 Productos Recientes', value: 'true' },
                { key: 'sin_imagen', label: '📷 Sin Imagen', value: 'true' }
              ].map(quickFilter => (
                <button
                  key={quickFilter.key}
                  onClick={() => handleFilterChange(quickFilter.key, 
                    filters[quickFilter.key] === quickFilter.value ? null : quickFilter.value
                  )}
                  className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
                    filters[quickFilter.key] === quickFilter.value
                      ? 'bg-blue-100 border-blue-300 text-blue-700'
                      : 'bg-gray-50 border-gray-300 text-gray-600 hover:bg-gray-100'
                  }`}
                >
                  {quickFilter.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Chips de filtros activos */}
      {activeFilterCount > 0 && (
        <div className="px-4 pb-4">
          <div className="flex flex-wrap gap-2">
            {Object.entries(filters).map(([key, value]) => (
              <div
                key={key}
                className="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full"
              >
                <span>{key}: {value}</span>
                <button
                  onClick={() => handleFilterChange(key, null)}
                  className="hover:bg-blue-200 rounded-full p-0.5"
                >
                  <X className="w-3 h-3" />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default React.memo(ProductFilters);
