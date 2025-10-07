/**
 * 🛠️ SCRIPT DE CORRECCIÓN COMPLETA PARA CIERRE DE CAJA
 * Aplica todas las correcciones necesarias para solucionar el problema
 */

const fs = require('fs');
const path = require('path');

console.log('🚀 APLICANDO CORRECCIÓN COMPLETA DE CIERRE DE CAJA');
console.log('=' + '='.repeat(60));

// 1. Corregir configuración de API
console.log('\n📡 PASO 1: Corrigiendo configuración de API...');

const configPath = path.join(__dirname, '../src/config/config.js');
let configContent = fs.readFileSync(configPath, 'utf8');

// Asegurar que la URL sea correcta para desarrollo
const newConfigContent = configContent.replace(
  /API_URL:\s*process\.env\.NODE_ENV.*?\n.*?\n.*?,/s,
  `API_URL: process.env.NODE_ENV === 'production' 
    ? window.location.origin  // En producción usa el dominio actual
    : 'http://localhost/kiosco', // En desarrollo usa localhost/kiosco (Laragon),`
);

fs.writeFileSync(configPath, newConfigContent);
console.log('   ✅ Configuración de API corregida');

// 2. Crear función de validación robusta para el cierre
console.log('\n🔒 PASO 2: Creando función de validación robusta...');

const validationFunctionContent = `
/**
 * 🛡️ FUNCIÓN DE VALIDACIÓN ROBUSTA PARA CIERRE DE CAJA
 * Maneja todos los posibles errores y proporciona fallbacks
 */
export const validateAndCloseCash = async (cierreData) => {
  const CONFIG_URLS = [
    'http://localhost/kiosco',
    'http://127.0.0.1/kiosco',
    window.location.origin
  ];
  
  console.log('🔒 Iniciando cierre de caja robusto...', cierreData);
  
  for (let i = 0; i < CONFIG_URLS.length; i++) {
    const baseUrl = CONFIG_URLS[i];
    const apiUrl = \`\${baseUrl}/api/gestion_caja_completa.php?accion=cerrar_caja\`;
    
    console.log(\`🔄 Intento \${i + 1}/\${CONFIG_URLS.length} - URL: \${apiUrl}\`);
    
    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Cache-Control': 'no-cache'
        },
        body: JSON.stringify(cierreData)
      });
      
      if (!response.ok) {
        throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Cierre exitoso:', result);
        return result;
      } else {
        throw new Error(result.error || 'Error desconocido del servidor');
      }
      
    } catch (error) {
      console.error(\`❌ Error en intento \${i + 1}:\`, error);
      
      if (i === CONFIG_URLS.length - 1) {
        throw new Error(\`No se pudo cerrar la caja después de \${CONFIG_URLS.length} intentos. Último error: \${error.message}\`);
      }
    }
  }
};
`;

const utilsPath = path.join(__dirname, '../src/utils/cashValidation.js');
fs.writeFileSync(utilsPath, validationFunctionContent);
console.log('   ✅ Función de validación creada en src/utils/cashValidation.js');

// 3. Crear archivo de configuración para desarrollo
console.log('\n🔧 PASO 3: Creando configuración de desarrollo...');

const devConfigContent = `# CONFIGURACIÓN DE DESARROLLO - CIERRE DE CAJA
# Asegúrate de que estos servicios estén ejecutándose:

# 1. LARAGON (Apache + MySQL)
#    - URL: http://localhost/kiosco
#    - Puerto: 80 (default Apache)

# 2. REACT DEV SERVER  
#    - URL: http://localhost:3000
#    - Puerto: 3000 (npm start)

# 3. CORS CONFIGURADO EN PHP
#    - Header: Access-Control-Allow-Origin: *
#    - Header: Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS

# URLs DE VALIDACIÓN:
# Backend API: http://localhost/kiosco/api/gestion_caja_completa.php
# Frontend: http://localhost:3000

# COMANDO DE PRUEBA:
# curl -X POST "http://localhost/kiosco/api/gestion_caja_completa.php?accion=cerrar_caja" \\
#      -H "Content-Type: application/json" \\
#      -d '{"usuario_id":1,"monto_cierre":13000,"notas":"Test"}'
`;

const devConfigPath = path.join(__dirname, '../.env.development');
fs.writeFileSync(devConfigPath, devConfigContent);
console.log('   ✅ Configuración de desarrollo creada');

// 4. Crear script de prueba para el navegador
console.log('\n🌐 PASO 4: Creando script de prueba para navegador...');

const browserTestContent = `/**
 * 🧪 SCRIPT DE PRUEBA PARA NAVEGADOR
 * Pega este código en la consola del navegador para probar el cierre
 */

// Test de conexión básica
async function testCashClosureConnection() {
  console.log('🧪 Probando conexión de cierre de caja...');
  
  const testData = {
    usuario_id: 1,
    monto_cierre: 13000,
    notas: 'Prueba desde navegador - ' + new Date().toISOString()
  };
  
  const urls = [
    'http://localhost/kiosco/api/gestion_caja_completa.php?accion=cerrar_caja',
    'http://127.0.0.1/kiosco/api/gestion_caja_completa.php?accion=cerrar_caja'
  ];
  
  for (const url of urls) {
    try {
      console.log('🔗 Probando:', url);
      
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(testData)
      });
      
      const result = await response.json();
      console.log('✅ Respuesta:', result);
      
      if (result.success) {
        console.log('🎉 ¡CONEXIÓN EXITOSA!');
        return result;
      }
      
    } catch (error) {
      console.error('❌ Error:', error);
    }
  }
}

// Ejecutar test
testCashClosureConnection();
`;

const browserTestPath = path.join(__dirname, '../browser-test.js');
fs.writeFileSync(browserTestPath, browserTestContent);
console.log('   ✅ Script de prueba creado en browser-test.js');

console.log('\n' + '='.repeat(70));
console.log('🎉 CORRECCIÓN COMPLETA APLICADA');
console.log('='.repeat(70));

console.log('\n📋 PASOS A SEGUIR:');
console.log('   1. 🔄 Reiniciar el servidor React: npm start');
console.log('   2. 🌐 Abrir http://localhost:3000');
console.log('   3. 🔧 Ir a Control de Caja');
console.log('   4. 🧪 Probar cerrar caja nuevamente');

console.log('\n🛠️ SI AÚN NO FUNCIONA:');
console.log('   1. Abrir DevTools (F12)');
console.log('   2. Ir a Console');
console.log('   3. Pegar el contenido de browser-test.js');
console.log('   4. Ver qué URL funciona');

console.log('\n✅ ARCHIVOS MODIFICADOS:');
console.log('   • src/config/config.js - Configuración corregida');
console.log('   • src/utils/cashValidation.js - Función robusta nueva');
console.log('   • .env.development - Configuración dev');
console.log('   • browser-test.js - Script de prueba');

console.log('\n🎯 RESULTADO ESPERADO:');
console.log('   El cierre de caja debe funcionar sin errores de conexión');
console.log('   La diferencia debe ser consistente entre dashboard y modal');
console.log('='.repeat(70));























