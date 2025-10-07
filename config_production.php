<?php
/**
 * 🚀 CONFIGURACIÓN DE PRODUCCIÓN - KIOSCO POS
 * 
 * Configuración optimizada para entorno de producción
 * Incluye todas las optimizaciones de seguridad y rendimiento
 */

// ========================================================================
// CONFIGURACIÓN DE PHP PARA PRODUCCIÓN
// ========================================================================

// Optimizaciones de rendimiento
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api/logs/php_errors.log');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);
ini_set('max_input_time', 60);
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '20M');

// Optimizaciones de sesión
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', 1); // Solo HTTPS en producción
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ========================================================================
// CONFIGURACIÓN DE BASE DE DATOS PARA PRODUCCIÓN
// ========================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'kiosco_user'); // Usuario específico con permisos limitados
define('DB_PASS', 'tu_password_seguro_aqui'); // Cambiar en producción
define('DB_CHARSET', 'utf8mb4');

// Pool de conexiones para alto rendimiento
define('DB_POOL_SIZE', 10);
define('DB_TIMEOUT', 30);

// ========================================================================
// CONFIGURACIÓN DE SEGURIDAD
// ========================================================================

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// HTTPS forzado en producción
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL");
    exit();
}

// ========================================================================
// CONFIGURACIÓN DE CORS PARA PRODUCCIÓN
// ========================================================================

$allowed_origins = [
    'https://tu-dominio.com',
    'https://www.tu-dominio.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://tu-dominio.com");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 horas

// ========================================================================
// CONFIGURACIÓN DE CACHE Y RENDIMIENTO
// ========================================================================

// Headers de cache para assets estáticos
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $_SERVER['REQUEST_URI'])) {
    header('Cache-Control: public, max-age=31536000'); // 1 año
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
}

// Compresión gzip
if (!ob_get_level()) {
    ob_start('ob_gzhandler');
}

// ========================================================================
// FUNCIÓN DE CONEXIÓN A BASE DE DATOS OPTIMIZADA
// ========================================================================

class DatabaseManager {
    private static $connections = [];
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Pool de conexiones para mejor rendimiento
        $key = DB_HOST . ':' . DB_NAME;
        
        if (!isset(self::$connections[$key]) || !self::isConnectionAlive(self::$connections[$key])) {
            self::$connections[$key] = $this->createConnection();
        }
        
        return self::$connections[$key];
    }
    
    private function createConnection() {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true, // Conexiones persistentes en producción
            PDO::ATTR_TIMEOUT => DB_TIMEOUT,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET . ' COLLATE utf8mb4_unicode_ci',
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Configuraciones adicionales para rendimiento
            $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $pdo->exec("SET SESSION innodb_lock_wait_timeout = 10");
            
            return $pdo;
        } catch (PDOException $e) {
            error_log('[DB ERROR] ' . $e->getMessage());
            throw new Exception('Error de conexión a la base de datos');
        }
    }
    
    private static function isConnectionAlive($pdo) {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// ========================================================================
// FUNCIONES DE UTILIDAD PARA PRODUCCIÓN
// ========================================================================

/**
 * Logger centralizado para producción
 */
class ProductionLogger {
    private static $logFile;
    
    public static function init() {
        self::$logFile = __DIR__ . '/api/logs/production.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function log($level, $message, $context = []) {
        if (!self::$logFile) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
}

/**
 * Validador de seguridad para requests
 */
class SecurityValidator {
    public static function validateRequest() {
        // Rate limiting básico
        if (self::isRateLimited()) {
            http_response_code(429);
            die(json_encode(['error' => 'Too many requests']));
        }
        
        // Validar User-Agent
        if (!isset($_SERVER['HTTP_USER_AGENT']) || strlen($_SERVER['HTTP_USER_AGENT']) < 10) {
            ProductionLogger::warning('Suspicious request without proper User-Agent', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
        
        // Validar contenido JSON
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if (!empty($input) && json_decode($input) === null) {
                http_response_code(400);
                die(json_encode(['error' => 'Invalid JSON']));
            }
        }
    }
    
    private static function isRateLimited() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip);
        
        $now = time();
        $windowSize = 60; // 1 minuto
        $maxRequests = 100; // 100 requests por minuto
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // Limpiar requests antiguos
            $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $windowSize) {
                return ($now - $timestamp) < $windowSize;
            });
            
            if (count($data['requests']) >= $maxRequests) {
                return true;
            }
            
            $data['requests'][] = $now;
        } else {
            $data = ['requests' => [$now]];
        }
        
        file_put_contents($cacheFile, json_encode($data));
        return false;
    }
}

// ========================================================================
// INICIALIZACIÓN AUTOMÁTICA
// ========================================================================

// Inicializar componentes para producción
ProductionLogger::init();
SecurityValidator::validateRequest();

// Log de inicio del sistema
ProductionLogger::info('Sistema iniciado', [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);

// ========================================================================
// CONSTANTES DEL SISTEMA
// ========================================================================

define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_ENV', 'production');
define('DEBUG_MODE', false);
define('CACHE_ENABLED', true);
define('SESSION_TIMEOUT', 3600); // 1 hora

// ========================================================================
// FUNCIÓN DE COMPATIBILIDAD
// ========================================================================

// Para compatibilidad con el código existente
if (!function_exists('obtenerConexionUnificada')) {
    function obtenerConexionUnificada() {
        return DatabaseManager::getInstance()->getConnection();
    }
}

if (!class_exists('Conexion')) {
    class Conexion {
        public static function obtenerConexion() {
            return DatabaseManager::getInstance()->getConnection();
        }
    }
}

ProductionLogger::info('Configuración de producción cargada exitosamente');
?>
