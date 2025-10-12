/**
 * src/components/PedidosInteligentes.jsx
 * Módulo de pedidos inteligentes con análisis de consumo y agrupación por proveedor
 * Genera listas listas para copiar a WhatsApp
 * RELEVANT FILES: api/pedidos_inteligentes.php, api/proveedores.php
 */

import React, { useState, useEffect } from 'react';
import { 
  ShoppingCart, RefreshCw, Copy, Check, TrendingDown, 
  AlertTriangle, Package, Calendar, DollarSign, 
  MessageCircle, ChevronDown, ChevronUp, Info,
  Zap, Target, Clock
} from 'lucide-react';
import CONFIG from '../config/config';

const PedidosInteligentes = () => {
  const [pedidos, setPedidos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [diasAnalisis, setDiasAnalisis] = useState(30);
  const [proveedorExpandido, setProveedorExpandido] = useState({});
  const [copiado, setCopiado] = useState({});

  useEffect(() => {
    cargarAnalisis();
  }, [diasAnalisis]);

  const cargarAnalisis = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${CONFIG.API_URL}/api/pedidos_inteligentes.php?accion=por_proveedor&dias=${diasAnalisis}`
      );
      const data = await response.json();
      
      if (data.success) {
        setPedidos(data.pedidos_por_proveedor || []);
      }
    } catch (error) {
      console.error('Error cargando análisis:', error);
    } finally {
      setLoading(false);
    }
  };

  const generarMensajeWhatsApp = (proveedor) => {
    const fecha = new Date().toLocaleDateString('es-AR');
    let mensaje = `🛒 *PEDIDO - ${proveedor.nombre.toUpperCase()}*\n`;
    mensaje += `📅 Fecha: ${fecha}\n\n`;
    mensaje += `━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
    
    proveedor.productos.forEach((producto, index) => {
      const emoji = producto.urgencia === 'alta' ? '🚨' : 
                   producto.urgencia === 'media' ? '⚠️' : '📦';
      mensaje += `${emoji} ${producto.nombre} x ${producto.cantidad_sugerida} unidades\n`;
    });
    
    mensaje += `━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n`;
    mensaje += `📦 *Total: ${proveedor.total_productos} productos, ${proveedor.total_unidades_sugeridas} unidades*\n\n`;
    mensaje += `Kiosco Tayrona 🏪`;
    
    return mensaje;
  };

  const copiarParaWhatsApp = (proveedor, index) => {
    const mensaje = generarMensajeWhatsApp(proveedor);
    
    navigator.clipboard.writeText(mensaje).then(() => {
      setCopiado({...copiado, [index]: true});
      setTimeout(() => {
        setCopiado({...copiado, [index]: false});
      }, 2000);
    });
  };

  const toggleProveedor = (index) => {
    setProveedorExpandido({
      ...proveedorExpandido,
      [index]: !proveedorExpandido[index]
    });
  };

  const getColorUrgencia = (urgencia) => {
    switch (urgencia) {
      case 'alta': return 'bg-red-100 text-red-800 border-red-300';
      case 'media': return 'bg-yellow-100 text-yellow-800 border-yellow-300';
      default: return 'bg-blue-100 text-blue-800 border-blue-300';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 text-blue-600 animate-spin" />
        <span className="ml-3 text-gray-600">Analizando inventario...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header con controles */}
      <div className="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-2xl font-bold text-gray-800 flex items-center">
              <Zap className="w-7 h-7 mr-3 text-blue-600" />
              🤖 Pedidos Inteligentes
            </h2>
            <p className="text-gray-600 mt-1">
              Análisis automático basado en consumo histórico
            </p>
          </div>
          
          <div className="flex items-center gap-3">
            <select
              value={diasAnalisis}
              onChange={(e) => setDiasAnalisis(e.target.value)}
              className="px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="7">Últimos 7 días</option>
              <option value="15">Últimos 15 días</option>
              <option value="30">Últimos 30 días</option>
              <option value="60">Últimos 60 días</option>
            </select>
            
            <button
              onClick={cargarAnalisis}
              className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              <RefreshCw className="w-4 h-4 mr-2" />
              Actualizar
            </button>
          </div>
        </div>

        {/* Info banner */}
        <div className="bg-white border border-blue-200 rounded-lg p-3">
          <div className="flex items-start">
            <Info className="w-5 h-5 text-blue-600 mr-2 mt-0.5" />
            <div className="text-sm text-blue-800">
              <strong>ℹ️ Cómo funciona:</strong> El sistema analiza las ventas de los últimos {diasAnalisis} días,
              calcula el consumo promedio y sugiere cantidades óptimas para evitar sobre-stock y vencimientos.
              Los pedidos se agrupan por proveedor y puedes copiarlos directamente para WhatsApp.
            </div>
          </div>
        </div>
      </div>

      {/* Resumen general */}
      {pedidos.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Proveedores con Pedidos</p>
            <p className="text-3xl font-bold text-blue-600">{pedidos.length}</p>
          </div>
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Total Productos</p>
            <p className="text-3xl font-bold text-green-600">
              {pedidos.reduce((sum, p) => sum + p.total_productos, 0)}
            </p>
          </div>
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Total Unidades</p>
            <p className="text-3xl font-bold text-purple-600">
              {pedidos.reduce((sum, p) => sum + p.total_unidades_sugeridas, 0)}
            </p>
          </div>
        </div>
      )}

      {/* Lista de pedidos por proveedor */}
      {pedidos.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <Package className="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <h3 className="text-lg font-semibold text-gray-600 mb-2">
            ✅ No hay productos que necesiten pedido
          </h3>
          <p className="text-gray-500">
            Todos los productos tienen stock suficiente según el consumo histórico
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {pedidos.map((proveedor, index) => (
            <div key={index} className="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
              {/* Header del proveedor */}
              <div 
                className="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 cursor-pointer"
                onClick={() => toggleProveedor(index)}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <div className="p-3 bg-white bg-opacity-20 rounded-xl mr-4">
                      <ShoppingCart className="w-6 h-6" />
                    </div>
                    <div>
                      <h3 className="text-xl font-bold">🏪 {proveedor.nombre}</h3>
                      <p className="text-blue-100 text-sm">
                        {proveedor.total_productos} productos • {proveedor.total_unidades_sugeridas} unidades
                        {proveedor.costo_total_proveedor > 0 && (
                          <> • Costo: ${proveedor.costo_total_proveedor.toLocaleString('es-AR')}</>
                        )}
                      </p>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-3">
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        copiarParaWhatsApp(proveedor, index);
                      }}
                      className={`flex items-center px-4 py-2 rounded-lg font-medium transition-colors ${
                        copiado[index]
                          ? 'bg-green-500 text-white'
                          : 'bg-white text-blue-600 hover:bg-blue-50'
                      }`}
                    >
                      {copiado[index] ? (
                        <>
                          <Check className="w-4 h-4 mr-2" />
                          ¡Copiado!
                        </>
                      ) : (
                        <>
                          <Copy className="w-4 h-4 mr-2" />
                          Copiar para WhatsApp
                        </>
                      )}
                    </button>
                    
                    {proveedorExpandido[index] ? (
                      <ChevronUp className="w-6 h-6" />
                    ) : (
                      <ChevronDown className="w-6 h-6" />
                    )}
                  </div>
                </div>
              </div>

              {/* Detalle de productos */}
              {proveedorExpandido[index] && (
                <div className="p-6 bg-gray-50">
                  <div className="space-y-3">
                    {proveedor.productos.map((producto, pIdx) => (
                      <div 
                        key={pIdx}
                        className={`bg-white rounded-lg border-2 p-4 ${
                          getColorUrgencia(producto.urgencia)
                        }`}
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <div className="flex items-center mb-2">
                              <span className={`px-2 py-1 rounded-full text-xs font-bold mr-3 ${
                                producto.urgencia === 'alta' ? 'bg-red-500 text-white' :
                                producto.urgencia === 'media' ? 'bg-yellow-500 text-white' :
                                'bg-blue-500 text-white'
                              }`}>
                                {producto.urgencia === 'alta' ? '🚨 URGENTE' :
                                 producto.urgencia === 'media' ? '⚠️ PRONTO' : '📦 NORMAL'}
                              </span>
                              <h4 className="font-bold text-gray-900">{producto.nombre}</h4>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                              <div>
                                <p className="text-gray-500">Stock Actual</p>
                                <p className="font-semibold">{producto.stock_actual} unidades</p>
                              </div>
                              <div>
                                <p className="text-gray-500">Consumo Semanal</p>
                                <p className="font-semibold">{producto.consumo_semanal} unidades</p>
                              </div>
                              <div>
                                <p className="text-gray-500">Se Agota en</p>
                                <p className="font-semibold text-red-600">{producto.dias_hasta_agotar} días</p>
                              </div>
                              <div className="md:col-span-1">
                                <p className="text-gray-500">Razón</p>
                                <p className="text-xs font-medium">{producto.razon}</p>
                              </div>
                            </div>
                          </div>
                          
                          <div className="ml-6 text-right">
                            <p className="text-sm text-gray-600 mb-1">Sugerencia</p>
                            <p className="text-3xl font-bold text-blue-600">
                              {producto.cantidad_sugerida}
                            </p>
                            <p className="text-xs text-gray-500">unidades</p>
                            {producto.costo_total_pedido > 0 && (
                              <p className="text-sm text-gray-600 mt-2">
                                ${producto.costo_total_pedido.toLocaleString('es-AR')}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* Footer con total */}
                  <div className="bg-blue-600 text-white rounded-lg p-4 mt-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-blue-100 text-sm">Total del Pedido</p>
                        <p className="text-2xl font-bold">
                          {proveedor.total_unidades_sugeridas} unidades
                        </p>
                      </div>
                      {proveedor.costo_total_proveedor > 0 && (
                        <div className="text-right">
                          <p className="text-blue-100 text-sm">Costo Estimado</p>
                          <p className="text-2xl font-bold">
                            ${proveedor.costo_total_proveedor.toLocaleString('es-AR')}
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Leyenda de urgencias */}
      <div className="bg-white rounded-xl border border-gray-200 p-4">
        <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
          <Info className="w-5 h-5 mr-2 text-blue-600" />
          Niveles de Urgencia
        </h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div className="flex items-center">
            <span className="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-bold mr-2">
              🚨 URGENTE
            </span>
            <span className="text-sm text-gray-600">Stock por debajo del mínimo</span>
          </div>
          <div className="flex items-center">
            <span className="px-3 py-1 bg-yellow-500 text-white rounded-full text-xs font-bold mr-2">
              ⚠️ PRONTO
            </span>
            <span className="text-sm text-gray-600">Se agota en menos de 7 días</span>
          </div>
          <div className="flex items-center">
            <span className="px-3 py-1 bg-blue-500 text-white rounded-full text-xs font-bold mr-2">
              📦 NORMAL
            </span>
            <span className="text-sm text-gray-600">Pedido preventivo</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PedidosInteligentes;

