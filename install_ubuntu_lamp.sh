#!/bin/bash
# Script de instalación automática para Ubuntu 22.04 LAMP
# Sistema: Tayrona Kiosco POS

echo "============================================"
echo "INSTALACIÓN TAYRONA KIOSCO POS"
echo "Ubuntu 22.04 + LAMP Stack"
echo "============================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables (MODIFICAR SEGÚN TU SERVIDOR)
DB_NAME="kiosco_db"
DB_USER="kiosco_admin"
DB_PASS="TuPasswordSeguro123!"  # ⚠️ CAMBIAR
DOMAIN="tudominio.com"           # ⚠️ CAMBIAR
WEBROOT="/var/www/html"

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   Base de datos: $DB_NAME"
echo "   Usuario BD: $DB_USER"
echo "   Dominio: $DOMAIN"
echo "   WebRoot: $WEBROOT"
echo ""
read -p "¿Continuar con la instalación? (s/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Instalación cancelada"
    exit 1
fi

# 1. ACTUALIZAR SISTEMA
echo -e "\n${GREEN}[1/8] Actualizando sistema...${NC}"
sudo apt update && sudo apt upgrade -y

# 2. VERIFICAR LAMP STACK
echo -e "\n${GREEN}[2/8] Verificando LAMP Stack...${NC}"
if ! command -v apache2 &> /dev/null; then
    echo "⚠️ Apache no instalado. Instalando..."
    sudo apt install apache2 -y
fi

if ! command -v mysql &> /dev/null; then
    echo "⚠️ MySQL no instalado. Instalando..."
    sudo apt install mysql-server -y
fi

if ! command -v php &> /dev/null; then
    echo "⚠️ PHP no instalado. Instalando..."
    sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd -y
fi

# 3. HABILITAR MÓDULOS APACHE
echo -e "\n${GREEN}[3/8] Configurando Apache...${NC}"
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo systemctl restart apache2

# 4. CONFIGURAR MYSQL
echo -e "\n${GREEN}[4/8] Configurando MySQL...${NC}"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo "✅ Base de datos creada: $DB_NAME"

# 5. IMPORTAR BASE DE DATOS
echo -e "\n${GREEN}[5/8] Importando base de datos...${NC}"
if [ -f "kiosco_db.sql" ]; then
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < kiosco_db.sql
    echo "✅ Base de datos importada"
else
    echo "⚠️ Archivo kiosco_db.sql no encontrado. Importar manualmente."
fi

# 6. COPIAR ARCHIVOS
echo -e "\n${GREEN}[6/8] Copiando archivos al servidor...${NC}"
sudo cp -r deploy_hostinger/* $WEBROOT/
sudo chown -R www-data:www-data $WEBROOT
sudo chmod -R 755 $WEBROOT
sudo chmod -R 777 $WEBROOT/uploads
sudo chmod -R 777 $WEBROOT/api/logs
echo "✅ Archivos copiados"

# 7. CONFIGURAR PERMISOS
echo -e "\n${GREEN}[7/8] Configurando permisos...${NC}"
sudo mkdir -p $WEBROOT/api/logs
sudo mkdir -p $WEBROOT/uploads
sudo chmod 777 $WEBROOT/api/logs
sudo chmod 777 $WEBROOT/uploads

# 8. VERIFICAR INSTALACIÓN
echo -e "\n${GREEN}[8/8] Verificando instalación...${NC}"
if [ -f "$WEBROOT/index.html" ]; then
    echo "✅ Frontend instalado"
fi

if [ -f "$WEBROOT/api/bd_conexion.php" ]; then
    echo "✅ API instalado"
fi

if sudo mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES;" | grep -q "productos"; then
    echo "✅ Base de datos OK"
fi

echo ""
echo "============================================"
echo "✅ INSTALACIÓN COMPLETADA"
echo "============================================"
echo ""
echo "🎯 PRÓXIMOS PASOS:"
echo "1. Editar: $WEBROOT/api/db_config.php"
echo "   - Cambiar DB_NAME, DB_USER, DB_PASS"
echo ""
echo "2. Probar: http://$DOMAIN"
echo "   - Usuario: admin"
echo "   - Password: admin123"
echo ""
echo "3. Verificar AFIP SDK funciona"
echo ""





