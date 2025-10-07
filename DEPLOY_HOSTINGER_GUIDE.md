# 🚀 GUÍA DE DEPLOYMENT EN HOSTINGER

## ✅ REQUISITOS PREVIOS
- Cuenta de Hostinger (Premium o Business)
- Acceso a cPanel
- FileZilla o cliente FTP
- Backup de tu base de datos local

---

## 📦 PASO 1: PREPARAR ARCHIVOS (Local)

### 1.1 Exportar Base de Datos
1. Abre HeidiSQL
2. Click derecho en `kiosco_db` → Export database as SQL
3. Guarda como: `kiosco_db_backup.sql`

### 1.2 Crear archivo de configuración para Hostinger
Crea: `api/bd_conexion_hostinger.php`

---

## 🌐 PASO 2: CONFIGURAR HOSTINGER

### 2.1 Crear Base de Datos
1. Login a Hostinger → cPanel
2. **MySQL Databases** → Create New Database
3. Nombre: `u123456789_kiosco` (ejemplo)
4. Create User → Username: `u123456789_admin`
5. Password: (genera una segura)
6. **Add User to Database** → All Privileges

### 2.2 Importar Base de Datos
1. **phpMyAdmin** en cPanel
2. Seleccionar tu BD creada
3. **Import** → Choose File → `kiosco_db_backup.sql`
4. Click **Go**

---

## 📤 PASO 3: SUBIR ARCHIVOS

### 3.1 Via File Manager (cPanel)
1. cPanel → **File Manager**
2. Navegar a `public_html`
3. Upload → Subir todo el proyecto (comprimido en ZIP)
4. Extract

### 3.2 Via FTP (Recomendado)
```
Host: ftp.tudominio.com
Username: tu_usuario_hostinger
Password: tu_password
Port: 21
```

Subir carpetas:
- `/api`
- `/src` (compilado)
- `/public`
- Todos los archivos raíz

---

## ⚙️ PASO 4: CONFIGURAR CONEXIÓN

Editar `api/bd_conexion.php` con datos de Hostinger:

```php
$host = 'localhost';  // Hostinger siempre es localhost
$db_name = 'u123456789_kiosco';  // Tu BD de Hostinger
$username = 'u123456789_admin';  // Tu usuario
$password = 'tu_password_seguro';
```

---

## 🧪 PASO 5: PROBAR

1. Abrir: `https://tudominio.com`
2. Login con: `admin` / tu_password
3. ✅ ¡Debería funcionar!

---

## 🔧 TROUBLESHOOTING

### Error de conexión:
- Verificar que BD existe en phpMyAdmin
- Verificar usuario tiene permisos
- Verificar credenciales en bd_conexion.php

### Error 500:
- Verificar permisos de carpetas (755)
- Verificar PHP version (8.1+)
- Revisar error_log en cPanel

### Frontend no carga:
- Verificar que index.html está en public_html
- Verificar .htaccess existe

---

## 📝 NOTAS IMPORTANTES

- Hostinger usa **localhost** como host de BD (no IP)
- El nombre de BD y usuario tienen prefijo: `u123456789_`
- PHP 8.1 está disponible (cambiar en cPanel si necesario)
- SSL gratis con Let's Encrypt incluido







