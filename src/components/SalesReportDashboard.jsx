import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { 
    DollarSign, TrendingUp, TrendingDown, Activity, Clock, Target,
    CreditCard, Banknote, Smartphone, QrCode, Filter, Download,
    RefreshCw, Calendar, Search, Eye, Printer, X, BarChart3,
    PieChart, Package, Users, Zap, AlertTriangle, CheckCircle
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import reportesService from '../services/reportesService';
import useExportManager from '../hooks/useExportManager';
import VentaDetalleCompleto from './VentaDetalleCompleto';

/**
 * 🚀 SALES REPORT DASHBOARD - ENTERPRISE GRADE
 * 
 * Características:
 * - Dashboard unificado sin scroll obligatorio
 * - KPIs en tiempo real
 * - Análisis por método de pago
 * - Filtrado inteligente
 * - Exportación avanzada
 * - Performance sub-segundo
 */

// ========== COMPONENTE KPI METRICS OVERVIEW ==========
const MetricsOverview = React.memo(({ data, periodo, isLoading }) => {
    const metrics = useMemo(() => {
        if (!data?.ventas) return null;
        
        const ventas = data.ventas;
        const ventasDetalladas = data.ventasDetalladas || [];
        const diferenciaDia = ventas.crecimiento || 0;
        const tendencia = diferenciaDia >= 0 ? 'up' : 'down';
        
        // 🔧 FIX: Calcular correctamente la venta mayor
        const montosVentas = ventasDetalladas.map(v => parseFloat(v.total || 0)).filter(v => v > 0);
        const ventaMayor = montosVentas.length > 0 ? Math.max(...montosVentas) : 0;
        
        // 🔧 FIX: Calcular correctamente productos vendidos desde el carrito
        let productosVendidos = 0;
        ventasDetalladas.forEach(venta => {
            if (venta.cart && Array.isArray(venta.cart)) {
                venta.cart.forEach(item => {
                    productosVendidos += parseInt(item.quantity || item.cantidad || 0);
                });
            } else if (venta.productos && Array.isArray(venta.productos)) {
                venta.productos.forEach(item => {
                    productosVendidos += parseInt(item.quantity || item.cantidad || 0);
                });
            } else if (venta.cantidad) {
                productosVendidos += parseInt(venta.cantidad || 0);
            }
        });
        
        return {
            totalVentas: ventas.ingresos_totales || 0,
            cantidadVentas: ventas.cantidad_ventas || 0,
            ticketPromedio: ventas.ticket_promedio || 0,
            ventaMayor,
            tendencia,
            crecimiento: Math.abs(diferenciaDia),
            productosVendidos
        };
    }, [data]);
    
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                {[...Array(4)].map((_, i) => (
                    <div key={i} className="bg-white rounded-lg p-4 shadow-sm border animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                        <div className="h-8 bg-gray-200 rounded w-1/2 mb-2"></div>
                        <div className="h-3 bg-gray-200 rounded w-1/4"></div>
                    </div>
                ))}
            </div>
        );
    }
    
    if (!metrics) return null;
    
    const kpiCards = [
        {
            title: 'Total Ventas',
            value: metrics.totalVentas,
            format: 'currency',
            icon: DollarSign,
            color: 'blue',
            trend: metrics.tendencia,
            trendValue: `${metrics.crecimiento.toFixed(1)}%`,
            description: `vs ${periodo === 'hoy' ? 'ayer' : 'período anterior'}`
        },
        {
            title: 'Ticket Promedio',
            value: metrics.ticketPromedio,
            format: 'currency',
            icon: BarChart3,
            color: 'green',
            subtitle: `${metrics.cantidadVentas} transacciones`,
            description: 'Valor medio por venta'
        },
        {
            title: 'Venta Mayor',
            value: metrics.ventaMayor,
            format: 'currency',
            icon: TrendingUp,
            color: 'purple',
            subtitle: 'Transacción más alta',
            description: 'Mayor monto registrado'
        },
        {
            title: 'Productos Vendidos',
            value: metrics.productosVendidos,
            format: 'number',
            icon: Package,
            color: 'orange',
            subtitle: `${metrics.cantidadVentas} ventas`,
            description: 'Unidades totales'
        }
    ];
    
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            {kpiCards.map((card, index) => {
                const IconComponent = card.icon;
                const colorClasses = {
                    blue: 'bg-blue-500 text-white',
                    green: 'bg-green-500 text-white',
                    purple: 'bg-purple-500 text-white',
                    orange: 'bg-orange-500 text-white'
                };
                
                return (
                    <div key={index} className="bg-white rounded-lg p-4 shadow-sm border hover:shadow-md transition-shadow">
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <div className="flex items-center space-x-3 mb-3">
                                    <div className={`p-2 rounded-lg ${colorClasses[card.color]}`}>
                                        <IconComponent className="w-5 h-5" />
                                    </div>
                                    <h3 className="text-sm font-medium text-gray-600">{card.title}</h3>
                                </div>
                                
                                <div className="mb-2">
                                    <div className="text-2xl font-black text-gray-900">
                                        {card.format === 'currency' 
                                            ? `$${card.value.toLocaleString('es-AR', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`
                                            : card.value.toLocaleString('es-AR')
                                        }
                                    </div>
                                    {card.subtitle && (
                                        <div className="text-xs text-gray-500">{card.subtitle}</div>
                                    )}
                                </div>
                                
                                {card.trend && (
                                    <div className={`flex items-center text-xs ${
                                        card.trend === 'up' ? 'text-green-600' : 'text-red-600'
                                    }`}>
                                        {card.trend === 'up' ? 
                                            <TrendingUp className="w-3 h-3 mr-1" /> : 
                                            <TrendingDown className="w-3 h-3 mr-1" />
                                        }
                                        <span className="font-medium">{card.trendValue}</span>
                                        <span className="ml-1 text-gray-500">{card.description}</span>
                                    </div>
                                )}
                                
                                {card.description && !card.trend && (
                                    <div className="text-xs text-gray-500">{card.description}</div>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
});

// ========== COMPONENTE PAYMENT METHOD BREAKDOWN ==========
const PaymentMethodBreakdown = React.memo(({ data, isLoading }) => {
    const paymentData = useMemo(() => {
        if (!data?.metodosPago) return null;
        
        const metodos = data.metodosPago;
        const total = data.ventas?.ingresos_totales || 0;
        
        const methodsConfig = [
            {
                id: 'efectivo',
                label: 'Efectivo',
                icon: Banknote,
                color: 'green',
                amount: metodos.efectivo || 0
            },
            {
                id: 'tarjeta',
                label: 'Tarjeta',
                icon: CreditCard,
                color: 'blue',
                amount: metodos.tarjeta || 0
            },
            {
                id: 'transferencia',
                label: 'Transferencia',
                icon: Smartphone,
                color: 'purple',
                amount: metodos.transferencia || 0
            },
            {
                id: 'qr',
                label: 'QR/Digital',
                icon: QrCode,
                color: 'indigo',
                amount: (metodos.mercadopago || 0) + (metodos.qr || 0)
            }
        ];
        
        return methodsConfig.map(method => ({
            ...method,
            percentage: total > 0 ? (method.amount / total) * 100 : 0
        })).filter(method => method.amount > 0);
    }, [data]);
    
    if (isLoading) {
        return (
            <div className="bg-white rounded-lg p-4 shadow-sm border mb-4">
                <div className="h-6 bg-gray-200 rounded w-1/3 mb-4 animate-pulse"></div>
                <div className="space-y-3">
                    {[...Array(3)].map((_, i) => (
                        <div key={i} className="flex items-center space-x-3 animate-pulse">
                            <div className="w-8 h-8 bg-gray-200 rounded"></div>
                            <div className="flex-1">
                                <div className="h-4 bg-gray-200 rounded w-3/4 mb-1"></div>
                                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }
    
    if (!paymentData || paymentData.length === 0) return null;
    
    return (
        <div className="bg-white rounded-lg p-4 shadow-sm border mb-4">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-gray-900">💳 Análisis por Método de Pago</h2>
                <div className="text-sm text-gray-500">
                    {paymentData.length} método{paymentData.length !== 1 ? 's' : ''} activo{paymentData.length !== 1 ? 's' : ''}
                </div>
            </div>
            
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Lista de métodos */}
                <div className="space-y-3">
                    {paymentData.map((method, index) => {
                        const IconComponent = method.icon;
                        const colorClasses = {
                            green: 'bg-green-100 text-green-800 border-green-200',
                            blue: 'bg-blue-100 text-blue-800 border-blue-200',
                            purple: 'bg-purple-100 text-purple-800 border-purple-200',
                            indigo: 'bg-indigo-100 text-indigo-800 border-indigo-200'
                        };
                        
                        return (
                            <div key={method.id} className={`border-2 rounded-lg p-4 ${colorClasses[method.color]}`}>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-3">
                                        <IconComponent className="w-6 h-6" />
                                        <div>
                                            <div className="font-bold text-base">{method.label}</div>
                                            <div className="text-sm opacity-75">
                                                {method.percentage.toFixed(1)}% del total
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-black text-lg">
                                            ${method.amount.toLocaleString('es-AR', {minimumFractionDigits: 0})}
                                        </div>
                                    </div>
                                </div>
                                
                                {/* Barra de progreso */}
                                <div className="mt-3">
                                    <div className="w-full bg-white bg-opacity-50 rounded-full h-2">
                                        <div 
                                            className="bg-current h-2 rounded-full transition-all duration-500"
                                            style={{ width: `${method.percentage}%` }}
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
                
                {/* Gráfico visual */}
                <div className="flex items-center justify-center">
                    <div className="relative w-48 h-48">
                        <PieChart className="w-full h-full text-gray-300" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <div className="text-2xl font-black text-gray-900">
                                    ${data.ventas?.ingresos_totales?.toLocaleString('es-AR', {minimumFractionDigits: 0}) || '0'}
                                </div>
                                <div className="text-xs text-gray-500">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
});

// ========== COMPONENTE QUICK FILTERS ==========
const QuickFilters = React.memo(({ periodo, setPeriodo, fechaInicio, setFechaInicio, fechaFin, setFechaFin, onRefresh, isLoading }) => {
    const periodOptions = [
        { value: 'hoy', label: '📅 Hoy', icon: '📅' },
        { value: 'ayer', label: '📅 Ayer', icon: '⏮️' },
        { value: 'semana', label: '📅 Semana', icon: '📊' },
        { value: 'mes', label: '📅 Mes', icon: '📈' },
        { value: 'personalizado', label: '🔍 Personalizado', icon: '🎯' }
    ];
    
    return (
        <div className="bg-white rounded-xl p-4 shadow-lg border border-gray-100 mb-6">
            <div className="flex flex-wrap items-center gap-3">
                {/* Filtros de período */}
                <div className="flex items-center space-x-2">
                    <span className="text-sm font-medium text-gray-700 mr-2">📅 Período:</span>
                    {periodOptions.map(option => (
                        <button
                            key={option.value}
                            onClick={() => setPeriodo(option.value)}
                            className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                                periodo === option.value
                                    ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg transform scale-105'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-md'
                            }`}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
                
                {/* Fechas personalizadas */}
                {periodo === 'personalizado' && (
                    <div className="flex items-center space-x-2 border-l pl-3 ml-3">
                        <input
                            type="date"
                            value={fechaInicio}
                            onChange={(e) => setFechaInicio(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        />
                        <span className="text-gray-400">→</span>
                        <input
                            type="date"
                            value={fechaFin}
                            onChange={(e) => setFechaFin(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        />
                    </div>
                )}
                
                {/* Botón de actualizar */}
                <button
                    onClick={onRefresh}
                    disabled={isLoading}
                    className="ml-auto flex items-center space-x-2 px-6 py-2 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl hover:from-indigo-600 hover:to-indigo-700 disabled:opacity-50 transition-all shadow-lg hover:shadow-xl"
                >
                    <RefreshCw className={`w-5 h-5 ${isLoading ? 'animate-spin' : ''}`} />
                    <span className="font-medium">Actualizar</span>
                </button>
            </div>
        </div>
    );
});

// ========== COMPONENTE SMART TRANSACTION LIST ==========
const SmartTransactionList = React.memo(({ data, isLoading }) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [sortBy, setSortBy] = useState('fecha');
    const [sortOrder, setSortOrder] = useState('desc');
    const [selectedVenta, setSelectedVenta] = useState(null);
    const [showDetalleCompleto, setShowDetalleCompleto] = useState(false);
    
    const transacciones = useMemo(() => {
        if (!data?.ventasDetalladas) return [];
        
        let filtered = data.ventasDetalladas.filter(venta => {
            if (!searchTerm) return true;
            const term = searchTerm.toLowerCase();
            return (
                (venta.numero_comprobante || `V${String(venta.id || '').padStart(4, '0')}`).toLowerCase().includes(term) ||
                (venta.cliente_nombre || 'Consumidor Final').toLowerCase().includes(term) ||
                (venta.metodo_pago || venta.paymentMethod || 'efectivo').toLowerCase().includes(term)
            );
        });
        
        filtered.sort((a, b) => {
            let aVal = a[sortBy];
            let bVal = b[sortBy];
            
            if (sortBy === 'total') {
                aVal = parseFloat(aVal) || 0;
                bVal = parseFloat(bVal) || 0;
            }
            
            if (sortOrder === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        // 🔧 FIX: Limitar a 8 transacciones máximo para evitar scroll
        return filtered.slice(0, 8);
    }, [data?.ventasDetalladas, searchTerm, sortBy, sortOrder]);
    
    // 🎫 Función para mostrar el detalle completo con datos reales
    const handleShowDetalleCompleto = (venta) => {
        // 🔧 FIX: Preparar datos reales para el detalle completo
        const ventaCompleta = {
            ...venta,
            // Asegurar que el carrito esté disponible
            cart: venta.cart || (venta.detalles_json ? JSON.parse(venta.detalles_json)?.cart || [] : []),
            // Datos financieros reales
            subtotal: parseFloat(venta.subtotal || venta.monto_total || 0),
            total: parseFloat(venta.monto_total || venta.total || 0),
            discount: parseFloat(venta.descuento || 0),
            // Datos de pago
            paymentMethod: venta.metodo_pago || venta.paymentMethod || 'efectivo',
            // Cliente
            cliente_nombre: venta.cliente_nombre || 'Consumidor Final',
            // Fecha
            fecha: venta.fecha || new Date().toISOString()
        };
        
        setSelectedVenta(ventaCompleta);
        setShowDetalleCompleto(true);
    };
    
    if (isLoading) {
        return (
            <div className="bg-white rounded-lg p-4 shadow-sm border">
                <div className="h-6 bg-gray-200 rounded w-1/4 mb-4 animate-pulse"></div>
                <div className="space-y-3">
                    {[...Array(5)].map((_, i) => (
                        <div key={i} className="flex items-center space-x-3 p-3 border rounded animate-pulse">
                            <div className="w-12 h-12 bg-gray-200 rounded"></div>
                            <div className="flex-1">
                                <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                            </div>
                            <div className="h-6 bg-gray-200 rounded w-20"></div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }
    
    return (
        <div className="bg-white rounded-lg p-4 shadow-sm border">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-gray-900">📋 Transacciones Detalladas</h2>
                <div className="text-sm text-gray-500">
                    {transacciones.length} transacciones
                </div>
            </div>
            
            {/* Controles de búsqueda y ordenamiento */}
            <div className="flex items-center space-x-3 mb-4">
                <div className="relative flex-1 max-w-md">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                    <input
                        type="text"
                        placeholder="Buscar por ticket, cliente o método..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
                
                <select
                    value={`${sortBy}-${sortOrder}`}
                    onChange={(e) => {
                        const [field, order] = e.target.value.split('-');
                        setSortBy(field);
                        setSortOrder(order);
                    }}
                    className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                >
                    <option value="fecha-desc">Más recientes</option>
                    <option value="fecha-asc">Más antiguos</option>
                    <option value="total-desc">Mayor monto</option>
                    <option value="total-asc">Menor monto</option>
                </select>
            </div>
            
            {/* Lista de transacciones - SIN SCROLL */}
            <div className="space-y-2">
                {transacciones.map((venta, index) => {
                    const metodo = venta.metodo_pago || venta.paymentMethod || 'efectivo';
                    const metodoPago = {
                        efectivo: { icon: Banknote, color: 'green', label: 'Efectivo' },
                        tarjeta: { icon: CreditCard, color: 'blue', label: 'Tarjeta' },
                        transferencia: { icon: Smartphone, color: 'purple', label: 'Transfer.' },
                        mercadopago: { icon: QrCode, color: 'indigo', label: 'QR' },
                        qr: { icon: QrCode, color: 'indigo', label: 'QR' }
                    }[metodo] || { icon: DollarSign, color: 'gray', label: metodo };
                    
                    const IconComponent = metodoPago.icon;
                    
                    // 🔧 FIX: Calcular productos correctamente
                    let cantidadProductos = 0;
                    if (venta.cart && Array.isArray(venta.cart)) {
                        cantidadProductos = venta.cart.reduce((sum, item) => sum + (parseInt(item.quantity || item.cantidad || 0)), 0);
                    } else if (venta.productos && Array.isArray(venta.productos)) {
                        cantidadProductos = venta.productos.reduce((sum, item) => sum + (parseInt(item.quantity || item.cantidad || 0)), 0);
                    } else {
                        cantidadProductos = parseInt(venta.cantidad || 0);
                    }
                    
                    return (
                        <div
                            key={venta.id || index}
                            className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <div className="flex items-center space-x-3">
                                <div className={`p-2 rounded-lg bg-${metodoPago.color}-100 text-${metodoPago.color}-600`}>
                                    <IconComponent className="w-4 h-4" />
                                </div>
                                <div>
                                    <div className="font-medium text-sm">
                                        #{venta.numero_comprobante || `V${String(venta.id || index + 1).padStart(4, '0')}`}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {venta.fecha ? new Date(venta.fecha).toLocaleDateString('es-AR') : 'Hoy'} - {metodoPago.label}
                                    </div>
                                </div>
                            </div>
                            
                            <div className="flex items-center space-x-3">
                                <div className="text-right">
                                    <div className="font-bold text-sm">
                                        ${parseFloat(venta.total || 0).toLocaleString('es-AR', {minimumFractionDigits: 0})}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {cantidadProductos} prod.
                                    </div>
                                </div>
                                
                                <button
                                    onClick={() => handleShowDetalleCompleto(venta)}
                                    className="flex items-center space-x-1 px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105"
                                    title="🔍 Ver análisis completo de la venta"
                                >
                                    <Eye className="w-4 h-4" />
                                    <span className="text-xs font-medium">Ver</span>
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>
            
            {transacciones.length === 0 && !isLoading && (
                <div className="text-center py-8 text-gray-500">
                    No se encontraron transacciones
                </div>
            )}
            
            {/* 🎫 Modal del Detalle Completo */}
            {showDetalleCompleto && selectedVenta && (
                <VentaDetalleCompleto 
                    venta={selectedVenta}
                    onClose={() => {
                        setShowDetalleCompleto(false);
                        setSelectedVenta(null);
                    }}
                    show={showDetalleCompleto}
                />
            )}
        </div>
    );
});

// ========== COMPONENTE PRINCIPAL ==========
const SalesReportDashboard = () => {
    const { currentUser } = useAuth();
    const [loading, setLoading] = useState(true);
    const [periodo, setPeriodo] = useState('hoy');
    const [fechaInicio, setFechaInicio] = useState('');
    const [fechaFin, setFechaFin] = useState('');
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    
    // 📤 Hook de exportación
    const { 
        exportToPDF, 
        exportToExcel, 
        isExporting, 
        exportProgress, 
        exportError 
    } = useExportManager();
    
    const cargarDatos = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);
            
            const parametros = {
                periodo,
                ...(fechaInicio && { fechaInicio }),
                ...(fechaFin && { fechaFin })
            };
            
            const response = await reportesService.obtenerDatosContables(parametros);
            setData(response);
        } catch (error) {
            console.error('Error cargando datos:', error);
            setError(error.message);
        } finally {
            setLoading(false);
        }
    }, [periodo, fechaInicio, fechaFin]);
    
    useEffect(() => {
        cargarDatos();
    }, [cargarDatos]);
    
    // 📤 Funciones de exportación mejoradas
    const exportarReporte = async (formato) => {
        if (!data) return;
        
        try {
            const filename = `reporte-ventas-${periodo}-${new Date().toISOString().split('T')[0]}`;
            
            if (formato === 'pdf') {
                await exportToPDF(data, {
                    filename: `${filename}.pdf`,
                    template: 'executive',
                    includeCharts: true,
                    orientation: 'portrait'
                });
            } else if (formato === 'excel') {
                await exportToExcel(data, {
                    filename: `${filename}.xlsx`,
                    includeMetrics: true,
                    includeTransactions: true,
                    includeCharts: false
                });
            }
        } catch (error) {
            console.error('Error exportando:', error);
        }
    };
    
    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-4">
            {/* Header Revolucionario */}
            <div className="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 p-3 rounded-xl">
                            <BarChart3 className="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-black text-gray-900">📊 Central de Análisis de Ventas</h1>
                            <p className="text-gray-600">Dashboard ejecutivo para inteligencia comercial en tiempo real</p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                    {/* Indicador de progreso de exportación */}
                    {isExporting && (
                        <div className="flex items-center space-x-2 text-sm text-gray-600">
                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            <span>Exportando... {exportProgress}%</span>
                        </div>
                    )}
                    
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => exportarReporte('pdf')}
                            disabled={isExporting}
                            className="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
                        >
                            <Download className="w-5 h-5" />
                            <span className="font-medium">PDF</span>
                        </button>
                        
                        <button
                            onClick={() => exportarReporte('excel')}
                            disabled={isExporting}
                            className="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
                        >
                            <Download className="w-5 h-5" />
                            <span className="font-medium">Excel</span>
                        </button>
                    </div>
                    </div>
                </div>
            </div>
            
            {/* Quick Filters */}
            <QuickFilters 
                periodo={periodo}
                setPeriodo={setPeriodo}
                fechaInicio={fechaInicio}
                setFechaInicio={setFechaInicio}
                fechaFin={fechaFin}
                setFechaFin={setFechaFin}
                onRefresh={cargarDatos}
                isLoading={loading}
            />
            
            {/* Error States */}
            {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div className="flex items-center space-x-2">
                        <AlertTriangle className="w-5 h-5 text-red-600" />
                        <span className="text-red-800 font-medium">Error cargando datos: {error}</span>
                    </div>
                </div>
            )}
            
            {exportError && (
                <div className="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                    <div className="flex items-center space-x-2">
                        <AlertTriangle className="w-5 h-5 text-orange-600" />
                        <span className="text-orange-800 font-medium">Error exportando reporte: {exportError}</span>
                    </div>
                </div>
            )}
            
            {/* Main Dashboard Content */}
            <div className="space-y-4">
                {/* KPI Metrics Overview */}
                <MetricsOverview data={data} periodo={periodo} isLoading={loading} />
                
                {/* Payment Method Breakdown */}
                <PaymentMethodBreakdown data={data} isLoading={loading} />
                
                {/* Smart Transaction List */}
                <SmartTransactionList data={data} isLoading={loading} />
            </div>
        </div>
    );
};

export default SalesReportDashboard;
