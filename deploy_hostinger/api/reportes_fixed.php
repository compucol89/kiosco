<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


try {
    $pdo = Conexion::obtenerConexion();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'obtener_datos_contables') {
            
            // Get period parameters
            $periodo = $_GET['periodo'] ?? 'mes-actual';
            
            // Calculate dates
            switch ($periodo) {
                case 'hoy':
                    $fechaInicio = date('Y-m-d');
                    $fechaFin = date('Y-m-d');
                    break;
                case 'mes-actual':
                    $fechaInicio = date('Y-m-01');
                    $fechaFin = date('Y-m-t');
                    break;
                default:
                    $fechaInicio = date('Y-m-01');
                    $fechaFin = date('Y-m-d');
                    break;
            }
            
            // Get ventas
            $stmtVentas = $pdo->prepare("
                SELECT * FROM ventas 
                WHERE DATE(fecha) BETWEEN ? AND ? 
                AND estado != 'anulada'
                ORDER BY fecha DESC
            ");
            $stmtVentas->execute([$fechaInicio, $fechaFin]);
            $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate trazabilidad
            $trazabilidad = [
                'ingresos_totales' => 0,
                'egresos_totales' => 0,
                'flujo_neto' => 0,
                'desglose_ingresos' => [
                    'ventas_efectivo' => 0,
                    'ventas_tarjeta' => 0,
                    'ventas_transferencia' => 0,
                    'ventas_mercadopago' => 0,
                    'ventas_otros' => 0,
                    'ingresos_extra' => 0
                ],
                'desglose_egresos' => [
                    'gastos_fijos' => 0,
                    'sueldos' => 0,
                    'compras_mercaderia' => 0,
                    'servicios' => 0,
                    'impuestos' => 0,
                    'otros_gastos' => 0
                ]
            ];
            
            // Process ventas by payment method
            foreach ($ventas as $venta) {
                $monto = floatval($venta['monto_total']);
                $metodo = strtolower($venta['metodo_pago']);
                
                switch ($metodo) {
                    case 'efectivo':
                        $trazabilidad['desglose_ingresos']['ventas_efectivo'] += $monto;
                        break;
                    case 'tarjeta':
                        $trazabilidad['desglose_ingresos']['ventas_tarjeta'] += $monto;
                        break;
                    case 'transferencia':
                        $trazabilidad['desglose_ingresos']['ventas_transferencia'] += $monto;
                        break;
                    case 'mercadopago':
                        $trazabilidad['desglose_ingresos']['ventas_mercadopago'] += $monto;
                        break;
                    default:
                        $trazabilidad['desglose_ingresos']['ventas_otros'] += $monto;
                        break;
                }
            }
            
            $trazabilidad['ingresos_totales'] = array_sum($trazabilidad['desglose_ingresos']);
            $trazabilidad['flujo_neto'] = $trazabilidad['ingresos_totales'] - $trazabilidad['egresos_totales'];
            
            // Return response
            echo json_encode([
                'success' => true,
                'datos' => [
                    'ventas' => $ventas,
                    'productos' => [],
                    'ingresos' => [],
                    'egresos' => [],
                    'trazabilidad_financiera' => $trazabilidad,
                    'utilidades_productos' => [],
                    'arqueo' => [],
                    'alertas' => [],
                    'comparativas' => [],
                    'periodo' => [
                        'inicio' => $fechaInicio,
                        'fin' => $fechaFin
                    ]
                ]
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 