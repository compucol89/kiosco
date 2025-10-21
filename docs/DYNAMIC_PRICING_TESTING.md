# 🧪 DYNAMIC PRICING: GUÍA DE TESTING

**Sistema:** Tayrona POS  
**Versión:** 1.0.0  
**Fecha:** 21 de Octubre, 2025

---

## 🎯 RESUMEN RÁPIDO

Ya está todo implementado y funcionando:
- ✅ Backend ajusta precios automáticamente
- ✅ Frontend muestra badge naranja cuando hay ajuste
- ✅ Sistema de simulación para testing
- ✅ Panel de control API

---

## 🧪 CÓMO PROBAR

### Test 1: Ver Estado del Sistema

```bash
# Ver estado actual
curl "http://localhost/kiosco/api/pricing_control.php?action=status"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "system": {
    "enabled": true,
    "timezone": "America/Argentina/Buenos_Aires",
    "total_rules": 2,
    "active_rules": 2
  },
  "current_time": "2025-10-21 15:30:00",
  "current_day": "tue"
}
```

---

### Test 2: Ver Reglas Activas

```bash
# Ver todas las reglas configuradas
curl "http://localhost/kiosco/api/pricing_control.php?action=rules"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "rules": [
    {
      "id": "alcoholic-friday",
      "name": "Bebidas alcohólicas - Viernes noche",
      "enabled": true,
      "type": "category",
      "target": "bebidas-alcoholicas",
      "days": ["fri"],
      "from": "18:00",
      "to": "23:59",
      "percent_inc": 10
    },
    {
      "id": "alcoholic-saturday",
      "name": "Bebidas alcohólicas - Sábado noche",
      "enabled": true,
      "type": "category",
      "target": "bebidas-alcoholicas",
      "days": ["sat"],
      "from": "18:00",
      "to": "23:59",
      "percent_inc": 10
    }
  ]
}
```

---

### Test 3: Simular Viernes 18:30 (sin tocar el reloj del sistema)

```bash
# Productos con simulación de fecha/hora
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T18:30:00"
```

**¿Qué hace?**
- Simula que es viernes 24 de octubre a las 18:30
- Las bebidas alcohólicas deberían tener +10%
- Solo funciona en desarrollo (APP_ENV !== 'production')

**Buscar en la respuesta:**
```json
{
  "id": 123,
  "nombre": "Quilmes 1L",
  "precio_venta": 1650,
  "dynamic_pricing": {
    "activo": true,
    "precio_original": 1500,
    "precio_ajustado": 1650,
    "porcentaje_incremento": 10,
    "regla_aplicada": "Bebidas alcohólicas - Viernes noche"
  }
}
```

---

### Test 4: Borde de Horario

```bash
# Antes del horario (17:59)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T17:59:00"
# Resultado: dynamic_pricing.activo = false (sin ajuste)

# Justo al inicio (18:00)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T18:00:00"
# Resultado: dynamic_pricing.activo = true (con ajuste +10%)

# Durante el horario (19:30)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T19:30:00"
# Resultado: dynamic_pricing.activo = true (con ajuste +10%)

# Después del horario (00:00 del día siguiente)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-25T00:00:00"
# Resultado: dynamic_pricing.activo = false (sin ajuste)
```

---

### Test 5: Diferentes Días

```bash
# Lunes (sin regla)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-20T19:00:00"
# Resultado: Sin ajuste

# Viernes (con regla)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T19:00:00"
# Resultado: Con ajuste +10%

# Sábado (con regla)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-25T19:00:00"
# Resultado: Con ajuste +10%

# Domingo (sin regla)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-26T19:00:00"
# Resultado: Sin ajuste
```

---

## 🎨 TESTING EN EL POS (VISUAL)

### 1. Abrir el POS

```
http://localhost:3000
```

### 2. Simular Fecha/Hora en la URL

```
http://localhost:3000

# En la consola del navegador, modificar temporalmente la URL de la API:
```

**Opción más simple:** Cambiar la hora de tu computadora temporalmente:
1. Cambiar a viernes
2. Cambiar hora a 18:30
3. Abrir POS
4. Ver productos con badge naranja `[+10%]`
5. Restaurar fecha/hora

---

## 📊 QUÉ BUSCAR EN EL POS

### Producto SIN ajuste:

```
┌─────────────────────┐
│  Coca Cola 500ml   │
│                     │
│  $1,500            │ ← Azul normal
│  [Stock: 10]       │
└─────────────────────┘
```

### Producto CON ajuste:

```
┌─────────────────────┐
│  Quilmes 1L        │
│                     │
│  $1,500            │ ← Gris tachado
│  $1,650  [+10%]    │ ← Naranja + badge
│  [Stock: 25]       │
└─────────────────────┘
```

---

## 🔍 VER LOGS EN TIEMPO REAL

```bash
# Ver ajustes aplicados
tail -f api/logs/pricing_adjustments.log

# Salida esperada:
[2025-10-24 18:05:23] Product #45 'Quilmes 1L' | Rule: alcoholic-friday | Original: $1500.00 → Adjusted: $1650.00 (+10.0%)
[2025-10-24 18:07:15] Product #48 'Fernet Branca 750ml' | Rule: alcoholic-friday | Original: $8500.00 → Adjusted: $9350.00 (+10.0%)
```

---

## 🧪 TEST DE VENTA COMPLETA

### 1. Agregar Producto con Precio Ajustado

1. Simular viernes 18:30 (o cambiar fecha del sistema)
2. Abrir POS
3. Buscar bebida alcohólica
4. Ver precio ajustado: `$1,650` con badge `[+10%]`
5. Agregar al carrito

### 2. Verificar Carrito

El carrito debe mostrar:
- Precio ajustado: `$1,650`
- Total correcto con el precio ajustado

### 3. Procesar Venta

1. Procesar venta con método de pago
2. Venta debe completarse con precio ajustado

### 4. Verificar en Base de Datos

```sql
-- Ver última venta
SELECT * FROM ventas ORDER BY id DESC LIMIT 1;

-- Ver detalle de venta
SELECT * FROM detalle_ventas WHERE venta_id = (SELECT MAX(id) FROM ventas);

-- El precio_unitario debe ser el ajustado ($1650, no $1500)
```

---

## 📝 CHECKLIST POST-IMPLEMENTACIÓN

### Backend

- [ ] `pricing_config.php` → `enabled: true`
- [ ] Reglas configuradas correctamente (días, horarios, %)
- [ ] Categorías existen en la BD (ej: `bebidas-alcoholicas`)
- [ ] Timezone correcto: `America/Argentina/Buenos_Aires`
- [ ] Logging activo: `'logging' => ['enabled' => true]`

### Frontend

- [ ] Badge naranja visible cuando hay ajuste
- [ ] Precio original tachado se muestra
- [ ] Precio ajustado en naranja
- [ ] Badge muestra porcentaje correcto (`+10%`)

### Testing

- [ ] Simulación funciona con `?__sim=...`
- [ ] Bordes de horario funcionan (17:59 vs 18:00)
- [ ] Diferentes días funcionan correctamente
- [ ] Venta procesa con precio ajustado
- [ ] Logs se generan correctamente

---

## 🎯 CASOS DE PRUEBA

### Caso 1: Happy Hour (descuento)

**Config:**
```php
[
    'id' => 'happy-hour-test',
    'type' => 'category',
    'category_slug' => 'bebidas',
    'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
    'from' => '14:00',
    'to' => '17:00',
    'percent_inc' => -15.0,  // -15% descuento
]
```

**Test:**
```bash
# Lunes 15:00 (dentro de happy hour)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-20T15:00:00"

# Buscar bebida:
# Precio original: $1000
# Precio ajustado: $850 (-15%)
# Badge: [-15%] en verde
```

---

### Caso 2: Producto Específico

**Config:**
```php
[
    'id' => 'cerveza-premium',
    'type' => 'sku',
    'sku' => 'CERVEZA-IPA-473',
    'days' => ['fri', 'sat'],
    'from' => '18:00',
    'percent_inc' => 12.5,
]
```

**Test:**
```bash
# Viernes 20:00
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T20:00:00"

# Solo el producto CERVEZA-IPA-473 debe tener ajuste
# Otros productos de la misma categoría: sin ajuste
```

---

## 🚨 TROUBLESHOOTING

### Problema: Badge no aparece en el POS

**Solución:**
1. Verificar que `pricing_config.php` → `enabled: true`
2. Refrescar el navegador con Ctrl+Shift+R
3. Ver consola del navegador (F12) para errores
4. Verificar que la categoría existe en la BD

### Problema: Simulación no funciona

**Solución:**
1. Verificar que NO estés en producción
2. URL debe incluir `?__sim=2025-10-24T18:30:00`
3. Formato debe ser exacto: `YYYY-MM-DDTHH:mm:ss`
4. Ver logs de PHP: `tail -f /var/log/php_errors.log`

### Problema: Precio no se ajusta

**Solución:**
1. Verificar que el día/hora esté dentro de la regla
2. Verificar que `category_slug` coincida exactamente
3. Ver logs: `api/logs/pricing_adjustments.log`
4. Probar con `pricing_control.php?action=test`

---

## 🎉 EJEMPLO COMPLETO DE TEST

```bash
# 1. Ver estado
curl "http://localhost/kiosco/api/pricing_control.php?action=status"

# 2. Ver reglas
curl "http://localhost/kiosco/api/pricing_control.php?action=rules"

# 3. Probar sin ajuste (martes 15:00)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-21T15:00:00" | grep dynamic_pricing

# 4. Probar con ajuste (viernes 19:00)
curl "http://localhost/kiosco/api/productos_pos_optimizado.php?__sim=2025-10-24T19:00:00" | grep dynamic_pricing

# 5. Ver logs
tail -f api/logs/pricing_adjustments.log
```

---

## 📚 DOCUMENTACIÓN RELACIONADA

- **Config:** `api/pricing_config.php`
- **Motor:** `api/pricing_engine.php`
- **Guía completa:** `docs/DYNAMIC_PRICING_SYSTEM.md`
- **Quick start:** `DYNAMIC_PRICING_QUICK_START.md`
- **Frontend:** `docs/DYNAMIC_PRICING_FRONTEND.md`

---

**Status:** ✅ TODO LISTO PARA TESTING

**Próximo paso:** Probar con `?__sim=...` o cambiar la fecha del sistema temporalmente.

---

**Implementado por:** Cursor AI Agent  
**Fecha:** 21 de Octubre, 2025  
**Versión:** 1.0.0

