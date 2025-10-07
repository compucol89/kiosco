import React, { useState, useRef } from 'react';
import { 
    X, Printer, Download, Eye, DollarSign, Package, Clock, User,
    CreditCard, Banknote, Smartphone, QrCode, TrendingUp, Info,
    Receipt, FileText, BarChart3, ShoppingCart, AlertCircle
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import TicketProfesional from './TicketProfesional';

/**
 * 🚀 COMPONENTE REVOLUCIONARIO - DETALLE COMPLETO DE VENTA
 * 
 * Características Enterprise:
 * - Vista 360° de la transacción
 * - Análisis financiero detallado  
 * - Información operativa completa
 * - Múltiples acciones disponibles
 * - Diseño clase mundial
 */

const VentaDetalleCompleto = ({ venta, onClose, show = true }) => {
    const { currentUser } = useAuth();
    const [activeTab, setActiveTab] = useState('resumen');
    const [showTicket, setShowTicket] = useState(false);
    const printRef = useRef();

    if (!show || !venta) return null;

    // 💰 Calcular métricas financieras
    const calcularMetricas = () => {
        const subtotal = parseFloat(venta.subtotal || venta.monto_total || 0);
        const descuento = parseFloat(venta.descuento || 0);
        const total = parseFloat(venta.monto_total || venta.total || 0);
        
        const cart = venta.cart || [];
        const cantidadProductos = cart.reduce((sum, item) => sum + (parseInt(item.quantity || item.cantidad || 0)), 0);
        const productosUnicos = cart.length;
        
        return {
            subtotal,
            descuento,
            total,
            cantidadProductos,
            productosUnicos,
            porcentajeDescuento: subtotal > 0 ? (descuento / subtotal * 100) : 0,
            promedioProducto: cantidadProductos > 0 ? (total / cantidadProductos) : 0
        };
    };

    const metricas = calcularMetricas();

    // 🎨 Obtener configuración del método de pago
    const obtenerMetodoPago = () => {
        const metodo = venta.metodo_pago || venta.paymentMethod || 'efectivo';
        const config = {
            efectivo: { 
                icon: Banknote, 
                color: 'green', 
                label: 'Efectivo',
                badge: 'bg-green-100 text-green-800 border-green-200'
            },
            tarjeta: { 
                icon: CreditCard, 
                color: 'blue', 
                label: 'Tarjeta de Crédito/Débito',
                badge: 'bg-blue-100 text-blue-800 border-blue-200'
            },
            transferencia: { 
                icon: Smartphone, 
                color: 'purple', 
                label: 'Transferencia Bancaria',
                badge: 'bg-purple-100 text-purple-800 border-purple-200'
            },
            mercadopago: { 
                icon: QrCode, 
                color: 'indigo', 
                label: 'MercadoPago',
                badge: 'bg-indigo-100 text-indigo-800 border-indigo-200'
            },
            qr: { 
                icon: QrCode, 
                color: 'indigo', 
                label: 'Código QR',
                badge: 'bg-indigo-100 text-indigo-800 border-indigo-200'
            }
        };
        return config[metodo] || { 
            icon: DollarSign, 
            color: 'gray', 
            label: metodo,
            badge: 'bg-gray-100 text-gray-800 border-gray-200'
        };
    };

    const metodoPago = obtenerMetodoPago();
    const IconMetodo = metodoPago.icon;

    // 📋 Tabs disponibles
    const tabs = [
        { id: 'resumen', label: '📊 Resumen', icon: BarChart3 },
        { id: 'productos', label: '📦 Productos', icon: Package },
        { id: 'financiero', label: '💰 Financiero', icon: DollarSign },
        { id: 'operativo', label: '⚙️ Operativo', icon: Info }
    ];

    // 🎯 Renderizar contenido según tab activo
    const renderTabContent = () => {
        switch (activeTab) {
            case 'resumen':
                return (
                    <div className="space-y-6">
                        {/* KPIs Principales */}
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                                <div className="flex items-center space-x-2 mb-2">
                                    <DollarSign className="w-5 h-5 text-blue-600" />
                                    <span className="text-sm font-medium text-blue-800">Total Cobrado</span>
                                </div>
                                <div className="text-2xl font-black text-blue-900">
                                    ${metricas.total.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                </div>
                            </div>
                            
                            <div className="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                                <div className="flex items-center space-x-2 mb-2">
                                    <Package className="w-5 h-5 text-green-600" />
                                    <span className="text-sm font-medium text-green-800">Productos</span>
                                </div>
                                <div className="text-2xl font-black text-green-900">
                                    {metricas.cantidadProductos}
                                </div>
                                <div className="text-xs text-green-600">
                                    {metricas.productosUnicos} tipo{metricas.productosUnicos !== 1 ? 's' : ''}
                                </div>
                            </div>

                            <div className="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                                <div className="flex items-center space-x-2 mb-2">
                                    <TrendingUp className="w-5 h-5 text-purple-600" />
                                    <span className="text-sm font-medium text-purple-800">Promedio/Unidad</span>
                                </div>
                                <div className="text-2xl font-black text-purple-900">
                                    ${metricas.promedioProducto.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                </div>
                            </div>

                            <div className="bg-gradient-to-r from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                                <div className="flex items-center space-x-2 mb-2">
                                    <IconMetodo className="w-5 h-5 text-orange-600" />
                                    <span className="text-sm font-medium text-orange-800">Método</span>
                                </div>
                                <div className="text-lg font-bold text-orange-900">
                                    {metodoPago.label}
                                </div>
                            </div>
                        </div>

                        {/* Información General */}
                        <div className="bg-gray-50 rounded-lg p-4">
                            <h3 className="font-bold text-gray-900 mb-3 flex items-center space-x-2">
                                <Info className="w-5 h-5" />
                                <span>Información General</span>
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div className="text-sm text-gray-600">Número de Comprobante</div>
                                    <div className="font-bold text-lg">
                                        #{venta.numero_comprobante || `V${String(venta.id).padStart(4, '0')}`}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Fecha y Hora</div>
                                    <div className="font-medium">
                                        {new Date(venta.fecha).toLocaleString('es-AR')}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Cliente</div>
                                    <div className="font-medium">
                                        {venta.cliente_nombre || 'Consumidor Final'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Atendido por</div>
                                    <div className="font-medium">
                                        {currentUser?.username || currentUser?.name || 'Sistema'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'productos':
                const cart = venta.cart || [];
                return (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-bold text-gray-900 flex items-center space-x-2">
                                <ShoppingCart className="w-5 h-5" />
                                <span>Productos Vendidos ({cart.length})</span>
                            </h3>
                        </div>
                        
                        {cart.length > 0 ? (
                            <div className="space-y-3">
                                {cart.map((item, index) => (
                                    <div key={index} className="bg-white border border-gray-200 rounded-lg p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="font-medium text-gray-900">
                                                    {item.name || item.nombre || 'Producto'}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    Cantidad: {item.quantity || item.cantidad || 0} unidades
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    Precio unitario: ${parseFloat(item.price || item.precio || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-lg font-bold text-gray-900">
                                                    ${(parseFloat(item.price || item.precio || 0) * parseInt(item.quantity || item.cantidad || 0)).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8 text-gray-500">
                                <Package className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                <p>No hay información detallada de productos disponible</p>
                            </div>
                        )}
                    </div>
                );

            case 'financiero':
                return (
                    <div className="space-y-6">
                        {/* Desglose Financiero */}
                        <div className="bg-white border border-gray-200 rounded-lg p-4">
                            <h3 className="font-bold text-gray-900 mb-4 flex items-center space-x-2">
                                <DollarSign className="w-5 h-5" />
                                <span>Desglose Financiero</span>
                            </h3>
                            <div className="space-y-3">
                                <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span className="text-gray-600">Subtotal</span>
                                    <span className="font-medium">${metricas.subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2})}</span>
                                </div>
                                
                                {metricas.descuento > 0 && (
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span className="text-green-600">
                                            Descuento ({metricas.porcentajeDescuento.toFixed(1)}%)
                                        </span>
                                        <span className="font-medium text-green-600">
                                            -${metricas.descuento.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                        </span>
                                    </div>
                                )}
                                
                                <div className="flex justify-between items-center py-2 border-t-2 border-gray-300">
                                    <span className="font-bold text-lg">Total Final</span>
                                    <span className="font-black text-xl text-blue-600">
                                        ${metricas.total.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Método de Pago */}
                        <div className={`border-2 rounded-lg p-4 ${metodoPago.badge}`}>
                            <div className="flex items-center space-x-3">
                                <IconMetodo className="w-8 h-8" />
                                <div>
                                    <div className="font-bold text-lg">{metodoPago.label}</div>
                                    <div className="text-sm opacity-75">Método de pago utilizado</div>
                                </div>
                            </div>
                        </div>

                        {/* Información de Cambio (si es efectivo) */}
                        {(venta.metodo_pago === 'efectivo' || venta.paymentMethod === 'efectivo') && (
                            <div className="bg-gray-50 rounded-lg p-4">
                                <h4 className="font-medium text-gray-900 mb-2">💵 Información de Efectivo</h4>
                                <div className="space-y-2">
                                    <div className="flex justify-between">
                                        <span>Recibido:</span>
                                        <span className="font-medium">
                                            ${parseFloat(venta.efectivo_recibido || venta.cashReceived || venta.total || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Cambio:</span>
                                        <span className="font-medium text-green-600">
                                            ${parseFloat(venta.cambio_entregado || venta.change || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                );

            case 'operativo':
                return (
                    <div className="space-y-6">
                        {/* Información del Sistema */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 className="font-bold text-blue-900 mb-3 flex items-center space-x-2">
                                <Info className="w-5 h-5" />
                                <span>Información del Sistema</span>
                            </h3>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-blue-700">ID de Venta:</span>
                                    <span className="font-mono font-medium text-blue-900">#{venta.id}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-blue-700">Estado:</span>
                                    <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-medium">
                                        {venta.estado || 'Completado'}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-blue-700">Procesado:</span>
                                    <span className="font-medium text-blue-900">
                                        {new Date(venta.fecha).toLocaleString('es-AR')}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Información Fiscal AFIP - Argentina */}
                        <div className="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4">
                            <h3 className="font-bold text-green-900 mb-3 flex items-center space-x-2">
                                <Receipt className="w-5 h-5" />
                                <span>🧾 Información Fiscal AFIP</span>
                            </h3>
                            
                            {venta.datos_fiscales?.cae ? (
                                <div className="space-y-3">
                                    <div className="flex items-center space-x-2 mb-2">
                                        <span className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">
                                            ✅ COMPROBANTE FISCAL VÁLIDO
                                        </span>
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div className="bg-white rounded p-3">
                                            <div className="font-medium text-gray-700">CAE (Código de Autorización)</div>
                                            <div className="font-mono text-green-700 font-bold">{venta.datos_fiscales.cae}</div>
                                        </div>
                                        
                                        {venta.datos_fiscales.numero_fiscal && (
                                            <div className="bg-white rounded p-3">
                                                <div className="font-medium text-gray-700">Número Fiscal</div>
                                                <div className="font-mono text-blue-700 font-bold">{venta.datos_fiscales.numero_fiscal}</div>
                                            </div>
                                        )}
                                        
                                        {venta.datos_fiscales.fecha_vencimiento && (
                                            <div className="bg-white rounded p-3">
                                                <div className="font-medium text-gray-700">Vencimiento CAE</div>
                                                <div className="text-gray-900">{new Date(venta.datos_fiscales.fecha_vencimiento).toLocaleDateString('es-AR')}</div>
                                            </div>
                                        )}
                                        
                                        <div className="bg-white rounded p-3">
                                            <div className="font-medium text-gray-700">Tipo de Comprobante</div>
                                            <div className="text-gray-900">{venta.datos_fiscales.tipo_comprobante || 'TICKET FISCAL'}</div>
                                        </div>
                                    </div>
                                    
                                    {venta.datos_fiscales.qr_data && (
                                        <div className="bg-white rounded p-3 mt-3">
                                            <div className="font-medium text-gray-700 mb-2">📱 Código QR para Verificación AFIP</div>
                                            <div className="text-xs text-gray-600 bg-gray-50 p-2 rounded font-mono break-all">
                                                {venta.datos_fiscales.qr_data.substring(0, 100)}...
                                            </div>
                                        </div>
                                    )}
                                    
                                    <div className="text-xs text-green-700 bg-green-100 p-2 rounded">
                                        ℹ️ Comprobante fiscalmente válido según Ley 27.743 - RG (AFIP) 4540/2019
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <span className="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                            ⚠️ COMPROBANTE EN PROCESO
                                        </span>
                                    </div>
                                    <div className="text-sm text-yellow-700 bg-yellow-50 p-2 rounded">
                                        El comprobante fiscal está siendo procesado por AFIP. Los datos fiscales aparecerán cuando esté disponible.
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Acciones Adicionales */}
                        <div className="bg-gray-50 rounded-lg p-4">
                            <h3 className="font-bold text-gray-900 mb-3">Acciones Disponibles</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <button
                                    onClick={() => setShowTicket(true)}
                                    className="flex items-center space-x-2 p-3 bg-white border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-colors"
                                >
                                    <Receipt className="w-5 h-5 text-blue-600" />
                                    <span className="font-medium">Ver Ticket Completo</span>
                                </button>
                                
                                <button
                                    onClick={() => window.print()}
                                    className="flex items-center space-x-2 p-3 bg-white border border-gray-300 rounded-lg hover:bg-green-50 hover:border-green-300 transition-colors"
                                >
                                    <Printer className="w-5 h-5 text-green-600" />
                                    <span className="font-medium">Imprimir Resumen</span>
                                </button>
                            </div>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                {/* Header */}
                <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-xl font-bold">🔍 Detalle Completo de Venta</h1>
                            <p className="text-blue-100 text-sm">
                                Comprobante #{venta.numero_comprobante || `V${String(venta.id).padStart(4, '0')}`} - 
                                {new Date(venta.fecha).toLocaleDateString('es-AR')}
                            </p>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 hover:bg-blue-500 rounded-lg transition-colors"
                        >
                            <X className="w-6 h-6" />
                        </button>
                    </div>
                </div>

                {/* Tabs */}
                <div className="bg-gray-50 border-b">
                    <div className="flex space-x-1 p-1">
                        {tabs.map(tab => {
                            const IconTab = tab.icon;
                            return (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`flex items-center space-x-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                                        activeTab === tab.id
                                            ? 'bg-white text-blue-600 shadow-sm border border-blue-200'
                                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                    }`}
                                >
                                    <IconTab className="w-4 h-4" />
                                    <span className="text-sm">{tab.label}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Content */}
                <div className="p-6 overflow-y-auto max-h-[60vh]">
                    {renderTabContent()}
                </div>

                {/* Actions Footer */}
                <div className="bg-gray-50 border-t p-4 flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        💡 <strong>Tip:</strong> Usa las pestañas para explorar diferentes aspectos de la venta
                    </div>
                    <div className="flex space-x-3">
                        <button
                            onClick={() => setShowTicket(true)}
                            className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <Receipt className="w-4 h-4" />
                            <span>Ver Ticket</span>
                        </button>
                        <button
                            onClick={onClose}
                            className="flex items-center space-x-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                        >
                            <X className="w-4 h-4" />
                            <span>Cerrar</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Modal del Ticket */}
            {showTicket && (
                <TicketProfesional 
                    venta={venta}
                    onClose={() => setShowTicket(false)}
                    show={showTicket}
                />
            )}
        </div>
    );
};

export default VentaDetalleCompleto;
