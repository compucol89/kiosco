// Configuración global para la aplicación
const CONFIG = {
  // URL base de la API - Detecta automáticamente el entorno
  API_URL: process.env.NODE_ENV === 'production' 
    ? window.location.origin  // En Railway usa la misma URL
    : 'http://localhost/kiosco', // En desarrollo usa Laragon local
  
  // URL para endpoints específicos de la API
  API_ENDPOINTS: {
    // ========== BÚSQUEDA ENTERPRISE - ELASTICSEARCH GRADE ==========
    SEARCH_ENTERPRISE: '/api/search_enterprise.php', // NEW: Búsqueda estricta <25ms, >95% precisión
    
    // ========== PRODUCTOS POS - ENTERPRISE GRADE ==========
    PRODUCTOS_POS_OPTIMIZADO: '/api/productos_pos_optimizado.php', // NEW: API con control de stock <50ms
    PRODUCTOS_POS_V2: '/api/productos_pos_v2.php', // NEW: API optimizada <50ms
    PRODUCTOS: '/api/productos.php', // LEGACY: Para administración
    
    // ========== VENTAS ==========
    VENTAS: '/api/reportes_financieros_precisos.php', // MODERN: API precisa con zona horaria correcta
    VENTAS_LEGACY: '/api/ventas_reales.php', // DEPRECATED: No usa filtros correctos
    VENTAS_BACKUP: '/api/listar_ventas.php',
    VENTAS_FALLBACK: '/api/ventas_db.php',
    PROCESAR_VENTA: '/api/procesar_venta_simple_v2.php', // V2: Ultra-simplificada para producción
    PROCESAR_VENTA_LEGACY: '/api/procesar_venta_ultra_rapida.php', // LEGACY
    ANULAR_VENTA: '/api/anular_venta.php',
    
    // ========== USUARIOS Y AUTENTICACIÓN ==========
    USUARIOS: '/api/usuarios.php',
    PERMISOS_USUARIO: '/api/permisos_usuario.php',
    
    // ========== CONEXIÓN Y TESTING ==========
    TEST: '/api/test_conexion.php',
    CONEXION: '/api/conexion_test.php',
    
    // ========== ENDPOINTS EMPRESARIALES - NIVEL BANCARIO ==========
    CONFIGURACION_EMPRESARIAL: '/api/configuracion_empresarial.php',
    CONFIGURACION_BACKUP: '/api/configuracion_backup.php',
    RESET_EMPRESARIAL: '/api/reset_sistema_empresarial.php',
    
    // ========== DASHBOARD Y REPORTES ==========
    DASHBOARD_STATS: '/api/dashboard_stats.php',
    AUDITORIA_INVENTARIO: '/api/auditoria_inventario.php',
    
    // ========== ENDPOINTS LEGACY (DEPRECATED) ==========
    CONFIGURACION: '/api/configuracion.php', // DEPRECATED - Usar CONFIGURACION_EMPRESARIAL
    RESET: '/api/reset_sistema.php' // DEPRECATED - Usar RESET_EMPRESARIAL
  },
  
  // Versión de la aplicación
  VERSION: '1.0.0',
  
  // Nombre de la aplicación
  APP_NAME: 'Tayrona Almacén',
  
  // Empresa
  COMPANY: 'Tayrona Software',
  
  // Formatos de fecha y hora - SPACEX GRADE SPECIFICATION
  DATE_FORMAT: 'DD/MM/YYYY',
  TIME_FORMAT: 'HH:mm:ss',
  DATETIME_FORMAT: 'DD/MM/YYYY HH:mm:ss',
  
  // Configuración de moneda
  CURRENCY: 'ARS',
  CURRENCY_SYMBOL: '$',
  
  // Tiempo de inactividad para cerrar sesión (en minutos)
  SESSION_TIMEOUT: 30,
  
  // Función para formatear moneda con separador de miles - SPACEX GRADE
  formatCurrency: (amount) => {
    if (amount === undefined || amount === null) return '$0.00';
    
    // Convertir a número si es string
    const value = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    // Formatear con separador de miles y dos decimales estilo internacional
    return '$' + value.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  },
  
  // Función para formatear fecha según especificación DD/MM/YYYY HH:MM:SS
  formatDateTime: (date) => {
    if (!date) return '';
    
    const d = new Date(date);
    return d.toLocaleString('es-AR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  },
  
  // Función para obtener la URL completa de un endpoint
  getApiUrl: (endpoint) => {
    return CONFIG.API_URL + endpoint;
  }
};

export default CONFIG; 