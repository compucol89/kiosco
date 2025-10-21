# 🔧 FIX REPORT: BOTÓN PRECIOS DINÁMICOS

**FECHA:** 21/10/2025 04:40 AM  
**ESTADO:** ✅ **RESUELTO**  
**TIEMPO DE FIX:** 5 minutos  
**COMPLEJIDAD:** Baja (error de ruta)

---

## 🎯 PROBLEMA REPORTADO

**Síntoma visible:**  
- Error en UI: **"Error al cambiar estado"**  
- No se puede activar/desactivar el toggle de Precios Dinámicos

**Errores de consola:**
```
Access to fetch at 'http://localhost/kiosco/pricing_control.php?action=status' 
from origin 'http://localhost:3000' has been blocked by CORS policy

GET http://localhost/kiosco/pricing_control.php?action=status 
net::ERR_FAILED 404 (Not Found)

TypeError: Cannot read properties of null (reading 'enabled')
at toggleSystem (PricingQuickPanel.jsx:50:1)
```

---

## 🔍 DIAGNÓSTICO (MÉTODO)

### 1️⃣ Análisis de errores de consola
- **404 Not Found** → El archivo no se encuentra en la ruta especificada
- **CORS blocked** → Efecto secundario del 404 (no es la causa raíz)
- **Cannot read 'enabled' of null** → Consecuencia: `status` es `null` porque el fetch falló

### 2️⃣ Verificación de archivos
```bash
✅ api/pricing_control.php → EXISTE
✅ api/pricing_save.php → EXISTE
✅ src/components/productos/PricingQuickPanel.jsx → EXISTE
```

### 3️⃣ Análisis de ruta
**Configuración:**
```javascript
// src/config/config.js
API_URL: 'http://localhost/kiosco'
```

**Llamada incorrecta:**
```javascript
// ❌ INCORRECTO (línea 22)
fetch(`${CONFIG.API_URL}/pricing_control.php?action=status`)
// Genera: http://localhost/kiosco/pricing_control.php
```

**Ubicación real del archivo:**
```
http://localhost/kiosco/api/pricing_control.php
                          ^^^^
                          Falta /api/ en el path
```

---

## ✅ CAUSA RAÍZ EXACTA

**Error:** Ruta de API incorrecta en el componente `PricingQuickPanel.jsx`

El componente llamaba a:
- `${CONFIG.API_URL}/pricing_control.php`

Debía llamar a:
- `${CONFIG.API_URL}/api/pricing_control.php`

**Impacto:**
- 3 llamadas fallaban con 404
- Estado del sistema quedaba en `null`
- Toggle no podía ejecutarse

---

## 🔧 SOLUCIÓN APLICADA

### Archivos modificados:
**`src/components/productos/PricingQuickPanel.jsx`** (3 cambios)

### Cambio 1: loadData() - Cargar estado
```javascript
// ANTES
const statusRes = await fetch(`${CONFIG.API_URL}/pricing_control.php?action=status`);
const rulesRes = await fetch(`${CONFIG.API_URL}/pricing_control.php?action=rules`);

// DESPUÉS
const statusRes = await fetch(`${CONFIG.API_URL}/api/pricing_control.php?action=status`);
const rulesRes = await fetch(`${CONFIG.API_URL}/api/pricing_control.php?action=rules`);
```

### Cambio 2: toggleSystem() - Activar/desactivar
```javascript
// ANTES
const response = await fetch(`${CONFIG.API_URL}/pricing_save.php`, { ... });

// DESPUÉS
const response = await fetch(`${CONFIG.API_URL}/api/pricing_save.php`, { ... });
```

### Cambio 3: saveRule() - Guardar cambios
```javascript
// ANTES
const response = await fetch(`${CONFIG.API_URL}/pricing_save.php`, { ... });

// DESPUÉS
const response = await fetch(`${CONFIG.API_URL}/api/pricing_save.php`, { ... });
```

### Mejoras adicionales aplicadas:
1. **Validación de null** antes del toggle
2. **Mensajes de error claros** en cada catch
3. **Estado de carga** visible ("Cargando...")
4. **Toggle deshabilitado** hasta que status cargue

---

## 🧪 VERIFICACIÓN DEL FIX

### ✅ Tests realizados:

1. **Cargar panel:**
   - ✅ GET `/api/pricing_control.php?action=status` → 200 OK
   - ✅ GET `/api/pricing_control.php?action=rules` → 200 OK
   - ✅ Estado carga correctamente

2. **Toggle ON:**
   - ✅ POST `/api/pricing_save.php` → 200 OK
   - ✅ Respuesta: `{ success: true, enabled: true }`
   - ✅ UI actualiza estado

3. **Toggle OFF:**
   - ✅ POST `/api/pricing_save.php` → 200 OK
   - ✅ Respuesta: `{ success: true, enabled: false }`
   - ✅ UI actualiza estado

4. **CORS:**
   - ✅ Headers correctos en ambos endpoints
   - ✅ OPTIONS preflight → 200 OK
   - ✅ Sin errores de bloqueo

---

## 📊 ANÁLISIS DE ARQUITECTURA

### Stack de llamadas:
```
UI (React)
  └─ PricingQuickPanel.jsx
      └─ CONFIG.API_URL + '/api/pricing_control.php'
          └─ api/pricing_control.php (GET)
              └─ require api/pricing_config.php
                  └─ return [ enabled, rules, timezone ]

UI (React)
  └─ PricingQuickPanel.jsx (toggle click)
      └─ CONFIG.API_URL + '/api/pricing_save.php'
          └─ api/pricing_save.php (POST)
              └─ require api/pricing_config.php
              └─ saveConfig() → escribir archivo PHP
                  └─ return { success, enabled }
```

### Middlewares activos:
```
CORS Headers (inline en cada endpoint)
  ↓
OPTIONS preflight (200, exit)
  ↓
Request handler (GET/POST)
  ↓
Response JSON
```

**Nota:** `.htaccess` está **deshabilitado** en DEV (renombrado a `.htaccess.DISABLED`)

---

## 🔐 SEGURIDAD

### ✅ Validaciones aplicadas:
- ✅ Método HTTP: solo POST para cambios
- ✅ CORS: headers correctos
- ✅ Content-Type: application/json
- ✅ Input validation: action requerido

### ⚠️ NOTA IMPORTANTE:
El sistema **escribe archivos PHP** desde la web. Esto funciona en DEV pero puede ser bloqueado en producción por:
- Permisos de filesystem
- SELinux / AppArmor
- Políticas de hosting

**Alternativa para PROD:**
- Usar archivo JSON en `/api/cache/pricing_state.json`
- O variable de entorno `DYNAMIC_PRICING_ENABLED`
- O flag en base de datos (tabla `configuracion`)

---

## 📁 ARCHIVOS INVOLUCRADOS

### Frontend:
```
src/components/productos/PricingQuickPanel.jsx  [MODIFICADO]
src/components/ProductosPage.jsx                [OK - no modificado]
src/components/productos/components/ProductSearch.jsx  [OK - no modificado]
src/config/config.js                            [OK - no modificado]
```

### Backend:
```
api/pricing_control.php   [OK - ya existía con CORS correcto]
api/pricing_save.php      [OK - ya existía con CORS correcto]
api/pricing_config.php    [OK - configuración base]
api/pricing_engine.php    [OK - motor de cálculo]
```

---

## 🎯 CRITERIOS DE ACEPTACIÓN

- [x] El botón de Precios Dinámicos carga sin error
- [x] El toggle cambia de estado sin mostrar "Error al cambiar estado"
- [x] La llamada retorna 200 OK con JSON válido
- [x] No hay errores de CORS en consola
- [x] Con toggle ON, el motor aplica reglas (verificado en POS)
- [x] No se modificó DB ni lógica de negocio
- [x] Archivos < 300 LOC con header comments

---

## 🚀 PASOS DE ROLLBACK (SI NECESARIO)

Si el fix causara problemas, revertir:

```bash
# 1. Revertir cambios en PricingQuickPanel.jsx
git checkout src/components/productos/PricingQuickPanel.jsx

# 2. O manualmente: quitar "/api/" de las 3 rutas
# Líneas 22, 27, 45, 69
```

---

## 📌 RECOMENDACIONES FUTURAS

### 1️⃣ Centralizar rutas de API
Agregar a `config.js`:
```javascript
API_ENDPOINTS: {
  PRICING_CONTROL: '/api/pricing_control.php',
  PRICING_SAVE: '/api/pricing_save.php',
}
```

### 2️⃣ Usar cliente HTTP centralizado
```javascript
// src/services/pricingService.js
export const getPricingStatus = () => 
  fetch(`${CONFIG.API_URL}/api/pricing_control.php?action=status`);
```

### 3️⃣ Estrategia de estado para PROD
- Archivo JSON temporal en `/api/cache/`
- O flag en tabla `configuracion` de DB
- Evitar escritura de archivos PHP desde web

### 4️⃣ Agregar loading states
- Skeleton mientras carga
- Toast notifications en cambios
- Confirmación antes de toggle

---

## 📞 SOPORTE

**Contacto:** Equipo Tayrona Almacén  
**Archivo de log:** `api/logs/pricing_adjustments.log`  
**Docs:** `docs/DYNAMIC_PRICING_SYSTEM.md`

---

✅ **FIX COMPLETADO Y VERIFICADO**  
✅ **Sistema 100% funcional**  
✅ **Sin cambios en DB ni lógica de negocio**  
✅ **Archivos < 300 LOC**

