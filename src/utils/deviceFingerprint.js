/**
 * src/utils/deviceFingerprint.js
 * Generador de huella digital única del dispositivo
 * Combina características del navegador y hardware para identificar el dispositivo
 * RELEVANT FILES: src/components/LoginPage.jsx, api/dispositivos_confiables.php
 */

/**
 * Generar fingerprint único del dispositivo
 * Combina: navegador, SO, resolución, idioma, zona horaria, etc.
 */
export const generateDeviceFingerprint = async () => {
  const components = [];
  
  // 1. User Agent
  components.push(navigator.userAgent);
  
  // 2. Idioma
  components.push(navigator.language || navigator.userLanguage);
  
  // 3. Zona horaria
  components.push(Intl.DateTimeFormat().resolvedOptions().timeZone);
  
  // 4. Resolución de pantalla
  components.push(`${window.screen.width}x${window.screen.height}x${window.screen.colorDepth}`);
  
  // 5. Platform
  components.push(navigator.platform);
  
  // 6. Hardware concurrency (núcleos CPU)
  components.push(navigator.hardwareConcurrency || 0);
  
  // 7. Device memory (si está disponible)
  components.push(navigator.deviceMemory || 0);
  
  // 8. Plugins instalados (funciona en algunos navegadores)
  const plugins = Array.from(navigator.plugins || [])
    .map(p => p.name)
    .sort()
    .join(',');
  components.push(plugins);
  
  // 9. Canvas fingerprinting (único por GPU)
  const canvasFingerprint = await getCanvasFingerprint();
  components.push(canvasFingerprint);
  
  // 10. WebGL (único por GPU)
  const webglFingerprint = getWebGLFingerprint();
  components.push(webglFingerprint);
  
  // Combinar todos los componentes
  const combined = components.join('|||');
  
  // Generar hash
  const fingerprint = await hashString(combined);
  
  return fingerprint;
};

/**
 * Canvas fingerprinting
 */
const getCanvasFingerprint = () => {
  return new Promise((resolve) => {
    try {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      
      if (!ctx) {
        resolve('no-canvas');
        return;
      }
      
      canvas.width = 200;
      canvas.height = 50;
      
      // Dibujar texto con estilo específico
      ctx.textBaseline = 'top';
      ctx.font = '14px Arial';
      ctx.fillStyle = '#f60';
      ctx.fillRect(125, 1, 62, 20);
      ctx.fillStyle = '#069';
      ctx.fillText('Kiosco Device ID', 2, 15);
      ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
      ctx.fillText('Kiosco Device ID', 4, 17);
      
      const data = canvas.toDataURL();
      resolve(data.slice(-50)); // Últimos 50 caracteres
    } catch (e) {
      resolve('canvas-error');
    }
  });
};

/**
 * WebGL fingerprinting
 */
const getWebGLFingerprint = () => {
  try {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
    
    if (!gl) return 'no-webgl';
    
    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
    if (!debugInfo) return 'no-debug-info';
    
    const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
    const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
    
    return `${vendor}~${renderer}`;
  } catch (e) {
    return 'webgl-error';
  }
};

/**
 * Generar hash de una cadena (simple pero efectivo)
 */
const hashString = async (str) => {
  try {
    // Usar Web Crypto API si está disponible
    const encoder = new TextEncoder();
    const data = encoder.encode(str);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    return hashHex;
  } catch (e) {
    // Fallback a hash simple
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash;
    }
    return Math.abs(hash).toString(16);
  }
};

/**
 * Obtener información del dispositivo para mostrar
 */
export const getDeviceInfo = () => {
  const ua = navigator.userAgent;
  
  // Detectar sistema operativo
  let os = 'Desconocido';
  if (ua.indexOf('Windows') !== -1) os = 'Windows';
  else if (ua.indexOf('Mac') !== -1) os = 'MacOS';
  else if (ua.indexOf('Linux') !== -1) os = 'Linux';
  else if (ua.indexOf('Android') !== -1) os = 'Android';
  else if (ua.indexOf('iOS') !== -1) os = 'iOS';
  
  // Detectar navegador
  let browser = 'Desconocido';
  if (ua.indexOf('Chrome') !== -1) browser = 'Chrome';
  else if (ua.indexOf('Firefox') !== -1) browser = 'Firefox';
  else if (ua.indexOf('Safari') !== -1) browser = 'Safari';
  else if (ua.indexOf('Edge') !== -1) browser = 'Edge';
  
  return {
    os,
    browser,
    screen: `${window.screen.width}x${window.screen.height}`,
    descripcion: `${os} - ${browser}`
  };
};

export default {
  generateDeviceFingerprint,
  getDeviceInfo
};

