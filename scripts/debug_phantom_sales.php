<?php
/**
 * 🔍 DEBUG - INVESTIGAR VENTAS "FANTASMA" EN EL DASHBOARD
 * 
 * Este script investiga por qué aparece "1 venta del día" cuando no se ha usado el sistema
 */

require_once 'api/config.php';

// Configurar zona horaria argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

echo "🔍 INVESTIGANDO VENTAS 'FANTASMA' EN EL DASHBOARD\n";
echo "=" . str_repeat("=", 60) . "\n";

$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));
$anteayer = date('Y-m-d', strtotime('-2 days'));

echo "📅 FECHAS A ANALIZAR:\n";
echo "   • Hoy: $hoy\n";
echo "   • Ayer: $ayer\n";
echo "   • Anteayer: $anteayer\n\n";

try {
    // 1. Verificar ventas de los últimos 3 días
    echo "📊 VENTAS POR DÍA (últimos 3 días):\n";
    $stmt = $pdo->prepare("
        SELECT 
            DATE(fecha) as fecha_venta,
            COUNT(*) as cantidad_ventas,
            SUM(monto_total) as total_ventas,
            GROUP_CONCAT(CONCAT('ID:', id, ' $', monto_total, ' (', metodo_pago, ')') SEPARATOR ' | ') as detalles,
            MAX(fecha) as ultima_venta_timestamp
        FROM ventas 
        WHERE DATE(fecha) >= ? 
        GROUP BY DATE(fecha)
        ORDER BY fecha_venta DESC
    ");
    $stmt->execute([$anteayer]);
    $ventas_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ventas_por_dia)) {
        echo "   ✅ NO HAY VENTAS en los últimos 3 días\n\n";
    } else {
        foreach ($ventas_por_dia as $dia) {
            echo "   📅 {$dia['fecha_venta']}: {$dia['cantidad_ventas']} ventas - {$dia['total_ventas']}\n";
            echo "      Última venta: {$dia['ultima_venta_timestamp']}\n";
            echo "      Detalles: {$dia['detalles']}\n\n";
        }
    }
    
    // 2. Verificar específicamente HOY
    echo "🎯 ANÁLISIS ESPECÍFICO DEL DÍA DE HOY ($hoy):\n";
    $stmt = $pdo->prepare("
        SELECT 
            id,
            fecha,
            monto_total,
            metodo_pago,
            estado,
            detalles_json,
            usuario_id
        FROM ventas 
        WHERE DATE(fecha) = ?
        ORDER BY fecha DESC
    ");
    $stmt->execute([$hoy]);
    $ventas_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ventas_hoy)) {
        echo "   ✅ NO HAY VENTAS HOY\n";
        echo "   🚨 PROBLEMA: El dashboard muestra 1 venta pero la BD no tiene ventas\n\n";
    } else {
        echo "   ⚠️ ENCONTRADAS " . count($ventas_hoy) . " VENTAS HOY:\n";
        foreach ($ventas_hoy as $venta) {
            echo "      • ID: {$venta['id']}\n";
            echo "        Fecha: {$venta['fecha']}\n";
            echo "        Monto: {$venta['monto_total']}\n";
            echo "        Método: {$venta['metodo_pago']}\n";
            echo "        Estado: {$venta['estado']}\n";
            echo "        Usuario: {$venta['usuario_id']}\n\n";
        }
    }
    
    // 3. Verificar qué está consultando exactamente el dashboard
    echo "🔍 SIMULANDO CONSULTA DEL DASHBOARD:\n";
    $fecha_inicio = $hoy . ' 00:00:00';
    $fecha_fin = $hoy . ' 23:59:59';
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as cantidad_ventas,
            COALESCE(SUM(monto_total), 0) as total_ventas,
            COALESCE(SUM(descuento), 0) as total_descuentos,
            COALESCE(AVG(monto_total), 0) as promedio_venta
        FROM ventas 
        WHERE fecha BETWEEN ? AND ? 
        AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $dashboard_query = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Resultado de la consulta del dashboard:\n";
    echo "      Cantidad: {$dashboard_query['cantidad_ventas']}\n";
    echo "      Total: {$dashboard_query['total_ventas']}\n";
    echo "      Promedio: {$dashboard_query['promedio_venta']}\n\n";
    
    // 4. Verificar si hay ventas con estados problemáticos
    echo "🔎 VERIFICANDO ESTADOS DE VENTAS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            estado,
            COUNT(*) as cantidad
        FROM ventas 
        WHERE DATE(fecha) = ?
        GROUP BY estado
    ");
    $stmt->execute([$hoy]);
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estados)) {
        echo "   ✅ NO HAY VENTAS con ningún estado\n";
    } else {
        foreach ($estados as $estado) {
            echo "   • Estado '{$estado['estado']}': {$estado['cantidad']} ventas\n";
        }
    }
    
    // 5. Verificar cache o problemas de zona horaria
    echo "\n🕐 VERIFICANDO ZONA HORARIA:\n";
    echo "   • Zona horaria PHP: " . date_default_timezone_get() . "\n";
    echo "   • Hora actual PHP: " . date('Y-m-d H:i:s') . "\n";
    
    $stmt = $pdo->prepare("SELECT NOW() as hora_mysql, @@time_zone as zona_mysql");
    $stmt->execute();
    $mysql_time = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   • Hora MySQL: {$mysql_time['hora_mysql']}\n";
    echo "   • Zona MySQL: {$mysql_time['zona_mysql']}\n\n";
    
    // 6. Diagnóstico final
    echo "🎯 DIAGNÓSTICO:\n";
    if ($dashboard_query['cantidad_ventas'] == 0) {
        echo "   ✅ La consulta del dashboard devuelve 0 ventas (correcto)\n";
        echo "   🚨 PROBLEMA: Debe ser un error de caché en el frontend\n";
        echo "   💡 SOLUCIÓN: Refrescar caché del navegador o revisar código del dashboard\n";
    } else {
        echo "   ⚠️ La consulta del dashboard devuelve {$dashboard_query['cantidad_ventas']} ventas\n";
        echo "   🔍 REVISAR: Las ventas listadas arriba para entender por qué existen\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔍 ANÁLISIS COMPLETADO\n";
?>
