<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
            
            // Get egresos from the new egresos table
            $stmtEgresos = $pdo->prepare("
                SELECT * FROM egresos 
                WHERE DATE(fecha) BETWEEN ? AND ?
                ORDER BY fecha DESC
            ");
            $stmtEgresos->execute([$fechaInicio, $fechaFin]);
            $egresos = $stmtEgresos->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            // Process egresos by category
            foreach ($egresos as $egreso) {
                $monto = floatval($egreso['monto']);
                $categoria = $egreso['categoria'];
                
                switch ($categoria) {
                    case 'gastos_fijos':
                        $trazabilidad['desglose_egresos']['gastos_fijos'] += $monto;
                        break;
                    case 'sueldos':
                        $trazabilidad['desglose_egresos']['sueldos'] += $monto;
                        break;
                    case 'compras_mercaderia':
                        $trazabilidad['desglose_egresos']['compras_mercaderia'] += $monto;
                        break;
                    case 'servicios':
                        $trazabilidad['desglose_egresos']['servicios'] += $monto;
                        break;
                    case 'impuestos':
                        $trazabilidad['desglose_egresos']['impuestos'] += $monto;
                        break;
                    default:
                        $trazabilidad['desglose_egresos']['otros_gastos'] += $monto;
                        break;
                }
            }
            
            $trazabilidad['ingresos_totales'] = array_sum($trazabilidad['desglose_ingresos']);
            $trazabilidad['egresos_totales'] = array_sum($trazabilidad['desglose_egresos']);
            $trazabilidad['flujo_neto'] = $trazabilidad['ingresos_totales'] - $trazabilidad['egresos_totales'];
            
            // Return response
            echo json_encode([
                'success' => true,
                'datos' => [
                    'ventas' => $ventas,
                    'productos' => [],
                    'ingresos' => [],
                    'egresos' => $egresos,
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
            
        } elseif ($action === 'obtener_egresos') {
            
            // Get egresos
            $stmtEgresos = $pdo->query("SELECT * FROM egresos ORDER BY fecha DESC, id DESC");
            $egresos = $stmtEgresos->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'egresos' => $egresos
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'crear_egreso') {
            
            // Validate required fields
            if (empty($input['concepto'])) {
                echo json_encode(['success' => false, 'message' => 'El concepto es obligatorio']);
                exit;
            }
            
            if (empty($input['monto']) || floatval($input['monto']) <= 0) {
                echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a 0']);
                exit;
            }
            
            // Map tipo to categoria
            $tipoToCategoriaMap = [
                'gasto' => 'gastos_fijos',
                'pago-prestamo' => 'sueldos',
                'compra-activo' => 'compras_mercaderia',
                'retiro-socio' => 'otros_gastos',
                'otro' => 'otros_gastos'
            ];
            
            $tipo = $input['tipo'] ?? 'gasto';
            $categoria = $tipoToCategoriaMap[$tipo] ?? 'gastos_fijos';
            
            // Prepare data
            $concepto = trim($input['concepto']);
            $descripcion = trim($input['descripcion'] ?? '');
            $monto = floatval($input['monto']);
            $fecha = $input['fecha'] ?? date('Y-m-d');
            
            // Insert into database
            $stmtInsert = $pdo->prepare("
                INSERT INTO egresos (concepto, descripcion, monto, tipo, categoria, fecha) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmtInsert->execute([
                $concepto,
                $descripcion,
                $monto,
                $tipo,
                $categoria,
                $fecha
            ]);
            
            if ($result) {
                $egresoId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Egreso creado exitosamente',
                    'id' => $egresoId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el egreso']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción POST no válida']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        
        // *** NEW: Handle PUT requests for updating egresos ***
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'actualizar_egreso') {
            
            // Validate required fields
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID del egreso es obligatorio']);
                exit;
            }
            
            if (empty($input['concepto'])) {
                echo json_encode(['success' => false, 'message' => 'El concepto es obligatorio']);
                exit;
            }
            
            if (empty($input['monto']) || floatval($input['monto']) <= 0) {
                echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a 0']);
                exit;
            }
            
            // Map tipo to categoria
            $tipoToCategoriaMap = [
                'gasto' => 'gastos_fijos',
                'pago-prestamo' => 'sueldos',
                'compra-activo' => 'compras_mercaderia',
                'retiro-socio' => 'otros_gastos',
                'otro' => 'otros_gastos'
            ];
            
            $id = intval($input['id']);
            $tipo = $input['tipo'] ?? 'gasto';
            $categoria = $tipoToCategoriaMap[$tipo] ?? 'gastos_fijos';
            
            // Prepare data
            $concepto = trim($input['concepto']);
            $descripcion = trim($input['descripcion'] ?? '');
            $monto = floatval($input['monto']);
            $fecha = $input['fecha'] ?? date('Y-m-d');
            
            // Update in database
            $stmtUpdate = $pdo->prepare("
                UPDATE egresos 
                SET concepto = ?, descripcion = ?, monto = ?, tipo = ?, categoria = ?, fecha = ?
                WHERE id = ?
            ");
            
            $result = $stmtUpdate->execute([
                $concepto,
                $descripcion,
                $monto,
                $tipo,
                $categoria,
                $fecha,
                $id
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Egreso actualizado exitosamente'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el egreso']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción PUT no válida']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
        // *** NEW: Handle DELETE requests for removing egresos ***
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'eliminar_egreso') {
            
            // Validate required fields
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID del egreso es obligatorio']);
                exit;
            }
            
            $id = intval($input['id']);
            
            // Check if egreso exists
            $stmtCheck = $pdo->prepare("SELECT id FROM egresos WHERE id = ?");
            $stmtCheck->execute([$id]);
            
            if (!$stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Egreso no encontrado']);
                exit;
            }
            
            // Delete from database
            $stmtDelete = $pdo->prepare("DELETE FROM egresos WHERE id = ?");
            $result = $stmtDelete->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Egreso eliminado exitosamente'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el egreso']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción DELETE no válida']);
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