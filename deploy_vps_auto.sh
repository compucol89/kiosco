#!/bin/bash
# Script de deployment automático para VPS Ubuntu 24.04 LAMP
# Mantiene configuración idéntica a local para simplicidad

echo "============================================"
echo "🚀 DEPLOYMENT AUTOMÁTICO - TAYRONA KIOSCO POS"
echo "============================================"
echo ""
echo "Servidor: 148.230.72.12"
echo "OS: Ubuntu 24.04 + LAMP"
echo ""

# Variables
WEBROOT="/var/www/html"
DB_NAME="kiosco_db"
DB_USER="root"
DB_PASS=""  # Sin password como en local

# 1. VERIFICAR QUE ESTAMOS COMO ROOT
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Este script debe ejecutarse como root"
    echo "Ejecuta: sudo bash deploy_vps_auto.sh"
    exit 1
fi

echo "✅ Ejecutando como root"
echo ""

# 2. VERIFICAR LAMP STACK
echo "[1/10] Verificando LAMP Stack..."
php -v > /dev/null 2>&1 && echo "   ✅ PHP instalado" || echo "   ❌ PHP no encontrado"
mysql --version > /dev/null 2>&1 && echo "   ✅ MySQL instalado" || echo "   ❌ MySQL no encontrado"
apache2 -v > /dev/null 2>&1 && echo "   ✅ Apache instalado" || echo "   ❌ Apache no encontrado"
echo ""

# 3. HABILITAR MÓDULOS APACHE
echo "[2/10] Configurando Apache..."
a2enmod rewrite > /dev/null 2>&1
a2enmod headers > /dev/null 2>&1
a2enmod expires > /dev/null 2>&1
systemctl restart apache2
echo "   ✅ Módulos habilitados"
echo ""

# 4. CREAR BASE DE DATOS (IDÉNTICA A LOCAL)
echo "[3/10] Creando base de datos..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ Base de datos '$DB_NAME' creada"
else
    echo "   ⚠️ BD ya existe o error (continuando...)"
fi
echo ""

# 5. IMPORTAR SQL (si existe en /root/)
echo "[4/10] Importando base de datos..."
if [ -f "/root/kiosco_db.sql" ]; then
    mysql -u root $DB_NAME < /root/kiosco_db.sql 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "   ✅ SQL importado exitosamente"
    else
        echo "   ❌ Error importando SQL"
    fi
else
    echo "   ⚠️ Archivo /root/kiosco_db.sql no encontrado"
    echo "   💡 Súbelo con: scp kiosco_db.sql root@148.230.72.12:/root/"
fi
echo ""

# 6. LIMPIAR WEBROOT
echo "[5/10] Preparando directorio web..."
rm -rf $WEBROOT/*
echo "   ✅ Directorio limpiado"
echo ""

# 7. EXTRAER ARCHIVOS (si existe ZIP en /root/)
echo "[6/10] Instalando archivos del sistema..."
if [ -f "/root/kiosco.zip" ]; then
    unzip -q /root/kiosco.zip -d $WEBROOT
    echo "   ✅ Archivos extraídos"
elif [ -d "/root/deploy_hostinger" ]; then
    cp -r /root/deploy_hostinger/* $WEBROOT/
    echo "   ✅ Archivos copiados desde deploy_hostinger"
else
    echo "   ⚠️ No se encontró /root/kiosco.zip"
    echo "   💡 Súbelo con: scp kiosco.zip root@148.230.72.12:/root/"
fi
echo ""

# 8. CONFIGURAR PERMISOS
echo "[7/10] Configurando permisos..."
chown -R www-data:www-data $WEBROOT
chmod -R 755 $WEBROOT
mkdir -p $WEBROOT/uploads
mkdir -p $WEBROOT/api/logs
mkdir -p $WEBROOT/api/queue
chmod -R 777 $WEBROOT/uploads
chmod -R 777 $WEBROOT/api/logs
chmod -R 777 $WEBROOT/api/queue
echo "   ✅ Permisos configurados"
echo ""

# 9. VERIFICAR db_config.php (ya debería estar correcto)
echo "[8/10] Verificando configuración de BD..."
if [ -f "$WEBROOT/api/db_config.php" ]; then
    echo "   ✅ db_config.php encontrado"
    echo "   📋 Configuración:"
    grep "define('DB_" $WEBROOT/api/db_config.php | head -5
else
    echo "   ❌ db_config.php no encontrado"
fi
echo ""

# 10. CONFIGURAR VIRTUAL HOST
echo "[9/10] Configurando Apache Virtual Host..."
cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # React Router SPA
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_URI} !^/api/
        RewriteRule . /index.html [L]
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

systemctl restart apache2
echo "   ✅ Apache configurado"
echo ""

# 11. VERIFICAR INSTALACIÓN
echo "[10/10] Verificando instalación..."
if [ -f "$WEBROOT/index.html" ]; then
    echo "   ✅ Frontend instalado"
fi

if [ -f "$WEBROOT/api/bd_conexion.php" ]; then
    echo "   ✅ API instalado"
fi

# Verificar BD
TABLES=$(mysql -u root $DB_NAME -e "SHOW TABLES;" 2>/dev/null | wc -l)
if [ $TABLES -gt 1 ]; then
    echo "   ✅ Base de datos OK ($((TABLES-1)) tablas)"
else
    echo "   ⚠️ Base de datos vacía o error"
fi

echo ""
echo "============================================"
echo "✅ DEPLOYMENT COMPLETADO"
echo "============================================"
echo ""
echo "🌐 Accede a tu sistema:"
echo "   http://148.230.72.12"
echo ""
echo "🔐 Login:"
echo "   Usuario: admin"
echo "   Password: admin123"
echo ""
echo "📊 Test de conexión:"
echo "   http://148.230.72.12/api/test_conexion_unificada.php"
echo ""





