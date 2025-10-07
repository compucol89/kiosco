import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import useEnterpriseSearch from './useEnterpriseSearch';
import usePOSProducts from './usePOSProducts';
import CONFIG from '../config/config';

/**
 * Hook Híbrido POS + Enterprise Search
 * Performance Target: <25ms search, >95% precision
 * Combina: usePOSProducts (cache local) + useEnterpriseSearch (precision)
 * 
 * Strategy:
 * - Cache local para respuesta inmediata
 * - Enterprise search para refinamiento
 * - Fallback automático en fallos
 * - Analytics integrado
 */
const useHybridPOSSearch = ({
    adminMode = false,
    stockOnly = true,
    pageSize = 20,
    enableAnalytics = true,
    performanceTarget = 25 // ms
} = {}) => {
    
    // ===== HOOKS BASE =====
    const posHook = usePOSProducts({
        adminMode,
        stockOnly,
        pageSize,
        initialSearch: '',
        initialCategory: 'all'
    });
    
    const enterpriseHook = useEnterpriseSearch({
        minQueryLength: 2,
        debounceMs: 200, // Más agresivo para POS
        cacheEnabled: true,
        cacheTTL: 30000,
        analyticsEnabled: enableAnalytics,
        autoSearch: false, // Control manual
        initialFilters: {
            stock_only: stockOnly,
            limit: pageSize
        }
    });
    
    // ===== ESTADO HÍBRIDO =====
    const [searchMode, setSearchMode] = useState('local'); // 'local' | 'enterprise' | 'hybrid'
    const [hybridResults, setHybridResults] = useState([]);
    const [isHybridSearching, setIsHybridSearching] = useState(false);
    const [performanceMetrics, setPerformanceMetrics] = useState({
        localTime: 0,
        enterpriseTime: 0,
        totalTime: 0,
        precision: 0,
        slaCompliance: true,
        searchCount: 0,
        hybridEffectiveness: 0
    });
    
    // ===== REFERENCIAS =====
    const searchTimeoutRef = useRef(null);
    const performanceStartRef = useRef(null);
    const lastQueryRef = useRef('');
    
    // ===== ESTRATEGIA DE BÚSQUEDA HÍBRIDA =====
    const performHybridSearch = useCallback(async (query, filters = {}) => {
        if (!query || query.trim().length < 2) {
            setHybridResults([]);
            return;
        }
        
        const normalizedQuery = query.trim();
        if (normalizedQuery === lastQueryRef.current) return;
        
        lastQueryRef.current = normalizedQuery;
        setIsHybridSearching(true);
        performanceStartRef.current = performance.now();
        
        try {
            // FASE 1: Búsqueda local inmediata (cache)
            const localStart = performance.now();
            posHook.updateSearch(normalizedQuery);
            const localTime = performance.now() - localStart;
            
            // FASE 2: Búsqueda enterprise en paralelo para refinamiento
            const enterpriseStart = performance.now();
            const enterpriseResult = await enterpriseHook.search(normalizedQuery, filters, { immediate: true });
            const enterpriseTime = performance.now() - enterpriseStart;
            
            // COMBINACIÓN INTELIGENTE DE RESULTADOS
            let combinedResults = [];
            
            if (enterpriseResult.success && enterpriseResult.results) {
                // Usar resultados enterprise como base (mayor precisión)
                const enterpriseIds = new Set(enterpriseResult.results.map(p => p.id));
                
                // Combinar con productos locales para completar
                const localProducts = posHook.products.filter(p => 
                    !enterpriseIds.has(p.id) && 
                    matchesLocalQuery(p, normalizedQuery)
                );
                
                combinedResults = [
                    ...enterpriseResult.results,
                    ...localProducts.slice(0, Math.max(0, pageSize - enterpriseResult.results.length))
                ];
            } else {
                // Fallback a resultados locales
                combinedResults = posHook.products;
                console.warn('Fallback a búsqueda local - Enterprise search failed');
            }
            
            setHybridResults(combinedResults);
            
            // CALCULAR MÉTRICAS
            const totalTime = performance.now() - performanceStartRef.current;
            const precision = calculatePrecision(combinedResults, normalizedQuery);
            
            setPerformanceMetrics(prev => ({
                localTime: Math.round(localTime),
                enterpriseTime: Math.round(enterpriseTime),
                totalTime: Math.round(totalTime),
                precision: Math.round(precision * 100),
                slaCompliance: totalTime < performanceTarget,
                searchCount: prev.searchCount + 1,
                hybridEffectiveness: Math.round(
                    (prev.hybridEffectiveness * prev.searchCount + precision) / (prev.searchCount + 1) * 100
                )
            }));
            
            // ANALYTICS
            if (enableAnalytics) {
                logHybridSearchAnalytics({
                    query: normalizedQuery,
                    resultsCount: combinedResults.length,
                    localTime,
                    enterpriseTime,
                    totalTime,
                    precision,
                    mode: enterpriseResult.success ? 'hybrid' : 'local-fallback'
                });
            }
            
        } catch (error) {
            console.error('Hybrid search error:', error);
            // Fallback completo a local
            setHybridResults(posHook.products);
        } finally {
            setIsHybridSearching(false);
        }
    }, [posHook, enterpriseHook, pageSize, performanceTarget, enableAnalytics]);
    
    // ===== BÚSQUEDA CON DEBOUNCE =====
    const debouncedHybridSearch = useCallback((query, filters) => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        
        searchTimeoutRef.current = setTimeout(() => {
            performHybridSearch(query, filters);
        }, 150); // Debounce agresivo para POS
    }, [performHybridSearch]);
    
    // ===== FUNCIÓN PÚBLICA DE BÚSQUEDA =====
    const updateSearch = useCallback((query) => {
        if (query.trim().length >= 2) {
            debouncedHybridSearch(query, { stock_only: stockOnly });
        } else {
            setHybridResults([]);
            lastQueryRef.current = '';
        }
    }, [debouncedHybridSearch, stockOnly]);
    
    // ===== UTILIDADES =====
    const matchesLocalQuery = useCallback((product, query) => {
        const searchTerms = query.toLowerCase().split(/\s+/);
        const productText = `${product.nombre || product.name || ''} ${product.categoria || ''} ${product.codigo || product.barcode || ''}`.toLowerCase();
        
        return searchTerms.some(term => productText.includes(term));
    }, []);
    
    const calculatePrecision = useCallback((results, query) => {
        if (results.length === 0) return 0;
        
        const relevantResults = results.filter(product => {
            const relevanceScore = calculateRelevanceScore(product, query);
            return relevanceScore > 0.3; // Threshold de relevancia
        });
        
        return relevantResults.length / results.length;
    }, []);
    
    const calculateRelevanceScore = useCallback((product, query) => {
        const queryLower = query.toLowerCase();
        const name = (product.nombre || product.name || '').toLowerCase();
        const category = (product.categoria || '').toLowerCase();
        const barcode = (product.codigo || product.barcode || '').toLowerCase();
        
        let score = 0;
        
        // Coincidencia exacta en nombre (máxima relevancia)
        if (name.includes(queryLower)) score += 1.0;
        
        // Coincidencia en código de barras
        if (barcode.includes(queryLower)) score += 0.9;
        
        // Coincidencia en categoría
        if (category.includes(queryLower)) score += 0.5;
        
        // Palabras en común
        const queryWords = queryLower.split(/\s+/);
        const nameWords = name.split(/\s+/);
        const commonWords = queryWords.filter(word => nameWords.some(nameWord => nameWord.includes(word)));
        score += (commonWords.length / queryWords.length) * 0.8;
        
        return Math.min(score, 1.0);
    }, []);
    
    const logHybridSearchAnalytics = useCallback((data) => {
        // Hybrid Search Analytics
        console.log({
            timestamp: new Date().toISOString(),
            ...data
        });
        
        // Aquí se podría enviar a un servicio de analytics real
        if (window.gtag) {
            window.gtag('event', 'hybrid_search', {
                search_term: data.query,
                results_count: data.resultsCount,
                performance_ms: data.totalTime,
                precision_rate: data.precision
            });
        }
    }, []);
    
    // ===== CLEANUP =====
    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);
    
    // ===== MÉTRICAS COMPUTADAS =====
    const qualityMetrics = useMemo(() => ({
        performanceGrade: performanceMetrics.slaCompliance ? 'EXCELLENT' : 
                         performanceMetrics.totalTime < 50 ? 'GOOD' : 'NEEDS_IMPROVEMENT',
        precisionGrade: performanceMetrics.precision >= 95 ? 'EXCELLENT' :
                       performanceMetrics.precision >= 85 ? 'GOOD' : 'NEEDS_IMPROVEMENT',
        overallScore: Math.round((
            (performanceMetrics.slaCompliance ? 100 : Math.max(0, 100 - performanceMetrics.totalTime)) * 0.4 +
            performanceMetrics.precision * 0.6
        )),
        searchEfficiency: performanceMetrics.searchCount > 0 ? 
                         Math.round(performanceMetrics.hybridEffectiveness) : 0
    }), [performanceMetrics]);
    
    // ===== RETORNO DEL HOOK =====
    return {
        // Resultados híbridos
        products: hybridResults,
        isSearching: isHybridSearching || posHook.loading,
        error: posHook.error || enterpriseHook.error,
        
        // Funciones de búsqueda
        updateSearch,
        clearSearch: () => {
            setHybridResults([]);
            lastQueryRef.current = '';
            posHook.updateSearch('');
            enterpriseHook.clearSearch();
        },
        
        // Métricas de performance
        performance: performanceMetrics,
        quality: qualityMetrics,
        
        // Datos de contexto
        categories: posHook.categories,
        stockStats: posHook.stockStats,
        
        // Funciones delegadas
        updateCategory: posHook.updateCategory,
        toggleStockFilter: posHook.toggleStockFilter,
        refresh: () => {
            posHook.refresh();
            enterpriseHook.refreshCache();
        },
        
        // Paginación (usa la del hook base)
        pagination: posHook.pagination,
        goToPage: posHook.goToPage,
        nextPage: posHook.nextPage,
        prevPage: posHook.prevPage,
        
        // Estado de configuración
        adminMode,
        stockOnly,
        searchMode,
        
        // Utilidades
        isProductAvailable: posHook.isProductAvailable,
        hasResults: hybridResults.length > 0,
        isHighPerformance: performanceMetrics.slaCompliance,
        isHighPrecision: performanceMetrics.precision >= 95
    };
};

export default useHybridPOSSearch;