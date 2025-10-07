<?php
/**
 * 🔄 OPTIMIZACIÓN DE DATOS SPACEX-GRADE
 * Actualiza datos existentes para aprovechar nuevas columnas
 * Calcula valores históricos y optimiza para reportes tiempo real
 */

header('Content-Type: text/html; charset=UTF-8');
require_once 'bd_conexion.php';

echo "<!DOCTYPE html><html><head><title>Optimización de Datos SpaceX-Grade</title></head><body>";
echo "<h1>🔄 OPTIMIZACIÓN DE DATOS SPACEX-GRADE</h1>";
echo "<p><strong>Calculando valores históricos y optimizando datos existentes...</strong></p>";

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<hr><h2>📊 PASO 1: OPTIMIZACIÓN TABLA PRODUCTOS</h2>";
    echo "<p><strong>Calculando valores históricos y estadísticas...</strong></p>";
    
    // Actualizar costo_actual con precio_costo
    $pdo->exec("UPDATE productos SET costo_actual = precio_costo WHERE costo_actual IS NULL OR costo_actual = 0");
    echo "<p>✅ Costo actual sincronizado con precio_costo</p>";
    
    // Calcular stock valorizado
    $pdo->exec("UPDATE productos SET stock_valorizado = stock_actual * precio_costo WHERE stock_actual > 0");
    echo "<p>✅ Stock valorizado calculado</p>";
    
    // Actualizar precio_venta_sugerido basado en margen objetivo
    $pdo->exec("UPDATE productos SET precio_venta_sugerido = precio_costo * (1 + margen_objetivo/100) WHERE precio_costo > 0");
    echo "<p>✅ Precio de venta sugerido calculado</p>";
    
    // Calcular estadísticas de ventas por producto
    echo "<p><strong>Calculando estadísticas de ventas históricas...</strong></p>";
    
    $productos_stats = $pdo->query("
        SELECT 
            p.id,
            COALESCE(SUM(vd.cantidad), 0) as total_vendido,
            COALESCE(SUM(vd.subtotal), 0) as ingresos_totales,
            COALESCE(SUM(vd.cantidad * (vd.precio_unitario - p.precio_costo)), 0) as utilidad_estimada,
            MAX(v.fecha) as ultima_venta,
            COUNT(DISTINCT v.id) as num_ventas
        FROM productos p
        LEFT JOIN venta_detalles vd ON p.id = vd.producto_id
        LEFT JOIN ventas v ON vd.venta_id = v.id
        WHERE v.estado IN ('completado', 'completada') OR v.estado IS NULL
        GROUP BY p.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_update = $pdo->prepare("
        UPDATE productos SET 
            total_vendido = ?,
            ingresos_totales = ?,
            utilidad_acumulada = ?,
            ultima_venta = ?
        WHERE id = ?
    ");
    
    foreach ($productos_stats as $stat) {
        $stmt_update->execute([
            $stat['total_vendido'],
            $stat['ingresos_totales'],
            $stat['utilidad_estimada'],
            $stat['ultima_venta'],
            $stat['id']
        ]);
    }
    
    echo "<p>✅ Estadísticas históricas calculadas para " . count($productos_stats) . " productos</p>";
    
    // Calcular días de rotación
    $pdo->exec("
        UPDATE productos SET rotacion_dias = 
        CASE 
            WHEN total_vendido > 0 AND ultima_venta IS NOT NULL THEN 
                GREATEST(1, DATEDIFF(CURDATE(), DATE(created_at)) / GREATEST(1, total_vendido) * stock_actual)
            ELSE 0 
        END
        WHERE total_vendido > 0
    ");
    echo "<p>✅ Días de rotación calculados</p>";
    
    echo "<hr><h2>💰 PASO 2: OPTIMIZACIÓN TABLA VENTAS</h2>";
    echo "<p><strong>Calculando utilidades y márgenes históricos...</strong></p>";
    
    // Actualizar efectivo_recibido donde esté vacío
    $pdo->exec("UPDATE ventas SET efectivo_recibido = monto_total WHERE efectivo_recibido = 0 AND metodo_pago = 'efectivo'");
    echo "<p>✅ Efectivo recibido actualizado para ventas en efectivo</p>";
    
    // Calcular utilidad y costo total por venta
    echo "<p><strong>Calculando utilidades por venta...</strong></p>";
    
    $ventas_calculo = $pdo->query("
        SELECT 
            v.id,
            v.detalles_json,
            v.monto_total
        FROM ventas v
        WHERE v.estado IN ('completado', 'completada')
        AND (v.utilidad_total = 0 OR v.utilidad_total IS NULL)
        ORDER BY v.fecha DESC
        LIMIT 1000
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $productos_lookup = [];
    $productos_data = $pdo->query("SELECT id, precio_costo FROM productos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos_data as $prod) {
        $productos_lookup[$prod['id']] = $prod['precio_costo'];
    }
    
    $stmt_update_venta = $pdo->prepare("
        UPDATE ventas SET 
            utilidad_total = ?,
            costo_total = ?,
            margen_promedio = ?
        WHERE id = ?
    ");
    
    $ventas_procesadas = 0;
    foreach ($ventas_calculo as $venta) {
        $detalles = json_decode($venta['detalles_json'], true);
        $utilidad_total = 0;
        $costo_total = 0;
        
        if ($detalles && isset($detalles['cart'])) {
            foreach ($detalles['cart'] as $item) {
                $producto_id = $item['id'];
                $cantidad = $item['quantity'];
                $precio_venta = $item['price'];
                $costo_unitario = $productos_lookup[$producto_id] ?? 0;
                
                $costo_item = $costo_unitario * $cantidad;
                $ingreso_item = $precio_venta * $cantidad;
                $utilidad_item = $ingreso_item - $costo_item;
                
                $costo_total += $costo_item;
                $utilidad_total += $utilidad_item;
            }
        }
        
        $margen_promedio = $venta['monto_total'] > 0 ? ($utilidad_total / $venta['monto_total']) * 100 : 0;
        
        $stmt_update_venta->execute([
            $utilidad_total,
            $costo_total,
            $margen_promedio,
            $venta['id']
        ]);
        
        $ventas_procesadas++;
    }
    
    echo "<p>✅ Utilidades calculadas para $ventas_procesadas ventas</p>";
    
    echo "<hr><h2>🛒 PASO 3: OPTIMIZACIÓN TABLA VENTA_DETALLES</h2>";
    echo "<p><strong>Completando información financiera de detalles...</strong></p>";
    
    // Actualizar códigos y categorías de productos en venta_detalles
    $pdo->exec("
        UPDATE venta_detalles vd
        JOIN productos p ON vd.producto_id = p.id
        SET 
            vd.codigo_producto = p.codigo,
            vd.categoria_producto = p.categoria,
            vd.costo_unitario = p.precio_costo,
            vd.precio_costo_momento = p.precio_costo
        WHERE vd.codigo_producto IS NULL OR vd.codigo_producto = ''
    ");
    echo "<p>✅ Códigos y categorías sincronizados con productos</p>";
    
    // Calcular utilidades en venta_detalles
    $pdo->exec("
        UPDATE venta_detalles SET 
            utilidad_unitaria = precio_unitario - COALESCE(costo_unitario, 0),
            utilidad_total = cantidad * (precio_unitario - COALESCE(costo_unitario, 0)),
            margen_porcentaje = CASE 
                WHEN precio_unitario > 0 THEN 
                    ((precio_unitario - COALESCE(costo_unitario, 0)) / precio_unitario) * 100
                ELSE 0 
            END
        WHERE costo_unitario > 0
    ");
    echo "<p>✅ Utilidades y márgenes calculados en venta_detalles</p>";
    
    echo "<hr><h2>📋 PASO 4: CREACIÓN DE VISTAS OPTIMIZADAS</h2>";
    echo "<p><strong>Creando vistas para reportes tiempo real...</strong></p>";
    
    // Vista de productos con estadísticas
    $pdo->exec("
        CREATE OR REPLACE VIEW vista_productos_estadisticas AS
        SELECT 
            p.*,
            CASE 
                WHEN p.stock_actual <= p.stock_minimo THEN 'CRÍTICO'
                WHEN p.stock_actual <= p.stock_minimo * 2 THEN 'BAJO'
                ELSE 'NORMAL'
            END as estado_stock,
            CASE 
                WHEN p.ultima_venta IS NULL THEN 'NUNCA'
                WHEN DATEDIFF(CURDATE(), p.ultima_venta) > 30 THEN 'LENTO'
                WHEN DATEDIFF(CURDATE(), p.ultima_venta) > 7 THEN 'NORMAL'
                ELSE 'RÁPIDO'
            END as velocidad_rotacion,
            ROUND(p.utilidad_acumulada / GREATEST(1, p.total_vendido), 2) as utilidad_promedio_unitaria
        FROM productos p
        WHERE p.activo = 1
    ");
    echo "<p>✅ Vista vista_productos_estadisticas creada</p>";
    
    // Vista de resumen diario de ventas
    $pdo->exec("
        CREATE OR REPLACE VIEW vista_ventas_diario AS
        SELECT 
            DATE(v.fecha) as fecha,
            COUNT(*) as num_ventas,
            SUM(v.monto_total) as ingresos_totales,
            SUM(v.utilidad_total) as utilidad_total,
            SUM(v.costo_total) as costo_total,
            AVG(v.monto_total) as ticket_promedio,
            AVG(v.margen_promedio) as margen_promedio,
            SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.monto_total ELSE 0 END) as efectivo,
            SUM(CASE WHEN v.metodo_pago = 'tarjeta' THEN v.monto_total ELSE 0 END) as tarjeta,
            SUM(CASE WHEN v.metodo_pago = 'transferencia' THEN v.monto_total ELSE 0 END) as transferencia,
            SUM(CASE WHEN v.metodo_pago IN ('mercadopago', 'qr') THEN v.monto_total ELSE 0 END) as digital
        FROM ventas v
        WHERE v.estado IN ('completado', 'completada')
        GROUP BY DATE(v.fecha)
        ORDER BY fecha DESC
    ");
    echo "<p>✅ Vista vista_ventas_diario creada</p>";
    
    // Vista de productos más vendidos
    $pdo->exec("
        CREATE OR REPLACE VIEW vista_productos_ranking AS
        SELECT 
            p.id,
            p.nombre,
            p.categoria,
            p.precio_venta,
            p.total_vendido,
            p.ingresos_totales,
            p.utilidad_acumulada,
            p.stock_actual,
            p.rotacion_dias,
            RANK() OVER (ORDER BY p.total_vendido DESC) as ranking_cantidad,
            RANK() OVER (ORDER BY p.ingresos_totales DESC) as ranking_ingresos,
            RANK() OVER (ORDER BY p.utilidad_acumulada DESC) as ranking_utilidad
        FROM productos p
        WHERE p.activo = 1 AND p.total_vendido > 0
    ");
    echo "<p>✅ Vista vista_productos_ranking creada</p>";
    
    echo "<hr><h2>🚀 PASO 5: PROCEDIMIENTOS ALMACENADOS</h2>";
    echo "<p><strong>Creando procedimientos para cálculos optimizados...</strong></p>";
    
    // Procedimiento para actualizar estadísticas de producto
    $pdo->exec("
        DROP PROCEDURE IF EXISTS actualizar_estadisticas_producto;
        
        CREATE PROCEDURE actualizar_estadisticas_producto(IN producto_id INT)
        BEGIN
            DECLARE total_vendido_calc INT DEFAULT 0;
            DECLARE ingresos_calc DECIMAL(12,2) DEFAULT 0;
            DECLARE utilidad_calc DECIMAL(12,2) DEFAULT 0;
            DECLARE ultima_venta_calc TIMESTAMP;
            
            SELECT 
                COALESCE(SUM(vd.cantidad), 0),
                COALESCE(SUM(vd.subtotal), 0),
                COALESCE(SUM(vd.utilidad_total), 0),
                MAX(v.fecha)
            INTO total_vendido_calc, ingresos_calc, utilidad_calc, ultima_venta_calc
            FROM venta_detalles vd
            JOIN ventas v ON vd.venta_id = v.id
            WHERE vd.producto_id = producto_id 
            AND v.estado IN ('completado', 'completada');
            
            UPDATE productos SET 
                total_vendido = total_vendido_calc,
                ingresos_totales = ingresos_calc,
                utilidad_acumulada = utilidad_calc,
                ultima_venta = ultima_venta_calc
            WHERE id = producto_id;
        END
    ");
    echo "<p>✅ Procedimiento actualizar_estadisticas_producto creado</p>";
    
    // Función para calcular días de stock
    $pdo->exec("
        DROP FUNCTION IF EXISTS calcular_dias_stock;
        
        CREATE FUNCTION calcular_dias_stock(producto_id INT) 
        RETURNS INT
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            DECLARE dias_stock INT DEFAULT 0;
            DECLARE venta_promedio_diaria DECIMAL(10,2) DEFAULT 0;
            DECLARE stock_actual_prod INT DEFAULT 0;
            
            SELECT stock_actual INTO stock_actual_prod 
            FROM productos WHERE id = producto_id;
            
            SELECT AVG(cantidad_diaria) INTO venta_promedio_diaria
            FROM (
                SELECT DATE(v.fecha) as fecha, SUM(vd.cantidad) as cantidad_diaria
                FROM venta_detalles vd
                JOIN ventas v ON vd.venta_id = v.id
                WHERE vd.producto_id = producto_id 
                AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND v.estado IN ('completado', 'completada')
                GROUP BY DATE(v.fecha)
            ) daily_sales;
            
            IF venta_promedio_diaria > 0 THEN
                SET dias_stock = CEIL(stock_actual_prod / venta_promedio_diaria);
            ELSE
                SET dias_stock = 999;
            END IF;
            
            RETURN dias_stock;
        END
    ");
    echo "<p>✅ Función calcular_dias_stock creada</p>";
    
    echo "<hr><h2>✅ OPTIMIZACIÓN COMPLETADA</h2>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 RESUMEN DE OPTIMIZACIÓN:</h3>";
    echo "<ul>";
    echo "<li>✅ Datos históricos calculados para todos los productos</li>";
    echo "<li>✅ Utilidades y márgenes actualizados en ventas</li>";
    echo "<li>✅ Información financiera completa en venta_detalles</li>";
    echo "<li>✅ 3 vistas optimizadas para reportes tiempo real</li>";
    echo "<li>✅ Procedimientos almacenados para cálculos automáticos</li>";
    echo "<li>✅ Sistema optimizado para informes instantáneos</li>";
    echo "</ul>";
    echo "<p><strong>🚀 Base de datos optimizada para máximo rendimiento en reportes.</strong></p>";
    echo "</div>";
    
    // Mostrar estadísticas finales
    echo "<hr><h2>📊 ESTADÍSTICAS FINALES</h2>";
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM productos WHERE activo = 1) as productos_activos,
            (SELECT COUNT(*) FROM ventas WHERE estado IN ('completado', 'completada')) as ventas_completadas,
            (SELECT COUNT(*) FROM venta_detalles) as items_vendidos,
            (SELECT SUM(stock_valorizado) FROM productos) as valor_inventario,
            (SELECT SUM(utilidad_acumulada) FROM productos) as utilidad_historica
    ")->fetch(PDO::FETCH_ASSOC);
    
    foreach ($stats as $clave => $valor) {
        echo "<p>📈 " . ucfirst(str_replace('_', ' ', $clave)) . ": " . number_format($valor, 2) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ ERROR EN OPTIMIZACIÓN:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
