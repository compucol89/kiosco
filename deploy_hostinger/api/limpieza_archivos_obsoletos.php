<?php
/**
 * 🧹 SCRIPT DE LIMPIEZA DE ARCHIVOS OBSOLETOS
 * Ejecutar periódicamente para mantener el sistema limpio
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Lista de archivos obsoletos que pueden causar conflictos
    $archivosObsoletos = [
        'caja_antigua.php',
        'cierre_caja_directo.php',
        'debug_*.php',
        'test_*.php',
        'temp_*.php',
        '*_backup.php',
        '*_old.php'
    ];
    
    $archivosEliminados = [];
    $directorio = __DIR__;
    
    foreach (glob($directorio . '/*') as $archivo) {
        $nombreArchivo = basename($archivo);
        
        // Verificar si coincide con patrones obsoletos
        foreach ($archivosObsoletos as $patron) {
            if (fnmatch($patron, $nombreArchivo)) {
                if (is_file($archivo) && unlink($archivo)) {
                    $archivosEliminados[] = $nombreArchivo;
                }
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Limpieza completada',
        'archivos_eliminados' => $archivosEliminados,
        'total_eliminados' => count($archivosEliminados)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


















