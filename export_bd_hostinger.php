<?php
/**
 * Script para exportar BD a SQL para Hostinger
 */

echo "=== EXPORTANDO BASE DE DATOS PARA HOSTINGER ===\n\n";

// Configuración
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'kiosco_db';
$output_file = 'kiosco_db_hostinger.sql';

// Comando mysqldump
$command = sprintf(
    'mysqldump -h %s -u %s %s %s > %s',
    $db_host,
    $db_user,
    $db_pass ? "-p{$db_pass}" : '',
    $db_name,
    $output_file
);

echo "Ejecutando mysqldump...\n";
exec($command, $output, $return_code);

if ($return_code === 0 && file_exists($output_file)) {
    $size = filesize($output_file);
    echo "✅ Base de datos exportada exitosamente\n";
    echo "   Archivo: $output_file\n";
    echo "   Tamaño: " . round($size / 1024, 2) . " KB\n\n";
    echo "🎯 Próximo paso: Subir este archivo a Hostinger phpMyAdmin\n";
} else {
    echo "❌ Error exportando. Intenta desde HeidiSQL manualmente.\n";
}
?>




