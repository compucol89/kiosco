// src/components/productos/components/ProductFormModal.jsx
// Modal optimizado para crear y editar productos
// Formulario completo con validación y carga de imágenes
// RELEVANT FILES: ProductosPage.jsx, ProductImage.jsx

import React, { useState, useEffect } from 'react';
import { X, Save, Upload, Calculator, Camera, Package, DollarSign } from 'lucide-react';
import axios from 'axios';
import CONFIG from '../../../config/config';
import ProductImage from './ProductImage';
import { notifyImageUpdate } from '../../../utils/imageCache';
import { showSuccess, showError, showWarning } from '../../../utils/toastNotifications';

const ProductFormModal = ({ producto = null, onClose, onSave }) => {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    nombre: '',
    categoria: '',
    precio_venta: '',
    precio_costo: '',
    stock: '',
    codigo: '',
    descripcion: '',
    stock_minimo: '',
    aplica_descuento_forma_pago: true
  });
  
  const [imagenFile, setImagenFile] = useState(null);
  const [imagenPreview, setImagenPreview] = useState('');
  const [precioCalculado, setPrecioCalculado] = useState('');
  const [porcentajeMargen, setPorcentajeMargen] = useState('');

  const esEdicion = !!producto;

  // Inicializar formulario
  useEffect(() => {
    if (producto) {
      setFormData({
        nombre: producto.nombre || '',
        categoria: producto.categoria || '',
        precio_venta: String(producto.precio_venta || ''),
        precio_costo: String(producto.precio_costo || ''),
        stock: String(producto.stock || ''),
        codigo: producto.codigo || '',
        descripcion: producto.descripcion || '',
        stock_minimo: String(producto.stock_minimo || ''),
        aplica_descuento_forma_pago: producto.aplica_descuento_forma_pago !== false
      });
    }
  }, [producto]);

  // Manejar cambios en el formulario
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  // Calcular precio con margen
  const calcularPrecioConMargen = () => {
    const costo = parseFloat(formData.precio_costo);
    const margen = parseFloat(porcentajeMargen);
    
    if (!isNaN(costo) && !isNaN(margen) && costo > 0 && margen > 0) {
      const precio = costo * (1 + margen / 100);
      setPrecioCalculado(precio.toFixed(2));
      setFormData(prev => ({ ...prev, precio_venta: precio.toFixed(2) }));
    }
  };

  // Manejar archivo de imagen
  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setImagenFile(file);
      
      // Preview
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagenPreview(e.target.result);
      };
      reader.readAsDataURL(file);
    }
  };

  // Subir imagen
  const subirImagen = async (codigo) => {
    if (!imagenFile || !codigo) return;

    const formDataImg = new FormData();
    formDataImg.append('imagen', imagenFile);
    formDataImg.append('codigo', codigo);

    try {
      const response = await axios.post(`${CONFIG.API_URL}/api/subir_imagen_producto.php`, formDataImg, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      if (response.data?.success) {
        // Notificar actualización de imagen
        notifyImageUpdate(codigo);
        return true;
      }
    } catch (error) {
      console.error('Error subiendo imagen:', error);
    }
    return false;
  };

  // Validar formulario
  const validarFormulario = () => {
    const errores = [];
    
    if (!formData.nombre.trim()) errores.push('🏷️ El nombre del producto es obligatorio');
    if (!formData.categoria.trim()) errores.push('📂 La categoría es obligatoria');
    if (!formData.precio_venta || parseFloat(formData.precio_venta) <= 0) errores.push('💵 El precio de venta debe ser mayor a 0');
    if (!formData.precio_costo || parseFloat(formData.precio_costo) <= 0) errores.push('💸 El precio de costo debe ser mayor a 0');
    if (formData.stock === '' || parseInt(formData.stock) < 0) errores.push('📦 El stock debe ser mayor o igual a 0');
    
    return errores;
  };

  // Guardar producto
  const handleGuardar = async () => {
    const errores = validarFormulario();
    if (errores.length > 0) {
      errores.forEach(error => showWarning(error, 5000));
      return;
    }

    setLoading(true);
    
    try {
      const method = esEdicion ? 'PUT' : 'POST';
      const url = `${CONFIG.API_URL}/api/productos.php`;
      
      const datos = {
        ...formData,
        precio_venta: parseFloat(formData.precio_venta),
        precio_costo: parseFloat(formData.precio_costo),
        stock: parseInt(formData.stock),
        stock_minimo: parseInt(formData.stock_minimo) || 0
      };
      
      if (esEdicion) {
        datos.id = producto.id;
      }

      const response = await axios({
        method,
        url,
        data: datos,
        headers: { 'Content-Type': 'application/json' }
      });

      if (response.data?.success) {
        // Subir imagen si hay una
        if (imagenFile && (datos.codigo || producto?.codigo)) {
          const imagenSubida = await subirImagen(datos.codigo || producto.codigo);
          if (imagenSubida) {
            showSuccess('📸 Imagen subida correctamente');
          }
        }
        
        showSuccess(
          esEdicion 
            ? `✨ ${formData.nombre} actualizado correctamente` 
            : `🎉 ${formData.nombre} creado correctamente`,
          4000
        );
        onSave();
      } else {
        throw new Error(response.data?.message || 'Error al guardar producto');
      }
    } catch (error) {
      console.error('Error:', error);
      showError(`❌ Error al ${esEdicion ? 'actualizar' : 'crear'} producto: ${error.message}`);
    }
    
    setLoading(false);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[85vh] overflow-y-auto">
        {/* Header Simple */}
        <div className="flex items-center justify-between p-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            {esEdicion ? 'Editar Producto' : 'Nuevo Producto'}
          </h2>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="w-5 h-5 text-gray-500" />
          </button>
        </div>

        {/* Content Simple */}
        <div className="p-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Información Básica */}
            <div className="lg:col-span-2 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Nombre del Producto *
                  </label>
                  <input
                    type="text"
                    name="nombre"
                    value={formData.nombre}
                    onChange={handleInputChange}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ej: Coca Cola 500ml"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Categoría *
                  </label>
                  <input
                    type="text"
                    name="categoria"
                    value={formData.categoria}
                    onChange={handleInputChange}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ej: Bebidas"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Código de Barras
                  </label>
                  <input
                    type="text"
                    name="codigo"
                    value={formData.codigo}
                    onChange={handleInputChange}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Código de barras"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Stock Actual *
                  </label>
                  <input
                    type="number"
                    name="stock"
                    value={formData.stock}
                    onChange={handleInputChange}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="0"
                    min="0"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Stock Mínimo
                  </label>
                  <input
                    type="number"
                    name="stock_minimo"
                    value={formData.stock_minimo}
                    onChange={handleInputChange}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="5"
                    min="0"
                  />
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    name="aplica_descuento_forma_pago"
                    checked={formData.aplica_descuento_forma_pago}
                    onChange={handleInputChange}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <label className="ml-2 text-sm text-gray-700">
                    Aplica descuento por forma de pago
                  </label>
                </div>
              </div>

              {/* Precios */}
              <div className="space-y-4 mt-6">
                <h3 className="font-medium text-gray-900 text-sm uppercase tracking-wide text-gray-500 border-b pb-2">
                  Configuración de Precios
                </h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Precio de Costo *
                    </label>
                    <input
                      type="number"
                      name="precio_costo"
                      value={formData.precio_costo}
                      onChange={handleInputChange}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Precio de Venta *
                    </label>
                    <input
                      type="number"
                      name="precio_venta"
                      value={formData.precio_venta}
                      onChange={handleInputChange}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                      required
                    />
                  </div>
                </div>

                {/* Calculadora de margen */}
                <div className="flex gap-2">
                  <input
                    type="number"
                    value={porcentajeMargen}
                    onChange={(e) => setPorcentajeMargen(e.target.value)}
                    className="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="% de margen"
                    step="0.1"
                  />
                  <button
                    type="button"
                    onClick={calcularPrecioConMargen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                  >
                    <Calculator className="w-4 h-4" />
                    Calcular
                  </button>
                </div>
              </div>

              {/* Descripción */}
              <div className="mt-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Descripción
                </label>
                <textarea
                  name="descripcion"
                  value={formData.descripcion}
                  onChange={handleInputChange}
                  rows="3"
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Descripción del producto..."
                />
              </div>
            </div>

            {/* Imagen */}
            <div className="space-y-4">
              <h3 className="font-medium text-gray-900 text-sm uppercase tracking-wide text-gray-500 border-b pb-2">
                Imagen del Producto
              </h3>
              
              {/* Preview actual */}
              <div className="border border-gray-300 rounded-lg p-4">
                {esEdicion && formData.codigo ? (
                  <ProductImage
                    codigo={formData.codigo}
                    nombre={formData.nombre}
                    size="hero"
                    className="rounded-lg"
                  />
                ) : imagenPreview ? (
                  <img
                    src={imagenPreview}
                    alt="Preview"
                    className="w-full h-48 object-cover rounded-lg"
                  />
                ) : (
                  <div className="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                    <div className="text-center">
                      <Camera className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                      <p className="text-sm text-gray-500">Sin imagen</p>
                    </div>
                  </div>
                )}
              </div>

              {/* Upload */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Subir nueva imagen
                </label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={handleImageChange}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
                <p className="text-xs text-gray-500 mt-1">
                  JPG, PNG, GIF, WEBP. Máximo 5MB.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
          <button
            onClick={onClose}
            disabled={loading}
            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            onClick={handleGuardar}
            disabled={loading}
            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            {loading ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Guardando...
              </>
            ) : (
              <>
                <Save className="w-4 h-4" />
                {esEdicion ? 'Actualizar' : 'Crear'} Producto
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProductFormModal;
