<?php
/**
 * api/cors_railway.php
 * Configuración CORS para Railway frontend
 * Permite conexión entre frontend online y backend local
 * RELEVANT FILES: api/cors_middleware.php, src/config/config.js
 */

// Dominios permitidos (agregar URL de Railway)
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'https://tayrona-kiosco-pos-production.up.railway.app', // Actualizar con tu URL real
    'https://*.railway.app' // Permitir subdominios Railway
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verificar si el origen está permitido
$origin_allowed = false;
foreach ($allowed_origins as $allowed) {
    if ($allowed === $origin || 
        (strpos($allowed, '*') !== false && fnmatch($allowed, $origin))) {
        $origin_allowed = true;
        break;
    }
}

if ($origin_allowed) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:3000");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log de conexión Railway
error_log("[RAILWAY] Request desde: $origin");
?>
