<?php
/**
 * CORS Middleware for API endpoints
 * This file handles Cross-Origin Resource Sharing (CORS) headers
 * to allow requests from the frontend application
 */

// 🌐 Headers CORS mejorados y más permisivos
header("Access-Control-Allow-Origin: *");
// Permitir métodos HTTP específicos
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// Permitir encabezados personalizados (incluyendo los problemáticos Cache-Control y Pragma)
header("Access-Control-Allow-Headers: Accept, Content-Type, Authorization, X-Requested-With, Cache-Control, Pragma");
// Establecer por cuánto tiempo el navegador puede cachear los resultados de las solicitudes preflight (OPTIONS)
header("Access-Control-Max-Age: 86400"); // 24 horas

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Las solicitudes OPTIONS solo necesitan los encabezados CORS
    exit(0);
} 