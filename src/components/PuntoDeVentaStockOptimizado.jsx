import React, { useState, useRef, useEffect, useMemo, useCallback, Suspense } from 'react';
import { 
    Search, ShoppingCart, Plus, Minus, X, 
    AlertCircle, AlertTriangle, RefreshCw,
    Package, Grid3X3, List, Trash2, ChevronLeft, ChevronRight
} from 'lucide-react';
import CONFIG from '../config/config';
import descuentosService from '../services/descuentosService';
import cashSyncService from '../services/cashSyncService';
import useStockManager from '../hooks/useStockManager';
import useCajaStatus from '../hooks/useCajaStatus';
import { ProductCardWithAlerts, StockCriticalAlert } from './StockAlerts';
import PaymentModalSleepyCashierProof from './PaymentModalSleepyCashierProof';

// ⚡ LAZY LOADING: Ticket solo cuando se imprima
const TicketProfesional = React.lazy(() => import('./TicketProfesional'));

const PuntoDeVentaStockOptimizado = () => {
    
    // 🎯 GESTIÓN INTELIGENTE DE STOCK CON HOOK PERSONALIZADO
    const {
        productos,
        estadisticas,
        isLoading,
        error: stockError,
        filtros,
        toggleIncluirSinStock,
        toggleSoloStockBajo,
        buscarProductos,
        filtrarPorCategoria,
        verificarStockTiempoReal,
        refrescar,
        lastUpdate
    } = useStockManager({
        incluirSinStock: false, // Por defecto no mostrar productos sin stock
        autoRefresh: true,
        refreshInterval: 30000 // 30 segundos
    });
    
    // Estados de interfaz
    const [filteredProductos, setFilteredProductos] = useState([]);
    const [displayedProductos, setDisplayedProductos] = useState([]);
    const [cart, setCart] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [viewMode, setViewMode] = useState('grid'); // 'grid' o 'list'
    
    // 📱 ESTADOS RESPONSIVE
    const [cartCollapsed, setCartCollapsed] = useState(false);
    const [isMobile, setIsMobile] = useState(false);
    const [isTablet, setIsTablet] = useState(false);
    const [screenSize, setScreenSize] = useState('lg');
    
    // Estados de paginación
    const [currentPage, setCurrentPage] = useState(1);
    const [productsPerPage, setProductsPerPage] = useState(20);
    
    // Estados del carrito y pagos
    const [showPaymentPanel, setShowPaymentPanel] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);
    const [lastSale, setLastSale] = useState(null);
    
    // Estados de descuentos y notificaciones
    const [appliedDiscount, setAppliedDiscount] = useState(null);
    const [notification, setNotification] = useState({ show: false, message: '', type: 'info' });
    
    // 🔔 SISTEMA DE NOTIFICACIONES (debe estar antes del hook useCajaStatus)
    const showNotification = useCallback((message, type = 'info') => {
        setNotification({ show: true, message, type });
        setTimeout(() => {
            setNotification({ show: false, message: '', type: 'info' });
        }, 3000);
    }, []);
    
    // 🔒 SISTEMA CRÍTICO DE VALIDACIÓN DE CAJA
    const {
        cajaStatus: cajaEstado,
        canProcessSales,
        validateSaleOperation,
        refreshStatus: refreshCajaStatus,
        isLoading: cajaLoading,
        error: cajaError
    } = useCajaStatus({
        autoRefresh: false, // Deshabilitado temporalmente para evitar loop
        refreshInterval: 60000, // Aumentado a 1 minuto
        enableNotifications: false, // Deshabilitado para reducir spam
        onStatusChange: (change) => {
            // showNotification(change.message, change.current === 'abierta' ? 'success' : 'warning');
        }
    });
    
    // Referencias
    const searchInputRef = useRef(null);
    const cartRef = useRef(null);
    
    // 📱 DETECTOR DE PANTALLA RESPONSIVE
    useEffect(() => {
        const updateScreenSize = () => {
            const width = window.innerWidth;
            
            setIsMobile(width < 768);
            setIsTablet(width >= 768 && width < 1200);
            
            if (width < 576) {
                setScreenSize('xs');
                setCartCollapsed(true);
                setProductsPerPage(8);
            } else if (width < 768) {
                setScreenSize('sm');
                setCartCollapsed(true);
                setProductsPerPage(12);
            } else if (width < 1200) {
                setScreenSize('md');
                setCartCollapsed(false);
                setProductsPerPage(16);
            } else {
                setScreenSize('lg');
                setCartCollapsed(false);
                setProductsPerPage(20);
            }
        };
        
        updateScreenSize();
        window.addEventListener('resize', updateScreenSize);
        return () => window.removeEventListener('resize', updateScreenSize);
    }, []);
    
    // 🔍 EFECTO PARA SINCRONIZAR BÚSQUEDA CON HOOK DE STOCK
    useEffect(() => {
        buscarProductos(searchQuery);
    }, [searchQuery, buscarProductos]);
    
    // 📂 EFECTO PARA SINCRONIZAR CATEGORÍA CON HOOK DE STOCK
    useEffect(() => {
        filtrarPorCategoria(selectedCategory === 'all' ? '' : selectedCategory);
    }, [selectedCategory, filtrarPorCategoria]);
    
    // 🔍 EFECTO PARA FILTRAR PRODUCTOS LOCALMENTE
    useEffect(() => {
        let filtered = [...productos];
        
        // Ordenamiento inteligente: primero productos con stock
        filtered.sort((a, b) => {
            const stockA = a.stock_info?.puede_vender || (a.stock > 0);
            const stockB = b.stock_info?.puede_vender || (b.stock > 0);
            
            if (stockA !== stockB) {
                return stockB ? 1 : -1;
            }
            
            const cantidadA = a.stock_info?.cantidad || a.stock || 0;
            const cantidadB = b.stock_info?.cantidad || b.stock || 0;
            
            if (cantidadA !== cantidadB) {
                return cantidadB - cantidadA;
            }
            
            return (a.nombre || '').localeCompare(b.nombre || '', 'es', { sensitivity: 'base' });
        });
        
        setFilteredProductos(filtered);
    }, [productos, selectedCategory]);

    // Efecto para manejar la paginación
    useEffect(() => {
        const startIndex = (currentPage - 1) * productsPerPage;
        const endIndex = startIndex + productsPerPage;
        const paginatedProducts = filteredProductos.slice(startIndex, endIndex);
        setDisplayedProductos(paginatedProducts);
    }, [filteredProductos, currentPage, productsPerPage]);

    // Resetear página cuando cambien los filtros
    useEffect(() => {
        setCurrentPage(1);
    }, [searchQuery, selectedCategory, productsPerPage]);

    // Obtener categorías únicas
    const categorias = useMemo(() => {
        const cats = [...new Set(productos.map(p => p.categoria).filter(Boolean))];
        return cats.sort();
    }, [productos]);

    // Cargar estado de caja al inicializar
    // ✅ El hook useCajaStatus ya maneja automáticamente la carga y refresh del estado de caja

    // Cálculos de paginación
    const totalPages = Math.ceil(filteredProductos.length / productsPerPage);
    const currentStartIndex = (currentPage - 1) * productsPerPage + 1;
    const currentEndIndex = Math.min(currentPage * productsPerPage, filteredProductos.length);

    // 🛒 FUNCIONES DEL CARRITO CON VERIFICACIÓN DE STOCK Y CAJA
    const addToCart = useCallback(async (producto) => {
        // 🔒 VALIDACIÓN CRÍTICA: Verificar si se pueden procesar ventas
        if (!canProcessSales) {
            const validation = await validateSaleOperation();
            showNotification(validation.message, 'error');
            return;
        }

        // Verificar stock usando la nueva estructura
        const stockInfo = producto.stock_info || { puede_vender: producto.stock > 0, cantidad: producto.stock };
        
        if (!stockInfo.puede_vender || stockInfo.cantidad <= 0) {
            showNotification('Producto sin stock disponible', 'error');
            return;
        }

        // Verificar stock en tiempo real antes de agregar
        try {
            const stockActualizado = await verificarStockTiempoReal([producto.id]);
            const stockReal = stockActualizado[producto.id];
            
            if (stockReal && stockReal.stock <= 0) {
                showNotification('Producto sin stock (verificación en tiempo real)', 'error');
                return;
            }
        } catch (error) {
            console.warn('No se pudo verificar stock en tiempo real:', error);
        }

        setCart(prevCart => {
            const existingItem = prevCart.find(item => item.id === producto.id);
            
            if (existingItem) {
                const stockDisponible = stockInfo.cantidad;
                if (existingItem.quantity >= stockDisponible) {
                    showNotification('No hay suficiente stock disponible', 'error');
                    return prevCart;
                }
                return prevCart.map(item =>
                    item.id === producto.id 
                        ? { ...item, quantity: item.quantity + 1 }
                        : item
                );
            } else {
                return [...prevCart, { 
                    ...producto, 
                    quantity: 1,
                    price: producto.precio_venta 
                }];
            }
        });
        
        showNotification(`${producto.nombre} agregado al carrito`, 'success');
    }, [canProcessSales, validateSaleOperation, verificarStockTiempoReal, showNotification]);

    const updateCartItemQuantity = useCallback((productId, newQuantity) => {
        if (newQuantity <= 0) {
            removeFromCart(productId);
            return;
        }

        setCart(prevCart => 
            prevCart.map(item => {
                if (item.id === productId) {
                    const maxQuantity = item.stock_info?.cantidad || item.stock || 0;
                    const finalQuantity = Math.min(newQuantity, maxQuantity);
                    
                    if (finalQuantity < newQuantity) {
                        showNotification(`Cantidad limitada al stock disponible: ${finalQuantity}`, 'warning');
                    }
                    
                    return { ...item, quantity: finalQuantity };
                }
                return item;
            })
        );
    }, []);

    const removeFromCart = useCallback((productId) => {
        setCart(prevCart => prevCart.filter(item => item.id !== productId));
        showNotification('Producto eliminado del carrito', 'info');
    }, []);

    const clearCart = useCallback(() => {
        setCart([]);
        setAppliedDiscount(null);
        showNotification('Carrito vaciado', 'info');
    }, []);

    // Calcular totales del carrito
    const cartTotals = useMemo(() => {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        return { subtotal, itemCount };
    }, [cart]);

    // 💰 FUNCIONES DE DESCUENTOS
    const applyDiscount = useCallback(async (discountType) => {
        try {
            const result = await descuentosService.aplicarDescuento(cart, discountType);
            setAppliedDiscount(result);
            showNotification(`Descuento ${result.descripcion} aplicado`, 'success');
        } catch (error) {
            showNotification(error.message, 'error');
        }
    }, [cart]);

    const removeDiscount = useCallback(() => {
        setAppliedDiscount(null);
        showNotification('Descuento eliminado', 'info');
    }, []);

    // 🛡️ FUNCIONES DE PAGO
    const calculateFinalTotals = useMemo(() => {
        const subtotal = cartTotals.subtotal;
        
        if (!appliedDiscount) {
            return {
                subtotal,
                discount: 0,
                total: subtotal
            };
        }

        let discountAmount = 0;
        if (appliedDiscount.tipo === 'porcentaje') {
            discountAmount = subtotal * (appliedDiscount.valor / 100);
        } else {
            discountAmount = Math.min(appliedDiscount.valor, subtotal);
        }

        return {
            subtotal,
            discount: discountAmount,
            total: Math.max(0, subtotal - discountAmount)
        };
    }, [cartTotals.subtotal, appliedDiscount]);

    // 💳 PROCESAMIENTO DE PAGO OPTIMIZADO CON VALIDACIÓN CRÍTICA
    const processOptimizedPayment = useCallback(async (paymentData) => {
        if (cart.length === 0) {
            showNotification('El carrito está vacío', 'error');
            return;
        }

        // 🔒 VALIDACIÓN CRÍTICA FINAL ANTES DE PROCESAR VENTA
        const validation = await validateSaleOperation();
        if (!validation.valid) {
            showNotification(validation.message, 'error');
            return;
        }

        setIsProcessing(true);

        try {
            // Verificar stock final antes de procesar
            const productosIds = cart.map(item => item.id);
            const stocksActualizados = await verificarStockTiempoReal(productosIds);
            
            for (const item of cart) {
                const stockReal = stocksActualizados[item.id];
                if (stockReal && stockReal.stock < item.quantity) {
                    throw new Error(`Stock insuficiente para ${item.nombre}. Disponible: ${stockReal.stock}`);
                }
            }

            const saleData = {
                cart: cart.map(item => ({
                    id: item.id,
                    name: item.nombre || item.name,
                    quantity: item.quantity,
                    price: item.price,
                    aplica_descuento_forma_pago: item.aplica_descuento_forma_pago
                })),
                paymentMethod: paymentData.method,
                subtotal: paymentData.originalTotal || paymentData.total,
                discount: paymentData.discountAmount || 0,
                total: paymentData.total,
                discountInfo: appliedDiscount,
                cashReceived: paymentData.received,
                change: paymentData.change,
                caja_id: cajaEstado && cajaEstado.caja ? cajaEstado.caja.id : null,
                // 🎫 DATOS DE DESGLOSE DE DESCUENTOS PARA EL TICKET
                desglose_descuentos: paymentData.desglose_descuentos
            };

            // Datos de venta enviados a procesamiento
            const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PROCESAR_VENTA), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(saleData)
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                const saleCompleteData = {
                    ...saleData,
                    venta_id: result.venta_id,
                    numero_comprobante: result.numero_comprobante,
                    fecha: new Date(),
                    // 🧾 DATOS FISCALES AFIP
                    comprobante_fiscal: result.comprobante_fiscal,
                    // ⚡ INFORMACIÓN DE PERFORMANCE
                    execution_time_ms: result.execution_time_ms,
                    fiscal_time_ms: result.fiscal_time_ms,
                    fast_mode: result.fast_mode,
                    background_tasks: result.background_tasks
                };
                
                setLastSale(saleCompleteData);
                
                // 🔄 SINCRONIZACIÓN AUTOMÁTICA CON SISTEMA DE CAJA
                try {
                    await cashSyncService.syncSaleToCache({
                        venta_id: result.venta_id,
                        metodo_pago: paymentData.method,
                        monto_total: paymentData.total,
                        numero_comprobante: result.numero_comprobante,
                        paymentMethod: paymentData.method,
                        total: paymentData.total
                    });
                    console.log('✅ Venta sincronizada automáticamente con caja');
                } catch (syncError) {
                    console.warn('⚠️  Error en sincronización automática con caja:', syncError);
                    // La venta ya fue procesada exitosamente, solo logueamos el error de sync
                }
                
                setCart([]);
                setAppliedDiscount(null);
                setShowPaymentPanel(false);
                setShowReceipt(true);
                
                // Mensaje optimizado con información fiscal
                if (result.fast_mode) {
                    let fiscalMsg = '';
                    if (result.comprobante_fiscal && result.comprobante_fiscal.estado_afip === 'APROBADO') {
                        fiscalMsg = ` | Ticket fiscal: ${result.comprobante_fiscal.numero_comprobante_fiscal}`;
                    }
                    showNotification(`✅ Venta procesada en ${result.execution_time_ms}ms (modo ultra-rápido)${fiscalMsg}`, 'success');
                } else {
                    showNotification('Venta procesada exitosamente', 'success');
                }
                
                // Refrescar productos para actualizar stock
                setTimeout(() => {
                    refrescar();
                }, 1000);
                
                // Actualizar estado de caja
                await refreshCajaStatus();
                
            } else {
                throw new Error(result.message || 'Error al procesar la venta');
            }

        } catch (error) {
            console.error('Error processing payment:', error);
            showNotification(error.message || 'Error al procesar el pago', 'error');
        } finally {
            setIsProcessing(false);
        }
    }, [cart, calculateFinalTotals, appliedDiscount, validateSaleOperation, verificarStockTiempoReal, refrescar, refreshCajaStatus, showNotification]);

    // 🚀 FUNCIONES DE NAVEGACIÓN Y PAGINACIÓN
    const goToPage = (page) => {
        if (page >= 1 && page <= totalPages) {
            setCurrentPage(page);
        }
    };

    const goToPreviousPage = () => {
        if (currentPage > 1) {
            setCurrentPage(currentPage - 1);
        }
    };

    const goToNextPage = () => {
        if (currentPage < totalPages) {
            setCurrentPage(currentPage + 1);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* 🚨 ALERTA DE STOCK CRÍTICO */}
            {estadisticas && (estadisticas.sin_stock > 0 || estadisticas.stock_bajo > 0) && (
                <div className="bg-white border-b border-gray-200 p-4">
                    <StockCriticalAlert 
                        estadisticas={estadisticas}
                        onViewDetails={() => toggleSoloStockBajo()}
                    />
                </div>
            )}

            {/* Notificaciones */}
            {notification.show && (
                <div className={`fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-300 ${
                    notification.type === 'success' ? 'bg-green-500 text-white' :
                    notification.type === 'error' ? 'bg-red-500 text-white' :
                    notification.type === 'warning' ? 'bg-yellow-500 text-white' :
                    'bg-blue-500 text-white'
                }`}>
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">{notification.message}</span>
                        <button 
                            onClick={() => setNotification({ show: false, message: '', type: 'info' })}
                            className="ml-2 text-white hover:text-gray-200"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            )}

            <div className="flex h-screen">
                {/* 📦 PANEL PRINCIPAL DE PRODUCTOS */}
                <div className={`flex-1 flex flex-col transition-all duration-300 ${
                    cartCollapsed ? 'mr-0' : 'mr-80'
                }`}>
                    {/* Header con controles */}
                    <div className="bg-white border-b border-gray-200 p-4">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            {/* Título y estadísticas */}
                            <div className="flex items-center gap-4">
                                <h1 className="text-xl font-bold text-gray-900">Punto de Venta</h1>
                                {estadisticas && (
                                    <div className="flex items-center gap-2 text-sm text-gray-600">
                                        <Package className="w-4 h-4" />
                                        <span>{estadisticas.total_productos} productos</span>
                                        {estadisticas.sin_stock > 0 && (
                                            <span className="text-red-600 font-medium">
                                                • {estadisticas.sin_stock} sin stock
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Controles de filtro */}
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={toggleIncluirSinStock}
                                    className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                                        filtros.incluirSinStock 
                                            ? 'bg-red-100 text-red-700 border border-red-200' 
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    <AlertCircle className="w-4 h-4" />
                                    {filtros.incluirSinStock ? 'Ocultando sin stock' : 'Mostrar sin stock'}
                                </button>

                                <button
                                    onClick={toggleSoloStockBajo}
                                    className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                                        filtros.soloStockBajo 
                                            ? 'bg-yellow-100 text-yellow-700 border border-yellow-200' 
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    <AlertTriangle className="w-4 h-4" />
                                    Solo stock bajo
                                </button>

                                <button
                                    onClick={refrescar}
                                    className="flex items-center gap-2 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
                                    title="Actualizar productos"
                                >
                                    <RefreshCw className="w-4 h-4" />
                                    {lastUpdate && (
                                        <span className="text-xs">
                                            {new Date(lastUpdate).toLocaleTimeString()}
                                        </span>
                                    )}
                                </button>
                            </div>
                        </div>

                        {/* Barra de búsqueda y filtros */}
                        <div className="mt-4 flex flex-col lg:flex-row gap-4">
                            {/* Buscador */}
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                <input
                                    ref={searchInputRef}
                                    type="text"
                                    placeholder="Buscar productos por nombre, código o categoría..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            {/* Filtro de categoría */}
                            <div className="flex items-center gap-2">
                                <select
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                    className="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="all">Todas las categorías</option>
                                    {categorias.map(cat => (
                                        <option key={cat} value={cat}>{cat}</option>
                                    ))}
                                </select>

                                {/* Toggle de vista */}
                                <div className="flex border border-gray-300 rounded-lg overflow-hidden">
                                    <button
                                        onClick={() => setViewMode('grid')}
                                        className={`p-2 ${viewMode === 'grid' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'}`}
                                    >
                                        <Grid3X3 className="w-5 h-5" />
                                    </button>
                                    <button
                                        onClick={() => setViewMode('list')}
                                        className={`p-2 ${viewMode === 'list' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700'}`}
                                    >
                                        <List className="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* 📦 CONTENIDO DE PRODUCTOS */}
                    <div className="flex-1 overflow-y-auto p-4">
                        {stockError && (
                            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div className="flex items-center">
                                    <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
                                    <span className="text-red-700">Error al cargar productos: {stockError}</span>
                                    <button 
                                        onClick={refrescar}
                                        className="ml-auto text-red-600 hover:text-red-800"
                                    >
                                        <RefreshCw className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        )}

                        {isLoading ? (
                            <div className="flex items-center justify-center h-64">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                            </div>
                        ) : filteredProductos.length === 0 ? (
                            <div className="text-center py-12">
                                <Package className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                                <p className="text-gray-500 text-lg">No se encontraron productos</p>
                                <p className="text-gray-400">Pruebe con otros términos de búsqueda</p>
                            </div>
                        ) : viewMode === 'grid' ? (
                            <div className="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                                {displayedProductos.map(producto => (
                                    <ProductCardWithAlerts 
                                        key={producto.id} 
                                        producto={producto} 
                                        onAdd={addToCart} 
                                        variant="card"
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {displayedProductos.map(producto => (
                                    <ProductCardWithAlerts 
                                        key={producto.id} 
                                        producto={producto} 
                                        onAdd={addToCart} 
                                        variant="list"
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Paginación */}
                    {totalPages > 1 && (
                        <div className="bg-white border-t border-gray-200 px-4 py-3 flex items-center justify-between">
                            <div className="flex-1 flex justify-between sm:hidden">
                                <button
                                    onClick={goToPreviousPage}
                                    disabled={currentPage === 1}
                                    className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Anterior
                                </button>
                                <button
                                    onClick={goToNextPage}
                                    disabled={currentPage === totalPages}
                                    className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Siguiente
                                </button>
                            </div>
                            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700">
                                        Mostrando <span className="font-medium">{currentStartIndex}</span> a{' '}
                                        <span className="font-medium">{currentEndIndex}</span> de{' '}
                                        <span className="font-medium">{filteredProductos.length}</span> productos
                                    </p>
                                </div>
                                <div>
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <button
                                            onClick={goToPreviousPage}
                                            disabled={currentPage === 1}
                                            className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            <ChevronLeft className="h-5 w-5" />
                                        </button>
                                        {[...Array(totalPages)].map((_, i) => (
                                            <button
                                                key={i + 1}
                                                onClick={() => goToPage(i + 1)}
                                                className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                    currentPage === i + 1
                                                        ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                }`}
                                            >
                                                {i + 1}
                                            </button>
                                        ))}
                                        <button
                                            onClick={goToNextPage}
                                            disabled={currentPage === totalPages}
                                            className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            <ChevronRight className="h-5 w-5" />
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* 🛒 PANEL LATERAL DEL CARRITO */}
                <div className={`fixed right-0 top-0 h-full bg-white border-l border-gray-200 transition-all duration-300 z-40 ${
                    cartCollapsed ? 'w-16' : 'w-80'
                }`}>
                    {/* Header del carrito */}
                    <div className="p-4 border-b border-gray-200">
                        <div className="flex items-center justify-between">
                            {!cartCollapsed && (
                                <h2 className="text-lg font-semibold text-gray-900 flex items-center">
                                    <ShoppingCart className="w-5 h-5 mr-2" />
                                    Carrito ({cartTotals.itemCount})
                                </h2>
                            )}
                            <button
                                onClick={() => setCartCollapsed(!cartCollapsed)}
                                className="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                            >
                                {cartCollapsed ? <ChevronLeft className="w-5 h-5" /> : <ChevronRight className="w-5 h-5" />}
                            </button>
                        </div>
                    </div>

                    {!cartCollapsed && (
                        <>
                            {/* Contenido del carrito */}
                            <div className="flex-1 overflow-y-auto p-4" style={{ maxHeight: 'calc(100vh - 300px)' }}>
                                {cart.length === 0 ? (
                                    <div className="text-center py-8">
                                        <ShoppingCart className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                        <p className="text-gray-500">Carrito vacío</p>
                                        <p className="text-sm text-gray-400">Agregue productos para comenzar</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {cart.map(item => (
                                            <CartItem 
                                                key={item.id} 
                                                item={item} 
                                                onUpdateQuantity={updateCartItemQuantity}
                                                onRemove={removeFromCart}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Totales y acciones */}
                            {cart.length > 0 && (
                                <div className="border-t border-gray-200 p-4 space-y-4">
                                    {/* Totales */}
                                    <div className="space-y-2">
                                        <div className="flex justify-between text-sm">
                                            <span>Subtotal:</span>
                                            <span>{CONFIG.formatCurrency(calculateFinalTotals.subtotal)}</span>
                                        </div>
                                        {calculateFinalTotals.discount > 0 && (
                                            <div className="flex justify-between text-sm text-green-600">
                                                <span>Descuento:</span>
                                                <span>-{CONFIG.formatCurrency(calculateFinalTotals.discount)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between text-lg font-bold border-t pt-2">
                                            <span>Total:</span>
                                            <span>{CONFIG.formatCurrency(calculateFinalTotals.total)}</span>
                                        </div>
                                    </div>

                                    {/* Acciones */}
                                    <div className="space-y-2">
                                        <button
                                            onClick={() => setShowPaymentPanel(true)}
                                            className="w-full payment-button bg-gradient-to-br from-green-600 to-green-700 text-white py-4 px-6 rounded-lg hover:from-green-700 hover:to-green-800 active:from-green-800 active:to-green-900 font-black text-lg transition-all duration-150 transform hover:-translate-y-0.5 hover:shadow-xl border-3 border-green-800 focus:outline-none focus:ring-4 focus:ring-blue-300 min-h-[60px] shadow-lg"
                                            style={{
                                                minWidth: '200px',
                                                minHeight: '60px',
                                                textShadow: '0 1px 2px rgba(0, 0, 0, 0.3)',
                                                letterSpacing: '0.5px',
                                                boxShadow: '0 4px 12px rgba(46, 125, 50, 0.4), 0 2px 4px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2)'
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter' || e.key === 'F9') {
                                                    e.preventDefault();
                                                    setShowPaymentPanel(true);
                                                }
                                            }}
                                        >
                                            💳 PROCESAR PAGO
                                        </button>
                                        <button
                                            onClick={clearCart}
                                            className="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 font-medium transition-colors"
                                        >
                                            Vaciar Carrito
                                        </button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* 🚀 MODAL DE PAGO OPTIMIZADO */}
            <PaymentModalSleepyCashierProof
                isOpen={showPaymentPanel}
                onClose={() => setShowPaymentPanel(false)}
                totalAmount={calculateFinalTotals.total}
                onPaymentComplete={processOptimizedPayment}
                cartItems={cart}
                discountInfo={appliedDiscount}
            />

            {/* Modal de comprobante */}
            {showReceipt && lastSale && (
                <Suspense fallback={<div className="text-center p-4">Cargando ticket...</div>}>
                    <TicketProfesional 
                        venta={lastSale}
                        onClose={() => setShowReceipt(false)}
                        show={showReceipt}
                    />
                </Suspense>
            )}
        </div>
    );
};

// 🛒 COMPONENTE DE ITEM DEL CARRITO
const CartItem = ({ item, onUpdateQuantity, onRemove }) => (
    <div className="bg-gray-50 rounded-lg p-3">
        <div className="flex items-start justify-between mb-2">
            <div className="flex-1 min-w-0">
                <h4 className="text-sm font-medium text-gray-900 truncate">{item.nombre}</h4>
                <p className="text-sm text-gray-500">{CONFIG.formatCurrency(item.price)} c/u</p>
            </div>
            <button
                onClick={() => onRemove(item.id)}
                className="text-red-500 hover:text-red-700 ml-2"
            >
                <Trash2 className="w-4 h-4" />
            </button>
        </div>
        
        <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
                <button
                    onClick={() => onUpdateQuantity(item.id, item.quantity - 1)}
                    className="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
                >
                    <Minus className="w-4 h-4" />
                </button>
                <span className="w-8 text-center font-medium">{item.quantity}</span>
                <button
                    onClick={() => onUpdateQuantity(item.id, item.quantity + 1)}
                    className="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
                >
                    <Plus className="w-4 h-4" />
                </button>
            </div>
            
            <span className="font-medium text-gray-900">
                {CONFIG.formatCurrency(item.price * item.quantity)}
            </span>
        </div>
    </div>
);

export default PuntoDeVentaStockOptimizado;
