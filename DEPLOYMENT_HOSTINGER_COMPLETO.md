# 🚀 DEPLOYMENT A HOSTINGER - GUÍA COMPLETA

## ✅ PREREQUISITOS

- ✅ Build compilado (carpeta `build/`)
- ✅ Base de datos exportada (desde HeidiSQL)
- ✅ Acceso a cPanel de Hostinger
- ✅ Dominio configurado

---

## 📋 PASO 1: EXPORTAR BASE DE DATOS (5 minutos)

### En HeidiSQL:
1. Click derecho en `kiosco_db`
2. **Export database as SQL**
3. Opciones:
   - ✅ Data: INSERT
   - ✅ Create tables
   - ✅ Drop tables (opcional)
4. Guardar como: `kiosco_db_backup.sql`

---

## 🌐 PASO 2: CONFIGURAR HOSTINGER (10 minutos)

### 2.1 Crear Base de Datos:
1. Login a Hostinger → **cPanel**
2. **MySQL Databases**
3. **Create New Database**
   - Nombre: `kiosco` (Hostinger agregará prefijo automáticamente)
   - Resultado: `u123456_kiosco`
4. **Create New User**
   - Usuario: `admin`
   - Password: (genera una segura)
   - Resultado: `u123456_admin`
5. **Add User to Database**
   - Seleccionar usuario y BD
   - **All Privileges** ✅

### 2.2 Importar Base de Datos:
1. **phpMyAdmin** en cPanel
2. Seleccionar tu BD (`u123456_kiosco`)
3. **Import**
4. Choose File → `kiosco_db_backup.sql`
5. **Go** → Esperar (puede tardar 1-2 min)
6. ✅ Verificar que las tablas se crearon

---

## 📤 PASO 3: SUBIR ARCHIVOS (15 minutos)

### Opción A - File Manager (Recomendado):

1. cPanel → **File Manager**
2. Ir a `public_html`
3. **Upload**
4. Comprimir primero en tu PC:
   - Carpeta `build/` → Renombrar archivos
   - Carpeta `api/`
   - Carpeta `vendor/` (composer)
   - Carpeta `uploads/`
   - Archivo `.htaccess`

5. Subir ZIP y **Extract**

### Opción B - FTP (FileZilla):
```
Host: ftp.tudominio.com
Usuario: tu_usuario_hostinger  
Password: tu_password
Puerto: 21
```

**Carpetas a subir:**
- `/api` → `/public_html/api`
- `/build/*` → `/public_html/` (archivos sueltos del build)
- `/vendor` → `/public_html/vendor`
- `/uploads` → `/public_html/uploads`

**NO subir:**
- `node_modules/`
- `src/` (código fuente React)
- `.git/`
- archivos de desarrollo

---

## ⚙️ PASO 4: CONFIGURAR CREDENCIALES (2 minutos)

### En File Manager de Hostinger:

1. Abrir: `public_html/api/db_config.php`
2. **Editar** y cambiar:

```php
// Comentar configuración local
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'kiosco_db');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// Descomentar y configurar Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_kiosco');     // ⚠️ TU BD
define('DB_USER', 'u123456_admin');      // ⚠️ TU USUARIO
define('DB_PASS', 'TU_PASSWORD_AQUI');   // ⚠️ TU PASSWORD
define('DB_CHARSET', 'utf8mb4');
```

3. **Guardar**

---

## 🧪 PASO 5: PROBAR (5 minutos)

### 5.1 Test de Conexión:
```
https://tudominio.com/api/test_conexion_unificada.php
```

Debería mostrar:
- ✅ Conexión exitosa
- ✅ Tablas encontradas: 48

### 5.2 Test de Login:
```
https://tudominio.com
```

Login con:
- Usuario: `admin`
- Password: `admin123` (o tu password configurado)

### 5.3 Verificar Módulos:
- ✅ Dashboard carga
- ✅ Punto de Venta funciona
- ✅ Análisis muestra datos
- ✅ Control de Caja operativo

---

## 🔧 PASO 6: CONFIGURAR .htaccess (Importante)

Crear/verificar `.htaccess` en `public_html`:

```apache
# React Router - SPA
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # No reescribir archivos/directorios existentes
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  
  # No reescribir rutas de API
  RewriteCond %{REQUEST_URI} !^/api/
  
  # Redirigir todo a index.html (React)
  RewriteRule . /index.html [L]
</IfModule>

# Seguridad
<Files .htaccess>
  Order allow,deny
  Deny from all
</Files>

# PHP Settings
php_value upload_max_filesize 20M
php_value post_max_size 50M
php_value memory_limit 256M
php_value max_execution_time 300
```

---

## 📋 CHECKLIST FINAL

Antes de declarar éxito, verificar:

- [ ] Base de datos importada (phpMyAdmin muestra tablas)
- [ ] Archivos subidos (public_html tiene api/, index.html, etc)
- [ ] db_config.php configurado con credenciales Hostinger
- [ ] Test de conexión funciona
- [ ] Login funciona
- [ ] Dashboard carga
- [ ] Puede hacer una venta de prueba
- [ ] AFIP genera CAE (si configuraste certificados)

---

## 🎯 TIEMPO TOTAL ESTIMADO: 30-40 minutos

## 🆘 TROUBLESHOOTING

### Error 500:
- Verificar permisos: `chmod 755` carpetas, `chmod 644` archivos
- Revisar error_log en cPanel

### No carga frontend:
- Verificar que `index.html` esté en `public_html`
- Verificar `.htaccess`

### Error de conexión BD:
- Verificar credenciales en `db_config.php`
- Verificar que usuario tenga permisos

---

**¿Estás listo para empezar?** 🚀

Dime cuando hayas:
1. Exportado la BD desde HeidiSQL
2. Y te guío con los siguientes pasos





