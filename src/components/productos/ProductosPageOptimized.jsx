// src/components/productos/ProductosPageOptimized.jsx
// Página de productos completamente optimizada y modular
// Arquitectura limpia con hooks personalizados y componentes separados
// RELEVANT FILES: ProductosPage.jsx, useProductos.js, ProductStats.jsx

import React, { useEffect, Suspense } from 'react';
import { Package } from 'lucide-react';

// ⚡ OPTIMIZACIÓN: Hooks personalizados
import { useProductos } from './hooks/useProductos';
import { useProductSearch } from './hooks/useProductSearch';
import { useProductStats } from './hooks/useProductStats';
import { useProductAnalysis } from './hooks/useProductAnalysis';

// ⚡ OPTIMIZACIÓN: Componentes modulares
import ProductStats from './components/ProductStats';
import ProductSearch from './components/ProductSearch';
import ProductList from './components/ProductList';

// ⚡ OPTIMIZACIÓN: Lazy loading de modales
import { ProductFormModal, ProductDetailModal, ProductImportModal } from './components/LazyModals';

const ProductosPageOptimized = () => {
  // ⚡ HOOKS PERSONALIZADOS: Lógica separada
  const { productos, loading, error, cargarProductos, eliminarProducto } = useProductos();
  const { estadisticas } = useProductStats(productos);
  const { datosVentas, cargarAnalisisVentas } = useProductAnalysis();
  
  const {
    searchTerm,
    currentPage,
    tipoVista,
    productosFiltrados,
    paginacionData,
    setCurrentPage,
    handleSearchChange,
    handleVistaChange
  } = useProductSearch(productos);

  // Estados para modales (mantener local para UI)
  const [modalDetalle, setModalDetalle] = React.useState(false);
  const [modalForm, setModalForm] = React.useState(false);
  const [modalImport, setModalImport] = React.useState(false);
  const [productoSeleccionado, setProductoSeleccionado] = React.useState(null);

  // ⚡ OPTIMIZACIÓN: Cargar productos al montar
  useEffect(() => {
    cargarProductos();
  }, [cargarProductos]);

  // ⚡ HANDLERS OPTIMIZADOS con useCallback implícito en hooks
  const handleNuevoProducto = () => {
    setProductoSeleccionado(null);
    setModalForm(true);
  };

  const handleEditarProducto = (producto) => {
    setProductoSeleccionado(producto);
    setModalForm(true);
  };

  const handleDetalleProducto = (producto) => {
    setProductoSeleccionado(producto);
    setModalDetalle(true);
    cargarAnalisisVentas(producto.id, producto.nombre);
  };

  const handleEliminarProducto = async (producto) => {
    const eliminado = await eliminarProducto(producto);
    if (eliminado) {
      // Toast notification o feedback visual
      console.log('Producto eliminado exitosamente');
    }
  };

  const handleExportar = () => {
    // Lógica de exportación
    console.log('Exportando productos...');
  };

  const handleImportar = () => {
    setModalImport(true);
  };

  const cerrarModales = () => {
    setModalDetalle(false);
    setModalForm(false);
    setModalImport(false);
    setProductoSeleccionado(null);
  };

  // ⚡ LOADING Y ERROR STATES
  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="text-center py-12">
          <div className="mx-auto w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mb-4">
            <Package className="w-12 h-12 text-red-400" />
          </div>
          <h3 className="text-lg font-medium text-gray-900 mb-2">Error al cargar productos</h3>
          <p className="text-gray-500 mb-4">{error}</p>
          <button
            onClick={cargarProductos}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center space-x-3 mb-2">
          <div className="p-2 bg-blue-100 rounded-lg">
            <Package className="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Gestión de Productos</h1>
            <p className="text-gray-600">Administra tu catálogo de productos</p>
          </div>
        </div>
      </div>

      {/* ⚡ COMPONENTE: Estadísticas */}
      <ProductStats estadisticas={estadisticas} loading={loading} />

      {/* ⚡ COMPONENTE: Búsqueda y controles */}
      <ProductSearch
        searchTerm={searchTerm}
        tipoVista={tipoVista}
        onSearchChange={handleSearchChange}
        onVistaChange={handleVistaChange}
        onNuevoProducto={handleNuevoProducto}
        onRefresh={cargarProductos}
        onExportar={handleExportar}
        onImportar={handleImportar}
        loading={loading}
      />

      {/* ⚡ COMPONENTE: Lista de productos */}
      <ProductList
        productos={paginacionData.productosPaginados}
        tipoVista={tipoVista}
        paginacionData={paginacionData}
        currentPage={currentPage}
        onPageChange={setCurrentPage}
        onEdit={handleEditarProducto}
        onDelete={handleEliminarProducto}
        onDetail={handleDetalleProducto}
        loading={loading}
      />

      {/* ⚡ OPTIMIZACIÓN: Modales con Lazy Loading */}
      <Suspense fallback={<div>Cargando...</div>}>
        {modalForm && (
          <ProductFormModal
            producto={productoSeleccionado}
            onClose={cerrarModales}
            onSave={() => {
              cargarProductos();
              cerrarModales();
            }}
          />
        )}

        {modalDetalle && productoSeleccionado && (
          <ProductDetailModal
            producto={productoSeleccionado}
            datosVentas={datosVentas}
            onClose={cerrarModales}
            onEdit={() => {
              setModalDetalle(false);
              setModalForm(true);
            }}
          />
        )}

        {modalImport && (
          <ProductImportModal
            onClose={cerrarModales}
            onImport={() => {
              cargarProductos();
              cerrarModales();
            }}
          />
        )}
      </Suspense>
    </div>
  );
};

export default ProductosPageOptimized;
