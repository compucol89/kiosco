// src/components/productos/components/ProductList.jsx
// Componente optimizado para mostrar lista de productos
// Soporte para vista grid y lista con memoización
// RELEVANT FILES: ProductosPage.jsx, ProductCard.jsx

import React from 'react';
import { Package } from 'lucide-react';
import ProductCard from './ProductCard';
import ProductPagination from './ProductPagination';

const ProductList = ({ 
  productos,
  tipoVista,
  paginacionData,
  currentPage,
  onPageChange,
  onEdit,
  onDelete,
  onDetail,
  onImageUpload,
  loading = false
}) => {
  
  if (loading) {
    return (
      <div className="space-y-4">
        {/* Skeleton de carga */}
        <div className={tipoVista === 'grid' 
          ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" 
          : "space-y-3"
        }>
          {Array.from({ length: 8 }).map((_, index) => (
            <div key={index} className="bg-white p-4 rounded-xl border border-gray-200">
              <div className="animate-pulse">
                <div className="h-20 bg-gray-200 rounded mb-3"></div>
                <div className="h-4 bg-gray-200 rounded mb-2"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4"></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (!productos || productos.length === 0) {
    return (
      <div className="text-center py-12">
        <div className="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <Package className="w-12 h-12 text-gray-400" />
        </div>
        <h3 className="text-lg font-medium text-gray-900 mb-2">
          No se encontraron productos
        </h3>
        <p className="text-gray-500">
          Prueba ajustando tus filtros de búsqueda o agrega nuevos productos.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Lista de productos */}
      <div className={tipoVista === 'grid' 
        ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" 
        : "space-y-3"
      }>
        {productos.map((producto) => (
          <ProductCard
            key={producto.id}
            producto={producto}
            vista={tipoVista}
            onEdit={onEdit}
            onDelete={onDelete}
            onDetail={onDetail}
            onImageUpload={onImageUpload}
          />
        ))}
      </div>

      {/* Paginación */}
      <ProductPagination
        currentPage={currentPage}
        totalPages={paginacionData.totalPages}
        onPageChange={onPageChange}
      />
    </div>
  );
};

export default React.memo(ProductList);
