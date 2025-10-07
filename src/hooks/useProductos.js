import { useState, useEffect, useCallback, useMemo } from 'react';
import CONFIG from '../config/config';

// ⚡ HOOK OPTIMIZADO: Manejo inteligente de productos con cache y paginación
const useProductos = () => {
  const [productos, setProductos] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [totalCount, setTotalCount] = useState(0);
  
  // Cache simple para evitar requests duplicados
  const [cache, setCache] = useState(new Map());
  
  // ⚡ OPTIMIZACIÓN: Función debounced para búsquedas
  const [searchTimeout, setSearchTimeout] = useState(null);
  
  const fetchProductos = useCallback(async (
    searchTerm = '', 
    category = 'all', 
    page = 1, 
    limit = 50,
    useCache = true
  ) => {
    // Crear key de cache
    const cacheKey = `${searchTerm}_${category}_${page}_${limit}`;
    
    // Verificar cache primero
    if (useCache && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      setProductos(cachedData.productos);
      setTotalCount(cachedData.total);
      return cachedData;
    }
    
    try {
      setLoading(true);
      setError(null);
      
      const params = new URLSearchParams({
        limit: limit.toString(),
        offset: ((page - 1) * limit).toString(),
        orderBy: 'nombre',
        direction: 'ASC'
      });
      
      if (searchTerm.trim()) {
        params.append('buscar', searchTerm.trim());
      }
      
      if (category && category !== 'all') {
        params.append('categoria', category);
      }
      
      const url = `${CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PRODUCTOS)}?${params.toString()}`;
      const response = await fetch(url);
      
      if (!response.ok) throw new Error(`Error: ${response.status}`);
      
      const data = await response.json();
      
      // Normalizar productos
      const productosNormalizados = data.map(producto => ({
        ...producto,
        id: producto.id,
        name: producto.nombre,
        precio_venta: parseFloat(producto.precio_venta) || 0,
        price: parseFloat(producto.precio_venta) || 0,
        stock: parseFloat(producto.stock) || 0,
        categoria: producto.categoria || 'General'
      }));
      
      // Guardar en cache
      const resultData = {
        productos: productosNormalizados,
        total: data.total || productosNormalizados.length
      };
      
      // Mantener cache limitado (máximo 20 entries)
      if (cache.size >= 20) {
        const firstKey = cache.keys().next().value;
        cache.delete(firstKey);
      }
      
      setCache(new Map(cache.set(cacheKey, resultData)));
      setProductos(productosNormalizados);
      setTotalCount(resultData.total);
      
      return resultData;
      
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [cache]);
  
  // ⚡ OPTIMIZACIÓN: Búsqueda con debouncing para evitar requests excesivos
  const searchProductos = useCallback((searchTerm, category = 'all', delay = 300) => {
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    
    const timeout = setTimeout(() => {
      fetchProductos(searchTerm, category, 1, 50);
    }, delay);
    
    setSearchTimeout(timeout);
  }, [fetchProductos, searchTimeout]);
  
  // ⚡ OPTIMIZACIÓN: Limpiar cache cuando sea necesario
  const clearCache = useCallback(() => {
    setCache(new Map());
  }, []);
  
  // ⚡ OPTIMIZACIÓN: Obtener categorías únicas de productos cacheados
  const categorias = useMemo(() => {
    const cats = new Set(['all']);
    productos.forEach(producto => {
      if (producto.categoria) {
        cats.add(producto.categoria);
      }
    });
    return Array.from(cats);
  }, [productos]);
  
  // Cleanup en unmount
  useEffect(() => {
    return () => {
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
    };
  }, [searchTimeout]);
  
  return {
    productos,
    loading,
    error,
    totalCount,
    categorias,
    fetchProductos,
    searchProductos,
    clearCache
  };
};

export default useProductos; 