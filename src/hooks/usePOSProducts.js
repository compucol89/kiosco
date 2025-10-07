import { useState, useEffect, useCallback, useMemo } from 'react';
import CONFIG from '../config/config';

/**
 * Hook optimizado para productos POS Enterprise
 * Performance Target: <50ms API calls + UI responsiveness
 * Features: Stock filtering, caching, error recovery
 * 
 * @param {Object} options - Configuración del hook
 * @param {boolean} options.adminMode - Mostrar productos sin stock (modo admin)
 * @param {boolean} options.stockOnly - Filtrar solo productos con stock (default: true)
 * @param {string} options.initialCategory - Categoría inicial
 * @param {string} options.initialSearch - Búsqueda inicial
 * @param {number} options.pageSize - Productos por página (default: 20)
 */
const usePOSProducts = ({
    adminMode = false,
    stockOnly = true,
    initialCategory = 'all',
    initialSearch = '',
    pageSize = 20
} = {}) => {
    
    // ===== ESTADO PRINCIPAL =====
    const [products, setProducts] = useState([]);
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [lastFetch, setLastFetch] = useState(null);
    
    // ===== ESTADO DE FILTROS =====
    const [filters, setFilters] = useState({
        search: initialSearch,
        category: initialCategory,
        stockOnly: !adminMode && stockOnly
    });
    
    // ===== ESTADO DE PAGINACIÓN =====
    const [pagination, setPagination] = useState({
        currentPage: 1,
        totalPages: 0,
        totalItems: 0,
        itemsPerPage: pageSize,
        hasMore: false
    });
    
    // ===== ESTADO DE PERFORMANCE =====
    const [performance, setPerformance] = useState({
        lastExecutionTime: 0,
        averageExecutionTime: 0,
        totalRequests: 0,
        slaCompliance: true
    });
    
    // ===== CACHE LOCAL =====
    const [cache, setCache] = useState(new Map());
    const CACHE_TTL = 60000; // 1 minuto
    
    // ===== FUNCIÓN DE FETCH OPTIMIZADA =====
    const fetchProducts = useCallback(async (searchTerm = '', category = 'all', page = 1, useCache = true) => {
        const startTime = performance.now();
        
        try {
            // Generar clave de cache
            const cacheKey = `${searchTerm}-${category}-${page}-${filters.stockOnly}-${adminMode}`;
            
            // Verificar cache
            if (useCache && cache.has(cacheKey)) {
                const cachedData = cache.get(cacheKey);
                if (Date.now() - cachedData.timestamp < CACHE_TTL) {
                    setProducts(cachedData.products);
                    setCategories(cachedData.categories);
                    setPagination(cachedData.pagination);
                    setLoading(false);
                    
                    const executionTime = performance.now() - startTime;
                    updatePerformanceMetrics(executionTime, true);
                    return;
                }
            }
            
            setLoading(true);
            setError(null);
            
            // Construir parámetros de la API v2
            const params = new URLSearchParams({
                admin: adminMode.toString(),
                stock_only: filters.stockOnly.toString(),
                limit: pagination.itemsPerPage.toString(),
                offset: ((page - 1) * pagination.itemsPerPage).toString()
            });
            
            if (searchTerm.trim()) {
                params.append('buscar', searchTerm.trim());
            }
            
            if (category && category !== 'all') {
                params.append('categoria', category);
            }
            
            // Llamada a API v2 optimizada
            const url = `${CONFIG.getApiUrl('api/productos_pos_v2.php')}?${params.toString()}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'max-age=60'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar productos');
            }
            
            // Normalizar datos para compatibilidad
            const normalizedProducts = data.data.productos.map(producto => ({
                ...producto,
                id: producto.id,
                name: producto.nombre,
                precio_venta: parseFloat(producto.precio_venta) || 0,
                price: parseFloat(producto.precio_venta) || 0,
                stock: parseFloat(producto.stock) || 0,
                categoria: producto.categoria || 'General',
                estado_stock: producto.estado_stock || 'disponible'
            }));
            
            // Actualizar estado
            setProducts(normalizedProducts);
            setCategories(data.data.categorias_disponibles || []);
            setPagination({
                currentPage: page,
                totalPages: Math.ceil(data.data.pagination.total / pagination.itemsPerPage),
                totalItems: data.data.pagination.total,
                itemsPerPage: pagination.itemsPerPage,
                hasMore: data.data.pagination.has_more
            });
            
            // Guardar en cache
            const cacheData = {
                products: normalizedProducts,
                categories: data.data.categorias_disponibles || [],
                pagination: {
                    currentPage: page,
                    totalPages: Math.ceil(data.data.pagination.total / pagination.itemsPerPage),
                    totalItems: data.data.pagination.total,
                    itemsPerPage: pagination.itemsPerPage,
                    hasMore: data.data.pagination.has_more
                },
                timestamp: Date.now()
            };
            
            setCache(prev => new Map(prev.set(cacheKey, cacheData)));
            
            // Limpiar cache antiguo
            setCache(prev => {
                const now = Date.now();
                const cleanedCache = new Map();
                for (const [key, value] of prev.entries()) {
                    if (now - value.timestamp < CACHE_TTL) {
                        cleanedCache.set(key, value);
                    }
                }
                return cleanedCache;
            });
            
            setLastFetch(new Date());
            
            // Métricas de performance
            const executionTime = performance.now() - startTime;
            updatePerformanceMetrics(executionTime, false, data.performance?.execution_time_ms);
            
        } catch (err) {
            console.error('Error fetching products:', err);
            setError(err.message);
            
            // Fallback: intentar cargar desde cache aunque esté expirado
            const cacheKey = `${searchTerm}-${category}-${page}-${filters.stockOnly}-${adminMode}`;
            if (cache.has(cacheKey)) {
                const cachedData = cache.get(cacheKey);
                setProducts(cachedData.products);
                setCategories(cachedData.categories);
                setPagination(cachedData.pagination);
                console.warn('Usando datos en cache debido a error de red');
            }
            
            const executionTime = performance.now() - startTime;
            updatePerformanceMetrics(executionTime, false);
        } finally {
            setLoading(false);
        }
    }, [adminMode, filters.stockOnly, pagination.itemsPerPage, cache]);
    
    // ===== ACTUALIZAR MÉTRICAS DE PERFORMANCE =====
    const updatePerformanceMetrics = useCallback((executionTime, fromCache, serverTime = null) => {
        setPerformance(prev => {
            const newTotalRequests = prev.totalRequests + 1;
            const newAverageTime = (prev.averageExecutionTime * prev.totalRequests + executionTime) / newTotalRequests;
            const slaTime = serverTime || executionTime;
            
            return {
                lastExecutionTime: Math.round(executionTime),
                averageExecutionTime: Math.round(newAverageTime),
                totalRequests: newTotalRequests,
                slaCompliance: slaTime < 50,
                fromCache,
                serverExecutionTime: serverTime
            };
        });
    }, []);
    
    // ===== FUNCIONES DE FILTROS =====
    const updateSearch = useCallback((searchTerm) => {
        setFilters(prev => ({ ...prev, search: searchTerm }));
        setPagination(prev => ({ ...prev, currentPage: 1 }));
        fetchProducts(searchTerm, filters.category, 1, false); // No usar cache para búsquedas
    }, [filters.category, fetchProducts]);
    
    const updateCategory = useCallback((category) => {
        setFilters(prev => ({ ...prev, category }));
        setPagination(prev => ({ ...prev, currentPage: 1 }));
        fetchProducts(filters.search, category, 1);
    }, [filters.search, fetchProducts]);
    
    const toggleStockFilter = useCallback(() => {
        if (!adminMode) {
            setFilters(prev => ({ ...prev, stockOnly: !prev.stockOnly }));
            setPagination(prev => ({ ...prev, currentPage: 1 }));
            fetchProducts(filters.search, filters.category, 1, false);
        }
    }, [adminMode, filters.search, filters.category, fetchProducts]);
    
    // ===== FUNCIONES DE PAGINACIÓN =====
    const goToPage = useCallback((page) => {
        if (page >= 1 && page <= pagination.totalPages) {
            fetchProducts(filters.search, filters.category, page);
        }
    }, [filters.search, filters.category, pagination.totalPages, fetchProducts]);
    
    const nextPage = useCallback(() => {
        if (pagination.hasMore) {
            goToPage(pagination.currentPage + 1);
        }
    }, [pagination.hasMore, pagination.currentPage, goToPage]);
    
    const prevPage = useCallback(() => {
        if (pagination.currentPage > 1) {
            goToPage(pagination.currentPage - 1);
        }
    }, [pagination.currentPage, goToPage]);
    
    // ===== FUNCIÓN DE REFRESH =====
    const refresh = useCallback(() => {
        setCache(new Map()); // Limpiar cache
        fetchProducts(filters.search, filters.category, pagination.currentPage, false);
    }, [filters.search, filters.category, pagination.currentPage, fetchProducts]);
    
    // ===== PRODUCTOS FILTRADOS LOCALMENTE =====
    const displayedProducts = useMemo(() => {
        return products; // Ya filtrados por la API v2
    }, [products]);
    
    // ===== ESTADÍSTICAS DE STOCK =====
    const stockStats = useMemo(() => {
        const total = products.length;
        const conStock = products.filter(p => p.stock > 0).length;
        const sinStock = products.filter(p => p.stock <= 0).length;
        const bajoStock = products.filter(p => p.stock > 0 && p.stock <= (p.stock_minimo || 10)).length;
        
        return {
            total,
            conStock,
            sinStock,
            bajoStock,
            porcentajeDisponible: total > 0 ? Math.round((conStock / total) * 100) : 0
        };
    }, [products]);
    
    // ===== CARGAR PRODUCTOS INICIAL =====
    useEffect(() => {
        fetchProducts(filters.search, filters.category, 1);
    }, []); // Solo al montar el componente
    
    // ===== RETORNO DEL HOOK =====
    return {
        // Datos principales
        products: displayedProducts,
        categories,
        loading,
        error,
        lastFetch,
        
        // Estado de filtros
        filters,
        updateSearch,
        updateCategory,
        toggleStockFilter,
        
        // Paginación
        pagination,
        goToPage,
        nextPage,
        prevPage,
        
        // Estadísticas
        stockStats,
        performance,
        
        // Acciones
        refresh,
        
        // Estado de configuración
        adminMode,
        
        // Utilidades
        isProductAvailable: useCallback((product) => {
            return adminMode || product.stock > 0;
        }, [adminMode])
    };
};

export default usePOSProducts;