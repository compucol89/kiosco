#!/bin/bash
# File: deploy_to_server.sh
# Script de despliegue automático al servidor de producción
# Exists to upload all necessary files to production server
# Related files: build/, api/, CORS_PRODUCCION_ACTUALIZADO.md

# ====================================
# CONFIGURACIÓN DEL SERVIDOR
# ====================================
SERVER_IP="148.230.72.12"
SERVER_USER="tu_usuario_ssh"  # CAMBIAR
SERVER_PATH="/home/tu_usuario/public_html/kiosco"  # CAMBIAR
SSH_PORT="22"  # Puerto SSH (cambiar si usas otro)

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}====================================${NC}"
echo -e "${GREEN}🚀 DESPLIEGUE A PRODUCCIÓN${NC}"
echo -e "${GREEN}====================================${NC}\n"

# ====================================
# VERIFICAR CONEXIÓN SSH
# ====================================
echo -e "${YELLOW}📡 Verificando conexión al servidor...${NC}"
ssh -p $SSH_PORT -o ConnectTimeout=5 $SERVER_USER@$SERVER_IP "echo 'Conexión exitosa'" 2>/dev/null

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    echo -e "${YELLOW}Verifica:${NC}"
    echo "  - Usuario SSH: $SERVER_USER"
    echo "  - IP: $SERVER_IP"
    echo "  - Puerto: $SSH_PORT"
    echo "  - Clave SSH configurada"
    exit 1
fi

echo -e "${GREEN}✅ Conexión exitosa${NC}\n"

# ====================================
# BACKUP DEL SERVIDOR (SEGURIDAD)
# ====================================
echo -e "${YELLOW}💾 Creando backup del servidor...${NC}"
BACKUP_NAME="kiosco_backup_$(date +%Y%m%d_%H%M%S).tar.gz"

ssh -p $SSH_PORT $SERVER_USER@$SERVER_IP "cd $(dirname $SERVER_PATH) && tar -czf $BACKUP_NAME $(basename $SERVER_PATH) 2>/dev/null"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Backup creado: $BACKUP_NAME${NC}\n"
else
    echo -e "${YELLOW}⚠️  No se pudo crear backup (puede que no exista el directorio)${NC}\n"
fi

# ====================================
# SUBIR BACKEND (API)
# ====================================
echo -e "${YELLOW}📤 Subiendo archivos del backend (API)...${NC}"
rsync -avz --progress \
    --exclude='cache/' \
    --exclude='logs/' \
    --exclude='*.log' \
    -e "ssh -p $SSH_PORT" \
    ./api/ \
    $SERVER_USER@$SERVER_IP:$SERVER_PATH/api/

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Backend subido correctamente${NC}\n"
else
    echo -e "${RED}❌ Error subiendo backend${NC}\n"
    exit 1
fi

# ====================================
# SUBIR FRONTEND (BUILD)
# ====================================
echo -e "${YELLOW}📤 Subiendo archivos del frontend (build)...${NC}"
rsync -avz --progress \
    --exclude='build/api' \
    -e "ssh -p $SSH_PORT" \
    ./build/ \
    $SERVER_USER@$SERVER_IP:$SERVER_PATH/

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Frontend subido correctamente${NC}\n"
else
    echo -e "${RED}❌ Error subiendo frontend${NC}\n"
    exit 1
fi

# ====================================
# VERIFICAR PERMISOS
# ====================================
echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
ssh -p $SSH_PORT $SERVER_USER@$SERVER_IP "chmod -R 755 $SERVER_PATH && chmod -R 777 $SERVER_PATH/api/cache"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Permisos configurados${NC}\n"
else
    echo -e "${YELLOW}⚠️  Advertencia: No se pudieron configurar todos los permisos${NC}\n"
fi

# ====================================
# RESUMEN
# ====================================
echo -e "${GREEN}====================================${NC}"
echo -e "${GREEN}✅ DESPLIEGUE COMPLETADO${NC}"
echo -e "${GREEN}====================================${NC}\n"

echo "🌐 URL de producción: http://$SERVER_IP/kiosco"
echo "📁 Ruta en servidor: $SERVER_PATH"
echo "💾 Backup creado: $BACKUP_NAME"
echo ""
echo "🧪 Próximos pasos:"
echo "1. Probar login: http://$SERVER_IP/kiosco"
echo "2. Usuario: admin"
echo "3. Verificar dynamic pricing en POS"
echo ""
echo -e "${YELLOW}⚠️  Recuerda:${NC}"
echo "- Configurar permisos de api/cache si hay errores"
echo "- Verificar api/bd_config.php con credenciales de producción"
echo "- Activar dynamic pricing en api/pricing_config.php si lo deseas"

exit 0

