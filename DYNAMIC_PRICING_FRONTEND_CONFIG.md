# 💰 PRECIOS DINÁMICOS - CONFIGURACIÓN FRONTEND

**FECHA:** 21/10/2025  
**ESTADO:** ✅ Implementado y funcional

---

## 📍 UBICACIÓN

El panel de configuración está en:

```
Productos → Botón naranja 📈 (junto a "Nuevo Producto")
```

---

## 🎯 QUÉ HACE

El **panel de Precios Dinámicos** te permite:

✅ **Activar/Desactivar** el sistema completo (toggle)  
✅ **Ver reglas configuradas** en tiempo real  
✅ **Editar reglas** desde el frontend (días, horarios, porcentajes)  
✅ **Ver estado** de cada regla (activa/inactiva)  

---

## 📁 ARCHIVOS INVOLUCRADOS

### Backend
```
api/pricing_config.php       → Configuración de reglas
api/pricing_save.php         → Guardar cambios desde frontend
api/pricing_control.php      → API de consulta
api/pricing_engine.php       → Motor de cálculo
```

### Frontend
```
src/components/productos/PricingQuickPanel.jsx  → Panel de configuración
src/components/ProductosPage.jsx                → Integración en Productos
src/components/productos/components/ProductSearch.jsx → Botón naranja 📈
```

---

## 🖥️ CÓMO USAR

### 1️⃣ Abrir el panel
1. Ir a **Productos**
2. Click en botón naranja 📈 (**TrendingUp icon**)
3. Se abre modal con configuración

### 2️⃣ Activar/Desactivar sistema
- **Toggle verde** = Sistema activo
- **Toggle gris** = Sistema desactivado
- El cambio es **inmediato**

### 3️⃣ Ver reglas
Cada regla muestra:
- **Nombre** (ej: "Bebidas alcohólicas - Viernes noche")
- **Target** (categoría o producto)
- **Días** (Lun, Mar, Mié, etc.)
- **Horario** (18:00 - 23:59)
- **Ajuste** (+10%, -15%, etc.)
- **Estado** (Activa/Inactiva)

### 4️⃣ Editar una regla
1. Click en **ícono lápiz** (Edit2)
2. Modificar:
   - **Horario desde/hasta**
   - **Porcentaje de ajuste**
3. Click en **"Guardar"**
4. Los cambios se aplican **inmediatamente** en el POS

---

## ⚙️ CONFIGURACIÓN AVANZADA

Si necesitas agregar/quitar reglas, editar:

```
api/pricing_config.php
```

### Estructura de una regla:

```php
[
    'id'          => 'mi-regla-unica',
    'name'        => 'Nombre descriptivo',
    'description' => 'Qué hace esta regla',
    'enabled'     => true,  // Activar/desactivar
    
    'type'        => 'category',  // 'category' o 'sku'
    'category_slug' => 'bebidas-alcoholicas',
    
    'days'        => ['fri', 'sat'],  // mon,tue,wed,thu,fri,sat,sun
    'from'        => '18:00',
    'to'          => '23:59',
    'percent_inc' => 10.0,  // +10% (negativo = descuento)
],
```

---

## 🔐 SEGURIDAD

✅ **Server-side only** → Los precios se calculan en el servidor  
✅ **Anti-tampering** → Se re-validan al procesar la venta  
✅ **Límites de seguridad** → Máximo +50% / -30%  
✅ **Logging** → Todos los cambios se registran  

---

## 🧪 TESTING

### Ver precios ajustados en el POS:
1. Activar sistema desde el panel
2. Ir a **Punto de Venta**
3. Buscar un producto de categoría "bebidas-alcoholicas"
4. Si es viernes/sábado 18:00+, verás:
   - Precio original tachado
   - Precio ajustado en naranja
   - Badge `[+10%]`

### Simular horarios (solo DEV):
Agregar `?__sim=YYYY-MM-DDTHH:mm:ss` en la URL del POS:

```
http://localhost:3000/pos?__sim=2025-10-25T19:30:00
```

Esto simula que son las 19:30 del viernes 25/10.

---

## 📊 EJEMPLO DE USO

### Caso: "Viernes a las 20:00"

**Regla activa:**  
- Bebidas alcohólicas  
- Viernes 18:00-23:59  
- +10%  

**Producto:** Cerveza IPA  
- Precio original: $1.000  
- **Precio ajustado: $1.100** ✅  

**En el POS se verá:**
```
Cerveza IPA
$1,000  [tachado]
$1,100  [naranja]  [+10%]
```

---

## 🚀 PRÓXIMOS PASOS (OPCIONAL)

Si querés más control:

1. **Agregar reglas desde el frontend** (ahora solo editas)
2. **Eliminar reglas desde el frontend**
3. **Programar reglas por fecha específica** (ej: "31/12 solo")
4. **Historial de cambios** (quién modificó qué y cuándo)

---

## 📞 SOPORTE

- **Archivo de config:** `api/pricing_config.php`
- **Logs:** `api/logs/pricing_adjustments.log`
- **Documentación completa:** `docs/DYNAMIC_PRICING_SYSTEM.md`

---

✅ **SISTEMA 100% FUNCIONAL**  
✅ **Sin cambios en base de datos**  
✅ **Server-side only (seguro)**  
✅ **Editable desde frontend**

