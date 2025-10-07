<?php
// Incluir middleware de CORS
require_once 'cors_middleware.php';

// Sólo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Directorio para guardar las evidencias
$uploadDir = '../uploads/evidencias/';

// Crear el directorio si no existe
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Verificar datos requeridos
if (!isset($data['imagen']) || empty($data['imagen'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta la imagen en formato base64']);
    exit;
}

// Extraer el contenido base64 (eliminar el prefijo si existe)
$imagen = $data['imagen'];
if (preg_match('/^data:image\/(\w+);base64,/', $imagen, $matches)) {
    $imageType = $matches[1];
    $imagen = substr($imagen, strpos($imagen, ',') + 1);
} else {
    $imageType = 'jpeg'; // Tipo por defecto
}

// Decodificar la imagen base64
$imagenDecodificada = base64_decode($imagen);

if ($imagenDecodificada === false) {
    http_response_code(400);
    echo json_encode(['error' => 'La imagen no está en formato base64 válido']);
    exit;
}

// Generar un nombre único para el archivo
$timestamp = date('YmdHis');
$tipo = isset($data['tipo']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $data['tipo']) : 'general';
$nombreArchivo = $tipo . '_' . $timestamp . '_' . uniqid() . '.' . $imageType;
$rutaCompleta = $uploadDir . $nombreArchivo;

// Guardar la imagen
if (file_put_contents($rutaCompleta, $imagenDecodificada)) {
    // URL relativa para acceder desde el frontend
    $urlRelativa = '/uploads/evidencias/' . $nombreArchivo;
    
    http_response_code(201);
    echo json_encode([
        'success' => true, 
        'message' => 'Evidencia subida correctamente', 
        'url' => $urlRelativa
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la evidencia']);
} 