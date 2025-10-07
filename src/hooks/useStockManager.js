import { useState, useEffect, useCallback, useRef } from 'react';
import CONFIG from '../config/config';

/**
 * 🎯 HOOK PERSONALIZADO PARA GESTIÓN INTELIGENTE DE STOCK
 * 
 * Hook que maneja:
 * - Carga optimizada de productos con filtros de stock
 * - Sincronización en tiempo real
 * - Cache inteligente
 * - Alertas de stock automáticas
 */
export const useStockManager = (config = {}) => {
    // Configuración por defecto
    const defaultConfig = {
        incluirSinStock: false,        // Por defecto no mostrar productos sin stock
        soloStockBajo: false,          // Solo productos con stock bajo
        autoRefresh: true,             // Refrescar automáticamente
        refreshInterval: 30000,        // 30 segundos
        cacheTimeout: 60000,           // 1 minuto de cache
        stockMinimoDefault: 3,         // Stock bajo <= 3 unidades
        ...config
    };
    
    // Estados principales
    const [productos, setProductos] = useState([]);
    const [estadisticas, setEstadisticas] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [lastUpdate, setLastUpdate] = useState(null);
    
    // Estados de filtros
    const [filtros, setFiltros] = useState({
        incluirSinStock: defaultConfig.incluirSinStock,
        soloStockBajo: defaultConfig.soloStockBajo,
        busqueda: '',
        categoria: '',
        limite: 100,
        offset: 0
    });
    
    // Referencias para control
    const cacheRef = useRef(new Map());
    const intervalRef = useRef(null);
    const abortControllerRef = useRef(null);
    
    /**
     * 🔄 CARGAR PRODUCTOS CON FILTROS DE STOCK
     */
    const cargarProductos = useCallback(async (forzarRecarga = false) => {
        try {
            // Cancelar request anterior si existe
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
            
            abortControllerRef.current = new AbortController();
            
            setError(null);
            if (forzarRecarga) {
                setIsLoading(true);
            }
            
            // Generar clave de cache
            const cacheKey = generarClaveCache(filtros);
            
            // Verificar cache si no es recarga forzada
            if (!forzarRecarga && cacheRef.current.has(cacheKey)) {
                const cached = cacheRef.current.get(cacheKey);
                const tiempoTranscurrido = Date.now() - cached.timestamp;
                
                if (tiempoTranscurrido < defaultConfig.cacheTimeout) {
                    setProductos(cached.productos);
                    setEstadisticas(cached.estadisticas);
                    setLastUpdate(new Date(cached.timestamp));
                    setIsLoading(false);
                    return cached;
                }
            }
            
            // Construir URL con parámetros
            const params = new URLSearchParams({
                accion: 'obtener_productos',
                incluir_sin_stock: filtros.incluirSinStock.toString(),
                solo_stock_bajo: filtros.soloStockBajo.toString(),
                search: filtros.busqueda,
                categoria: filtros.categoria,
                limite: filtros.limite.toString(),
                offset: filtros.offset.toString()
            });
            
            const url = CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PRODUCTOS_POS_OPTIMIZADO) + `?${params}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                signal: abortControllerRef.current.signal
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error al cargar productos');
            }
            
            // Procesar productos
            const productosEnriquecidos = procesarProductos(data.data);
            
            // Actualizar estado
            setProductos(productosEnriquecidos);
            setEstadisticas(data.estadisticas);
            setLastUpdate(new Date());
            setIsLoading(false);
            
            // Guardar en cache
            cacheRef.current.set(cacheKey, {
                productos: productosEnriquecidos,
                estadisticas: data.estadisticas,
                timestamp: Date.now()
            });
            
            // Limpiar cache viejo
            limpiarCacheViejo(cacheRef);
            
            return data;
            
        } catch (error) {
            if (error.name === 'AbortError') {
                return; // Request cancelado, no es error
            }
            
            console.error('Error cargando productos:', error);
            setError(error.message);
            setIsLoading(false);
            throw error;
        }
    }, [filtros]);
    
    /**
     * 🔍 VERIFICAR STOCK EN TIEMPO REAL
     */
    const verificarStockTiempoReal = useCallback(async (productosIds) => {
        try {
            if (!productosIds || productosIds.length === 0) {
                return {};
            }
            
            const params = new URLSearchParams({
                accion: 'verificar_stock',
                productos_ids: productosIds.join(',')
            });
            
            const url = CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PRODUCTOS_POS_OPTIMIZADO) + `?${params}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                // Actualizar productos en estado local
                setProductos(prevProductos => {
                    return prevProductos.map(producto => {
                        const stockActualizado = data.stocks.find(s => s.id === producto.id);
                        if (stockActualizado) {
                            return {
                                ...producto,
                                stock: stockActualizado.stock,
                                stock_info: {
                                    ...producto.stock_info,
                                    cantidad: stockActualizado.stock,
                                    estado: stockActualizado.estado_stock,
                                    puede_vender: stockActualizado.stock > 0
                                },
                                alertas_visuales: generarAlertasVisuales(stockActualizado.stock, producto.stock_minimo || defaultConfig.stockMinimoDefault)
                            };
                        }
                        return producto;
                    });
                });
                
                return data.stocks.reduce((acc, stock) => {
                    acc[stock.id] = stock;
                    return acc;
                }, {});
            }
            
            return {};
            
        } catch (error) {
            console.error('Error verificando stock:', error);
            return {};
        }
    }, []);
    
    /**
     * 🎯 ACTUALIZAR FILTROS
     */
    const actualizarFiltros = useCallback((nuevosFiltros) => {
        setFiltros(prevFiltros => ({
            ...prevFiltros,
            ...nuevosFiltros,
            offset: 0 // Resetear paginación cuando cambien filtros
        }));
    }, []);
    
    /**
     * 🔄 TOGGLE INCLUIR SIN STOCK
     */
    const toggleIncluirSinStock = useCallback(() => {
        actualizarFiltros({ incluirSinStock: !filtros.incluirSinStock });
    }, [filtros.incluirSinStock, actualizarFiltros]);
    
    /**
     * 🚨 TOGGLE SOLO STOCK BAJO
     */
    const toggleSoloStockBajo = useCallback(() => {
        actualizarFiltros({ soloStockBajo: !filtros.soloStockBajo });
    }, [filtros.soloStockBajo, actualizarFiltros]);
    
    /**
     * 🔍 BUSCAR PRODUCTOS
     */
    const buscarProductos = useCallback((termino) => {
        actualizarFiltros({ busqueda: termino });
    }, [actualizarFiltros]);
    
    /**
     * 📂 FILTRAR POR CATEGORÍA
     */
    const filtrarPorCategoria = useCallback((categoria) => {
        actualizarFiltros({ categoria });
    }, [actualizarFiltros]);
    
    /**
     * 🔄 CONFIGURAR AUTO-REFRESH
     */
    useEffect(() => {
        if (defaultConfig.autoRefresh && !intervalRef.current) {
            intervalRef.current = setInterval(() => {
                cargarProductos(false); // Recarga suave sin loading
            }, defaultConfig.refreshInterval);
        }
        
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };
    }, [cargarProductos, defaultConfig.autoRefresh, defaultConfig.refreshInterval]);
    
    /**
     * 🎬 EFECTO INICIAL Y CAMBIOS DE FILTROS
     */
    useEffect(() => {
        cargarProductos(true);
    }, [cargarProductos]);
    
    /**
     * 🧹 CLEANUP
     */
    useEffect(() => {
        return () => {
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, []);
    
    /**
     * 📊 OBTENER ESTADÍSTICAS COMPUTADAS
     */
    const estadisticasComputadas = {
        ...estadisticas,
        productosVisibles: productos.length,
        productosSinStock: productos.filter(p => p.stock <= 0).length,
        productosStockBajo: productos.filter(p => p.stock > 0 && p.stock <= (p.stock_minimo || defaultConfig.stockMinimoDefault)).length,
        productosStockNormal: productos.filter(p => p.stock > (p.stock_minimo || defaultConfig.stockMinimoDefault)).length
    };
    
    return {
        // Estados principales
        productos,
        estadisticas: estadisticasComputadas,
        isLoading,
        error,
        lastUpdate,
        
        // Estados de filtros
        filtros,
        
        // Acciones
        cargarProductos,
        verificarStockTiempoReal,
        actualizarFiltros,
        toggleIncluirSinStock,
        toggleSoloStockBajo,
        buscarProductos,
        filtrarPorCategoria,
        
        // Utilidades
        refrescar: () => cargarProductos(true),
        limpiarCache: () => cacheRef.current.clear()
    };
};

// ============================================================
// 🔧 FUNCIONES AUXILIARES
// ============================================================

/**
 * Generar clave de cache basada en filtros
 */
function generarClaveCache(filtros) {
    return `productos_${JSON.stringify(filtros)}`;
}

/**
 * Limpiar entradas de cache viejas
 */
function limpiarCacheViejo(cacheRef) {
    const tiempoLimite = Date.now() - 300000; // 5 minutos
    
    for (const [key, value] of cacheRef.current.entries()) {
        if (value.timestamp < tiempoLimite) {
            cacheRef.current.delete(key);
        }
    }
}

/**
 * Procesar productos para enriquecer datos
 */
function procesarProductos(productos) {
    return productos.map(producto => ({
        ...producto,
        // Asegurar que tenga la estructura necesaria para compatibilidad
        stock_info: producto.stock_info || {
            cantidad: parseInt(producto.stock) || 0,
            puede_vender: (parseInt(producto.stock) || 0) > 0,
            stock_minimo: parseInt(producto.stock_minimo) || 3,
            estado: determinarEstadoStock(producto.stock, producto.stock_minimo || 3)
        },
        alertas_visuales: producto.alertas_visuales || generarAlertasVisuales(
            producto.stock, 
            producto.stock_minimo || 3
        )
    }));
}

/**
 * Determinar estado de stock
 */
function determinarEstadoStock(stock, stockMinimo) {
    stock = parseInt(stock) || 0;
    stockMinimo = parseInt(stockMinimo) || 3;
    
    if (stock <= 0) return 'sin_stock';
    if (stock <= stockMinimo) return 'stock_bajo';
    return 'stock_normal';
}

/**
 * Generar alertas visuales para productos
 */
function generarAlertasVisuales(stock, stockMinimo) {
    stock = parseInt(stock) || 0;
    stockMinimo = parseInt(stockMinimo) || 3;
    
    if (stock <= 0) {
        return {
            mostrar_badge: true,
            tipo_badge: 'sin_stock',
            mensaje: 'Sin Stock',
            color_badge: 'bg-red-500 text-white',
            css_classes: ['border-red-300', 'bg-red-50', 'opacity-75']
        };
    }
    
    if (stock <= stockMinimo) {
        return {
            mostrar_badge: true,
            tipo_badge: 'stock_bajo',
            mensaje: `Stock Bajo (${stock})`,
            color_badge: 'bg-yellow-500 text-white',
            css_classes: ['border-yellow-300', 'bg-yellow-50']
        };
    }
    
    if (stock <= stockMinimo * 1.5) {
        return {
            mostrar_badge: true,
            tipo_badge: 'stock_critico',
            mensaje: `¡Últimas ${stock}!`,
            color_badge: 'bg-orange-500 text-white',
            css_classes: ['border-orange-300']
        };
    }
    
    return { mostrar_badge: false };
}

export default useStockManager;
