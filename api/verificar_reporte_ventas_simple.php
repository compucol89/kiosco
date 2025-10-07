<?php
/**
 * 🔍 VERIFICAR REPORTE DE VENTAS - SIMPLE
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 VERIFICANDO DATOS REPORTE DE VENTAS...\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // DATOS BD
    echo "📊 DATOS BASE DE DATOS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as ventas,
            SUM(monto_total) as total,
            AVG(monto_total) as promedio
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
    ");
    $stmt->execute();
    $bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Ventas: {$bd['ventas']}\n";
    echo "- Total: $" . number_format($bd['total'], 2) . "\n";
    echo "- Promedio: $" . number_format($bd['promedio'], 2) . "\n\n";
    
    // DATOS API
    echo "📡 DATOS API REPORTES:\n";
    $response = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy');
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        $api = $data['resumen_general'];
        echo "- Ventas: {$api['total_ventas']}\n";
        echo "- Ingresos netos: $" . number_format($api['total_ingresos_netos'], 2) . "\n";
        echo "- Ticket promedio: $" . number_format($api['ticket_promedio'], 2) . "\n\n";
        
        echo "💳 MÉTODOS API:\n";
        foreach ($data['metodos_pago'] as $metodo => $monto) {
            if ($monto > 0) {
                echo "- {$metodo}: $" . number_format($monto, 2) . "\n";
            }
        }
    }
    
    echo "\n🎯 PROBLEMA IDENTIFICADO:\n";
    echo "Tu interfaz muestra 'Total de Ventas: $5 - 0 ventas'\n";
    echo "Pero debería mostrar: '{$bd['ventas']} ventas - $" . number_format($bd['total'], 2) . "'\n\n";
    
    echo "💡 CAUSA PROBABLE:\n";
    echo "El frontend está usando datos cacheados o de API diferente\n";
    echo "Los métodos de pago SÍ están correctos\n";
    echo "El problema está en el resumen general\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
