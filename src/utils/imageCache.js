// src/utils/imageCache.js
// Sistema de cache optimizado para imágenes de productos
// Reduce llamadas al servidor y mejora rendimiento
// RELEVANT FILES: ProductImage.jsx, ProductCard.jsx

class ImageCache {
  constructor() {
    this.cache = new Map();
    this.maxSize = 100; // Máximo 100 imágenes en cache
    this.formatosImagen = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  }

  // ⚡ OPTIMIZACIÓN: Generar clave única para cache
  getCacheKey(codigo) {
    return `img_${codigo}`;
  }

  // ⚡ OPTIMIZACIÓN: Obtener imagen del cache
  get(codigo) {
    const key = this.getCacheKey(codigo);
    const cached = this.cache.get(key);
    
    if (cached) {
      // Mover al final para LRU
      this.cache.delete(key);
      this.cache.set(key, cached);
      return cached;
    }
    
    return null;
  }

  // ⚡ OPTIMIZACIÓN: Guardar imagen en cache
  set(codigo, imageData) {
    const key = this.getCacheKey(codigo);
    
    // Aplicar LRU - eliminar el más antiguo si llegamos al límite
    if (this.cache.size >= this.maxSize) {
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }
    
    this.cache.set(key, {
      url: imageData.url,
      estado: imageData.estado,
      timestamp: Date.now()
    });
  }

  // ⚡ OPTIMIZACIÓN: Precargar imagen con soporte multi-formato
  async preloadImage(codigo, baseUrl) {
    const cached = this.get(codigo);
    if (cached && cached.estado === 'cargada') {
      return cached;
    }

    for (const formato of this.formatosImagen) {
      try {
        const img = new Image();
        const imageUrl = `${baseUrl}/img/productos/${codigo}.${formato}?t=${Date.now()}`;
        
        await new Promise((resolve, reject) => {
          img.onload = () => {
            const imageData = {
              url: imageUrl,
              estado: 'cargada',
              formato
            };
            
            this.set(codigo, imageData);
            resolve(imageData);
          };
          img.onerror = reject;
          img.src = imageUrl;
        });
        
        return this.get(codigo);
      } catch (error) {
        continue;
      }
    }
    
    // Si no se encontró ninguna imagen, marcar como error
    const errorData = {
      url: null,
      estado: 'error'
    };
    this.set(codigo, errorData);
    return errorData;
  }

  // ⚡ OPTIMIZACIÓN: Limpiar cache
  clear() {
    this.cache.clear();
  }

  // ⚡ OPTIMIZACIÓN: Obtener estadísticas del cache
  getStats() {
    return {
      size: this.cache.size,
      maxSize: this.maxSize,
      keys: Array.from(this.cache.keys())
    };
  }

  // ⚡ OPTIMIZACIÓN: Invalidar imagen específica
  invalidate(codigo) {
    const key = this.getCacheKey(codigo);
    this.cache.delete(key);
  }
}

// Instancia singleton
export const imageCache = new ImageCache();

// Helper para notificar actualizaciones de imagen
export const notifyImageUpdate = (codigo) => {
  imageCache.invalidate(codigo);
  
  // Disparar evento personalizado
  const event = new CustomEvent('productImageUpdated', {
    detail: { codigo }
  });
  window.dispatchEvent(event);
};
