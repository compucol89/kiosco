-- Crear tabla usuarios para Railway
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    password_hash VARCHAR(255),
    rol VARCHAR(20) DEFAULT 'admin',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario admin
INSERT INTO usuarios (nombre, email, password_hash, rol) 
VALUES ('Harold Zuluaga', 'admin@tayrona.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
