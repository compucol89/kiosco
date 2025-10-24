#!/bin/bash

# ========================================
# File: fix_auth_production.sh
# Script para diagnosticar y corregir problemas de autenticación en producción
# Exists to automate the detection and fix of non-bcrypt passwords
# Related files: api/diagnostico_auth_completo.php, SOLUCION_CREDENCIALES_INVALIDAS.md
# ========================================

set -e

echo "🔍 DIAGNÓSTICO DE AUTENTICACIÓN - PRODUCCIÓN"
echo "==========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuración
SERVER_URL="http://148.230.72.12/kiosco"
DIAGNOSTIC_ENDPOINT="${SERVER_URL}/api/diagnostico_auth_completo.php"

# ========================================
# PASO 1: EJECUTAR DIAGNÓSTICO
# ========================================

echo "📋 PASO 1: Ejecutando diagnóstico completo..."
echo ""

DIAGNOSTIC_RESULT=$(curl -s "$DIAGNOSTIC_ENDPOINT")

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error: No se pudo conectar al servidor${NC}"
    echo "Verifica que el archivo diagnostico_auth_completo.php esté en api/"
    exit 1
fi

echo "$DIAGNOSTIC_RESULT" > diagnostico_result.json
echo -e "${GREEN}✅ Diagnóstico completado${NC}"
echo "Resultado guardado en: diagnostico_result.json"
echo ""

# ========================================
# PASO 2: ANALIZAR RESULTADO
# ========================================

echo "📊 PASO 2: Analizando resultado..."
echo ""

# Verificar si hay usuarios sin bcrypt
TOTAL_USUARIOS=$(echo "$DIAGNOSTIC_RESULT" | grep -o '"total_usuarios":[0-9]*' | cut -d: -f2)
BCRYPT_USUARIOS=$(echo "$DIAGNOSTIC_RESULT" | grep -o '"bcrypt_usuarios":[0-9]*' | cut -d: -f2)

echo "Total usuarios: $TOTAL_USUARIOS"
echo "Usuarios con bcrypt: $BCRYPT_USUARIOS"
echo ""

if [ "$BCRYPT_USUARIOS" -lt "$TOTAL_USUARIOS" ]; then
    USUARIOS_PROBLEMA=$((TOTAL_USUARIOS - BCRYPT_USUARIOS))
    echo -e "${RED}❌ PROBLEMA DETECTADO${NC}"
    echo "Hay $USUARIOS_PROBLEMA usuario(s) SIN contraseña bcrypt"
    echo ""
    echo "Revisa el archivo diagnostico_result.json para más detalles"
    echo ""
    echo "=========================================="
    echo "SOLUCIÓN RECOMENDADA:"
    echo "=========================================="
    echo ""
    echo "1. Generar hash bcrypt con PHP:"
    echo ""
    echo "   php -r \"echo password_hash('TuPasswordReal', PASSWORD_BCRYPT);\""
    echo ""
    echo "2. Actualizar en base de datos:"
    echo ""
    echo "   UPDATE usuarios"
    echo "   SET password = '\$2y\$10\$HASH_GENERADO...'"
    echo "   WHERE username = 'admin';"
    echo ""
    echo "3. O usar el script automatizado:"
    echo ""
    echo "   Editar: api/rehash_passwords_seguro.php"
    echo "   Ejecutar: curl ${SERVER_URL}/api/rehash_passwords_seguro.php"
    echo ""
    echo "Consulta SOLUCION_CREDENCIALES_INVALIDAS.md para más detalles"
    echo ""
    exit 1
else
    echo -e "${GREEN}✅ TODOS LOS USUARIOS TIENEN BCRYPT${NC}"
    echo ""
    echo "Las contraseñas están correctamente hasheadas."
    echo "El problema de autenticación debe estar en otro lado."
    echo ""
    echo "Verifica:"
    echo "- Frontend apunta al URL correcto"
    echo "- CORS configurado correctamente"
    echo "- Logs de Apache/PHP para más detalles"
    echo ""
fi

# ========================================
# PASO 3: MOSTRAR RECOMENDACIONES
# ========================================

echo "📌 RECOMENDACIONES DEL SISTEMA:"
echo ""

echo "$DIAGNOSTIC_RESULT" | grep -A 20 '"recomendaciones"' || echo "No hay recomendaciones adicionales"

echo ""
echo "=========================================="
echo "Para ver el reporte completo:"
echo "cat diagnostico_result.json | python3 -m json.tool"
echo "=========================================="

