-- ========================================
-- 🔐 PROPUESTA: NUEVAS TABLAS DE SEGURIDAD
-- ========================================
-- 
-- Sistema: Tayrona Almacén - Kiosco POS
-- Fecha: 21 de Octubre, 2025
-- 
-- IMPORTANTE: Este script NO se ejecuta automáticamente
-- Requiere aprobación del usuario antes de aplicar
-- 
-- ========================================

USE kiosco;

-- ========================================
-- 1. TABLA: sesiones
-- Gestión de tokens con expiración
-- ========================================

CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL COMMENT 'IPv4 o IPv6',
    user_agent TEXT NULL COMMENT 'Navegador y SO del cliente',
    device_fingerprint VARCHAR(255) NULL COMMENT 'Fingerprint único del dispositivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuando se creó la sesión',
    expires_at TIMESTAMP NOT NULL COMMENT 'Cuando expira el token (8 horas default)',
    last_activity TIMESTAMP NULL COMMENT 'Última actividad del usuario',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Si la sesión está activa (logout = false)',
    
    -- Índices para performance
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id),
    INDEX idx_expires (expires_at, is_active),
    INDEX idx_active_sessions (usuario_id, is_active, expires_at),
    
    -- Foreign key
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Gestión de sesiones con tokens y expiración';

-- ========================================
-- 2. TABLA: audit_log
-- Registro de auditoría de acciones importantes
-- ========================================

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL COMMENT 'ID del usuario (NULL si no autenticado)',
    username VARCHAR(50) NULL COMMENT 'Username (redundante para casos de usuario eliminado)',
    accion VARCHAR(100) NOT NULL COMMENT 'Descripción de la acción (ej: login, crear_usuario)',
    modulo VARCHAR(50) NOT NULL COMMENT 'Módulo afectado (ej: usuarios, ventas, caja)',
    detalles JSON NULL COMMENT 'Detalles adicionales en formato JSON',
    ip_address VARCHAR(45) NULL COMMENT 'IP del cliente',
    user_agent TEXT NULL COMMENT 'Navegador del cliente',
    resultado ENUM('exito','fallo') DEFAULT 'exito' COMMENT 'Si la acción fue exitosa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp de la acción',
    
    -- Índices para búsquedas rápidas
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_fecha (created_at),
    INDEX idx_resultado (resultado),
    INDEX idx_usuario_fecha (usuario_id, created_at),
    
    -- Foreign key (ON DELETE SET NULL para preservar logs si usuario se elimina)
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de auditoría de acciones del sistema';

-- ========================================
-- 3. TABLA: login_attempts
-- Registro de intentos de login (éxito y fallo)
-- ========================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL COMMENT 'Username intentado (puede no existir)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP del cliente',
    user_agent TEXT NULL COMMENT 'Navegador del cliente',
    success BOOLEAN DEFAULT FALSE COMMENT 'Si el login fue exitoso',
    fail_reason ENUM('usuario_no_existe','password_incorrecta','cuenta_bloqueada','otro') NULL COMMENT 'Razón del fallo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp del intento',
    
    -- Índices para rate limiting y análisis
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_username_time (username, created_at),
    INDEX idx_success (success),
    INDEX idx_ip_success (ip_address, success, created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de todos los intentos de login';

-- ========================================
-- 4. VISTA: sesiones_activas
-- Vista conveniente de sesiones activas y no expiradas
-- ========================================

CREATE OR REPLACE VIEW sesiones_activas AS
SELECT 
    s.id,
    s.usuario_id,
    u.username,
    u.nombre,
    u.role,
    s.token,
    s.ip_address,
    s.created_at,
    s.expires_at,
    s.last_activity,
    TIMESTAMPDIFF(MINUTE, NOW(), s.expires_at) AS minutos_restantes
FROM sesiones s
INNER JOIN usuarios u ON s.usuario_id = u.id
WHERE s.is_active = TRUE 
  AND s.expires_at > NOW()
ORDER BY s.last_activity DESC;

-- ========================================
-- 5. EVENTO: Limpiar sesiones expiradas
-- Auto-limpieza diaria de sesiones viejas
-- ========================================

-- Activar el scheduler de eventos (solo una vez)
SET GLOBAL event_scheduler = ON;

-- Evento para limpiar sesiones expiradas cada día a las 3 AM
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 03:00:00')
DO
BEGIN
    -- Marcar sesiones expiradas como inactivas
    UPDATE sesiones 
    SET is_active = FALSE 
    WHERE expires_at < NOW() 
      AND is_active = TRUE;
    
    -- Eliminar sesiones muy antiguas (más de 30 días)
    DELETE FROM sesiones 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Log de limpieza
    INSERT INTO audit_log (username, accion, modulo, resultado, detalles)
    VALUES ('system', 'cleanup_sesiones_expiradas', 'sesiones', 'exito', 
            JSON_OBJECT('deleted_sessions', ROW_COUNT()));
END;

-- ========================================
-- 6. ÍNDICES ADICIONALES EN USUARIOS
-- Optimización de queries comunes
-- ========================================

-- Índice para búsqueda por role (usado frecuentemente)
CREATE INDEX IF NOT EXISTS idx_role ON usuarios(role);

-- Índice para búsqueda por username (ya existe UNIQUE, pero optimizamos)
-- (El UNIQUE KEY ya crea un índice, este comentario es informativo)

-- ========================================
-- 7. CONSULTAS DE VALIDACIÓN
-- Para verificar que todo se creó correctamente
-- ========================================

-- Mostrar estructura de tablas nuevas
-- SELECT TABLE_NAME, TABLE_COMMENT 
-- FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = 'kiosco' 
--   AND TABLE_NAME IN ('sesiones', 'audit_log', 'login_attempts');

-- Mostrar índices de sesiones
-- SHOW INDEX FROM sesiones;

-- Verificar que el evento está activo
-- SELECT EVENT_NAME, EVENT_DEFINITION, STATUS 
-- FROM INFORMATION_SCHEMA.EVENTS 
-- WHERE EVENT_SCHEMA = 'kiosco' 
--   AND EVENT_NAME = 'cleanup_expired_sessions';

-- ========================================
-- 8. EJEMPLO DE DATOS DE PRUEBA (OPCIONAL)
-- ========================================

-- Insertar una sesión de ejemplo (NO ejecutar en producción)
/*
INSERT INTO sesiones (usuario_id, token, ip_address, expires_at, last_activity)
VALUES (
    1, -- ID del admin
    '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
    '127.0.0.1',
    DATE_ADD(NOW(), INTERVAL 8 HOUR),
    NOW()
);
*/

-- Insertar log de auditoría de ejemplo
/*
INSERT INTO audit_log (usuario_id, username, accion, modulo, resultado, ip_address)
VALUES (
    1,
    'admin',
    'login',
    'auth',
    'exito',
    '127.0.0.1'
);
*/

-- Insertar intento de login de ejemplo
/*
INSERT INTO login_attempts (username, ip_address, success)
VALUES ('admin', '127.0.0.1', TRUE);
*/

-- ========================================
-- 9. ROLLBACK SCRIPT (SI ES NECESARIO)
-- ========================================

-- Si necesitas deshacer estos cambios:
/*
DROP EVENT IF EXISTS cleanup_expired_sessions;
DROP VIEW IF EXISTS sesiones_activas;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS sesiones;
*/

-- ========================================
-- 10. NOTAS IMPORTANTES
-- ========================================

/*
DESPUÉS DE EJECUTAR ESTE SCRIPT:

1. Actualizar auth.php para:
   - Insertar token en tabla 'sesiones' al login
   - Validar token contra tabla 'sesiones' en cada request
   - Actualizar 'last_activity' en cada request
   - Marcar sesión como inactiva al logout

2. Actualizar auth_middleware.php para:
   - Validar token en tabla 'sesiones' (no solo formato)
   - Verificar que no esté expirado (expires_at > NOW())
   - Verificar que esté activa (is_active = TRUE)
   - Actualizar last_activity

3. Actualizar todos los endpoints para:
   - Registrar acciones importantes en audit_log
   - Usar logAudit() del middleware

4. Configurar retention policies:
   - Sesiones: 30 días
   - Audit log: 1 año (ajustar según necesidad)
   - Login attempts: 90 días

5. Monitoreo:
   - Configurar alertas para múltiples intentos fallidos desde misma IP
   - Revisar audit_log periódicamente
   - Monitorear sesiones activas concurrentes por usuario

6. Seguridad:
   - Considerar limitar sesiones concurrentes por usuario
   - Implementar "Cerrar todas las sesiones" para usuarios
   - Agregar notificación de nuevo login desde dispositivo desconocido
*/

-- FIN DEL SCRIPT

