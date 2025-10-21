# 💡 DYNAMIC PRICING: INTEGRACIÓN FRONTEND (OPCIONAL)

**Sistema:** Tayrona POS  
**Estado:** Frontend NO requiere cambios obligatorios  
**Esta guía:** Mejoras visuales opcionales

---

## ✅ LO QUE YA FUNCIONA (sin tocar frontend)

El backend automáticamente:
1. ✅ Ajusta precios en `precio_venta`
2. ✅ Los envía al frontend
3. ✅ Frontend los muestra normalmente

**No necesitás cambiar nada para que funcione.** ✨

---

## 🎨 MEJORA OPCIONAL: Badge Visual

Si querés mostrar un **indicador visual** cuando hay un ajuste de precio:

### Opción 1: Badge Naranja (recomendado)

Editar `src/components/StockAlerts.jsx` línea ~256:

```jsx
// ANTES (línea 256):
<p className="text-lg font-bold text-blue-600">
    ${producto.precio_venta?.toLocaleString() || '0'}
</p>

// DESPUÉS:
<div className="flex items-center gap-2">
    <p className="text-lg font-bold text-blue-600">
        ${producto.precio_venta?.toLocaleString() || '0'}
    </p>
    {/* 💰 DYNAMIC PRICING BADGE */}
    {producto.dynamic_pricing?.activo && (
        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
            +{producto.dynamic_pricing.porcentaje_incremento}%
        </span>
    )}
</div>
```

**Resultado visual:**

```
$1,650  [+10%]
        ↑ badge naranja
```

---

### Opción 2: Precio Original Tachado

Si querés mostrar el precio original:

```jsx
<div className="flex flex-col items-end">
    {/* Precio original tachado */}
    {producto.dynamic_pricing?.activo && (
        <span className="text-xs text-gray-500 line-through">
            ${producto.dynamic_pricing.precio_original?.toLocaleString()}
        </span>
    )}
    
    {/* Precio ajustado */}
    <div className="flex items-center gap-2">
        <p className="text-lg font-bold text-orange-600">
            ${producto.precio_venta?.toLocaleString() || '0'}
        </p>
        <span className="text-xs font-medium text-orange-600">
            +{producto.dynamic_pricing.porcentaje_incremento}%
        </span>
    </div>
</div>
```

**Resultado visual:**

```
$1,500  ← tachado gris
$1,650 +10%  ← naranja destacado
```

---

### Opción 3: Tooltip Informativo

Mostrar regla aplicada al hacer hover:

```jsx
<div className="relative group">
    <p className="text-lg font-bold text-blue-600">
        ${producto.precio_venta?.toLocaleString() || '0'}
    </p>
    
    {/* Tooltip */}
    {producto.dynamic_pricing?.activo && (
        <>
            <span className="ml-1 text-xs text-orange-500 cursor-help">
                ⓘ
            </span>
            <div className="absolute bottom-full right-0 mb-2 hidden group-hover:block z-10 w-48">
                <div className="bg-gray-900 text-white text-xs rounded py-2 px-3 shadow-lg">
                    <p className="font-semibold">
                        {producto.dynamic_pricing.regla_aplicada}
                    </p>
                    <p className="text-gray-300 mt-1">
                        Precio base: ${producto.dynamic_pricing.precio_original}
                    </p>
                    <p className="text-orange-300">
                        Ajuste: +{producto.dynamic_pricing.porcentaje_incremento}%
                    </p>
                </div>
            </div>
        </>
    )}
</div>
```

**Resultado:** Al pasar el mouse sobre ⓘ se muestra la info completa.

---

## 📍 DÓNDE MODIFICAR

### Archivo: `src/components/StockAlerts.jsx`

#### Vista Lista (línea ~256):

```jsx
// Buscar:
<p className="text-lg font-bold text-blue-600">
    ${producto.precio_venta?.toLocaleString() || '0'}
</p>

// Agregar badge después de esta línea
```

#### Vista Card (línea ~295 aprox):

Buscar similar y agregar el mismo badge.

---

## 🎯 EJEMPLO COMPLETO

```jsx
// File: src/components/StockAlerts.jsx
// Buscar la función ProductCardWithAlerts, variant 'list' (línea ~239)

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
                {/* ✅ MODIFICAR ESTA SECCIÓN */}
                <div className="flex items-center gap-2 justify-end">
                    <p className="text-lg font-bold text-blue-600">
                        ${producto.precio_venta?.toLocaleString() || '0'}
                    </p>
                    
                    {/* 💰 NUEVO: Dynamic Pricing Badge */}
                    {producto.dynamic_pricing?.activo && (
                        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                            +{producto.dynamic_pricing.porcentaje_incremento}%
                        </span>
                    )}
                </div>
                
                <div className="flex items-center gap-2 justify-end">
                    <StockIndicator stock={stockInfo.cantidad} stockMinimo={stockInfo.stock_minimo} size="sm" />
                    <StockBadge producto={producto} size="xs" />
                </div>
            </div>
        </div>
    );
}
```

---

## 🧪 PROBAR

1. Activar dynamic pricing: `api/pricing_config.php` → `enabled: true`
2. Configurar regla para categoría que tenés
3. Abrir POS en horario configurado
4. Ver productos con badge naranja `+10%`

---

## 🎨 COLORES SUGERIDOS

| Caso | Color | Clase Tailwind |
|------|-------|----------------|
| **Aumento** | Naranja | `bg-orange-100 text-orange-800` |
| **Descuento** | Verde | `bg-green-100 text-green-800` |
| **Neutro** | Azul | `bg-blue-100 text-blue-800` |

---

## 🔄 ROLLBACK FRONTEND

Si agregaste el badge y querés quitarlo:

1. Buscar `{producto.dynamic_pricing?.activo &&`
2. Eliminar ese bloque
3. Guardar
4. Listo

---

## ✅ RESUMEN

**Sin cambios en frontend:**
- ✅ Sistema funciona perfectamente
- ✅ Precios se muestran correctos
- ✅ Ventas procesan OK

**Con badge visual (opcional):**
- 🎨 Usuario VE que hay ajuste
- 🎨 Más transparencia
- 🎨 Mejor UX

**Recomendación:** Empezá sin cambios. Si después querés agregar el badge, es fácil.

---

**Conclusión:** NO necesitás tocar el frontend. Todo funciona desde el backend. ✅

