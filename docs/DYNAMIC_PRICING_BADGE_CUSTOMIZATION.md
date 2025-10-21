# 🎨 PERSONALIZACIÓN DEL BADGE DE DYNAMIC PRICING

**Archivo:** `src/components/StockAlerts.jsx`  
**Líneas:** 256-290 (vista lista) y 316-337 (vista card)

---

## 📍 UBICACIONES EXACTAS

### Vista Lista
- **Línea 256:** Inicio del bloque de precio
- **Línea 272:** Badge de porcentaje
- **Línea 280:** Nombre de la regla

### Vista Card
- **Línea 316:** Inicio del bloque de precio
- **Línea 332:** Badge de porcentaje

---

## 🎨 PERSONALIZACIONES COMUNES

### 1. Badge Estilo "Oferta" (verde para descuentos)

```jsx
// Línea 271-276 (vista lista) y 331-336 (vista card)
{producto.dynamic_pricing?.activo && (
    <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium border ${
        producto.dynamic_pricing.porcentaje_incremento < 0 
            ? 'bg-green-100 text-green-800 border-green-300'  // Descuento (negativo)
            : 'bg-orange-100 text-orange-800 border-orange-300'  // Aumento (positivo)
    }`}>
        {producto.dynamic_pricing.porcentaje_incremento < 0 ? '🎉 ' : ''}
        {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
        {Math.abs(producto.dynamic_pricing.porcentaje_incremento)}%
        {producto.dynamic_pricing.porcentaje_incremento < 0 ? ' OFF' : ''}
    </span>
)}
```

**Resultado:**
- Aumento: `+10%` (naranja)
- Descuento: `🎉 15% OFF` (verde)

---

### 2. Badge Minimalista (solo ícono)

```jsx
{producto.dynamic_pricing?.activo && (
    <span className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold">
        {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
        {producto.dynamic_pricing.porcentaje_incremento}
    </span>
)}
```

**Resultado:** Círculo naranja con `+10` dentro

---

### 3. Badge Grande con Texto

```jsx
{producto.dynamic_pricing?.activo && (
    <div className="inline-flex flex-col items-center px-2 py-1 rounded bg-orange-100 border border-orange-300">
        <span className="text-xs font-medium text-orange-800">
            {producto.dynamic_pricing.porcentaje_incremento > 0 ? 'AUMENTO' : 'DESCUENTO'}
        </span>
        <span className="text-lg font-bold text-orange-600">
            {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
            {producto.dynamic_pricing.porcentaje_incremento}%
        </span>
    </div>
)}
```

**Resultado:**
```
┌─────────┐
│ AUMENTO │
│  +10%   │
└─────────┘
```

---

### 4. Badge con Hora de Finalización

```jsx
{producto.dynamic_pricing?.activo && (
    <div className="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-orange-100 text-orange-800 border border-orange-300">
        <span className="font-medium">
            {producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
            {producto.dynamic_pricing.porcentaje_incremento}%
        </span>
        <span className="text-[10px] opacity-75">
            hasta 23:59
        </span>
    </div>
)}
```

**Resultado:** `+10% hasta 23:59`

---

### 5. Sin Badge, Solo Color

```jsx
{/* Eliminar el badge completamente, solo cambiar color del precio */}
<p className={`text-lg font-bold ${
    producto.dynamic_pricing?.activo 
        ? 'text-orange-600' 
        : 'text-blue-600'
}`}>
    ${producto.precio_venta?.toLocaleString() || '0'}
    {producto.dynamic_pricing?.activo && (
        <span className="text-xs ml-1">
            ({producto.dynamic_pricing.porcentaje_incremento > 0 ? '+' : ''}
            {producto.dynamic_pricing.porcentaje_incremento}%)
        </span>
    )}
</p>
```

**Resultado:** `$1,650 (+10%)` todo naranja

---

## 🎯 COLORES TAILWIND DISPONIBLES

```jsx
// Rojos
bg-red-50   text-red-500   border-red-200
bg-red-100  text-red-600   border-red-300
bg-red-200  text-red-700   border-red-400

// Naranjas (actual)
bg-orange-50   text-orange-500   border-orange-200
bg-orange-100  text-orange-600   border-orange-300
bg-orange-200  text-orange-700   border-orange-400

// Amarillos
bg-yellow-50   text-yellow-500   border-yellow-200
bg-yellow-100  text-yellow-600   border-yellow-300
bg-yellow-200  text-yellow-700   border-yellow-400

// Verdes
bg-green-50   text-green-500   border-green-200
bg-green-100  text-green-600   border-green-300
bg-green-200  text-green-700   border-green-400

// Azules
bg-blue-50   text-blue-500   border-blue-200
bg-blue-100  text-blue-600   border-blue-300
bg-blue-200  text-blue-700   border-blue-400

// Morados
bg-purple-50   text-purple-500   border-purple-200
bg-purple-100  text-purple-600   border-purple-300
bg-purple-200  text-purple-700   border-purple-400
```

---

## 🔧 MODIFICACIÓN PASO A PASO

### Paso 1: Abrir el archivo

```bash
# Windows con VSCode:
code src/components/StockAlerts.jsx

# O usar el editor que prefieras
```

### Paso 2: Buscar la línea

**Buscar:** `💰 PRECIO CON DYNAMIC PRICING BADGE`

Encontrarás **2 bloques** (vista lista y vista card)

### Paso 3: Modificar

Editar las clases CSS o el contenido del badge

### Paso 4: Guardar

Guardar archivo (Ctrl+S o Cmd+S)

### Paso 5: Refrescar navegador

```
Ctrl + Shift + R  (o Cmd + Shift + R en Mac)
```

---

## 💡 TIPS

### Mantener Consistencia

Modificar AMBOS bloques (vista lista y vista card) para mantener consistencia:

```jsx
// Vista lista (línea ~272)
<span className="... bg-orange-100 ...">

// Vista card (línea ~332)
<span className="... bg-orange-100 ...">
```

### Probar con Simulador

```bash
# Activar pricing temporalmente para ver el badge
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T18:30:00"
```

### Responsive

El badge actual es responsive (se adapta a móvil y desktop). Si lo modificás, probá en ambos:
- Desktop: Vista normal
- Móvil: Abrir DevTools (F12) → Toggle device toolbar

---

## ✅ RESUMEN

**Archivo:** `src/components/StockAlerts.jsx`

**Modificar:**
- **Línea 272:** Badge en vista lista
- **Línea 332:** Badge en vista card
- **Línea 266 y 326:** Color del precio

**Colores actuales:**
- Badge: Naranja (`bg-orange-100`)
- Precio ajustado: Naranja (`text-orange-600`)
- Precio original: Gris tachado (`text-gray-500 line-through`)

**Después de modificar:**
1. Guardar archivo
2. Refrescar navegador (Ctrl+Shift+R)
3. Listo ✅

---

**Recomendación:** Empezá cambiando solo el color para ver cómo queda, luego ajustá lo demás.

