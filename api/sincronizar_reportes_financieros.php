<?php
/**
 * 🔄 SINCRONIZAR REPORTES FINANCIEROS
 * Fuerza recálculo de todos los reportes para que usen datos actualizados
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';
require_once 'financial_calculator_corrected.php';

try {
    echo "🔄 SINCRONIZANDO REPORTES FINANCIEROS...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. LIMPIAR CUALQUIER CACHE
    echo "🧹 LIMPIANDO CACHE...\n";
    $cache_dirs = ['api/cache', 'cache', 'temp'];
    
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            $cleaned = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $cleaned++;
                }
            }
            if ($cleaned > 0) {
                echo "  ✅ Limpiados {$cleaned} archivos de {$dir}\n";
            }
        }
    }
    
    // 2. RECALCULAR DATOS BASE
    echo "\n📊 RECALCULANDO DATOS BASE...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, cliente_nombre, metodo_pago, monto_total, 
            subtotal, descuento, detalles_json, estado
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        ORDER BY id DESC
    ");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  ✅ Encontradas " . count($ventas) . " ventas hoy\n";
    
    // 3. RECALCULAR MÉTODOS DE PAGO DIRECTAMENTE
    echo "\n💰 RECALCULANDO MÉTODOS DE PAGO...\n";
    
    $metodos_recalculados = [
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'mercadopago' => 0,
        'qr' => 0,
        'otros' => 0
    ];
    
    $total_recalculado = 0;
    
    foreach ($ventas as $venta) {
        $metodo = strtolower($venta['metodo_pago']);
        $monto = floatval($venta['monto_total']);
        
        $metodo_mapeado = match($metodo) {
            'efectivo' => 'efectivo',
            'tarjeta', 'debito', 'credito' => 'tarjeta',
            'transferencia', 'transfer' => 'transferencia',
            'mercadopago', 'mp' => 'mercadopago',
            'qr', 'codigo_qr', 'qr_code' => 'qr',
            default => 'otros'
        };
        
        $metodos_recalculados[$metodo_mapeado] += $monto;
        $total_recalculado += $monto;
    }
    
    echo "  Métodos recalculados:\n";
    foreach ($metodos_recalculados as $metodo => $monto) {
        if ($monto > 0) {
            echo "  - " . ucfirst($metodo) . ": $" . number_format($monto, 2) . "\n";
        }
    }
    echo "  Total: $" . number_format($total_recalculado, 2) . "\n";
    
    // 4. ACTUALIZAR CONFIGURACIÓN PARA FORZAR REFRESH
    echo "\n🔄 FORZANDO ACTUALIZACIÓN DE REPORTES...\n";
    
    // Crear archivo temporal para invalidar cache
    $timestamp = time();
    file_put_contents('api/last_data_update.txt', $timestamp);
    echo "  ✅ Timestamp actualizado: {$timestamp}\n";
    
    // 5. VERIFICAR REPORTES DESPUÉS DE LIMPIEZA
    echo "\n✅ VERIFICACIÓN POST-SINCRONIZACIÓN:\n";
    echo str_repeat("-", 40) . "\n";
    
    // Test reporte 1
    $response1 = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy&_force=' . $timestamp);
    $data1 = json_decode($response1, true);
    $total1 = $data1 ? array_sum($data1['metodos_pago']) : 0;
    
    // Test reporte 2  
    $response2 = file_get_contents('http://localhost/kiosco/api/finanzas_completo.php?periodo=hoy&_force=' . $timestamp);
    $data2 = json_decode($response2, true);
    
    if ($data2 && isset($data2['componente_3_metodos_pago'])) {
        $metodos = $data2['componente_3_metodos_pago'];
        $efectivo = $metodos['tarjeta_1_efectivo']['valor_principal'] ?? 0;
        $qr = $metodos['tarjeta_4_qr']['valor_principal'] ?? 0;
        $tarjeta = $metodos['tarjeta_3_tarjeta']['valor_principal'] ?? 0;
        $transferencia = $metodos['tarjeta_2_transferencia']['valor_principal'] ?? 0;
        $total2 = $efectivo + $qr + $tarjeta + $transferencia;
    } else {
        $total2 = 0;
    }
    
    echo "Reporte 1: $" . number_format($total1, 2) . "\n";
    echo "Reporte 2: $" . number_format($total2, 2) . "\n";
    echo "BD Real: $" . number_format($total_recalculado, 2) . "\n";
    
    if (abs($total1 - $total_recalculado) < 0.01 && abs($total2 - $total_recalculado) < 0.01) {
        echo "\n🎉 ¡SINCRONIZACIÓN EXITOSA!\n";
        echo "✅ Todos los reportes ahora muestran datos consistentes\n";
        echo "✅ Los porcentajes serán correctos\n";
    } else {
        echo "\n⚠️ AÚN HAY DISCREPANCIAS\n";
        echo "Reporte 2 necesita corrección adicional\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
