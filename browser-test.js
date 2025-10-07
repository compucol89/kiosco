/**
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
