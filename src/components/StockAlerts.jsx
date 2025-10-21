import React, { useState, useEffect } from 'react';
import { AlertCircle, AlertTriangle, Clock, Package, CheckCircle } from 'lucide-react';
import CONFIG from '../config/config';

/**
 * 🖼️ COMPONENTE DE IMAGEN PARA PUNTO DE VENTAS
 * 
 * Componente optimizado para mostrar imágenes de productos en el POS
 */
const ProductImagePOS = ({ producto, size = 'default' }) => {
    const [estado, setEstado] = useState('cargando');
    const [imagenUrl, setImagenUrl] = useState(null);

    const sizeConfig = {
        small: { container: "w-6 h-6", icon: "w-3 h-3" },
        default: { container: "w-8 h-8", icon: "w-4 h-4" },
        large: { container: "w-12 h-12", icon: "w-6 h-6" }
    };

    const config = sizeConfig[size];

    useEffect(() => {
        const codigo = producto?.codigo || producto?.barcode;
        if (!codigo) {
            setEstado('error');
            return;
        }

        setEstado('cargando');
        
        // Buscar imagen en diferentes formatos
        const timestamp = Date.now();
        const formatosImagen = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        let formatoEncontrado = false;

        const probarFormatos = async () => {
            for (const formato of formatosImagen) {
                try {
                    const img = new Image();
                    const imagenUrl = `${CONFIG.API_URL}/img/productos/${codigo}.${formato}?t=${timestamp}`;
                    
                    await new Promise((resolve, reject) => {
                        img.onload = () => {
                            if (!formatoEncontrado) {
                                formatoEncontrado = true;
                                setImagenUrl(imagenUrl);
                                setEstado('cargada');
                                resolve();
                            }
                        };
                        img.onerror = reject;
                        img.src = imagenUrl;
                    });
                    
                    break;
                } catch (error) {
                    continue;
                }
            }
            
            if (!formatoEncontrado) {
                setEstado('error');
            }
        };

        probarFormatos();

        // Listener para actualización de imágenes
        const handleImageUpdate = () => {
            setTimeout(() => {
                probarFormatos();
            }, 1000);
        };

        window.addEventListener('productImageUpdated', handleImageUpdate);
        
        return () => {
            window.removeEventListener('productImageUpdated', handleImageUpdate);
        };
    }, [producto?.codigo, producto?.barcode]);

    if (estado === 'cargada' && imagenUrl) {
        return (
            <img
                src={imagenUrl}
                alt={producto?.nombre || 'Producto'}
                className="w-full h-full object-cover rounded"
            />
        );
    }

    // Estado de carga o error - ícono por defecto
    return (
        <div className="w-full h-full flex items-center justify-center">
            <Package className={`${config.container} text-gray-400`} />
        </div>
    );
};

/**
 * 🚨 COMPONENTE DE BADGE DE STOCK
 * 
 * Componente reutilizable para mostrar alertas de stock
 * con estilos consistentes y responsive
 */
export const StockBadge = ({ 
    producto, 
    size = 'sm', 
    showIcon = true, 
    showText = true,
    position = 'inline' // 'inline', 'absolute-top-right', 'absolute-bottom'
}) => {
    // Verificar si el producto tiene alertas visuales configuradas
    const alertas = producto?.alertas_visuales || generarAlertasVisualesLocal(producto);
    
    if (!alertas.mostrar_badge) {
        return null;
    }
    
    // Configuraciones de tamaño
    const sizeConfig = {
        xs: {
            text: 'text-xs',
            padding: 'px-1.5 py-0.5',
            icon: 'w-3 h-3',
            gap: 'gap-1'
        },
        sm: {
            text: 'text-xs',
            padding: 'px-2 py-1',
            icon: 'w-3 h-3',
            gap: 'gap-1'
        },
        md: {
            text: 'text-sm',
            padding: 'px-2.5 py-1.5',
            icon: 'w-4 h-4',
            gap: 'gap-1.5'
        },
        lg: {
            text: 'text-sm',
            padding: 'px-3 py-2',
            icon: 'w-4 h-4',
            gap: 'gap-2'
        }
    };
    
    const config = sizeConfig[size] || sizeConfig.sm;
    
    // Configuraciones de posición
    const positionConfig = {
        'inline': '',
        'absolute-top-right': 'absolute -top-1 -right-1 z-10',
        'absolute-bottom': 'absolute bottom-1 left-1/2 transform -translate-x-1/2 z-10'
    };
    
    // Iconos por tipo de alerta
    const iconos = {
        'sin_stock': AlertCircle,
        'stock_bajo': AlertTriangle,
        'stock_critico': Clock,
        'stock_normal': CheckCircle
    };
    
    const IconComponent = iconos[alertas.tipo_badge] || AlertCircle;
    
    const baseClasses = `
        inline-flex items-center 
        ${config.gap} ${config.padding} ${config.text}
        font-medium rounded-full
        ${alertas.color_badge}
        transition-all duration-200
        ${positionConfig[position]}
        shadow-sm
    `;
    
    return (
        <span className={baseClasses}>
            {showIcon && (
                <IconComponent className={config.icon} />
            )}
            {showText && (
                <span className="whitespace-nowrap">
                    {alertas.mensaje}
                </span>
            )}
        </span>
    );
};

/**
 * 🎯 COMPONENTE DE CARD DE PRODUCTO CON ALERTAS
 * 
 * Card optimizada para el punto de venta con alertas integradas
 */
export const ProductCardWithAlerts = ({ 
    producto, 
    onAdd, 
    disabled = false,
    variant = 'card' // 'card', 'list', 'compact'
}) => {
    const alertas = producto?.alertas_visuales || generarAlertasVisualesLocal(producto);
    const stockInfo = producto?.stock_info || generarStockInfoLocal(producto);
    
    // Configurar clases CSS basadas en el estado del stock
    const cardClasses = `
        bg-white rounded-lg border transition-all duration-200 cursor-pointer
        transform hover:scale-[1.02] hover:shadow-md
        ${alertas.css_classes?.join(' ') || ''}
        ${stockInfo.puede_vender ? 'hover:border-blue-300' : 'cursor-not-allowed opacity-75'}
        ${disabled ? 'pointer-events-none opacity-50' : ''}
    `;
    
    const handleClick = () => {
        if (!disabled && stockInfo.puede_vender) {
            onAdd(producto);
        }
    };
    
    if (variant === 'compact') {
        return (
            <div className={`${cardClasses} p-2 flex items-center justify-between`} onClick={handleClick}>
                <div className="flex-1 min-w-0">
                    <h3 className="font-medium text-sm text-gray-900 truncate">
                        {producto.nombre}
                    </h3>
                    <p className="text-sm font-bold text-blue-600">
                        ${producto.precio_venta?.toLocaleString() || '0'}
                    </p>
                </div>
                <div className="flex-shrink-0 ml-2 flex flex-col items-end gap-1">
                    <StockBadge producto={producto} size="xs" />
                    <StockIndicator stock={stockInfo.cantidad} stockMinimo={stockInfo.stock_minimo} />
                </div>
            </div>
        );
    }
    
    if (variant === 'list') {
        return (
            <div className={`${cardClasses} p-3 flex items-center justify-between`} onClick={handleClick}>
                <div className="flex items-center flex-1 min-w-0">
                    <div className="bg-gray-100 rounded mr-3 flex-shrink-0 w-12 h-12 overflow-hidden">
                        <ProductImagePOS producto={producto} size="small" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h3 className="font-medium text-sm text-gray-900 truncate">
                            {producto.nombre}
                        </h3>
                        <p className="text-xs text-gray-500 truncate">
                            {producto.categoria || 'Sin categoría'}
                        </p>
                    </div>
                </div>
                <div className="text-right flex-shrink-0 ml-2 space-y-1">
                    {/* 💰 PRECIO CON DYNAMIC PRICING BADGE */}
                    <div className="flex items-center gap-2 justify-end">
                        {/* Precio original tachado si hay ajuste */}
                        {producto.dynamic_pricing?.activo && (
                            <span className="text-xs text-gray-500 line-through">
                                ${producto.dynamic_pricing.precio_original?.toLocaleString()}
                            </span>
                        )}
                        
                        {/* Precio actual */}
                        <p className={`text-lg font-bold ${producto.dynamic_pricing?.activo ? 'text-orange-600' : 'text-blue-600'}`}>
                            ${producto.precio_venta?.toLocaleString() || '0'}
                        </p>
                        
                        {/* Badge de ajuste */}
                        {producto.dynamic_pricing?.activo && (
                            <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 border border-orange-300">
                                {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
                                {producto.dynamic_pricing.porcentaje_incremento}%
                            </span>
                        )}
                    </div>
                    
                    {/* Nombre de la regla (opcional, solo si hay espacio) */}
                    {producto.dynamic_pricing?.activo && producto.dynamic_pricing.regla_aplicada && (
                        <p className="text-xs text-orange-600 truncate max-w-[200px]">
                            {producto.dynamic_pricing.regla_aplicada}
                        </p>
                    )}
                    
                    <div className="flex items-center gap-2 justify-end">
                        <StockIndicator stock={stockInfo.cantidad} stockMinimo={stockInfo.stock_minimo} size="sm" />
                        <StockBadge producto={producto} size="xs" />
                    </div>
                </div>
            </div>
        );
    }
    
    // Variant 'card' (por defecto)
    return (
        <div className={`${cardClasses} p-3 relative`} onClick={handleClick}>
            {/* Badge de alerta posicionado absolutamente */}
            <StockBadge 
                producto={producto} 
                size="xs" 
                position="absolute-top-right"
            />
            
            <div className="text-center">
                {/* Imagen o ícono del producto */}
                <div className="bg-gray-100 rounded-lg mb-3 h-20 w-full overflow-hidden">
                    <ProductImagePOS producto={producto} size="large" />
                </div>
                
                {/* Información del producto */}
                <h3 className="font-medium text-sm leading-tight text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem]">
                    {producto.nombre}
                </h3>
                
                {/* 💰 PRECIO CON DYNAMIC PRICING BADGE */}
                <div className="mb-2">
                    {/* Precio original tachado si hay ajuste */}
                    {producto.dynamic_pricing?.activo && (
                        <p className="text-xs text-gray-500 line-through mb-1">
                            ${producto.dynamic_pricing.precio_original?.toLocaleString()}
                        </p>
                    )}
                    
                    {/* Precio actual */}
                    <p className={`text-lg font-bold mb-1 ${producto.dynamic_pricing?.activo ? 'text-orange-600' : 'text-blue-600'}`}>
                        ${producto.precio_venta?.toLocaleString() || '0'}
                    </p>
                    
                    {/* Badge de ajuste */}
                    {producto.dynamic_pricing?.activo && (
                        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 border border-orange-300">
                            {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
                            {producto.dynamic_pricing.porcentaje_incremento}%
                        </span>
                    )}
                </div>
                
                {/* Indicador de stock */}
                <StockIndicator 
                    stock={stockInfo.cantidad} 
                    stockMinimo={stockInfo.stock_minimo}
                    showText={true}
                />
            </div>
        </div>
    );
};

/**
 * 📊 INDICADOR VISUAL DE STOCK
 * 
 * Componente que muestra el nivel de stock con una barra visual
 */
export const StockIndicator = ({ 
    stock, 
    stockMinimo = 3, 
    size = 'md',
    showText = false,
    maxStock = 50 // Para calcular el porcentaje de la barra
}) => {
    const sizeConfig = {
        sm: {
            bar: 'h-1',
            text: 'text-xs'
        },
        md: {
            bar: 'h-1.5',
            text: 'text-xs'
        },
        lg: {
            bar: 'h-2',
            text: 'text-sm'
        }
    };
    
    const config = sizeConfig[size] || sizeConfig.md;
    
    // Calcular el porcentaje de la barra
    const porcentaje = Math.min(100, (stock / maxStock) * 100);
    
    // Determinar color basado en el stock
    const getColorClass = () => {
        if (stock <= 0) return 'bg-red-500';
        if (stock <= stockMinimo) return 'bg-yellow-500';
        if (stock <= stockMinimo * 1.5) return 'bg-orange-500';
        return 'bg-green-500';
    };
    
    return (
        <div className="w-full">
            {showText && (
                <div className={`flex justify-between items-center mb-1 ${config.text}`}>
                    <span className="text-gray-500">Stock:</span>
                    <span className="font-medium">{stock}</span>
                </div>
            )}
            
            <div className={`w-full bg-gray-200 rounded-full ${config.bar}`}>
                <div 
                    className={`${config.bar} rounded-full transition-all duration-300 ${getColorClass()}`}
                    style={{ width: `${porcentaje}%` }}
                />
            </div>
            
            {!showText && (
                <span className={`${config.text} text-gray-500 mt-1 block text-center`}>
                    {stock} unidades
                </span>
            )}
        </div>
    );
};

/**
 * 🏷️ ETIQUETA DE CATEGORÍA CON FILTRO
 */
export const CategoryTag = ({ categoria, onClick, isActive = false }) => {
    const baseClasses = `
        inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
        transition-colors duration-200 cursor-pointer
        ${isActive 
            ? 'bg-blue-100 text-blue-800 border border-blue-200' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        }
    `;
    
    return (
        <span className={baseClasses} onClick={() => onClick?.(categoria)}>
            {categoria || 'Sin categoría'}
        </span>
    );
};

/**
 * 🚨 ALERTA DE STOCK CRÍTICO
 * 
 * Componente para mostrar alertas importantes sobre el estado del inventario
 */
export const StockCriticalAlert = ({ estadisticas, onViewDetails }) => {
    if (!estadisticas || (estadisticas.sin_stock === 0 && estadisticas.stock_bajo === 0)) {
        return null;
    }
    
    const totalProblemas = estadisticas.sin_stock + estadisticas.stock_bajo;
    
    return (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <div className="flex items-start">
                <AlertTriangle className="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" />
                <div className="flex-1">
                    <h3 className="text-sm font-medium text-yellow-800 mb-1">
                        Atención: Productos con stock bajo detectados
                    </h3>
                    <div className="text-sm text-yellow-700 space-y-1">
                        {estadisticas.sin_stock > 0 && (
                            <p>• {estadisticas.sin_stock} productos sin stock</p>
                        )}
                        {estadisticas.stock_bajo > 0 && (
                            <p>• {estadisticas.stock_bajo} productos con stock bajo</p>
                        )}
                    </div>
                    {onViewDetails && (
                        <button 
                            onClick={onViewDetails}
                            className="text-sm text-yellow-800 font-medium hover:text-yellow-900 mt-2 underline"
                        >
                            Ver detalles →
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

// ============================================================
// 🔧 FUNCIONES AUXILIARES PARA COMPATIBILIDAD
// ============================================================

/**
 * Generar alertas visuales localmente si no vienen del backend
 */
function generarAlertasVisualesLocal(producto) {
    if (!producto) return { mostrar_badge: false };
    
    const stock = parseInt(producto.stock) || 0;
    const stockMinimo = parseInt(producto.stock_minimo) || 3;
    
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

/**
 * Generar información de stock localmente si no viene del backend
 */
function generarStockInfoLocal(producto) {
    if (!producto) return { puede_vender: false, cantidad: 0 };
    
    const stock = parseInt(producto.stock) || 0;
    const stockMinimo = parseInt(producto.stock_minimo) || 3;
    
    return {
        cantidad: stock,
        puede_vender: stock > 0,
        stock_minimo: stockMinimo,
        estado: stock <= 0 ? 'sin_stock' : stock <= stockMinimo ? 'stock_bajo' : 'stock_normal'
    };
}

export default {
    StockBadge,
    ProductCardWithAlerts,
    StockIndicator,
    CategoryTag,
    StockCriticalAlert
};
