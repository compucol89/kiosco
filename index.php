<?php
/**
 * PUNTO DE ENTRADA PRINCIPAL PARA TAYRONA POS
 * Maneja tanto el frontend React como las llamadas API
 */

// Configuración básica
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Obtener la URL solicitada
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Si es una llamada a la API, redirigir
if (strpos($path, '/api/') === 0) {
    // Remover /api/ del path
    $apiPath = substr($path, 5);
    $apiFile = __DIR__ . '/api/' . $apiPath;
    
    if (file_exists($apiFile) && pathinfo($apiFile, PATHINFO_EXTENSION) === 'php') {
        // Incluir el archivo PHP de la API
        chdir(__DIR__ . '/api/');
        include $apiFile;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

// Si es un archivo estático que existe, servirlo
if (file_exists(__DIR__ . '/public' . $path) && $path !== '/') {
    $file = __DIR__ . '/public' . $path;
    $mime = mime_content_type($file);
    
    header('Content-Type: ' . $mime);
    
    // Cache para archivos estáticos
    if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'])) {
        header('Cache-Control: public, max-age=31536000');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
    }
    
    readfile($file);
    exit;
}

// Para todo lo demás, servir el index.html de React
$indexFile = __DIR__ . '/public/index.html';

if (file_exists($indexFile)) {
    readfile($indexFile);
} else {
    http_response_code(404);
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Tayrona POS - Error</title>
</head>
<body>
    <h1>Error 404</h1>
    <p>Sistema Tayrona POS no encontrado.</p>
    <p>Verifica que los archivos se hayan subido correctamente.</p>
</body>
</html>';
}
?>