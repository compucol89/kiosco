// ⚡ UTILIDADES DE PERFORMANCE - OPTIMIZACIÓN AVANZADA

// 🔧 Debouncing para inputs de búsqueda
export const debounce = (func, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(null, args), delay);
  };
};

// 🔧 Throttling para scroll y resize events
export const throttle = (func, limit) => {
  let inThrottle;
  return function() {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

// 🔧 Intersection Observer para lazy loading
export const createIntersectionObserver = (callback, options = {}) => {
  const defaultOptions = {
    root: null,
    rootMargin: '50px',
    threshold: 0.1
  };
  
  return new IntersectionObserver(callback, { ...defaultOptions, ...options });
};

// 🔧 Virtual scrolling helper
export const getVisibleItems = (items, containerHeight, itemHeight, scrollTop) => {
  const visibleStart = Math.floor(scrollTop / itemHeight);
  const visibleEnd = Math.min(
    visibleStart + Math.ceil(containerHeight / itemHeight) + 1,
    items.length
  );
  
  return {
    startIndex: Math.max(0, visibleStart),
    endIndex: visibleEnd,
    visibleItems: items.slice(Math.max(0, visibleStart), visibleEnd)
  };
};

// 🔧 Optimización de imágenes
export const optimizeImageUrl = (url, width = 300, quality = 80) => {
  if (!url) return '/img/no-image.svg';
  
  // Si es una URL local, mantenerla como está
  if (url.startsWith('/') || url.includes('localhost')) {
    return url;
  }
  
  // Para imágenes externas, se podría usar un servicio de optimización
  return url;
};

// 🔧 Cache manager simple
class CacheManager {
  constructor(maxSize = 100) {
    this.cache = new Map();
    this.maxSize = maxSize;
  }
  
  get(key) {
    if (this.cache.has(key)) {
      // Mover al final (LRU)
      const value = this.cache.get(key);
      this.cache.delete(key);
      this.cache.set(key, value);
      return value;
    }
    return null;
  }
  
  set(key, value) {
    if (this.cache.has(key)) {
      this.cache.delete(key);
    } else if (this.cache.size >= this.maxSize) {
      // Eliminar el más antiguo
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }
    this.cache.set(key, value);
  }
  
  clear() {
    this.cache.clear();
  }
  
  size() {
    return this.cache.size;
  }
}

export const apiCache = new CacheManager(50);

// 🔧 Batching de requests
export class RequestBatcher {
  constructor(batchDelay = 100) {
    this.pending = new Map();
    this.batchDelay = batchDelay;
  }
  
  batch(key, request) {
    return new Promise((resolve, reject) => {
      if (this.pending.has(key)) {
        // Agregar a la cola existente
        this.pending.get(key).callbacks.push({ resolve, reject });
      } else {
        // Crear nueva cola
        this.pending.set(key, {
          request,
          callbacks: [{ resolve, reject }]
        });
        
        // Programar ejecución
        setTimeout(() => {
          this.executeBatch(key);
        }, this.batchDelay);
      }
    });
  }
  
  async executeBatch(key) {
    const batch = this.pending.get(key);
    if (!batch) return;
    
    this.pending.delete(key);
    
    try {
      const result = await batch.request();
      batch.callbacks.forEach(({ resolve }) => resolve(result));
    } catch (error) {
      batch.callbacks.forEach(({ reject }) => reject(error));
    }
  }
}

export const requestBatcher = new RequestBatcher();

// 🔧 Performance metrics
export const measurePerformance = (name, fn) => {
  return async (...args) => {
    const start = performance.now();
    try {
      const result = await fn(...args);
      const end = performance.now();
      console.log(`⚡ ${name}: ${end - start}ms`);
      return result;
    } catch (error) {
      const end = performance.now();
      console.error(`❌ ${name} failed after ${end - start}ms:`, error);
      throw error;
    }
  };
};

// 🔧 Memory usage monitor
export const getMemoryUsage = () => {
  if (performance.memory) {
    return {
      used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
      total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
      limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
    };
  }
  return null;
};

// 🔧 Component render optimization
export const shouldComponentUpdate = (prevProps, nextProps, keys = []) => {
  if (keys.length === 0) {
    return JSON.stringify(prevProps) !== JSON.stringify(nextProps);
  }
  
  return keys.some(key => prevProps[key] !== nextProps[key]);
};

// 🔧 Cleanup utilities
export const createCleanupManager = () => {
  const cleanupTasks = [];
  
  return {
    add: (task) => cleanupTasks.push(task),
    cleanup: () => {
      cleanupTasks.forEach(task => {
        try {
          if (typeof task === 'function') task();
        } catch (error) {
          console.warn('Cleanup task failed:', error);
        }
      });
      cleanupTasks.length = 0;
    }
  };
}; 