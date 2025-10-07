# 🚀 DEPLOYMENT EN VPS UBUNTU 22.04 (LAMP)

## ✅ LO QUE YA TIENES LISTO:

1. ✅ `kiosco_db.sql` (en Desktop) - 308 KB
2. ✅ `deploy_hostinger\` (archivos listos)
3. ✅ Build compilado
4. ✅ Certificados AFIP

---

## 📦 PASO 1: COMPRIMIR ARCHIVOS (En tu PC)

```bash
# En Windows, comprimir manualmente:
C:\laragon\www\kiosco\deploy_hostinger\ → Click derecho → Comprimir → kiosco.zip
C:\Users\harol\Desktop\kiosco_db.sql → Tener listo
```

---

## 🔐 PASO 2: CONECTAR VIA SSH

```bash
ssh usuario@tu-servidor-ip
# O
ssh usuario@tudominio.com
```

---

## 🛠️ PASO 3: PREPARAR SERVIDOR (Una sola vez)

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar herramientas necesarias
sudo apt install unzip curl wget -y

# Verificar LAMP Stack
php -v          # Debe mostrar PHP 8.1+
mysql --version # Debe mostrar MySQL
apache2 -v      # Debe mostrar Apache

# Habilitar módulos Apache necesarios
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

---

## 🗄️ PASO 4: CREAR BASE DE DATOS

```bash
# Entrar a MySQL
sudo mysql

# Dentro de MySQL, ejecutar:
CREATE DATABASE kiosco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kiosco_admin'@'localhost' IDENTIFIED BY 'TuPasswordSeguro123!';
GRANT ALL PRIVILEGES ON kiosco_db.* TO 'kiosco_admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 📤 PASO 5: SUBIR ARCHIVOS

### Opción A - SCP (Desde tu PC Windows):

```bash
# Subir ZIP
scp C:\laragon\www\kiosco\deploy_hostinger.zip usuario@servidor:/home/usuario/

# Subir SQL
scp C:\Users\harol\Desktop\kiosco_db.sql usuario@servidor:/home/usuario/
```

### Opción B - SFTP (FileZilla):
- Host: sftp://tudominio.com
- Usuario: tu_usuario_ssh
- Password: tu_password_ssh
- Puerto: 22

Subir:
- `kiosco.zip` → `/home/usuario/`
- `kiosco_db.sql` → `/home/usuario/`

---

## 🔧 PASO 6: INSTALAR EN SERVIDOR (Via SSH)

```bash
# Ir al directorio web
cd /var/www/html

# Limpiar si hay archivos
sudo rm -rf *

# Extraer archivos
sudo unzip ~/kiosco.zip -d /var/www/html/

# Configurar permisos
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 777 /var/www/html/uploads
sudo chmod -R 777 /var/www/html/api/logs

# Crear directorios necesarios
sudo mkdir -p /var/www/html/api/logs
sudo mkdir -p /var/www/html/uploads
```

---

## 📊 PASO 7: IMPORTAR BASE DE DATOS

```bash
# Importar SQL
mysql -u kiosco_admin -p kiosco_db < ~/kiosco_db.sql

# Verificar importación
mysql -u kiosco_admin -p -e "USE kiosco_db; SHOW TABLES;"
```

---

## ⚙️ PASO 8: CONFIGURAR CREDENCIALES

```bash
# Editar configuración
sudo nano /var/www/html/api/db_config.php
```

Cambiar líneas 18-22:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'kiosco_admin');
define('DB_PASS', 'TuPasswordSeguro123!');
```

Guardar: `Ctrl+O`, `Enter`, `Ctrl+X`

---

## 🧪 PASO 9: PROBAR INSTALACIÓN

```bash
# Test de conexión
curl http://localhost/api/test_conexion_unificada.php

# Debería mostrar:
# ✅ Conexión exitosa
# ✅ Tablas: 44
```

---

## 🌐 PASO 10: CONFIGURAR APACHE

```bash
# Editar configuración de sitio
sudo nano /etc/apache2/sites-available/000-default.conf
```

Agregar dentro de `<VirtualHost *:80>`:
```apache
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Reiniciar Apache:
```bash
sudo systemctl restart apache2
```

---

## ✅ VERIFICACIÓN FINAL

Abrir navegador:
```
http://tu-servidor-ip
# O
http://tudominio.com
```

Login:
- Usuario: `admin`
- Password: `admin123`

---

## 🎯 TIEMPO TOTAL: 15-20 minutos

**¿Ya tienes los datos de conexión SSH?** Pásame:
- Host/IP
- Usuario
- Ruta del public_html

Y te creo un script automatizado que hace todo esto en 1 solo comando. 🚀





