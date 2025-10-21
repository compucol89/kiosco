# 🔐 FIX: Admin Login en Producción

**Fecha:** 21/10/2025  
**Sistema:** Tayrona Almacén – Kiosco POS  
**Estado:** Diagnóstico completado ✅

---

## 📊 DIAGNÓSTICO COMPLETO

### ✅ Base de Datos - SIN PROBLEMAS

```
Usuario Admin:
- ID: 1000
- Username: admin
- Password: ✅ BCRYPT válido ($2y$10$...)
- Role: admin
- Sin bloqueos de rate limiting
```

**Conclusión:** La BD y el usuario admin están correctamente configurados. El problema NO está aquí.

---

## 🔴 CAUSAS RAÍCES IDENTIFICADAS

### 1. CORS - Whitelist de Producción (CRÍTICO)

**Archivo:** `api/cors_middleware.php`

**Problema:** Los dominios permitidos son placeholders:
```php
$allowed_origins = [
    'http://localhost:3000',
    'https://tudominio.com',      // ❌ PLACEHOLDER
    'https://www.tudominio.com'   // ❌ PLACEHOLDER
];
```

**Síntoma:** Requests del frontend en producción son bloqueados por CORS.

**Fix:** Reemplazar placeholders con dominios reales del servidor.

---

### 2. API_URL - Auto-detecta pero...

**Archivo:** `src/config/config.js`

```javascript
API_URL: process.env.NODE_ENV === 'production' 
  ? window.location.origin  // Asume frontend y API en mismo dominio
  : 'http://localhost/kiosco'
```

**Análisis:** Funciona SI frontend y backend están en el mismo dominio. Si están separados, necesita ajuste manual.

---

### 3. Verificación de Dispositivos Confiables

**Archivo:** `src/components/LoginPage.jsx`

El sistema verifica `dispositivos_confiables.php` ANTES del login. Si falla o bloquea, el login nunca llega a `auth.php`.

---

## 🔧 SOLUCIÓN COMPLETA (3 PASOS)

### PASO 1: Actualizar CORS Whitelist (5 min)

**Archivo a editar:** `api/cors_middleware.php`

**Acción:**
1. Identifica tu dominio real de producción (ej: `https://misistema.railway.app` o `https://tuempresa.com`)
2. Reemplaza los placeholders en líneas 14-15:

```php
$allowed_origins = [
    'http://localhost:3000',           // DEV
    'http://localhost',                // DEV
    'http://127.0.0.1:3000',          // DEV
    'https://TU_DOMINIO_REAL.com',    // PROD - CAMBIAR AQUÍ
    'https://www.TU_DOMINIO_REAL.com' // PROD con www - CAMBIAR AQUÍ
];
```

**Ejemplo real:**
```php
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:3000',
    'https://kiosco-tayrona.railway.app',      // Railway
    'https://www.tayrona-kiosco.com',          // Dominio custom
];
```

**Validación:**
```bash
# Desde consola del navegador en producción:
console.log(window.location.origin); // Este valor debe estar en el whitelist
```

---

### PASO 2: Verificar API_URL en Producción (2 min)

**Si frontend y backend están en dominios diferentes:**

Opción A: Variable de entorno (Recomendado)
```bash
# En Railway o servidor:
REACT_APP_API_URL=https://api.tudominio.com
```

Opción B: Ajustar `src/config/config.js`:
```javascript
API_URL: process.env.REACT_APP_API_URL 
    || (process.env.NODE_ENV === 'production' 
        ? 'https://api.tudominio.com'  // Hardcoded para prod
        : 'http://localhost/kiosco')
```

**Validación en producción:**
```javascript
// Consola del navegador:
import CONFIG from './config/config';
console.log(CONFIG.API_URL); // Debe apuntar a tu API
```

---

### PASO 3: Desactivar Verificación de Dispositivos Temporalmente (OPCIONAL)

**Si el problema persiste después de PASO 1 y 2:**

**Archivo:** `src/components/LoginPage.jsx`

**Comentar temporalmente** líneas 43-79 para saltarse la verificación de dispositivos:

```javascript
const handleSubmit = async (e) => {
  e.preventDefault();
  
  if (!username || !password) {
    setError('Por favor, ingrese usuario y contraseña.');
    return;
  }
  
  setLoading(true);
  setError(null);
  
  try {
    // 🔐 TEMPORAL: Comentar verificación de dispositivo
    /*
    const dispositivoResponse = await axios.get(...);
    if (dispositivoResponse.data && !dispositivoResponse.data.acceso_concedido) {
      // ... código de verificación
    }
    */
    
    // 🔐 Ir directo a autenticación
    const response = await axios.post(`${CONFIG.API_URL}/api/auth.php`, {
      username: username,
      password: password
    });
    
    // ... resto del código
```

**⚠️ IMPORTANTE:** Esto es temporal solo para debugging. Una vez confirmado que funciona, reactivar la verificación de dispositivos.

---

## ✅ VALIDACIÓN (2 PASOS)

### Test 1: Verificar CORS

```bash
# Desde consola del navegador en PRODUCCIÓN:
fetch('https://TU_API_URL/api/auth.php', {
  method: 'OPTIONS',
  headers: { 'Origin': window.location.origin }
}).then(r => console.log('CORS OK:', r.status === 200));
```

**Resultado esperado:** `CORS OK: true`

### Test 2: Login Real

1. Abrir app en producción
2. Ir a login
3. Ingresar:
   - Usuario: `admin`
   - Password: (la contraseña configurada)
4. Click "Iniciar Sesión"
5. **Resultado esperado:** Acceso exitoso al Dashboard

---

## 📋 CHECKLIST RÁPIDO

- [ ] Identificar dominio real de producción (`window.location.origin`)
- [ ] Actualizar `api/cors_middleware.php` con dominios reales
- [ ] Verificar `CONFIG.API_URL` apunta a la API correcta
- [ ] (Opcional) Comentar verificación de dispositivos temporalmente
- [ ] Subir cambios al servidor
- [ ] Test CORS con `fetch()` en consola
- [ ] Test login completo con usuario `admin`
- [ ] ✅ Confirmar acceso al Dashboard

---

## 🔄 ROLLBACK (Por si algo falla)

### Revertir cambios CORS:
```bash
git checkout api/cors_middleware.php
```

### Revertir cambios API_URL:
```bash
git checkout src/config/config.js
```

### Reactivar verificación de dispositivos:
Descomentar código en `LoginPage.jsx`

---

## 📊 RESUMEN EJECUTIVO

| Componente | Estado | Acción Requerida |
|------------|--------|------------------|
| **Base de Datos** | ✅ OK | Ninguna |
| **Usuario Admin** | ✅ OK | Ninguna |
| **Password Hash** | ✅ BCRYPT | Ninguna |
| **Rate Limiting** | ✅ Sin bloqueos | Ninguna |
| **CORS Whitelist** | ❌ Placeholders | **Actualizar dominios** |
| **API_URL Config** | 🟡 Verificar | Validar en producción |
| **Dispositivos** | 🟡 Puede bloquear | Temporal bypass opcional |

---

## 💡 RECOMENDACIONES POST-FIX

1. **Documentar dominios:** Mantener lista actualizada de todos los dominios (dev, staging, prod)
2. **Variables de entorno:** Usar `.env` para configuraciones que cambien por entorno
3. **Monitoreo:** Agregar logging en `api/auth.php` para detectar problemas futuros
4. **Dispositivos confiables:** Revisar flujo y considerar whitelist inicial de dispositivos admin

---

## 🎯 CAUSA RAÍZ (5 LÍNEAS)

1. ✅ Usuario admin existe y está correctamente configurado (ID:1000, bcrypt, role:admin)
2. ❌ **CORS whitelist tiene placeholders genéricos**, no dominios reales de producción
3. 🟡 API_URL auto-detecta pero puede fallar si frontend/backend en dominios separados
4. 🟡 Verificación de dispositivos confiables puede bloquear login antes de llegar a auth.php
5. ✅ Sin bloqueos de rate limiting activos

**Fix principal:** Actualizar `api/cors_middleware.php` con dominios reales (5 min).

---

**Archivo de diagnóstico:** `check_admin_status.php` (ejecutar: `php check_admin_status.php`)  
**Resultado:** Usuario admin OK, problema está en CORS/Config de producción.

---

**FIN DEL REPORTE** ✅

