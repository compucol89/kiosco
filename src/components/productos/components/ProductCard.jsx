// src/components/productos/components/ProductCard.jsx
// Componente optimizado para tarjetas de productos
// Soporte para vista grid y lista con imágenes y acciones
// RELEVANT FILES: ProductList.jsx, ProductImage.jsx

import React from 'react';
import { Edit, Trash2, Eye, Camera } from 'lucide-react';
import ProductImage from './ProductImage';

const ProductCard = ({ producto, vista = 'list', onEdit, onDelete, onDetail, onImageUpload }) => {
  // Calcular porcentaje de ganancia
  const porcentajeGanancia = producto.precio_costo > 0 
    ? (((producto.precio_venta - producto.precio_costo) / producto.precio_costo) * 100).toFixed(1)
    : 0;

  // Determinar estado del stock
  const getStockStatus = (stock) => {
    if (stock === 0) return { color: 'text-red-600', bgColor: 'bg-red-100', label: 'Sin Stock' };
    if (stock <= 5) return { color: 'text-yellow-600', bgColor: 'bg-yellow-100', label: 'Bajo Stock' };
    return { color: 'text-green-600', bgColor: 'bg-green-100', label: 'Disponible' };
  };

  const stockStatus = getStockStatus(producto.stock);

  // Vista Grid (tarjetas)
  if (vista === 'grid') {
    return (
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-200 group">
        <div className="p-4">
          {/* Imagen del producto */}
          <div className="relative mb-3">
            <ProductImage
              codigo={producto.codigo}
              nombre={producto.nombre}
              size="card"
              className="rounded-lg"
            />
            
            {/* Botón de cámara flotante */}
            <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <input
                type="file"
                accept="image/*"
                onChange={(e) => {
                  const file = e.target.files[0];
                  if (file && onImageUpload) {
                    onImageUpload(producto, file);
                  }
                }}
                className="hidden"
                id={`camera-upload-${producto.id}`}
              />
              <label
                htmlFor={`camera-upload-${producto.id}`}
                className="cursor-pointer p-2 bg-white bg-opacity-90 rounded-full shadow-sm hover:bg-white transition-colors flex items-center justify-center"
                title="Cambiar imagen"
              >
                <Camera className="w-4 h-4 text-gray-600" />
              </label>
            </div>
          </div>

          {/* Información básica */}
          <div className="space-y-2">
            <h3 className="font-semibold text-gray-900 text-sm leading-tight line-clamp-2">
              {producto.nombre}
            </h3>
            
            <div className="flex items-center justify-between text-xs text-gray-500">
              <span>{producto.categoria}</span>
              <span className={`px-2 py-1 rounded-full text-xs font-medium ${stockStatus.bgColor} ${stockStatus.color}`}>
                {producto.stock}
              </span>
            </div>

            {/* Precios */}
            <div className="space-y-1">
              <div className="flex justify-between items-center">
                <span className="text-xs text-gray-500">Venta:</span>
                <span className="text-sm font-semibold text-gray-900">
                  ${producto.precio_venta.toLocaleString()}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-xs text-gray-500">Ganancia:</span>
                <span className="text-sm font-medium text-green-600">
                  {porcentajeGanancia}%
                </span>
              </div>
            </div>

            {/* Código de barras */}
            {producto.codigo && (
              <div className="text-xs text-gray-400 font-mono">
                {producto.codigo}
              </div>
            )}
          </div>

          {/* Acciones */}
          <div className="flex justify-between items-center mt-4 pt-3 border-t border-gray-100">
            <button
              onClick={() => onDetail(producto)}
              className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              title="Ver detalle"
            >
              <Eye className="w-4 h-4" />
            </button>
            <button
              onClick={() => onEdit(producto)}
              className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
              title="Editar producto"
            >
              <Edit className="w-4 h-4" />
            </button>
            <button
              onClick={() => onDelete(producto)}
              className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="Eliminar producto"
            >
              <Trash2 className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Vista Lista (filas)
  return (
    <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200">
      <div className="flex items-center space-x-4">
        {/* Imagen */}
        <div className="flex-shrink-0">
          <ProductImage
            codigo={producto.codigo}
            nombre={producto.nombre}
            size="default"
            className="rounded-lg"
          />
        </div>

        {/* Información principal */}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h3 className="font-semibold text-gray-900 truncate">
                {producto.nombre}
              </h3>
              <p className="text-sm text-gray-500">{producto.categoria}</p>
              {producto.codigo && (
                <p className="text-xs text-gray-400 font-mono">{producto.codigo}</p>
              )}
            </div>

            {/* Métricas rápidas */}
            <div className="flex items-center space-x-6 text-sm">
              <div className="text-center">
                <div className="text-gray-500">Stock</div>
                <div className={`font-semibold ${stockStatus.color}`}>
                  {producto.stock}
                </div>
              </div>
              <div className="text-center">
                <div className="text-gray-500">Precio</div>
                <div className="font-semibold text-gray-900">
                  ${producto.precio_venta.toLocaleString()}
                </div>
              </div>
              <div className="text-center">
                <div className="text-gray-500">Ganancia</div>
                <div className="font-semibold text-green-600">
                  {porcentajeGanancia}%
                </div>
              </div>
            </div>

            {/* Acciones */}
            <div className="flex items-center space-x-2 ml-4">
              <button
                onClick={() => onDetail(producto)}
                className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="Ver detalle"
              >
                <Eye className="w-4 h-4" />
              </button>
              <button
                onClick={() => onEdit(producto)}
                className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                title="Editar producto"
              >
                <Edit className="w-4 h-4" />
              </button>
              <button
                onClick={() => onDelete(producto)}
                className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Eliminar producto"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(ProductCard);
