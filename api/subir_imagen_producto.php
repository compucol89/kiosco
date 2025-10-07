<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Verificar que se enviaron los datos necesarios
    if (!isset($_FILES['imagen']) || !isset($_POST['codigo'])) {
        throw new Exception('Faltan datos requeridos (imagen y código)');
    }

    $archivo = $_FILES['imagen'];
    $codigo = trim($_POST['codigo']);

    // Validar que el código no esté vacío
    if (empty($codigo)) {
        throw new Exception('El código del producto es requerido');
    }

    // Validar el archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $archivo['error']);
    }

    // Validar tipo de archivo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($archivo['type'], $tiposPermitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF y WEBP');
    }

    // Validar tamaño (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($archivo['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo permitido: 5MB');
    }

    // Crear directorio si no existe
    $directorioDestino = '../img/productos/';
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de imágenes');
        }
    }

    // Determinar extensión del archivo
    $extension = 'jpg'; // Por defecto JPG
    switch ($archivo['type']) {
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
    }

    // Nombre del archivo destino
    $nombreArchivo = $codigo . '.' . $extension;
    $rutaCompleta = $directorioDestino . $nombreArchivo;

    // Eliminar archivo anterior si existe (con cualquier extensión)
    $extensionesAEliminar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($extensionesAEliminar as $ext) {
        $archivoAnterior = $directorioDestino . $codigo . '.' . $ext;
        if (file_exists($archivoAnterior)) {
            unlink($archivoAnterior);
        }
    }

    // Procesar la imagen (redimensionar si es muy grande)
    $imagen = null;
    switch ($archivo['type']) {
        case 'image/jpeg':
            $imagen = imagecreatefromjpeg($archivo['tmp_name']);
            break;
        case 'image/png':
            $imagen = imagecreatefrompng($archivo['tmp_name']);
            break;
        case 'image/gif':
            $imagen = imagecreatefromgif($archivo['tmp_name']);
            break;
        case 'image/webp':
            $imagen = imagecreatefromwebp($archivo['tmp_name']);
            break;
    }

    if ($imagen === false) {
        throw new Exception('No se pudo procesar la imagen');
    }

    // Obtener dimensiones originales
    $anchoOriginal = imagesx($imagen);
    $altoOriginal = imagesy($imagen);

    // Redimensionar si es necesario (máximo 800x800)
    $maxDimension = 800;
    if ($anchoOriginal > $maxDimension || $altoOriginal > $maxDimension) {
        $ratio = min($maxDimension / $anchoOriginal, $maxDimension / $altoOriginal);
        $nuevoAncho = (int)($anchoOriginal * $ratio);
        $nuevoAlto = (int)($altoOriginal * $ratio);

        $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        
        // Preservar transparencia para PNG
        if ($archivo['type'] === 'image/png') {
            imagealphablending($imagenRedimensionada, false);
            imagesavealpha($imagenRedimensionada, true);
        }

        imagecopyresampled(
            $imagenRedimensionada, $imagen,
            0, 0, 0, 0,
            $nuevoAncho, $nuevoAlto,
            $anchoOriginal, $altoOriginal
        );

        imagedestroy($imagen);
        $imagen = $imagenRedimensionada;
    }

    // Guardar la imagen
    $guardadoExitoso = false;
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $guardadoExitoso = imagejpeg($imagen, $rutaCompleta, 85);
            break;
        case 'png':
            $guardadoExitoso = imagepng($imagen, $rutaCompleta, 6);
            break;
        case 'gif':
            $guardadoExitoso = imagegif($imagen, $rutaCompleta);
            break;
        case 'webp':
            $guardadoExitoso = imagewebp($imagen, $rutaCompleta, 85);
            break;
    }

    imagedestroy($imagen);

    if (!$guardadoExitoso) {
        throw new Exception('No se pudo guardar la imagen');
    }

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'mensaje' => 'Imagen subida exitosamente',
        'archivo' => $nombreArchivo,
        'ruta' => 'img/productos/' . $nombreArchivo,
        'codigo' => $codigo
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 