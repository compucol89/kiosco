<?php
/*
 * Script de migración: Quitar restricción UNIQUE del campo codigo
 * 
 * Ejecutar una sola vez para permitir códigos duplicados en productos
 */

header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';

try {
    echo "<h2>🔧 Migración: Permitir códigos duplicados</h2>";
    
    // Verificar si la tabla existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'productos'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo "<p>✅ Tabla productos no existe. No se requiere migración.</p>";
        exit;
    }
    
    // Verificar si existe la restricción UNIQUE en codigo
    $stmt = $pdo->prepare("SHOW INDEX FROM productos WHERE Key_name != 'PRIMARY'");
    $stmt->execute();
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tieneUniqueEnCodigo = false;
    $nombreIndice = '';
    
    foreach ($indices as $indice) {
        if ($indice['Column_name'] === 'codigo' && $indice['Non_unique'] == 0) {
            $tieneUniqueEnCodigo = true;
            $nombreIndice = $indice['Key_name'];
            break;
        }
    }
    
    if (!$tieneUniqueEnCodigo) {
        echo "<p>✅ El campo 'codigo' ya permite duplicados. No se requiere migración.</p>";
        exit;
    }
    
    echo "<p>⚠️ Encontrada restricción UNIQUE en campo 'codigo': $nombreIndice</p>";
    echo "<p>🔄 Removiendo restricción...</p>";
    
    // Quitar la restricción UNIQUE
    $sql = "ALTER TABLE productos DROP INDEX $nombreIndice";
    $pdo->exec($sql);
    
    echo "<p>✅ Restricción UNIQUE removida exitosamente!</p>";
    echo "<p>✅ Ahora los productos pueden tener códigos duplicados (ej: '0', '1', códigos genéricos)</p>";
    
    // Verificar que se aplicó correctamente
    $stmt = $pdo->prepare("SHOW INDEX FROM productos WHERE Key_name != 'PRIMARY' AND Column_name = 'codigo'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo "<p>✅ Verificación: Campo 'codigo' ahora permite duplicados</p>";
    } else {
        echo "<p>⚠️ Advertencia: Aún existe algún índice en 'codigo'</p>";
    }
    
    echo "<br><h3>📋 Resumen:</h3>";
    echo "<ul>";
    echo "<li>✅ Códigos pueden estar vacíos</li>";
    echo "<li>✅ Múltiples productos pueden usar '0', '1', etc.</li>";
    echo "<li>✅ Códigos genéricos permitidos</li>";
    echo "<li>✅ Importación sin errores por códigos duplicados</li>";
    echo "</ul>";
    
    echo "<p><strong>🎉 Migración completada exitosamente!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Error durante la migración: " . $e->getMessage() . "</p>";
    echo "<p>Detalles técnicos: " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 