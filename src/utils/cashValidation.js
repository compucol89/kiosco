
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
    const apiUrl = `${baseUrl}/api/gestion_caja_completa.php?accion=cerrar_caja`;
    
    console.log(`🔄 Intento ${i + 1}/${CONFIG_URLS.length} - URL: ${apiUrl}`);
    
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
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        console.log('✅ Cierre exitoso:', result);
        return result;
      } else {
        throw new Error(result.error || 'Error desconocido del servidor');
      }
      
    } catch (error) {
      console.error(`❌ Error en intento ${i + 1}:`, error);
      
      if (i === CONFIG_URLS.length - 1) {
        throw new Error(`No se pudo cerrar la caja después de ${CONFIG_URLS.length} intentos. Último error: ${error.message}`);
      }
    }
  }
};
