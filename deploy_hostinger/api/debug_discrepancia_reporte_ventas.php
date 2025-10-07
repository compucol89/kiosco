<?php
/**
 * 🔍 DEBUG DISCREPANCIA EN REPORTE DE VENTAS
 * Identificar por qué hay inconsistencias en los datos
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 DEBUG DISCREPANCIA EN REPORTE DE VENTAS\n";
    echo str_repeat("=", 70) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. DATOS REALES DE LA BASE DE DATOS
    echo "📊 DATOS REALES DE BASE DE DATOS (HOY):\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_ventas,
            SUM(monto_total) as total_monto,
            SUM(subtotal) as total_subtotal,
            SUM(descuento) as total_descuentos,
            AVG(monto_total) as ticket_promedio
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
    ");
    $stmt->execute();
    $totales_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ TOTALES REALES:\n";
    echo "- Total ventas: {$totales_bd['total_ventas']}\n";
    echo "- Total monto: $" . number_format($totales_bd['total_monto'], 2) . "\n";
    echo "- Total subtotal: $" . number_format($totales_bd['total_subtotal'], 2) . "\n";
    echo "- Total descuentos: $" . number_format($totales_bd['total_descuentos'], 2) . "\n";
    echo "- Ticket promedio: $" . number_format($totales_bd['ticket_promedio'], 2) . "\n\n";
    
    // Métodos de pago reales
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(monto_total) as total
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
        ORDER BY total DESC
    ");
    $stmt->execute();
    $metodos_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "💳 MÉTODOS DE PAGO REALES (BD):\n";
    $total_metodos = 0;
    foreach ($metodos_bd as $metodo) {
        $porcentaje = ($totales_bd['total_monto'] > 0) ? 
            ($metodo['total'] / $totales_bd['total_monto']) * 100 : 0;
        
        echo sprintf("- %-15s: %d ventas | $%8.2f | %5.1f%%\n", 
            strtoupper($metodo['metodo_pago']),
            $metodo['cantidad'],
            $metodo['total'],
            $porcentaje
        );
        $total_metodos += $metodo['total'];
    }
    echo "TOTAL: $" . number_format($total_metodos, 2) . "\n\n";
    
    // 2. DATOS DESDE LA API DEL REPORTE
    echo "📡 DATOS DESDE API REPORTES_FINANCIEROS_PRECISOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy&_t=' . time());
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        $resumen = $data['resumen_general'];
        
        echo "API RESUMEN:\n";
        echo "- Total ventas: {$resumen['total_ventas']}\n";
        echo "- Ingresos brutos: $" . number_format($resumen['total_ingresos_brutos'], 2) . "\n";
        echo "- Ingresos netos: $" . number_format($resumen['total_ingresos_netos'], 2) . "\n";
        echo "- Descuentos: $" . number_format($resumen['total_descuentos'], 2) . "\n";
        echo "- Utilidad bruta: $" . number_format($resumen['total_utilidad_bruta'], 2) . "\n";
        echo "- Ticket promedio: $" . number_format($resumen['ticket_promedio'], 2) . "\n\n";
        
        echo "API MÉTODOS DE PAGO:\n";
        $total_api = 0;
        foreach ($data['metodos_pago'] as $metodo => $monto) {
            if ($monto > 0) {
                $porcentaje = ($resumen['total_ingresos_netos'] > 0) ? 
                    ($monto / $resumen['total_ingresos_netos']) * 100 : 0;
                echo "- " . strtoupper($metodo) . ": $" . number_format($monto, 2) . " ({$porcentaje:.1f}%)\n";
                $total_api += $monto;
            }
        }
        echo "TOTAL API: $" . number_format($total_api, 2) . "\n\n";
    }
    
    // 3. IDENTIFICAR DISCREPANCIAS ESPECÍFICAS
    echo "🎯 ANÁLISIS DE DISCREPANCIAS:\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "PROBLEMA IDENTIFICADO EN TU INTERFAZ:\n";
    echo "- Muestra: 'Total de Ventas: $5 - 0 ventas realizadas'\n";
    echo "- Debería mostrar: '{$totales_bd['total_ventas']} ventas - $" . number_format($totales_bd['total_monto'], 2) . "'\n\n";
    
    echo "MÉTODOS DE PAGO (Comparación):\n";
    echo "Tu interfaz vs BD:\n";
    foreach ($metodos_bd as $metodo) {
        $api_value = $data['metodos_pago'][strtolower($metodo['metodo_pago'])] ?? 0;
        $coincide = abs($api_value - $metodo['total']) < 0.01 ? "✅" : "❌";
        
        echo "- {$metodo['metodo_pago']}: Interfaz $" . number_format($api_value, 2) . " | BD $" . number_format($metodo['total'], 2) . " {$coincide}\n";
    }
    
    // 4. DIAGNÓSTICO ESPECÍFICO
    echo "\n🔍 DIAGNÓSTICO:\n";
    echo str_repeat("-", 30) . "\n";
    
    if ($totales_bd['total_ventas'] != $resumen['total_ventas']) {
        echo "❌ PROBLEMA 1: Cantidad de ventas no coincide\n";
        echo "   BD: {$totales_bd['total_ventas']} vs API: {$resumen['total_ventas']}\n";
    }
    
    if (abs($totales_bd['total_monto'] - $total_api) > 0.01) {
        echo "❌ PROBLEMA 2: Total de montos no coincide\n";
        echo "   BD: $" . number_format($totales_bd['total_monto'], 2) . " vs API: $" . number_format($total_api, 2) . "\n";
    }
    
    if (abs($totales_bd['ticket_promedio'] - $resumen['ticket_promedio']) > 0.01) {
        echo "❌ PROBLEMA 3: Ticket promedio no coincide\n";
        echo "   BD: $" . number_format($totales_bd['ticket_promedio'], 2) . " vs API: $" . number_format($resumen['ticket_promedio'], 2) . "\n";
    }
    
    echo "\n💡 POSIBLES CAUSAS:\n";
    echo "1. Cache no actualizado en el frontend\n";
    echo "2. API usando datos de período diferente\n";
    echo "3. Frontend mostrando datos de API diferente\n";
    echo "4. Problema en el mapeo de datos del servicio\n";
    
    echo "\n🔧 SOLUCIÓN RECOMENDADA:\n";
    echo "Forzar actualización del frontend (Ctrl+F5)\n";
    echo "o verificar qué API está usando el componente\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
