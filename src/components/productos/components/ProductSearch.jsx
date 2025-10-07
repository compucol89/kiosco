// src/components/productos/components/ProductSearch.jsx
// Componente optimizado para búsqueda y filtros de productos
// Incluye controles de vista y paginación
// RELEVANT FILES: ProductosPage.jsx, useProductSearch.js

import React from 'react';
import { Search, Plus, RefreshCw, Grid, List, Download, Upload } from 'lucide-react';

const ProductSearch = ({ 
  searchTerm,
  tipoVista,
  onSearchChange,
  onVistaChange,
  onNuevoProducto,
  onRefresh,
  onExportar,
  onImportar,
  loading = false
}) => {
  return (
    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        {/* Búsqueda */}
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar productos..."
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            disabled={loading}
          />
        </div>

        {/* Controles principales */}
        <div className="flex items-center gap-3">
          {/* Selector de vista */}
          <div className="flex bg-gray-100 rounded-lg p-1">
            <button
              onClick={() => onVistaChange('list')}
              className={`p-2 rounded-md transition-colors ${
                tipoVista === 'list' 
                  ? 'bg-white text-blue-600 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              }`}
              title="Vista Lista"
            >
              <List className="w-4 h-4" />
            </button>
            <button
              onClick={() => onVistaChange('grid')}
              className={`p-2 rounded-md transition-colors ${
                tipoVista === 'grid' 
                  ? 'bg-white text-blue-600 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              }`}
              title="Vista Grid"
            >
              <Grid className="w-4 h-4" />
            </button>
          </div>

          {/* Botones de acción */}
          <button
            onClick={onRefresh}
            disabled={loading}
            className="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50"
            title="Actualizar"
          >
            <RefreshCw className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} />
          </button>

          <button
            onClick={onExportar}
            className="p-2 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors"
            title="Exportar Excel"
          >
            <Download className="w-5 h-5" />
          </button>

          <button
            onClick={onImportar}
            className="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
            title="Importar Productos"
          >
            <Upload className="w-5 h-5" />
          </button>

          <button
            onClick={onNuevoProducto}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <Plus className="w-5 h-5" />
            Nuevo Producto
          </button>
        </div>
      </div>
    </div>
  );
};

export default React.memo(ProductSearch);
