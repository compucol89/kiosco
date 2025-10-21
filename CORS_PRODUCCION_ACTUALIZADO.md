# ✅ CORS + API_URL ACTUALIZADOS PARA PRODUCCIÓN

**Fecha:** 21/10/2025  
**Dominio de producción:** `http://148.230.72.12`  
**Base API:** `http://148.230.72.12/kiosco`  
**Estado:** ✅ Cambios aplicados

---

## 📊 CAMBIOS REALIZADOS

### 1️⃣ CORS Whitelist Actualizada

**Archivo:** `api/cors_middleware.php` (líneas 10-16)

**ANTES:**
```php
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:3000',
    'https://tudominio.com',          // ❌ Placeholder
    'https://www.tudominio.com'       // ❌ Placeholder
];
```

**DESPUÉS:**
```php
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:3000',
    'http://148.230.72.12',           // ✅ Producción HTTP
    'https://148.230.72.12'           // ✅ Producción HTTPS
];
```

**Diff:**
```diff
- 'https://tudominio.com',
- 'https://www.tudominio.com'
+ 'http://148.230.72.12',
+ 'https://148.230.72.12'
```

---

### 2️⃣ API_URL Corregida

**Archivo:** `src/config/config.js` (línea 5)

**ANTES:**
```javascript
API_URL: process.env.NODE_ENV === 'production' 
  ? window.location.origin  // ❌ Falta /kiosco
  : 'http://localhost/kiosco'
```

**DESPUÉS:**
```javascript
API_URL: process.env.NODE_ENV === 'production' 
  ? window.location.origin + '/kiosco'  // ✅ Incluye /kiosco
  : 'http://localhost/kiosco'
```

**Diff:**
```diff
- ? window.location.origin
+ ? window.location.origin + '/kiosco'
```

**Resultado en producción:**
- `window.location.origin` = `http://148.230.72.12`
- `CONFIG.API_URL` = `http://148.230.72.12/kiosco` ✅

---

## 🧪 TEST PLAN

### Test 1: Verificar CORS (Preflight OPTIONS)

**Desde terminal del servidor:**

```bash
curl -X OPTIONS \
  -H "Origin: http://148.230.72.12" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -i \
  http://148.230.72.12/kiosco/api/auth.php
```

**Resultado esperado:**
```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://148.230.72.12
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Accept, Content-Type, Authorization, ...
```

**✅ Si ves `Access-Control-Allow-Origin: http://148.230.72.12` → CORS OK**

---

### Test 2: Login desde Producción

**Navegador → Abrir:** `http://148.230.72.12/kiosco`

**Consola del navegador (F12):**

```javascript
// 1. Verificar API_URL
console.log('API_URL:', window.location.origin + '/kiosco');
// Debe mostrar: "API_URL: http://148.230.72.12/kiosco"

// 2. Test CORS con fetch
fetch('http://148.230.72.12/kiosco/api/auth.php', {
  method: 'OPTIONS',
  headers: {
    'Origin': 'http://148.230.72.12'
  }
}).then(r => console.log('CORS OK:', r.ok));
// Debe mostrar: "CORS OK: true"
```

**Login manual:**
1. Usuario: `admin`
2. Password: (tu contraseña)
3. Click "Iniciar Sesión"

**Resultado esperado:**
- ✅ Sin errores CORS en consola
- ✅ Request a `http://148.230.72.12/kiosco/api/auth.php` → 200 OK
- ✅ Redirect al Dashboard

---

### Test 3: Verificar Network Requests

**Abrir DevTools → Network → Intentar login**

**Request esperado:**
```
Request URL: http://148.230.72.12/kiosco/api/auth.php
Request Method: POST
Status Code: 200 OK

Request Headers:
  Origin: http://148.230.72.12
  Content-Type: application/json

Response Headers:
  Access-Control-Allow-Origin: http://148.230.72.12
  Content-Type: application/json
```

**✅ Si Status = 200 y response tiene `success: true` → Login OK**

---

## 🔄 ROLLBACK (Si algo falla)

### Revertir CORS:

**Archivo:** `api/cors_middleware.php`

```php
// Volver a la versión anterior:
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:3000',
    'https://tudominio.com',
    'https://www.tudominio.com'
];
```

**O con git:**
```bash
git checkout api/cors_middleware.php
```

---

### Revertir API_URL:

**Archivo:** `src/config/config.js`

```javascript
// Volver a:
API_URL: process.env.NODE_ENV === 'production' 
  ? window.location.origin
  : 'http://localhost/kiosco'
```

**O con git:**
```bash
git checkout src/config/config.js
```

**⚠️ IMPORTANTE:** Si reviertes `config.js`, necesitas rebuild del frontend:
```bash
npm run build
```

---

## 📋 CHECKLIST DE DESPLIEGUE

- [x] Actualizar `api/cors_middleware.php` con IP de producción
- [x] Actualizar `src/config/config.js` con `/kiosco`
- [ ] **Rebuild del frontend** (necesario porque cambió `config.js`):
  ```bash
  npm run build
  ```
- [ ] Subir archivos al servidor:
  - `api/cors_middleware.php` (backend)
  - `build/*` (frontend rebuild)
- [ ] Test CORS con `curl` (Test 1)
- [ ] Test login desde navegador (Test 2)
- [ ] Verificar Network requests (Test 3)
- [ ] ✅ Confirmar acceso al Dashboard

---

## ⚠️ NOTA CRÍTICA: REBUILD NECESARIO

**Porque modificaste `src/config/config.js`**, el frontend necesita un rebuild:

```bash
# 1. Desde la raíz del proyecto:
npm run build

# 2. El contenido de la carpeta `build/` debe subirse al servidor
#    y reemplazar el contenido actual de `/kiosco/`
```

**Sin el rebuild, el cambio de `API_URL` NO se aplicará en producción.**

---

## 📊 RESUMEN DE CAMBIOS

| Archivo | Líneas | Cambio | Crítico |
|---------|--------|--------|---------|
| `api/cors_middleware.php` | 14-15 | Reemplazar placeholders con `http://148.230.72.12` | ✅ Sí |
| `src/config/config.js` | 5 | Agregar `/kiosco` a `API_URL` en producción | ✅ Sí |
| Frontend | N/A | **Rebuild necesario** (`npm run build`) | ✅ Sí |

---

## 🎯 RESULTADO ESPERADO

**Antes:**
- CORS bloqueaba requests desde `http://148.230.72.12`
- `API_URL` apuntaba a `http://148.230.72.12` (sin `/kiosco`)
- Login fallaba con "Failed to fetch" o error 404

**Después:**
- ✅ CORS permite `http://148.230.72.12`
- ✅ `API_URL` apunta a `http://148.230.72.12/kiosco`
- ✅ Login funciona correctamente
- ✅ Dashboard carga sin errores

---

## 📞 TROUBLESHOOTING

### Si login sigue fallando:

1. **Verificar en consola del navegador:**
   ```javascript
   // ¿API_URL es correcta?
   console.log(window.location.origin + '/kiosco');
   // Debe ser: http://148.230.72.12/kiosco
   ```

2. **Verificar en Network tab:**
   - ¿El request va a la URL correcta?
   - ¿El Status es 200 o hay error 404/403?
   - ¿Hay error CORS en consola?

3. **Verificar backend:**
   ```bash
   # ¿El archivo CORS se actualizó?
   cat api/cors_middleware.php | grep 148.230.72.12
   # Debe mostrar la línea con tu IP
   ```

4. **Limpiar cache del navegador:**
   - Ctrl+Shift+R (Windows/Linux)
   - Cmd+Shift+R (Mac)

5. **Verificar bloqueos de rate limiting:**
   ```bash
   # Eliminar archivos de bloqueo:
   rm api/cache/rate_limit_*.json
   ```

---

## ✅ CONFIRMACIÓN FINAL

Una vez que hayas:
1. ✅ Hecho `npm run build`
2. ✅ Subido `api/cors_middleware.php` al servidor
3. ✅ Subido el contenido de `build/` al servidor
4. ✅ Probado login con usuario `admin`
5. ✅ Confirmado acceso al Dashboard

**El sistema estará operativo en producción.** 🚀

---

**Archivos modificados:**
- `api/cors_middleware.php` (2 líneas)
- `src/config/config.js` (1 línea)

**Tiempo total:** ~5 minutos (+ tiempo de rebuild y deploy)

**FIN DEL REPORTE** ✅

