<?php
/**
 * File: api/api_key_middleware.php
 * Middleware de validación de API Key compartida (capa adicional de seguridad)
 * Exists to add shared secret layer preventing anonymous script access
 * Related files: api/auth_middleware.php, api/cors_middleware.php, src/services/api.js
 */

/**
 * 🔐 VALIDACIÓN DE API KEY COMPARTIDA
 * 
 * Esta es una **capa adicional** de seguridad (defense in depth):
 * - Capa 1: API Key (este middleware) - previene scraping y acceso anónimo
 * - Capa 2: Auth Token (auth_middleware) - valida usuario autenticado
 * - Capa 3: Roles (auth_middleware) - valida permisos por rol
 * 
 * ⚠️ IMPORTANTE:
 * - Este NO reemplaza la autenticación de usuario
 * - Es un "shared secret" entre frontend y backend
 * - Cambiarlo invalida TODAS las instancias del frontend
 * - NO aplicar a endpoints públicos (login, health check)
 * 
 * @throws Exit con 401 si falta o es inválido
 */
function require_api_key() {
    // Obtener API key esperada (desde env o fallback)
    // IMPORTANTE: Cambiar el fallback en producción
    $expected = getenv('API_SHARED_KEY') ?: 'kiosco-api-2025-cambiar-en-produccion';
    
    // Intentar obtener del header
    $got = '';
    
    // Método 1: Header estándar HTTP_X_API_KEY
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $got = $_SERVER['HTTP_X_API_KEY'];
    }
    // Método 2: Header alternativo X-Api-Key (con guiones)
    elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
        $got = $_SERVER['HTTP_X_API_KEY'];
    }
    // Método 3: Buscar en todos los headers (Apache a veces los modifica)
    elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                $got = $value;
                break;
            }
        }
    }
    
    // Validar que existe y coincide
    if (empty($got)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'API key required',
            'message' => 'Missing X-Api-Key header'
        ]);
        error_log("API Key: Request bloqueado - header faltante desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        exit;
    }
    
    // Comparación segura contra timing attacks
    if (!hash_equals($expected, $got)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid API key',
            'message' => 'API key is incorrect'
        ]);
        error_log("API Key: Request bloqueado - key inválida desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        exit;
    }
    
    // ✅ API Key válida - continuar
    return true;
}

/**
 * 🔍 VALIDACIÓN OPCIONAL DE API KEY
 * 
 * Igual que require_api_key() pero no hace exit(), retorna bool
 * Útil para endpoints que son opcionales con API key
 * 
 * @return bool True si API key es válida, false si no
 */
function check_api_key() {
    $expected = getenv('API_SHARED_KEY') ?: 'kiosco-api-2025-cambiar-en-produccion';
    
    $got = '';
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $got = $_SERVER['HTTP_X_API_KEY'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                $got = $value;
                break;
            }
        }
    }
    
    if (empty($got)) {
        return false;
    }
    
    return hash_equals($expected, $got);
}

/**
 * 📊 ESTADÍSTICAS DE USO (opcional)
 * 
 * Registra uso de API para monitoreo
 * Puede guardar en archivo o BD
 */
function log_api_usage() {
    $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    error_log("API Usage: $method $endpoint from $ip");
    
    // TODO: Opcional - guardar en tabla api_usage para analytics
    // INSERT INTO api_usage (endpoint, method, ip, timestamp) VALUES (...)
}

/**
 * 🛠️ HELPER: Generar nueva API Key
 * 
 * Útil para rotar la key periódicamente
 * Ejecutar desde CLI: php -r "require 'api_key_middleware.php'; echo generate_api_key();"
 * 
 * @return string Nueva API key de 64 caracteres
 */
function generate_api_key() {
    return bin2hex(random_bytes(32));
}

/**
 * 🔐 CONFIGURACIÓN DE API KEY POR AMBIENTE
 * 
 * Recomendación de configuración:
 * 
 * 1. DESARROLLO (localhost):
 *    - Puede usar el fallback del código
 *    - No crítico si se filtra
 * 
 * 2. STAGING:
 *    - Setear en archivo .env o variable de entorno
 *    - export API_SHARED_KEY="staging-key-abc123..."
 * 
 * 3. PRODUCCIÓN:
 *    - OBLIGATORIO setear en variable de entorno
 *    - export API_SHARED_KEY="prod-key-xyz789..."
 *    - NO comitear en git
 *    - Rotar cada 3-6 meses
 * 
 * Apache: SetEnv API_SHARED_KEY "tu-key-aquí"
 * Nginx: fastcgi_param API_SHARED_KEY "tu-key-aquí";
 * Docker: -e API_SHARED_KEY="tu-key-aquí"
 * Railway: Variable de entorno en dashboard
 */

/**
 * 🧪 EJEMPLO DE USO EN ENDPOINTS
 * 
 * Aplicar en TODOS los endpoints sensibles:
 * 
 * ```php
 * <?php
 * // File: api/usuarios.php
 * 
 * require_once 'cors_middleware.php';
 * require_once 'api_key_middleware.php';  // <- Agregar
 * require_once 'auth_middleware.php';
 * 
 * // Capa 1: API Key (opcional para endpoints públicos)
 * if ($_SERVER['REQUEST_URI'] !== '/api/auth.php') {
 *     require_api_key();
 * }
 * 
 * // Capa 2: Auth + Roles
 * $usuario = requireAuth(['admin']);
 * 
 * // ... resto del endpoint
 * ```
 * 
 * ENDPOINTS QUE NO DEBEN TENER require_api_key():
 * - auth.php (login)
 * - health.php (health check)
 * - cors_middleware.php (obviamente)
 * 
 * TODOS LOS DEMÁS DEBEN TENERLO.
 */

/**
 * 🔒 MEJORES PRÁCTICAS
 * 
 * 1. **NO** comitear API keys en git
 * 2. **NO** loguear la API key completa (solo primeros 8 chars)
 * 3. **SÍ** usar variables de entorno
 * 4. **SÍ** rotar keys periódicamente
 * 5. **SÍ** usar HTTPS siempre
 * 6. **SÍ** monitorear intentos fallidos
 * 7. **NO** reusar keys entre ambientes
 * 8. **SÍ** documentar proceso de rotación
 */

/**
 * 🚨 MODO DE EMERGENCIA
 * 
 * Si la API key se compromete:
 * 
 * 1. Generar nueva key inmediatamente:
 *    php -r "echo bin2hex(random_bytes(32));"
 * 
 * 2. Actualizar en servidor:
 *    export API_SHARED_KEY="nueva-key"
 *    sudo systemctl restart apache2
 * 
 * 3. Actualizar en frontend:
 *    REACT_APP_API_KEY=nueva-key npm run build
 * 
 * 4. Desplegar ambos (backend y frontend) simultáneamente
 * 
 * 5. Monitorear logs por intentos con key antigua:
 *    tail -f /var/log/apache2/error.log | grep "API Key"
 */

