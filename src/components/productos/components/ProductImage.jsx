// src/components/productos/components/ProductImage.jsx
// Componente unificado para imágenes de productos
// Soporte multi-formato, lazy loading y cache optimizado
// RELEVANT FILES: ProductosPage.jsx, StockAlerts.jsx, POS components

import React, { useState, useEffect } from 'react';
import { Package } from 'lucide-react';
import CONFIG from '../../../config/config';
import { imageCache } from '../../../utils/imageCache';

const ProductImage = ({ 
  codigo, 
  nombre, 
  size = 'default',
  className = '',
  showFallback = true,
  onClick = null
}) => {
  const [estado, setEstado] = useState('cargando'); // 'cargando', 'cargada', 'error'
  const [imagenUrl, setImagenUrl] = useState(null);

  // Configuración de tamaños
  const sizeConfig = {
    small: { container: "w-8 h-8", icon: "w-4 h-4" },
    default: { container: "w-12 h-12", icon: "w-6 h-6" },
    large: { container: "w-20 h-20", icon: "w-10 h-10" },
    card: { container: "w-full h-32", icon: "w-8 h-8" },
    hero: { container: "w-full h-48", icon: "w-12 h-12" }
  };

  const config = sizeConfig[size] || sizeConfig.default;

  // ⚡ OPTIMIZACIÓN: Precargar imagen con cache cuando cambie el código
  useEffect(() => {
    if (!codigo) {
      setEstado('error');
      return;
    }

    // ⚡ CACHE: Verificar cache primero
    const cached = imageCache.get(codigo);
    if (cached) {
      if (cached.estado === 'cargada') {
        setImagenUrl(cached.url);
        setEstado('cargada');
        return;
      } else if (cached.estado === 'error') {
        setEstado('error');
        return;
      }
    }

    setEstado('cargando');
    
    // ⚡ CACHE: Usar cache manager para cargar imagen
    const cargarImagenConCache = async () => {
      try {
        const imageData = await imageCache.preloadImage(codigo, CONFIG.API_URL);
        
        if (imageData.estado === 'cargada') {
          setImagenUrl(imageData.url);
          setEstado('cargada');
        } else {
          setEstado('error');
        }
      } catch (error) {
        console.warn('Error cargando imagen:', error);
        setEstado('error');
      }
    };

    cargarImagenConCache();
    
    // ⚡ OPTIMIZACIÓN: Listener para actualizar imagen cuando se suba una nueva
    const handleImageUpdate = (event) => {
      if (event.detail?.codigo === codigo) {
        // Invalidar cache y recargar
        imageCache.invalidate(codigo);
        setTimeout(() => {
          cargarImagenConCache();
        }, 1000);
      }
    };
    
    window.addEventListener('productImageUpdated', handleImageUpdate);
    
    return () => {
      window.removeEventListener('productImageUpdated', handleImageUpdate);
    };
  }, [codigo]);

  // Componente de imagen cargada
  if (estado === 'cargada' && imagenUrl) {
    return (
      <div 
        className={`${config.container} ${className} overflow-hidden bg-gray-100 flex items-center justify-center ${onClick ? 'cursor-pointer' : ''}`}
        onClick={onClick}
        title={nombre}
      >
        <img
          src={imagenUrl}
          alt={nombre || 'Producto'}
          className="w-full h-full object-cover"
          loading="lazy"
        />
      </div>
    );
  }

  // Estado de carga o error - mostrar ícono de fallback
  if (showFallback) {
    return (
      <div 
        className={`${config.container} ${className} overflow-hidden bg-gray-100 flex items-center justify-center ${onClick ? 'cursor-pointer' : ''}`}
        onClick={onClick}
        title={nombre}
      >
        {estado === 'cargando' ? (
          <div className={`${config.icon} animate-pulse`}>
            <Package className="w-full h-full text-gray-400" />
          </div>
        ) : (
          <Package className={`${config.icon} text-gray-400`} />
        )}
      </div>
    );
  }

  // No mostrar nada si no hay fallback
  return null;
};

// ⚡ OPTIMIZACIÓN: Memorizar componente para evitar re-renders innecesarios
export default React.memo(ProductImage);
