import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import CONFIG from '../config/config';

/**
 * Hook Enterprise Search - Elasticsearch-Grade Precision
 * Performance Target: <25ms simple search, <40ms complex search
 * Precision Target: >95% relevant results
 * 
 * Features:
 * - Strict AND logic (ALL words required)
 * - Intelligent query parsing and debouncing
 * - Relevance scoring with quality metrics
 * - Real-time performance monitoring
 * - Advanced caching with TTL
 * - Search analytics integration
 */
const useEnterpriseSearch = ({
    minQueryLength = 2,
    debounceMs = 300,
    cacheEnabled = true,
    cacheTTL = 30000, // 30 segundos
    analyticsEnabled = true,
    autoSearch = true,
    initialFilters = {}
} = {}) => {
    
    // ===== ESTADO PRINCIPAL =====
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [error, setError] = useState(null);
    const [lastSearchTime, setLastSearchTime] = useState(null);
    
    // ===== ESTADO DE FILTROS =====
    const [filters, setFilters] = useState({
        categoria: '',
        stock_only: true,
        precio_min: null,
        precio_max: null,
        limit: 20,
        offset: 0,
        ...initialFilters
    });
    
    // ===== ESTADO DE PERFORMANCE =====
    const [performance, setPerformance] = useState({
        lastExecutionTime: 0,
        averageExecutionTime: 0,
        totalSearches: 0,
        slaCompliance: true,
        searchHistory: []
    });
    
    // ===== ESTADO DE CALIDAD =====
    const [quality, setQuality] = useState({
        precisionRate: 0,
        qualityGrade: 'UNKNOWN',
        totalResults: 0,
        highRelevance: 0,
        mediumRelevance: 0,
        lowRelevance: 0
    });
    
    // ===== METADATA DE BÚSQUEDA =====
    const [searchMetadata, setSearchMetadata] = useState({
        originalQuery: '',
        parsedQuery: null,
        conditionsApplied: 0,
        scoringEnabled: false,
        complexity: 'simple'
    });
    
    // ===== PAGINACIÓN =====
    const [pagination, setPagination] = useState({
        total: 0,
        count: 0,
        filteredCount: 0,
        currentPage: 1,
        totalPages: 0,
        hasMore: false
    });
    
    // ===== CACHE Y REFERENCIAS =====
    const [cache, setCache] = useState(new Map());
    const searchTimeoutRef = useRef(null);
    const abortControllerRef = useRef(null);
    const lastSearchQueryRef = useRef('');
    
    // ===== UTILIDADES DE CACHE =====
    const generateCacheKey = useCallback((searchQuery, searchFilters) => {
        const filterString = JSON.stringify(searchFilters);
        return `${searchQuery}-${filterString}`;
    }, []);
    
    const getCachedResult = useCallback((cacheKey) => {
        if (!cacheEnabled) return null;
        
        const cached = cache.get(cacheKey);
        if (!cached) return null;
        
        const isExpired = Date.now() - cached.timestamp > cacheTTL;
        if (isExpired) {
            setCache(prev => {
                const newCache = new Map(prev);
                newCache.delete(cacheKey);
                return newCache;
            });
            return null;
        }
        
        return cached.data;
    }, [cache, cacheEnabled, cacheTTL]);
    
    const setCachedResult = useCallback((cacheKey, data) => {
        if (!cacheEnabled) return;
        
        setCache(prev => {
            const newCache = new Map(prev);
            newCache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });
            
            // Limpiar cache viejo
            const cutoff = Date.now() - cacheTTL;
            for (const [key, value] of newCache.entries()) {
                if (value.timestamp < cutoff) {
                    newCache.delete(key);
                }
            }
            
            return newCache;
        });
    }, [cacheEnabled, cacheTTL]);
    
    // ===== FUNCIÓN DE BÚSQUEDA PRINCIPAL =====
    const performSearch = useCallback(async (searchQuery, searchFilters, options = {}) => {
        const { 
            skipCache = false, 
            updateHistory = true,
            isRetry = false 
        } = options;
        
        // Validar query mínima
        if (!searchQuery || searchQuery.trim().length < minQueryLength) {
            setResults([]);
            setError(null);
            setPagination(prev => ({ ...prev, total: 0, count: 0 }));
            return { success: true, results: [], fromCache: false };
        }
        
        const normalizedQuery = searchQuery.trim();
        const cacheKey = generateCacheKey(normalizedQuery, searchFilters);
        
        // Verificar cache primero
        if (!skipCache) {
            const cachedResult = getCachedResult(cacheKey);
            if (cachedResult) {
                setResults(cachedResult.results);
                setPerformance(prev => ({
                    ...prev,
                    lastExecutionTime: cachedResult.performance.executionTime,
                    slaCompliance: cachedResult.performance.slaCompliance
                }));
                setQuality(cachedResult.quality);
                setSearchMetadata(cachedResult.metadata);
                setPagination(cachedResult.pagination);
                setError(null);
                
                return { 
                    success: true, 
                    results: cachedResult.results, 
                    fromCache: true 
                };
            }
        }
        
        // Cancelar búsqueda anterior si existe
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        
        // Crear nuevo AbortController
        abortControllerRef.current = new AbortController();
        
        setIsSearching(true);
        setError(null);
        
        const searchStart = performance.now();
        
        try {
            // Construir URL de la API enterprise
            const params = new URLSearchParams({
                q: normalizedQuery,
                ...searchFilters
            });
            
            // Limpiar parámetros vacíos
            for (const [key, value] of params.entries()) {
                if (value === '' || value === null || value === undefined) {
                    params.delete(key);
                }
            }
            
            const apiUrl = `${CONFIG.getApiUrl('api/search_enterprise.php')}?${params.toString()}`;
            
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'max-age=30'
                },
                signal: abortControllerRef.current.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error en búsqueda enterprise');
            }
            
            // Extraer datos de la respuesta
            const searchResults = data.data.products || [];
            const performanceData = data.performance || {};
            const qualityData = data.quality || {};
            const metadataData = data.data.search_metadata || {};
            const paginationData = data.data.pagination || {};
            
            // Calcular tiempo total (frontend + backend)
            const clientExecutionTime = performance.now() - searchStart;
            const serverExecutionTime = performanceData.execution_time_ms || 0;
            
            // Actualizar estados
            setResults(searchResults);
            
            // Actualizar performance
            setPerformance(prev => {
                const newTotalSearches = prev.totalSearches + 1;
                const newAverageTime = (
                    (prev.averageExecutionTime * prev.totalSearches + clientExecutionTime) 
                    / newTotalSearches
                );
                
                return {
                    lastExecutionTime: Math.round(clientExecutionTime),
                    serverExecutionTime: Math.round(serverExecutionTime),
                    averageExecutionTime: Math.round(newAverageTime),
                    totalSearches: newTotalSearches,
                    slaCompliance: performanceData.sla_compliance?.compliant || false,
                    searchHistory: [
                        ...prev.searchHistory.slice(-9), // Mantener últimas 10
                        {
                            query: normalizedQuery,
                            time: Math.round(clientExecutionTime),
                            results: searchResults.length,
                            timestamp: Date.now()
                        }
                    ]
                };
            });
            
            // Actualizar calidad
            setQuality({
                precisionRate: qualityData.precision_rate || 0,
                qualityGrade: qualityData.quality_grade || 'UNKNOWN',
                totalResults: qualityData.total_results || 0,
                highRelevance: qualityData.high_relevance || 0,
                mediumRelevance: qualityData.medium_relevance || 0,
                lowRelevance: qualityData.low_relevance || 0
            });
            
            // Actualizar metadata
            setSearchMetadata({
                originalQuery: metadataData.query_original || normalizedQuery,
                parsedQuery: metadataData.query_parsed || null,
                conditionsApplied: metadataData.conditions_applied || 0,
                scoringEnabled: metadataData.scoring_enabled || false,
                complexity: metadataData.query_parsed?.complexity || 'simple'
            });
            
            // Actualizar paginación
            setPagination({
                total: paginationData.total || 0,
                count: paginationData.count || 0,
                filteredCount: paginationData.filtered_count || 0,
                currentPage: Math.floor((searchFilters.offset || 0) / (searchFilters.limit || 20)) + 1,
                totalPages: Math.ceil((paginationData.total || 0) / (searchFilters.limit || 20)),
                hasMore: (searchFilters.offset || 0) + (paginationData.count || 0) < (paginationData.total || 0)
            });
            
            // Guardar en cache
            const cacheData = {
                results: searchResults,
                performance: {
                    executionTime: Math.round(clientExecutionTime),
                    slaCompliance: performanceData.sla_compliance?.compliant || false
                },
                quality: qualityData,
                metadata: metadataData,
                pagination: paginationData
            };
            setCachedResult(cacheKey, cacheData);
            
            setLastSearchTime(new Date());
            
            // Analytics (opcional)
            if (analyticsEnabled && updateHistory) {
                logSearchAnalytics(normalizedQuery, searchResults.length, clientExecutionTime, qualityData);
            }
            
            return { 
                success: true, 
                results: searchResults, 
                fromCache: false,
                performance: performanceData,
                quality: qualityData
            };
            
        } catch (err) {
            // No mostrar error si fue cancelado
            if (err.name === 'AbortError') {
                return { success: false, cancelled: true };
            }
            
            console.error('Enterprise Search Error:', err);
            setError(err.message);
            
            // Intentar usar cache expirado como fallback
            if (!isRetry) {
                const cachedResult = cache.get(cacheKey);
                if (cachedResult) {
                    console.warn('Usando cache expirado como fallback');
                    setResults(cachedResult.data.results);
                    return { 
                        success: true, 
                        results: cachedResult.data.results, 
                        fromCache: true,
                        fallback: true
                    };
                }
            }
            
            setResults([]);
            return { success: false, error: err.message };
            
        } finally {
            setIsSearching(false);
            abortControllerRef.current = null;
        }
    }, [
        minQueryLength, 
        generateCacheKey, 
        getCachedResult, 
        setCachedResult, 
        analyticsEnabled, 
        cache
    ]);
    
    // ===== BÚSQUEDA CON DEBOUNCE =====
    const debouncedSearch = useCallback((searchQuery, searchFilters) => {
        // Limpiar timeout anterior
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        
        // Configurar nuevo timeout
        searchTimeoutRef.current = setTimeout(() => {
            if (searchQuery !== lastSearchQueryRef.current) {
                lastSearchQueryRef.current = searchQuery;
                performSearch(searchQuery, searchFilters);
            }
        }, debounceMs);
    }, [debounceMs, performSearch]);
    
    // ===== FUNCIÓN PÚBLICA DE BÚSQUEDA =====
    const search = useCallback((newQuery, newFilters = {}, options = {}) => {
        const finalFilters = { ...filters, ...newFilters };
        
        if (options.immediate) {
            return performSearch(newQuery, finalFilters, options);
        } else {
            debouncedSearch(newQuery, finalFilters);
            return Promise.resolve({ success: true, debounced: true });
        }
    }, [filters, performSearch, debouncedSearch]);
    
    // ===== ACTUALIZAR QUERY =====
    const updateQuery = useCallback((newQuery) => {
        setQuery(newQuery);
        
        if (autoSearch) {
            if (newQuery.trim().length >= minQueryLength) {
                debouncedSearch(newQuery, filters);
            } else {
                setResults([]);
                setPagination(prev => ({ ...prev, total: 0, count: 0 }));
            }
        }
    }, [autoSearch, minQueryLength, debouncedSearch, filters]);
    
    // ===== ACTUALIZAR FILTROS =====
    const updateFilters = useCallback((newFilters) => {
        setFilters(prev => {
            const updatedFilters = { ...prev, ...newFilters };
            
            // Reset offset si cambiaron filtros importantes
            if ('categoria' in newFilters || 'precio_min' in newFilters || 'precio_max' in newFilters) {
                updatedFilters.offset = 0;
            }
            
            // Realizar búsqueda automática si hay query
            if (autoSearch && query.trim().length >= minQueryLength) {
                debouncedSearch(query, updatedFilters);
            }
            
            return updatedFilters;
        });
    }, [autoSearch, query, minQueryLength, debouncedSearch]);
    
    // ===== FUNCIONES DE PAGINACIÓN =====
    const goToPage = useCallback((page) => {
        const newOffset = (page - 1) * filters.limit;
        updateFilters({ offset: newOffset });
    }, [filters.limit, updateFilters]);
    
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
    
    // ===== FUNCIÓN DE ANALYTICS =====
    const logSearchAnalytics = useCallback((searchQuery, resultCount, executionTime, qualityData) => {
        // Aquí se podría enviar a un servicio de analytics
        // Search Analytics
        console.log({
            query: searchQuery,
            results: resultCount,
            time: executionTime,
            quality: qualityData.quality_grade,
            timestamp: new Date().toISOString()
        });
    }, []);
    
    // ===== FUNCIÓN DE LIMPIEZA =====
    const clearSearch = useCallback(() => {
        setQuery('');
        setResults([]);
        setError(null);
        setPagination(prev => ({ ...prev, total: 0, count: 0 }));
        
        // Cancelar búsqueda en progreso
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        
        // Limpiar timeout
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
    }, []);
    
    // ===== REFRESH CACHE =====
    const refreshCache = useCallback(() => {
        setCache(new Map());
        if (query.trim().length >= minQueryLength) {
            performSearch(query, filters, { skipCache: true });
        }
    }, [query, filters, minQueryLength, performSearch]);
    
    // ===== ESTADÍSTICAS COMPUTADAS =====
    const stats = useMemo(() => ({
        hasResults: results.length > 0,
        isEmpty: results.length === 0 && !isSearching && !error,
        isHighQuality: quality.qualityGrade === 'EXCELLENT',
        isSlowSearch: performance.lastExecutionTime > 50,
        cacheSize: cache.size,
        searchEffectiveness: pagination.total > 0 ? 'effective' : 'needs_improvement'
    }), [results.length, isSearching, error, quality.qualityGrade, performance.lastExecutionTime, cache.size, pagination.total]);
    
    // ===== CLEANUP AL DESMONTAR =====
    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
        };
    }, []);
    
    // ===== RETORNO DEL HOOK =====
    return {
        // Estado de búsqueda
        query,
        results,
        isSearching,
        error,
        lastSearchTime,
        
        // Filtros y configuración
        filters,
        updateFilters,
        
        // Funciones principales
        search,
        updateQuery,
        clearSearch,
        refreshCache,
        
        // Paginación
        pagination,
        goToPage,
        nextPage,
        prevPage,
        
        // Métricas y calidad
        performance,
        quality,
        searchMetadata,
        stats,
        
        // Estado de configuración
        minQueryLength,
        autoSearch,
        cacheEnabled,
        
        // Utilidades
        isReady: !isSearching && !error,
        hasQuery: query.trim().length >= minQueryLength,
        canSearch: query.trim().length >= minQueryLength && !isSearching
    };
};

export default useEnterpriseSearch;