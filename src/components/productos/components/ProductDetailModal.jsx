// src/components/productos/components/ProductDetailModal.jsx
// Modal optimizado para mostrar detalles completos de productos
// Incluye análisis de ventas, métricas y acciones rápidas
// RELEVANT FILES: ProductFormModal.jsx, useProductAnalysis.js

import React from 'react';
import { X, Edit, Trash2, BarChart3, TrendingUp, Calendar, Package } from 'lucide-react';
import ProductImage from './ProductImage';
import BarcodeDisplay from './BarcodeDisplay';

const ProductDetailModal = ({ producto, datosVentas, onClose, onEdit, onDelete }) => {
  // Calcular métricas
  const margenGanancia = producto.precio_costo > 0 
    ? (((producto.precio_venta - producto.precio_costo) / producto.precio_costo) * 100).toFixed(1)
    : 0;

  const valorStock = (producto.precio_costo * producto.stock).toFixed(2);
  
  const getStockStatus = (stock) => {
    if (stock === 0) return { color: 'text-red-600', bg: 'bg-red-100', label: 'Sin Stock' };
    if (stock <= 5) return { color: 'text-yellow-600', bg: 'bg-yellow-100', label: 'Stock Bajo' };
    if (stock <= 20) return { color: 'text-blue-600', bg: 'bg-blue-100', label: 'Stock Normal' };
    return { color: 'text-green-600', bg: 'bg-green-100', label: 'Stock Alto' };
  };

  const stockStatus = getStockStatus(producto.stock);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      return new Date(dateString).toLocaleDateString('es-AR');
    } catch {
      return 'N/A';
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-6xl w-full max-h-[95vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center gap-4">
            <div className="p-2 bg-blue-100 rounded-lg">
              <Package className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">{producto.nombre}</h2>
              <p className="text-gray-600">{producto.categoria}</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {/* Información principal */}
            <div className="lg:col-span-2 space-y-6">
              
              {/* Métricas principales */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-blue-50 p-4 rounded-lg">
                  <div className="text-2xl font-bold text-blue-600">
                    ${producto.precio_venta.toLocaleString()}
                  </div>
                  <div className="text-sm text-gray-600">Precio Venta</div>
                </div>
                
                <div className="bg-green-50 p-4 rounded-lg">
                  <div className="text-2xl font-bold text-green-600">{margenGanancia}%</div>
                  <div className="text-sm text-gray-600">Margen</div>
                </div>
                
                <div className={`p-4 rounded-lg ${stockStatus.bg}`}>
                  <div className={`text-2xl font-bold ${stockStatus.color}`}>{producto.stock}</div>
                  <div className="text-sm text-gray-600">Stock</div>
                </div>
                
                <div className="bg-purple-50 p-4 rounded-lg">
                  <div className="text-2xl font-bold text-purple-600">
                    ${parseFloat(valorStock).toLocaleString()}
                  </div>
                  <div className="text-sm text-gray-600">Valor Stock</div>
                </div>
              </div>

              {/* Información detallada */}
              <div className="bg-gray-50 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Información del Producto</h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="md:col-span-2">
                    <label className="text-sm font-medium text-gray-600 mb-2 block">Código de Barras</label>
                    {producto.codigo ? (
                      <BarcodeDisplay 
                        value={producto.codigo} 
                        width={1.5}
                        height={40}
                        fontSize={12}
                      />
                    ) : (
                      <div className="text-gray-500 text-center py-4 border border-gray-200 rounded-lg">
                        <p className="text-sm">No hay código de barras asignado</p>
                      </div>
                    )}
                  </div>
                  
                  <div>
                    <label className="text-sm font-medium text-gray-600">Precio de Costo</label>
                    <p className="text-gray-900">${producto.precio_costo.toLocaleString()}</p>
                  </div>
                  
                  <div>
                    <label className="text-sm font-medium text-gray-600">Stock Mínimo</label>
                    <p className="text-gray-900">{producto.stock_minimo || 'No definido'}</p>
                  </div>
                  
                  <div>
                    <label className="text-sm font-medium text-gray-600">Descuento F.P.</label>
                    <p className="text-gray-900">
                      {producto.aplica_descuento_forma_pago ? 'Sí' : 'No'}
                    </p>
                  </div>
                </div>

                {producto.descripcion && (
                  <div className="mt-4">
                    <label className="text-sm font-medium text-gray-600">Descripción</label>
                    <p className="text-gray-900 mt-1">{producto.descripcion}</p>
                  </div>
                )}
              </div>

              {/* Análisis de ventas */}
              <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                <div className="flex items-center gap-2 mb-4">
                  <BarChart3 className="w-5 h-5 text-blue-600" />
                  <h3 className="text-lg font-semibold text-gray-900">Análisis de Ventas</h3>
                  {datosVentas.cargando && (
                    <div className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                  )}
                </div>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-blue-600">
                      {datosVentas.ventasUltimos7Dias}
                    </div>
                    <div className="text-sm text-gray-600">Últimos 7 días</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-indigo-600">
                      {datosVentas.ventasUltimos30Dias}
                    </div>
                    <div className="text-sm text-gray-600">Últimos 30 días</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-purple-600">
                      {datosVentas.promedioMensual}
                    </div>
                    <div className="text-sm text-gray-600">Promedio mensual</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-600">
                      {datosVentas.rotacionEstimada}x
                    </div>
                    <div className="text-sm text-gray-600">Rotación (días)</div>
                  </div>
                </div>

                {datosVentas.ultimoMovimiento && (
                  <div className="mt-4 pt-4 border-t border-blue-200">
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <Calendar className="w-4 h-4" />
                      <span>Última venta: {formatDate(datosVentas.ultimoMovimiento)}</span>
                    </div>
                  </div>
                )}
              </div>

              {/* Recomendaciones */}
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 className="font-medium text-yellow-800 mb-2">💡 Recomendaciones</h4>
                <ul className="text-sm text-yellow-700 space-y-1">
                  {producto.stock === 0 && (
                    <li>• ⚠️ Producto sin stock - Reabastecer urgentemente</li>
                  )}
                  {producto.stock <= 5 && producto.stock > 0 && (
                    <li>• 📦 Stock bajo - Considerar reposición</li>
                  )}
                  {parseFloat(margenGanancia) < 10 && (
                    <li>• 💰 Margen bajo ({margenGanancia}%) - Revisar precio de venta</li>
                  )}
                  {parseFloat(margenGanancia) > 50 && (
                    <li>• 🎯 Excelente margen ({margenGanancia}%) - Producto rentable</li>
                  )}
                  {datosVentas.ventasUltimos30Dias === 0 && (
                    <li>• 📉 Sin ventas en 30 días - Evaluar demanda o promoción</li>
                  )}
                  {datosVentas.ventasUltimos30Dias > 10 && (
                    <li>• 🔥 Producto popular - {datosVentas.ventasUltimos30Dias} ventas en 30 días</li>
                  )}
                </ul>
              </div>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              
              {/* Imagen */}
              <div>
                <h3 className="font-medium text-gray-900 mb-3">Imagen del Producto</h3>
                <div className="border border-gray-200 rounded-lg p-4">
                  <ProductImage
                    codigo={producto.codigo}
                    nombre={producto.nombre}
                    size="hero"
                    className="rounded-lg"
                  />
                </div>
              </div>

              {/* Acciones */}
              <div className="space-y-3">
                <h3 className="font-medium text-gray-900">Acciones</h3>
                
                <button
                  onClick={onEdit}
                  className="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                >
                  <Edit className="w-4 h-4" />
                  Editar Producto
                </button>
                
                <button
                  onClick={() => {
                    if (window.confirm(`¿Estás seguro de eliminar "${producto.nombre}"?`)) {
                      onDelete(producto);
                    }
                  }}
                  className="w-full bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2"
                >
                  <Trash2 className="w-4 h-4" />
                  Eliminar Producto
                </button>

                <button
                  onClick={() => {
                    // Generar reporte individual del producto
                    const reporte = `
REPORTE INDIVIDUAL - ${producto.nombre}
=======================================
Categoría: ${producto.categoria}
Código: ${producto.codigo || 'N/A'}
Stock Actual: ${producto.stock}
Precio Costo: $${producto.precio_costo}
Precio Venta: $${producto.precio_venta}
Margen: ${margenGanancia}%
Valor Stock: $${valorStock}

VENTAS (ÚLTIMOS 30 DÍAS)
========================
Total vendido: ${datosVentas.ventasUltimos30Dias}
Promedio mensual: ${datosVentas.promedioMensual}
Rotación: ${datosVentas.rotacionEstimada} días
Última venta: ${formatDate(datosVentas.ultimoMovimiento)}
                    `.trim();
                    
                    const blob = new Blob([reporte], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `reporte_${producto.nombre.replace(/[^a-zA-Z0-9]/g, '_')}.txt`;
                    a.click();
                    URL.revokeObjectURL(url);
                  }}
                  className="w-full bg-gray-600 text-white px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center gap-2"
                >
                  <TrendingUp className="w-4 h-4" />
                  Exportar Reporte
                </button>
              </div>

              {/* Estado del producto */}
              <div className={`p-4 rounded-lg ${stockStatus.bg}`}>
                <div className="text-center">
                  <div className={`text-lg font-semibold ${stockStatus.color}`}>
                    {stockStatus.label}
                  </div>
                  <div className="text-sm text-gray-600 mt-1">
                    {producto.stock} unidades disponibles
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductDetailModal;
