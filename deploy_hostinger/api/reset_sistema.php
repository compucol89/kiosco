<?php
/**
 * SISTEMA DE REINICIO MEJORADO - Actualizado para todas las tablas nuevas
 * Incluye todas las tablas que se han añadido durante el desarrollo
 */

// Asegurar que no haya salida antes de los headers
ob_start();

// Manejar errores de PHP para que no interfieran con la respuesta JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Headers CORS y JSON deben ser lo primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Función para enviar respuesta JSON y terminar
function enviarRespuesta($data, $httpCode = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para registrar errores
function registrarError($mensaje, $data = null) {
    $log = "[" . date('Y-m-d H:i:s') . "] RESET_SISTEMA_MEJORADO: " . $mensaje;
    if ($data) {
        $log .= " | Datos: " . json_encode($data);
    }
    error_log($log);
}

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    enviarRespuesta(['status' => 'OK'], 200);
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    registrarError("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
    enviarRespuesta([
        'success' => false,
        'mensaje' => 'Método no permitido. Se requiere POST.'
    ], 405);
}

try {
    // Obtener los datos enviados
    $input = file_get_contents('php://input');
    if (empty($input)) {
        registrarError("No se recibieron datos en el cuerpo de la solicitud");
        enviarRespuesta([
            'success' => false,
            'mensaje' => 'No se recibieron datos en la solicitud'
        ], 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        registrarError("Error al decodificar JSON: " . json_last_error_msg(), $input);
        enviarRespuesta([
            'success' => false,
            'mensaje' => 'Datos JSON inválidos: ' . json_last_error_msg()
        ], 400);
    }

    registrarError("Datos recibidos correctamente", $data);

    // Verificar clave de confirmación
    if (!isset($data['clave_confirmacion']) || $data['clave_confirmacion'] !== 'REINICIAR_SISTEMA_CONFIRMAR') {
        registrarError("Clave de confirmación inválida", $data['clave_confirmacion'] ?? 'no proporcionada');
        enviarRespuesta([
            'success' => false,
            'mensaje' => 'Clave de confirmación inválida'
        ], 403);
    }

    // Verificar usuario
    if (!isset($data['usuario_id'])) {
        registrarError("Usuario ID no proporcionado");
        enviarRespuesta([
            'success' => false,
            'mensaje' => 'Se requiere identificación de usuario'
        ], 400);
    }

    // Incluir la conexión a la base de datos
    require_once 'bd_conexion.php';

    // Inicializar la conexión a la base de datos
    $pdo = Conexion::obtenerConexion();
    
    if ($pdo === null) {
        registrarError("No se pudo conectar a la base de datos");
        enviarRespuesta([
            'success' => false,
            'mensaje' => 'No se pudo conectar a la base de datos'
        ], 500);
    }

    registrarError("Conexión a base de datos establecida");

    // *** LÓGICA MEJORADA DE SELECCIÓN DE TABLAS ***
    
    // Obtener lista de todas las tablas disponibles
    $stmt = $pdo->query("SHOW TABLES");
    $todasLasTablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    registrarError("Tablas disponibles: " . implode(", ", $todasLasTablas));
    
    // *** TABLAS PROTEGIDAS - NUNCA SE BORRAN ***
    $tablasProtegidas = [
        'usuarios',         // Usuarios del sistema
        'configuracion',    // Configuraciones del sistema
        'security_logs',    // Logs de seguridad importantes
        'permisos_roles'    // Sistema de permisos
    ];
    
    // Obtener opciones de eliminación del frontend
    $opciones = isset($data['opciones']) ? $data['opciones'] : [];
    
    // Si NO se debe eliminar productos, agregarlo a tablas protegidas
    if (!isset($opciones['eliminarProductos']) || !$opciones['eliminarProductos']) {
        $tablasProtegidas[] = 'productos';
        registrarError("Productos protegidos según opciones del usuario");
    } else {
        registrarError("Productos serán eliminados según opciones del usuario");
    }
    
    // *** TABLAS DE BACKUP - SE BORRAN SIEMPRE (limpieza) ***
    $tablasBackup = [];
    foreach ($todasLasTablas as $tabla) {
        if (strpos($tabla, '_backup') !== false || 
            strpos($tabla, '_old') !== false ||
            strpos($tabla, 'repair_backup_') === 0) {
            $tablasBackup[] = $tabla;
        }
    }
    
    // *** CATEGORIZACIÓN DE TABLAS OPERACIONALES ***
    $categoriasTablas = [
        'ventas' => [
            'ventas',
            'venta_detalles', 
            'detalle_ventas'
        ],
        'inventario' => [
            'movimientos_inventario',
            'auditoria_inventario'
        ],
        'financiero' => [
            'egresos',
            'egresos_gastos',
            'gastos_fijos_mensuales',
            'ingresos_extra'
        ],
        'caja' => [
            'caja',
            'caja_sesiones', 
            'caja_movimientos',
            'movimientos_caja',
            'turnos_caja',
            'movimientos_caja_detallados',
            'historial_turnos_caja',
            'configuracion_alertas_caja'
        ],
        'clientes' => [
            'clientes',
            'proveedores'
        ],
        'sistema' => [
            'metodos_pago'
        ]
    ];
    
    // Combinar todas las tablas operacionales
    $tablasOperacionales = [];
    foreach ($categoriasTablas as $categoria => $tablas) {
        $tablasOperacionales = array_merge($tablasOperacionales, $tablas);
    }
    
    // *** RESET SEGÚN OPCIONES DEL USUARIO ***
    $tablasALimpiar = [];
    
    registrarError("RESET CON OPCIONES: Eliminando según selección del usuario");
    registrarError("Opciones recibidas: " . json_encode($opciones));
    
    // Agregar todas las tablas de backup (siempre se limpian)
    $tablasALimpiar = array_merge($tablasALimpiar, $tablasBackup);
    
    // Procesar según las opciones seleccionadas
    foreach ($todasLasTablas as $tabla) {
        // Verificar que la tabla no esté protegida
        if (in_array($tabla, $tablasProtegidas)) {
            registrarError("Tabla protegida conservada: {$tabla}");
            continue;
        }
        
        // Determinar si la tabla debe ser limpiada según las opciones
        $debeEliminar = false;
        
        // Verificar categorías según opciones
        if (isset($opciones['eliminarVentas']) && $opciones['eliminarVentas'] && 
            in_array($tabla, $categoriasTablas['ventas'])) {
            $debeEliminar = true;
            registrarError("Tabla de ventas a eliminar: {$tabla}");
        }
        
        if (isset($opciones['eliminarCaja']) && $opciones['eliminarCaja'] && 
            (in_array($tabla, $categoriasTablas['caja']) || in_array($tabla, $categoriasTablas['financiero']))) {
            $debeEliminar = true;
            registrarError("Tabla de caja/financiero a eliminar: {$tabla}");
        }
        
        if (isset($opciones['eliminarProductos']) && $opciones['eliminarProductos'] && 
            ($tabla === 'productos' || in_array($tabla, $categoriasTablas['inventario']))) {
            $debeEliminar = true;
            registrarError("Tabla de productos/inventario a eliminar: {$tabla}");
        }
        
        if (isset($opciones['eliminarClientes']) && $opciones['eliminarClientes'] && 
            in_array($tabla, $categoriasTablas['clientes'])) {
            $debeEliminar = true;
            registrarError("Tabla de clientes a eliminar: {$tabla}");
        }
        
        // Tablas del sistema y otras (si no están en ninguna categoría específica)
        if (!in_array($tabla, array_merge(...array_values($categoriasTablas))) && 
            !in_array($tabla, $tablasProtegidas)) {
            $debeEliminar = true;
            registrarError("Tabla de sistema/otras a eliminar: {$tabla}");
        }
        
        if ($debeEliminar) {
            $tablasALimpiar[] = $tabla;
            registrarError("Agregando tabla para limpieza: {$tabla}");
        } else {
            registrarError("Tabla conservada según opciones: {$tabla}");
        }
    }
    
    // Eliminar duplicados
    $tablasALimpiar = array_unique($tablasALimpiar);
    
    registrarError("Tablas protegidas: " . implode(", ", $tablasProtegidas));
    registrarError("Tablas de backup encontradas: " . implode(", ", $tablasBackup));
    registrarError("Tablas a limpiar: " . implode(", ", $tablasALimpiar));

    // 🔒 CIERRE AUTOMÁTICO DE CAJA ANTES DEL RESET
    if (in_array('turnos_caja', $tablasALimpiar) || (isset($data['opciones']['eliminarCaja']) && $data['opciones']['eliminarCaja'])) {
        try {
            // Verificar si hay turnos abiertos
            $stmt = $pdo->prepare("SELECT id, monto_apertura, efectivo_teorico FROM turnos_caja WHERE estado = 'abierto'");
            $stmt->execute();
            $turnosAbiertos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($turnosAbiertos as $turno) {
                // Cerrar turno automáticamente usando el efectivo teórico
                $stmt = $pdo->prepare("
                    UPDATE turnos_caja 
                    SET estado = 'cerrado', 
                        fecha_cierre = NOW(), 
                        monto_cierre = efectivo_teorico,
                        diferencia = 0,
                        notas = CONCAT(COALESCE(notas, ''), 'CIERRE AUTOMÁTICO POR RESET DEL SISTEMA - ', NOW())
                    WHERE id = ?
                ");
                $stmt->execute([$turno['id']]);
                registrarError("Turno #{$turno['id']} cerrado automáticamente por reset del sistema");
            }
            
            if (count($turnosAbiertos) > 0) {
                registrarError("Se cerraron automáticamente " . count($turnosAbiertos) . " turnos abiertos antes del reset");
            }
            
        } catch (Exception $e) {
            registrarError("Error al cerrar turnos automáticamente: " . $e->getMessage());
            // No fallar todo el reset por esto, solo registrar el error
        }
    }

    if (empty($tablasALimpiar)) {
        registrarError("No se encontraron tablas para limpiar");
        enviarRespuesta([
            'success' => true,
            'mensaje' => 'No hay tablas que limpiar. El sistema ya está limpio.',
            'tablas_procesadas' => 0,
            'tablas_limpiadas' => 0,
            'tablas_protegidas' => $tablasProtegidas
        ]);
    }

    $resultados = [];
    $tablasLimpiadas = 0;
    $transaccionIniciada = false;
    
    // Desactivar verificación de claves foráneas temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    registrarError("Verificación de claves foráneas desactivada");
    
    // Comenzar transacción
    $pdo->beginTransaction();
    $transaccionIniciada = true;
    registrarError("Transacción iniciada");
    
    // *** LIMPIAR TABLAS CON MANEJO DE ERRORES MEJORADO ***
    foreach ($tablasALimpiar as $tabla) {
        try {
            // Verificar si la tabla realmente existe (doble verificación)
            $checkQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute([$tabla]);
            $existe = $stmt->fetchColumn() > 0;
            
            if ($existe) {
                // La tabla existe, limpiarla
                $pdo->exec("TRUNCATE TABLE `{$tabla}`");
                $resultados[] = [
                    'tabla' => $tabla,
                    'estado' => 'LIMPIADA',
                    'categoria' => determinarCategoriaTabla($tabla, $categoriasTablas)
                ];
                $tablasLimpiadas++;
                registrarError("Tabla {$tabla} limpiada exitosamente");
            } else {
                $resultados[] = [
                    'tabla' => $tabla,
                    'estado' => 'NO_EXISTE',
                    'mensaje' => 'La tabla no existe'
                ];
                registrarError("Tabla {$tabla} no existe, omitiendo");
            }
            
        } catch (PDOException $e) {
            // Error al limpiar tabla específica
            $resultados[] = [
                'tabla' => $tabla,
                'estado' => 'ERROR',
                'mensaje' => $e->getMessage()
            ];
            registrarError("Error al limpiar tabla {$tabla}: " . $e->getMessage());
            
            // Para tablas críticas, abortar todo
            if (in_array($tabla, ['ventas', 'productos', 'egresos'])) {
                throw new Exception("Error crítico al procesar la tabla {$tabla}: " . $e->getMessage());
            }
        }
    }
    
    // Reactivar verificación de claves foráneas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    registrarError("Verificación de claves foráneas reactivada");
    
    // Confirmar cambios
    if ($pdo->inTransaction()) {
        $pdo->commit();
        registrarError("Transacción confirmada");
    }
    
    // *** CREAR REGISTRO DE AUDITORÍA ***
    $fecha_reinicio = date('Y-m-d H:i:s');
    
    registrarError("Reinicio completado exitosamente. Tablas limpiadas: {$tablasLimpiadas}");
    
    // Respuesta exitosa detallada
    $mensajeReset = "🎉 RESET COMPLETO: Sistema reiniciado exitosamente. Se eliminaron TODOS los registros excepto usuarios y productos. Tablas limpiadas: {$tablasLimpiadas}.";
    
    // Agregar información de turnos cerrados si aplica
    if (isset($turnosAbiertos) && count($turnosAbiertos) > 0) {
        $mensajeReset .= " Se cerraron automáticamente " . count($turnosAbiertos) . " turnos abiertos antes del reset.";
    }
    
    enviarRespuesta([
        'success' => true,
        'mensaje' => $mensajeReset,
        'fecha_reinicio' => $fecha_reinicio,
        'estadisticas' => [
            'tablas_totales_sistema' => count($todasLasTablas),
            'tablas_protegidas' => count($tablasProtegidas),
            'tablas_procesadas' => count($tablasALimpiar),
            'tablas_limpiadas' => $tablasLimpiadas,
            'tablas_backup_eliminadas' => count($tablasBackup),
            'turnos_cerrados_automaticamente' => isset($turnosAbiertos) ? count($turnosAbiertos) : 0
        ],
        'tablas_protegidas' => $tablasProtegidas,
        'categorias_limpiadas' => array_keys($categoriasTablas),
        'resultados_detallados' => $resultados,
        'cierre_automatico_caja' => isset($turnosAbiertos) && count($turnosAbiertos) > 0
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($pdo) && isset($transaccionIniciada) && $transaccionIniciada && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
            registrarError("Rollback ejecutado en catch principal");
        } catch (Exception $rollbackError) {
            registrarError("Error en rollback: " . $rollbackError->getMessage());
        }
    }
    
    // Registrar el error para debugging
    registrarError("Error crítico: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    
    // Enviar respuesta de error
    enviarRespuesta([
        'success' => false,
        'mensaje' => '❌ Error al reiniciar el sistema: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], 500);
}

// Función auxiliar para determinar categoría de tabla
function determinarCategoriaTabla($tabla, $categoriasTablas) {
    foreach ($categoriasTablas as $categoria => $tablas) {
        if (in_array($tabla, $tablas)) {
            return $categoria;
        }
    }
    
    if (strpos($tabla, '_backup') !== false || strpos($tabla, '_old') !== false) {
        return 'backup';
    }
    
    return 'otros';
}
?> 