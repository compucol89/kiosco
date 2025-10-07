<?php
/**
 * 🔍 DEBUG TOTAL INGRESOS - IDENTIFICAR DISCREPANCIA
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🔍 DEBUGGING TOTAL INGRESOS - IDENTIFICAR DISCREPANCIA\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // 1. DATOS DESDE REPORTES_FINANCIEROS_PRECISOS
    echo "📊 DATOS DESDE reportes_financieros_precisos.php:\n";
    echo str_repeat("-", 40) . "\n";
    
    $response1 = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy');
    $data1 = json_decode($response1, true);
    
    if ($data1) {
        echo "Resumen general:\n";
        echo "- Total ventas: " . ($data1['resumen_general']['total_ventas'] ?? 'N/A') . "\n";
        echo "- Total ingresos brutos: $" . number_format($data1['resumen_general']['total_ingresos_brutos'] ?? 0, 2) . "\n";
        echo "- Total ingresos netos: $" . number_format($data1['resumen_general']['total_ingresos_netos'] ?? 0, 2) . "\n";
        echo "- Total descuentos: $" . number_format($data1['resumen_general']['total_descuentos'] ?? 0, 2) . "\n";
        
        echo "\nMétodos de pago:\n";
        foreach ($data1['metodos_pago'] as $metodo => $monto) {
            if ($monto > 0) {
                echo "- {$metodo}: $" . number_format($monto, 2) . "\n";
            }
        }
        
        $total_metodos = array_sum($data1['metodos_pago']);
        echo "Total métodos: $" . number_format($total_metodos, 2) . "\n";
    }
    
    // 2. DATOS DESDE FINANZAS_COMPLETO
    echo "\n\n📊 DATOS DESDE finanzas_completo.php:\n";
    echo str_repeat("-", 40) . "\n";
    
    $response2 = file_get_contents('http://localhost/kiosco/api/finanzas_completo.php?periodo=hoy');
    $data2 = json_decode($response2, true);
    
    if ($data2) {
        echo "Componente ventas:\n";
        $ventas = $data2['componente_4_detalle_ventas']['totales'] ?? [];
        echo "- Total ventas: $" . number_format($ventas['total_ventas'] ?? 0, 2) . "\n";
        echo "- Total costos: $" . number_format($ventas['total_costos'] ?? 0, 2) . "\n";
        echo "- Total descuentos: $" . number_format($ventas['total_descuentos'] ?? 0, 2) . "\n";
        echo "- Total ganancias: $" . number_format($ventas['total_ganancias'] ?? 0, 2) . "\n";
        
        echo "\nMétodos de pago:\n";
        $metodos = $data2['componente_3_metodos_pago'] ?? [];
        $efectivo = $metodos['tarjeta_1_efectivo']['valor_principal'] ?? 0;
        $qr = $metodos['tarjeta_4_qr']['valor_principal'] ?? 0;
        $tarjeta = $metodos['tarjeta_3_tarjeta']['valor_principal'] ?? 0;
        $transferencia = $metodos['tarjeta_2_transferencia']['valor_principal'] ?? 0;
        
        echo "- efectivo: $" . number_format($efectivo, 2) . "\n";
        echo "- qr: $" . number_format($qr, 2) . "\n";
        echo "- tarjeta: $" . number_format($tarjeta, 2) . "\n";
        echo "- transferencia: $" . number_format($transferencia, 2) . "\n";
        
        $total_metodos2 = $efectivo + $qr + $tarjeta + $transferencia;
        echo "Total métodos: $" . number_format($total_metodos2, 2) . "\n";
    }
    
    // 3. DATOS DIRECTOS DE BD
    echo "\n\n📊 DATOS DIRECTOS DE BASE DE DATOS:\n";
    echo str_repeat("-", 40) . "\n";
    
    require_once 'bd_conexion.php';
    $pdo = Conexion::obtenerConexion();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_ventas,
            SUM(monto_total) as total_monto,
            SUM(subtotal) as total_subtotal,
            SUM(descuento) as total_descuentos
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
    ");
    $stmt->execute();
    $totales_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Total ventas: " . $totales_bd['total_ventas'] . "\n";
    echo "- Total monto: $" . number_format($totales_bd['total_monto'], 2) . "\n";
    echo "- Total subtotal: $" . number_format($totales_bd['total_subtotal'], 2) . "\n";
    echo "- Total descuentos: $" . number_format($totales_bd['total_descuentos'], 2) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            SUM(monto_total) as total
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
    ");
    $stmt->execute();
    $metodos_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nMétodos BD:\n";
    $total_bd = 0;
    foreach ($metodos_bd as $metodo) {
        echo "- {$metodo['metodo_pago']}: $" . number_format($metodo['total'], 2) . "\n";
        $total_bd += $metodo['total'];
    }
    echo "Total BD: $" . number_format($total_bd, 2) . "\n";
    
    // 4. IDENTIFICAR DISCREPANCIA
    echo "\n\n🎯 ANÁLISIS DE DISCREPANCIA:\n";
    echo str_repeat("-", 30) . "\n";
    
    if (isset($data1) && isset($data2)) {
        $total1 = array_sum($data1['metodos_pago']);
        $total2 = $efectivo + $qr + $tarjeta + $transferencia;
        
        echo "Reporte 1 (financieros_precisos): $" . number_format($total1, 2) . "\n";
        echo "Reporte 2 (finanzas_completo): $" . number_format($total2, 2) . "\n";
        echo "Base de datos: $" . number_format($total_bd, 2) . "\n";
        
        if (abs($total1 - $total2) > 0.01) {
            echo "\n❌ DISCREPANCIA ENCONTRADA: $" . number_format(abs($total1 - $total2), 2) . "\n";
            echo "Causa probable: APIs usando datos diferentes\n";
        } else {
            echo "\n✅ TOTALES COINCIDEN\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
