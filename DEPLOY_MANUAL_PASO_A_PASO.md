# 📋 DESPLIEGUE MANUAL PASO A PASO

**Servidor:** `148.230.72.12`  
**Aplicación:** Kiosco POS  
**Tiempo estimado:** 15-20 minutos

---

## 🎯 MÉTODOS DISPONIBLES

Elige el que prefieras:

### Método A: FTP/SFTP (FileZilla) ⭐ RECOMENDADO
### Método B: Panel de Control (cPanel/Plesk)
### Método C: SSH/SCP (Terminal)

---

## 📦 MÉTODO A: FTP/SFTP CON FILEZILLA

### Paso 1: Conectar al Servidor

1. Abrir **FileZilla** (o tu cliente FTP favorito)
2. Configurar conexión:
   ```
   Host:     148.230.72.12
   Usuario:  [tu_usuario_ftp]
   Password: [tu_password]
   Puerto:   21 (FTP) o 22 (SFTP)
   ```
3. Click **Conexión Rápida**

### Paso 2: Navegar a la Carpeta Correcta

**En el servidor (panel derecho):**
```
/ → public_html → kiosco
```

O la ruta que uses (puede ser `/home/usuario/kiosco` o similar)

### Paso 3: Subir Backend (API)

**En tu PC (panel izquierdo):**
```
C:\laragon\www\kiosco\api
```

**Arrastrar TODO el contenido de `api/` a la carpeta `kiosco/api/` del servidor**

**Archivos críticos:**
- ✅ `cors_middleware.php` (actualizado con IP)
- ✅ `pricing_engine.php`
- ✅ `pricing_config.php`
- ✅ `pricing_control.php`
- ✅ `pricing_save.php`
- ✅ `auth.php`
- ✅ `bd_conexion.php`
- ✅ Todos los demás `.php`

### Paso 4: Subir Frontend (Build)

**En tu PC (panel izquierdo):**
```
C:\laragon\www\kiosco\build
```

**Arrastrar TODO el contenido DENTRO de `build/` a la carpeta `kiosco/` del servidor**

**NO arrastres la carpeta `build` completa, solo su contenido:**
- ✅ `index.html`
- ✅ `static/` (carpeta completa)
- ✅ `asset-manifest.json`
- ✅ `manifest.json`
- ✅ Todos los archivos de la raíz de `build/`

### Paso 5: Configurar Permisos

**Click derecho en la carpeta `api/cache` → Permisos del archivo**

Configurar:
```
Permisos numéricos: 777
o
rwxrwxrwx (todos los checks marcados)
```

### Paso 6: Verificar

Abrir en navegador:
```
http://148.230.72.12/kiosco
```

**Debe cargar la pantalla de login** ✅

---

## 📦 MÉTODO B: PANEL DE CONTROL (cPanel/Plesk)

### Paso 1: Acceder al Administrador de Archivos

1. Abrir `http://148.230.72.12:2083` (cPanel) o el panel que uses
2. Login con tus credenciales
3. Click en **Administrador de Archivos**

### Paso 2: Navegar a tu Carpeta Web

```
public_html/kiosco
```

### Paso 3: Comprimir y Subir

**En tu PC:**

1. **Comprimir `api/`:**
   ```
   Click derecho en carpeta api/ → Comprimir → api.zip
   ```

2. **Comprimir contenido de `build/`:**
   ```
   Seleccionar TODO dentro de build/ → Comprimir → frontend.zip
   ```

**En el servidor:**

3. **Subir `api.zip`:**
   - Click **Cargar** en el administrador
   - Seleccionar `api.zip`
   - Esperar upload
   - Click derecho en `api.zip` → **Extraer**
   - Eliminar `api.zip`

4. **Subir `frontend.zip`:**
   - Click **Cargar**
   - Seleccionar `frontend.zip`
   - Esperar upload
   - Click derecho → **Extraer**
   - Eliminar `frontend.zip`

### Paso 4: Permisos

- Click derecho en `api/cache` → **Cambiar permisos** → `777`

---

## 📦 MÉTODO C: SSH/SCP (Terminal)

### Si tienes acceso SSH:

```bash
# 1. Comprimir archivos localmente
cd C:\laragon\www\kiosco
tar -czf backend.tar.gz api/
cd build && tar -czf ../frontend.tar.gz * && cd ..

# 2. Subir al servidor
scp backend.tar.gz tu_usuario@148.230.72.12:/ruta/a/kiosco/
scp frontend.tar.gz tu_usuario@148.230.72.12:/ruta/a/kiosco/

# 3. Conectar por SSH
ssh tu_usuario@148.230.72.12

# 4. Descomprimir en el servidor
cd /ruta/a/kiosco
tar -xzf backend.tar.gz
tar -xzf frontend.tar.gz
rm backend.tar.gz frontend.tar.gz

# 5. Permisos
chmod -R 755 .
chmod -R 777 api/cache

# 6. Salir
exit
```

---

## ✅ VALIDACIÓN POST-DESPLIEGUE

### Test 1: Verificar que cargue el sitio

```
http://148.230.72.12/kiosco
```

**Debe mostrar:** Pantalla de login de Tayrona Almacén ✅

### Test 2: Verificar API

```
http://148.230.72.12/kiosco/api/auth.php
```

**Debe mostrar:** Mensaje JSON (no HTML) ✅

### Test 3: Verificar CORS (Consola del navegador)

**F12 → Console → Ejecutar:**

```javascript
fetch('http://148.230.72.12/kiosco/api/auth.php', {
  method: 'OPTIONS'
}).then(r => console.log('CORS:', r.ok ? '✅ OK' : '❌ Error'));
```

### Test 4: Login Real

1. Usuario: `admin`
2. Password: tu contraseña
3. Click "Iniciar Sesión"

**Debe acceder al Dashboard** ✅

### Test 5: Dynamic Pricing

1. Ir al POS
2. Buscar "cerveza" o "fernet"
3. Si estás en horario (vie/sáb 18:00-23:59) o usas simulador:
   ```
   http://148.230.72.12/kiosco/pos?__sim=2025-10-25T19:30:00
   ```
4. **Debe mostrar badge [+10%]** ✅

---

## ⚠️ TROUBLESHOOTING

### Problema 1: Página en blanco

**Causa:** Frontend no se subió correctamente

**Solución:**
- Verificar que `index.html` esté en la raíz de `kiosco/`
- Verificar que carpeta `static/` exista con archivos `.js` y `.css`

### Problema 2: Error 404 en API

**Causa:** Backend no se subió o ruta incorrecta

**Solución:**
- Verificar que `api/auth.php` exista en servidor
- URL correcta: `http://148.230.72.12/kiosco/api/auth.php`

### Problema 3: Error CORS

**Causa:** `cors_middleware.php` no actualizado o no se subió

**Solución:**
- Verificar que `api/cors_middleware.php` contenga: `'http://148.230.72.12'`
- Re-subir el archivo

### Problema 4: Error 500 Internal Server Error

**Causa:** Permisos incorrectos o error PHP

**Solución:**
- Configurar `api/cache/` con permisos `777`
- Verificar logs de PHP: `api/logs/error.log`
- Verificar `api/bd_config.php` con credenciales correctas de BD

### Problema 5: Login no funciona

**Causa:** Base de datos no conecta

**Solución:**
- Editar `api/bd_config.php` con credenciales de producción:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'nombre_bd_produccion');
  define('DB_USER', 'usuario_bd_produccion');
  define('DB_PASS', 'password_bd_produccion');
  ```

---

## 📋 CHECKLIST COMPLETO

- [ ] Conectar a servidor (FTP/SSH/Panel)
- [ ] Subir carpeta `api/` completa
- [ ] Subir contenido de `build/` (index.html, static/, etc.)
- [ ] Configurar permisos `777` en `api/cache/`
- [ ] Verificar `api/bd_config.php` con credenciales correctas
- [ ] Test: Abrir `http://148.230.72.12/kiosco`
- [ ] Test: Login con usuario `admin`
- [ ] Test: Acceso al Dashboard
- [ ] Test: Dynamic pricing en POS
- [ ] ✅ Sistema funcionando en producción

---

## 🎯 ARCHIVOS CRÍTICOS A VERIFICAR

**Backend:**
```
api/cors_middleware.php     ← DEBE tener IP 148.230.72.12
api/bd_config.php           ← DEBE tener credenciales de producción
api/pricing_config.php      ← Configuración de precios dinámicos
api/cache/                  ← Permisos 777
```

**Frontend:**
```
index.html                  ← Punto de entrada
static/js/main.*.js         ← JavaScript compilado
static/css/main.*.css       ← CSS compilado
```

---

**Tiempo total estimado:** 15-20 minutos  
**Dificultad:** Media

**¿Problemas?** Comparte el mensaje de error exacto y te ayudo a solucionarlo. 🚀

