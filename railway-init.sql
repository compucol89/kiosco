-- Inicialización COMPLETA de base de datos para Railway
-- Replica EXACTAMENTE la estructura de tu BD local
-- Este script se ejecutará automáticamente en Railway

-- Crear tablas principales si no existen
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    precio_costo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    categoria VARCHAR(100) DEFAULT 'General',
    codigo VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    password_hash VARCHAR(255),
    rol ENUM('admin', 'cajero', 'vendedor') DEFAULT 'cajero',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(100) DEFAULT 'Consumidor Final',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    metodo_pago VARCHAR(50) NOT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    monto_total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'completado',
    numero_comprobante VARCHAR(50),
    detalles_json TEXT,
    cae VARCHAR(50),
    comprobante_fiscal VARCHAR(100),
    tipo_comprobante VARCHAR(20) DEFAULT 'ticket',
    usuario_id INT,
    INDEX idx_ventas_fecha (fecha),
    INDEX idx_ventas_metodo (metodo_pago),
    INDEX idx_ventas_estado (estado)
);

CREATE TABLE IF NOT EXISTS turnos_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    monto_apertura DECIMAL(12,2) NOT NULL,
    monto_cierre DECIMAL(12,2) NULL,
    total_entradas DECIMAL(12,2) DEFAULT 0.00,
    total_salidas DECIMAL(12,2) DEFAULT 0.00,
    diferencia DECIMAL(12,2) NULL,
    ventas_efectivo DECIMAL(12,2) DEFAULT 0.00,
    ventas_transferencia DECIMAL(12,2) DEFAULT 0.00,
    ventas_tarjeta DECIMAL(12,2) DEFAULT 0.00,
    ventas_qr DECIMAL(12,2) DEFAULT 0.00,
    cantidad_ventas INT DEFAULT 0,
    estado ENUM('abierto','cerrado','suspendido') DEFAULT 'abierto',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS movimientos_caja_detallados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    tipo ENUM('ingreso','egreso') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(100),
    referencia VARCHAR(100),
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    FOREIGN KEY (turno_id) REFERENCES turnos_caja(id)
);

-- Insertar usuario administrador por defecto
INSERT IGNORE INTO usuarios (id, nombre, email, password_hash, rol) 
VALUES (1, 'Harold Zuluaga', 'harold@tayrona.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertar algunos productos de ejemplo
INSERT IGNORE INTO productos (id, nombre, precio_venta, precio_costo, stock, categoria) VALUES
(1, 'Coca Cola 500ml', 75.00, 50.00, 100, 'Bebidas'),
(2, 'Agua Mineral 500ml', 30.00, 20.00, 200, 'Bebidas'),
(3, 'Caramelos Sugus', 150.00, 80.00, 50, 'Golosinas');

-- Crear índices optimizados
CREATE INDEX IF NOT EXISTS idx_productos_stock ON productos(stock, activo);
CREATE INDEX IF NOT EXISTS idx_ventas_performance ON ventas(fecha, estado, metodo_pago);
CREATE INDEX IF NOT EXISTS idx_turnos_estado ON turnos_caja(estado, fecha_apertura);
