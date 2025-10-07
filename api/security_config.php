<?php
/**
 * CONFIGURACIÓN PHP SEGURA PARA PRODUCCIÓN
 * Incluir este archivo en cada endpoint API
 */

// Configuraciones de seguridad
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Configurar tiempo límite
ini_set('max_execution_time', 30);
ini_set('memory_limit', '128M');

// Headers de seguridad
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Función para logging seguro
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
        'details' => $details
    ];
    
    $logLine = json_encode($logEntry) . "\n";
    file_put_contents(__DIR__ . '/logs/security.log', $logLine, FILE_APPEND | LOCK_EX);
}

// Validación básica de entrada
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        default:
            return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
?>