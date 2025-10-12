/**
 * src/components/GestionProveedores.jsx
 * Módulo para gestión completa de proveedores
 * CRUD de proveedores para sistema de pedidos inteligentes
 * RELEVANT FILES: api/proveedores.php, src/components/PedidosInteligentes.jsx
 */

import React, { useState, useEffect } from 'react';
import { 
  Store, Plus, Edit, Trash2, Save, X, Phone, 
  Mail, MapPin, Calendar, DollarSign, RefreshCw,
  Search, Building, MessageCircle, AlertTriangle
} from 'lucide-react';
import CONFIG from '../config/config';

const GestionProveedores = () => {
  const [proveedores, setProveedores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalAbierto, setModalAbierto] = useState(false);
  const [proveedorEditando, setProveedorEditando] = useState(null);
  const [busqueda, setBusqueda] = useState('');
  const [formData, setFormData] = useState({
    nombre: '',
    razon_social: '',
    cuit: '',
    telefono: '',
    whatsapp: '',
    email: '',
    direccion: '',
    categoria: '',
    dias_entrega: '',
    monto_minimo: 0,
    tiempo_entrega_dias: 2,
    notas: ''
  });

  useEffect(() => {
    cargarProveedores();
  }, []);

  const cargarProveedores = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/proveedores.php`);
      const data = await response.json();
      
      if (data.success) {
        setProveedores(data.proveedores);
      }
    } catch (error) {
      console.error('Error cargando proveedores:', error);
    } finally {
      setLoading(false);
    }
  };

  const abrirModal = (proveedor = null) => {
    if (proveedor) {
      setProveedorEditando(proveedor);
      setFormData(proveedor);
    } else {
      setProveedorEditando(null);
      setFormData({
        nombre: '',
        razon_social: '',
        cuit: '',
        telefono: '',
        whatsapp: '',
        email: '',
        direccion: '',
        categoria: '',
        dias_entrega: '',
        monto_minimo: 0,
        tiempo_entrega_dias: 2,
        notas: ''
      });
    }
    setModalAbierto(true);
  };

  const cerrarModal = () => {
    setModalAbierto(false);
    setProveedorEditando(null);
  };

  const guardarProveedor = async () => {
    if (!formData.nombre.trim()) {
      alert('⚠️ El nombre del proveedor es obligatorio');
      return;
    }

    try {
      const url = proveedorEditando 
        ? `${CONFIG.API_URL}/api/proveedores.php?id=${proveedorEditando.id}`
        : `${CONFIG.API_URL}/api/proveedores.php`;
      
      const method = proveedorEditando ? 'PUT' : 'POST';
      
      const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert(`✅ Proveedor ${proveedorEditando ? 'actualizado' : 'creado'} exitosamente`);
        cerrarModal();
        cargarProveedores();
      } else {
        alert(`❌ Error: ${data.error}`);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ Error al guardar proveedor');
    }
  };

  const eliminarProveedor = async (id, nombre) => {
    if (!window.confirm(`¿Está seguro de eliminar al proveedor "${nombre}"?`)) {
      return;
    }

    try {
      const response = await fetch(`${CONFIG.API_URL}/api/proveedores.php?id=${id}`, {
        method: 'DELETE'
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('✅ Proveedor eliminado');
        cargarProveedores();
      } else {
        alert(`❌ ${data.error}`);
      }
    } catch (error) {
      alert('❌ Error al eliminar proveedor');
    }
  };

  const proveedoresFiltrados = proveedores.filter(p => 
    p.nombre.toLowerCase().includes(busqueda.toLowerCase()) ||
    (p.categoria || '').toLowerCase().includes(busqueda.toLowerCase())
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex-1 max-w-md">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="Buscar proveedor..."
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        
        <button
          onClick={() => abrirModal()}
          className="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
        >
          <Plus className="w-5 h-5 mr-2" />
          Nuevo Proveedor
        </button>
      </div>

      {/* Lista de proveedores */}
      {loading ? (
        <div className="flex items-center justify-center h-64">
          <RefreshCw className="w-8 h-8 text-blue-600 animate-spin" />
        </div>
      ) : proveedoresFiltrados.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <Store className="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <h3 className="text-lg font-semibold text-gray-600 mb-2">
            {busqueda ? 'No se encontraron proveedores' : 'No hay proveedores registrados'}
          </h3>
          <p className="text-gray-500 mb-4">
            {busqueda ? 'Intenta con otro término de búsqueda' : 'Crea tu primer proveedor para comenzar'}
          </p>
          {!busqueda && (
            <button
              onClick={() => abrirModal()}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Crear Proveedor
            </button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {proveedoresFiltrados.map(proveedor => (
            <div key={proveedor.id} className="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center">
                  <div className="p-3 bg-blue-100 rounded-lg mr-3">
                    <Store className="w-6 h-6 text-blue-600" />
                  </div>
                  <div>
                    <h3 className="font-bold text-gray-900">{proveedor.nombre}</h3>
                    {proveedor.categoria && (
                      <span className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full">
                        {proveedor.categoria}
                      </span>
                    )}
                  </div>
                </div>
                
                <div className="flex gap-2">
                  <button
                    onClick={() => abrirModal(proveedor)}
                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  >
                    <Edit className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => eliminarProveedor(proveedor.id, proveedor.nombre)}
                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div className="space-y-2 text-sm">
                {proveedor.whatsapp && (
                  <div className="flex items-center text-gray-600">
                    <MessageCircle className="w-4 h-4 mr-2 text-green-600" />
                    <a 
                      href={`https://wa.me/${proveedor.whatsapp.replace(/[^0-9]/g, '')}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-green-600 hover:underline"
                    >
                      {proveedor.whatsapp}
                    </a>
                  </div>
                )}
                {proveedor.telefono && (
                  <div className="flex items-center text-gray-600">
                    <Phone className="w-4 h-4 mr-2" />
                    {proveedor.telefono}
                  </div>
                )}
                {proveedor.email && (
                  <div className="flex items-center text-gray-600">
                    <Mail className="w-4 h-4 mr-2" />
                    {proveedor.email}
                  </div>
                )}
                
                <div className="pt-3 mt-3 border-t border-gray-200">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-500">Productos:</span>
                    <span className="font-bold text-blue-600">{proveedor.total_productos || 0}</span>
                  </div>
                  {proveedor.tiempo_entrega_dias && (
                    <div className="flex items-center justify-between mt-1">
                      <span className="text-gray-500">Entrega:</span>
                      <span className="font-medium">{proveedor.tiempo_entrega_dias} días</span>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal de crear/editar */}
      {modalAbierto && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6 rounded-t-xl">
              <div className="flex items-center justify-between">
                <h3 className="text-2xl font-bold">
                  {proveedorEditando ? '✏️ Editar Proveedor' : '➕ Nuevo Proveedor'}
                </h3>
                <button onClick={cerrarModal} className="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg">
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <label className="block text-sm font-bold text-gray-700 mb-2">
                    🏪 Nombre del Proveedor *
                  </label>
                  <input
                    type="text"
                    value={formData.nombre}
                    onChange={(e) => setFormData({...formData, nombre: e.target.value})}
                    className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ej: Distribuidora Norte"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📋 Categoría
                  </label>
                  <select
                    value={formData.categoria}
                    onChange={(e) => setFormData({...formData, categoria: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Seleccionar...</option>
                    <option value="Panadería">Panadería</option>
                    <option value="Bebidas">Bebidas</option>
                    <option value="Lácteos">Lácteos</option>
                    <option value="Snacks">Snacks</option>
                    <option value="Golosinas">Golosinas</option>
                    <option value="Limpieza">Limpieza</option>
                    <option value="Varios">Varios</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📄 CUIT
                  </label>
                  <input
                    type="text"
                    value={formData.cuit}
                    onChange={(e) => setFormData({...formData, cuit: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="20-12345678-9"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📱 WhatsApp
                  </label>
                  <input
                    type="text"
                    value={formData.whatsapp}
                    onChange={(e) => setFormData({...formData, whatsapp: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="+54 11 1234-5678"
                  />
                  <p className="text-xs text-green-600 mt-1">Para enviar pedidos directamente</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📞 Teléfono
                  </label>
                  <input
                    type="text"
                    value={formData.telefono}
                    onChange={(e) => setFormData({...formData, telefono: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="011 1234-5678"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📧 Email
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({...formData, email: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="proveedor@ejemplo.com"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📍 Dirección
                  </label>
                  <input
                    type="text"
                    value={formData.direccion}
                    onChange={(e) => setFormData({...formData, direccion: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Av. Ejemplo 1234"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📅 Días de Entrega
                  </label>
                  <input
                    type="text"
                    value={formData.dias_entrega}
                    onChange={(e) => setFormData({...formData, dias_entrega: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Lunes y Jueves"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    ⏱️ Tiempo de Entrega (días)
                  </label>
                  <input
                    type="number"
                    value={formData.tiempo_entrega_dias}
                    onChange={(e) => setFormData({...formData, tiempo_entrega_dias: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    min="1"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    💰 Monto Mínimo de Pedido
                  </label>
                  <input
                    type="number"
                    value={formData.monto_minimo}
                    onChange={(e) => setFormData({...formData, monto_minimo: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    min="0"
                    step="0.01"
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📝 Notas
                  </label>
                  <textarea
                    value={formData.notas}
                    onChange={(e) => setFormData({...formData, notas: e.target.value})}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    rows="3"
                    placeholder="Información adicional..."
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <button
                  onClick={cerrarModal}
                  className="px-6 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
                >
                  Cancelar
                </button>
                <button
                  onClick={guardarProveedor}
                  className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center"
                >
                  <Save className="w-4 h-4 mr-2" />
                  Guardar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default GestionProveedores;

