<?php
/**
 * File: api/cors_middleware.php
 * Middleware de CORS con whitelist de dominios permitidos
 * Exists to restrict API access to authorized origins only
 * Related files: api/auth.php, api/usuarios.php, src/config/config.js
 */

// 🔐 FIX CRÍTICO: Whitelist de dominios permitidos (NO más *)
$allowed_origins = [
    'http://localhost:3000',           // React development
    'http://localhost',                // Laragon local
    'http://127.0.0.1:3000',          // Alternate localhost
    'http://148.230.72.12',           // Producción (HTTP)
    'https://148.230.72.12'           // Producción (HTTPS)
];

// Obtener origin del request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// 🔥 MODO DEV: Permitir siempre en desarrollo
// Solo permitir origins en whitelist
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
} else {
    // 🔥 FIX TEMPORAL: En desarrollo, permitir siempre
    // COMENTAR EN PRODUCCIÓN
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header("Vary: Origin");
    
    // Log para debugging
    if (!empty($origin)) {
        error_log("CORS: Origin no en whitelist (permitido por modo dev): " . $origin);
    }
}

// Permitir métodos HTTP específicos
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Permitir encabezados personalizados (incluir X-Api-Key para cuando se active)
header("Access-Control-Allow-Headers: Accept, Content-Type, Authorization, X-Requested-With, Cache-Control, Pragma, X-Api-Key");

// Cache de preflight por 24 horas
header("Access-Control-Max-Age: 86400");

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
} 