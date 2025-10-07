<?php
// Incluir middleware de CORS
require_once 'cors_middleware.php';

// Asegurar que el tipo de contenido es JSON
header("Content-Type: application/json; charset=UTF-8");

// Incluir archivo de configuración
require_once 'config.php';

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Ruta del endpoint (para expandir la API en el futuro)
$request = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

// Para DELETE y PUT con parámetros en query string
if (($method === 'DELETE' || $method === 'PUT') && isset($_GET['id'])) {
    $request = $_GET['id'];
}

// Verificar si la tabla existe y crearla si es necesario
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'productos'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // La tabla no existe, crearla con estructura completa
        $sql = "CREATE TABLE productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50),
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            precio_costo DECIMAL(10,2) DEFAULT 0,
            porcentaje_ganancia DECIMAL(10,2) DEFAULT 40,
            precio_venta DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            stock_actual INT DEFAULT 0,
            stock_minimo INT DEFAULT 10,
            categoria VARCHAR(100) DEFAULT 'Sin categoría',
            barcode VARCHAR(100),
            proveedor VARCHAR(255),
            impuesto VARCHAR(50) DEFAULT 'IVA 21%',
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insertar algunos productos de ejemplo con más campos
        $productos_muestra = [
            [
                'codigo' => 'BIZC001',
                'nombre' => 'Bizcochos Surtidos',
                'descripcion' => 'Deliciosos bizcochos caseros surtidos',
                'precio_costo' => 80,
                'precio_venta' => 120,
                'stock' => 25,
                'categoria' => 'Panadería'
            ],
            [
                'codigo' => 'ACEI001',
                'nombre' => 'Aceite de Girasol 900ml',
                'descripcion' => 'Aceite de girasol puro primera prensada',
                'precio_costo' => 180,
                'precio_venta' => 250,
                'stock' => 15,
                'categoria' => 'Aceites'
            ],
            [
                'codigo' => 'AGUA001',
                'nombre' => 'Agua Mineral 500ml',
                'descripcion' => 'Agua mineral natural sin gas',
                'precio_costo' => 30,
                'precio_venta' => 50,
                'stock' => 100,
                'categoria' => 'Bebidas'
            ],
            [
                'codigo' => 'LECH001',
                'nombre' => 'Leche Entera 1L',
                'descripcion' => 'Leche entera pasteurizada',
                'precio_costo' => 120,
                'precio_venta' => 180,
                'stock' => 3,
                'categoria' => 'Lácteos'
            ],
            [
                'codigo' => 'PAN001',
                'nombre' => 'Pan Francés',
                'descripcion' => 'Pan francés tradicional recién horneado',
                'precio_costo' => 50,
                'precio_venta' => 80,
                'stock' => 0,
                'categoria' => 'Panadería'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio_costo, precio_venta, stock, stock_actual, categoria, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($productos_muestra as $producto) {
            $stmt->execute([
                $producto['codigo'],
                $producto['nombre'],
                $producto['descripcion'],
                $producto['precio_costo'],
                $producto['precio_venta'],
                $producto['stock'],
                $producto['stock'], // stock_actual igual a stock inicial
                $producto['categoria'],
                $producto['codigo'] // barcode igual al código
            ]);
        }
        
        error_log("Tabla productos creada con datos de ejemplo actualizados");
    } else {
        // Verificar si existen las columnas necesarias y agregarlas si no existen
        $columnas_verificar = [
            'descripcion' => 'ALTER TABLE productos ADD COLUMN descripcion TEXT',
            'stock_actual' => 'ALTER TABLE productos ADD COLUMN stock_actual INT DEFAULT 0',
            'created_at' => 'ALTER TABLE productos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE productos ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'activo' => 'ALTER TABLE productos ADD COLUMN activo BOOLEAN DEFAULT TRUE'
        ];
        
        foreach ($columnas_verificar as $columna => $sql_alter) {
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM productos LIKE '$columna'");
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    $pdo->exec($sql_alter);
                }
            } catch (PDOException $e) {
                // Columna ya existe o error al agregar, continuar
            }
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al verificar/crear tabla de productos: ' . $e->getMessage()]);
    exit;
}

// Función para limpiar y validar datos
function validarDatosProducto($data, $esActualizacion = false) {
    $errores = [];
    
    // Validar nombre (requerido siempre)
    if (!$esActualizacion && (empty($data['nombre']) || trim($data['nombre']) === '')) {
        $errores[] = 'El nombre del producto es obligatorio';
    }
    
    // Validar precio de venta (opcional - puede ser 0 para completar después)
    if (!$esActualizacion && isset($data['precio_venta']) && floatval($data['precio_venta']) < 0) {
        $errores[] = 'El precio de venta no puede ser negativo';
    }
    
    // Si se proporciona precio de venta en actualización, validarlo
    if ($esActualizacion && isset($data['precio_venta']) && floatval($data['precio_venta']) < 0) {
        $errores[] = 'El precio de venta no puede ser negativo';
    }
    
    // Validar datos numéricos
    if (isset($data['precio_costo']) && floatval($data['precio_costo']) < 0) {
        $errores[] = 'El precio de costo no puede ser negativo';
    }
    
    if (isset($data['stock']) && intval($data['stock']) < 0) {
        $errores[] = 'El stock no puede ser negativo';
    }
    
    return $errores;
}

// Procesar según el método HTTP
switch ($method) {
    case 'GET':
        // Obtener productos
        if (!empty($request) && is_numeric($request)) {
            // Obtener un producto específico por ID
            try {
                $stmt = $pdo->prepare("SELECT *, stock_actual as stock FROM productos WHERE id = ? AND activo = TRUE");
                $stmt->execute([$request]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($producto) {
                    echo json_encode($producto);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Producto no encontrado']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al obtener producto: ' . $e->getMessage()]);
            }
        } else {
            // Obtener todos los productos
            try {
                $sql = "SELECT *, 
                       COALESCE(stock_actual, stock) as stock,
                       CASE 
                           WHEN COALESCE(stock_actual, stock) = 0 THEN 'sin_stock'
                           WHEN COALESCE(stock_actual, stock) <= stock_minimo THEN 'bajo_stock'
                           ELSE 'normal'
                       END as estado_stock
                       FROM productos 
                       WHERE activo = TRUE";
                
                // Agregar filtros opcionales
                $params = [];
                if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                    $sql .= " AND categoria = ?";
                    $params[] = $_GET['categoria'];
                }
                
                if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                    $sql .= " AND (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ?)";
                    $buscar = '%' . $_GET['buscar'] . '%';
                    $params[] = $buscar;
                    $params[] = $buscar;
                    $params[] = $buscar;
                }
                
                // Agregar ordenamiento
                $orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'nombre';
                $direction = isset($_GET['direction']) && $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';
                
                // Validar campo de ordenamiento
                $validFields = ['id', 'codigo', 'nombre', 'precio_costo', 'precio_venta', 'stock', 'categoria', 'created_at'];
                if (!in_array($orderBy, $validFields)) {
                    $orderBy = 'nombre';
                }
                
                // Validación estricta para ORDER BY y DIRECTION
$validFields = ['id', 'codigo', 'nombre', 'precio_costo', 'precio_venta', 'stock', 'categoria', 'created_at'];
$validDirections = ['ASC', 'DESC'];

$orderBy = in_array($orderBy, $validFields) ? $orderBy : 'nombre';
$direction = in_array($direction, $validDirections) ? $direction : 'ASC';

$sql .= " ORDER BY `{$orderBy}` {$direction}";
                
                // ⚡ OPTIMIZACIÓN: Paginación inteligente
                // Parámetros disponibles:
                // - ?admin=true : Carga TODOS los productos (para páginas administrativas)
                // - ?all=true : Carga TODOS los productos (alias para compatibilidad)
                // - Sin parámetros: Aplica paginación (para punto de venta optimizado)
                $cargarTodos = isset($_GET['all']) && $_GET['all'] === 'true';
                $esAdministracion = isset($_GET['admin']) && $_GET['admin'] === 'true';
                
                if (!$cargarTodos && !$esAdministracion) {
                    // Solo paginar para punto de venta
                    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? 
                             min(intval($_GET['limit']), 1000) : 50; // Máximo 1000, defecto 50
                    $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? 
                             max(intval($_GET['offset']), 0) : 0;
                    $sql .= " LIMIT $limit OFFSET $offset";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Obtener conteo total para paginación
                $sqlCount = "SELECT COUNT(*) as total FROM productos WHERE activo = TRUE";
                if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                    $sqlCount .= " AND categoria = ?";
                }
                if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                    $sqlCount .= " AND (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ?)";
                }
                
                $stmtCount = $pdo->prepare($sqlCount);
                $stmtCount->execute($params);
                $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
                
                $respuesta = [
                    'productos' => $productos,
                    'total' => intval($total),
                    'count' => count($productos)
                ];
                
                echo json_encode($productos); // Por compatibilidad, devolver solo los productos
            } catch (PDOException $e) {
                error_log("Error al obtener productos: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
            }
        }
        break;
    
    case 'POST':
        // Crear un nuevo producto
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'JSON inválido']);
                exit;
            }
            
            // Validar datos
            $errores = validarDatosProducto($data, false);
            if (!empty($errores)) {
                http_response_code(400);
                echo json_encode(['message' => 'Errores de validación', 'errores' => $errores]);
                exit;
            }
            
            // Preparar datos con valores por defecto
            $codigo = !empty($data['codigo']) ? trim($data['codigo']) : null;
            $nombre = trim($data['nombre']);
            $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : '';
            $precio_costo = floatval($data['precio_costo'] ?? 0);
            $precio_venta = floatval($data['precio_venta']);
            $stock = intval($data['stock'] ?? 0);
            $categoria = !empty($data['categoria']) ? trim($data['categoria']) : 'Sin categoría';
            $barcode = !empty($data['codigo']) ? trim($data['codigo']) : null;
            $aplica_descuento_forma_pago = isset($data['aplica_descuento_forma_pago']) ? ($data['aplica_descuento_forma_pago'] ? 1 : 0) : 1; // Default TRUE
            
            // Calcular porcentaje de ganancia automáticamente
            $porcentaje_ganancia = 0;
            if ($precio_costo > 0) {
                $porcentaje_ganancia = (($precio_venta - $precio_costo) / $precio_costo) * 100;
            }
            
            $stmt = $pdo->prepare("INSERT INTO productos 
                (codigo, nombre, descripcion, precio_costo, porcentaje_ganancia, precio_venta, stock, stock_actual, categoria, barcode, aplica_descuento_forma_pago) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $codigo,
                $nombre,
                $descripcion,
                $precio_costo,
                $porcentaje_ganancia,
                $precio_venta,
                $stock,
                $stock, // stock_actual igual a stock inicial
                $categoria,
                $barcode,
                $aplica_descuento_forma_pago
            ]);
            
            $nuevoId = $pdo->lastInsertId();
            
            // Obtener el producto recién creado
            $stmt = $pdo->prepare("SELECT *, stock_actual as stock FROM productos WHERE id = ?");
            $stmt->execute([$nuevoId]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(201);
            echo json_encode($producto);
        } catch (PDOException $e) {
            // Note: Ya no validamos duplicados de código porque ahora son permitidos
            if ($e->getCode() == 23000) { // Código de error para duplicados
                $message = $e->getMessage();
                if (strpos($message, 'nombre') !== false) {
                    http_response_code(409);
                    echo json_encode(['message' => "El nombre '{$nombre}' ya existe en otro producto"]);
                } else {
                    // Otros duplicados que no sean código (códigos están permitidos)
                    error_log("Posible duplicado no manejado: " . $message);
                    http_response_code(409);
                    echo json_encode(['message' => 'Ya existe un producto con datos similares']);
                }
            } else {
                error_log("Error al crear producto: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['message' => 'Error en base de datos: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['message' => 'Error en los datos: ' . $e->getMessage()]);
        }
        break;
    
    case 'PUT':
        // Actualizar un producto existente
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'JSON inválido']);
                exit;
            }
            
            $id = null;
            
            // Obtener ID desde la data o desde la URL
            if (isset($data['id'])) {
                $id = $data['id'];
            } elseif (!empty($request) && is_numeric($request)) {
                $id = $request;
            }
            
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode(['message' => 'ID de producto inválido']);
                exit;
            }
            
            // Validar datos
            $errores = validarDatosProducto($data, true);
            if (!empty($errores)) {
                http_response_code(400);
                echo json_encode(['message' => 'Errores de validación', 'errores' => $errores]);
                exit;
            }
            
            // Construir dinámicamente la consulta SQL
            $updateFields = [];
            $params = [];
            
            $camposPermitidos = ['codigo', 'nombre', 'descripcion', 'precio_costo', 'precio_venta', 'stock', 'categoria', 'barcode', 'aplica_descuento_forma_pago'];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $camposPermitidos)) {
                    if ($key === 'stock') {
                        // Actualizar tanto stock como stock_actual
                        $updateFields[] = "stock = ?";
                        $updateFields[] = "stock_actual = ?";
                        $params[] = intval($value);
                        $params[] = intval($value);
                    } elseif ($key === 'precio_costo' || $key === 'precio_venta') {
                        $updateFields[] = "$key = ?";
                        $params[] = floatval($value);
                    } elseif ($key === 'aplica_descuento_forma_pago') {
                        $updateFields[] = "$key = ?";
                        $params[] = $value ? 1 : 0; // Convertir a boolean
                    } else {
                        $updateFields[] = "$key = ?";
                        $params[] = is_string($value) ? trim($value) : $value;
                    }
                }
            }
            
            // Recalcular porcentaje de ganancia si se actualizan los precios
            if (isset($data['precio_costo']) || isset($data['precio_venta'])) {
                // Obtener datos actuales del producto
                $stmt = $pdo->prepare("SELECT precio_costo, precio_venta FROM productos WHERE id = ?");
                $stmt->execute([$id]);
                $productoActual = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productoActual) {
                    $precio_costo_final = isset($data['precio_costo']) ? floatval($data['precio_costo']) : $productoActual['precio_costo'];
                    $precio_venta_final = isset($data['precio_venta']) ? floatval($data['precio_venta']) : $productoActual['precio_venta'];
                    
                    if ($precio_costo_final > 0) {
                        $porcentaje_ganancia = (($precio_venta_final - $precio_costo_final) / $precio_costo_final) * 100;
                        $updateFields[] = "porcentaje_ganancia = ?";
                        $params[] = $porcentaje_ganancia;
                    }
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['message' => 'No hay campos válidos para actualizar']);
                exit;
            }
            
            // Agregar updated_at
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            
            $sql = "UPDATE productos SET " . implode(', ', $updateFields) . " WHERE id = ? AND activo = TRUE";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['message' => 'Producto no encontrado o sin cambios']);
            } else {
                // Obtener el producto actualizado
                $stmt = $pdo->prepare("SELECT *, stock_actual as stock FROM productos WHERE id = ?");
                $stmt->execute([$id]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode($producto);
            }
        } catch (PDOException $e) {
            error_log("Error al actualizar producto: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar el producto: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['message' => 'Error en los datos: ' . $e->getMessage()]);
        }
        break;
    
    case 'DELETE':
        // Eliminar un producto (soft delete)
        if (empty($request) || !is_numeric($request)) {
            http_response_code(400);
            echo json_encode(['message' => 'ID de producto inválido']);
            exit;
        }
        
        try {
            // Usar soft delete en lugar de eliminación física
            $stmt = $pdo->prepare("UPDATE productos SET activo = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND activo = TRUE");
            $stmt->execute([$request]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['message' => 'Producto no encontrado']);
            } else {
                http_response_code(200);
                echo json_encode(['message' => 'Producto eliminado correctamente']);
            }
        } catch (PDOException $e) {
            error_log("Error al eliminar producto: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'Error al eliminar el producto: ' . $e->getMessage()]);
        }
        break;
    
    default:
        // Método no permitido
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}
?> 