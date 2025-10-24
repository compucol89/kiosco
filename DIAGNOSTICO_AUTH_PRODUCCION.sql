-- ========================================
-- File: DIAGNOSTICO_AUTH_PRODUCCION.sql
-- Script SQL para diagnosticar problemas de autenticación en producción
-- Exists to identify password hashing issues and table structure problems
-- Related files: api/auth.php, api/diagnostico_auth_completo.php
-- ========================================

-- 1️⃣ VERIFICAR VERSIÓN Y CONFIGURACIÓN DE MYSQL
-- ========================================
SELECT VERSION() AS mysql_version;

SHOW VARIABLES LIKE 'character_set_%';
SHOW VARIABLES LIKE 'collation_%';
SHOW VARIABLES LIKE 'lower_case_table_names';
SHOW VARIABLES LIKE 'sql_mode';

-- 2️⃣ VERIFICAR ESTRUCTURA DE TABLA USUARIOS
-- ========================================
SHOW CREATE TABLE usuarios;
SHOW FULL COLUMNS FROM usuarios;

-- 3️⃣ ANALIZAR CONTRASEÑAS (DETECTAR ALGORITMOS)
-- ========================================
SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN password REGEXP '^\\$2y\\$' THEN 1 ELSE 0 END) as bcrypt_2y,
    SUM(CASE WHEN password REGEXP '^\\$2a\\$' THEN 1 ELSE 0 END) as bcrypt_2a,
    SUM(CASE WHEN password REGEXP '^\\$argon2id\\$' THEN 1 ELSE 0 END) as argon2id,
    SUM(CASE WHEN LENGTH(password) < 30 THEN 1 ELSE 0 END) as password_sospechosas,
    SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as posible_md5,
    SUM(CASE WHEN LENGTH(password) = 40 THEN 1 ELSE 0 END) as posible_sha1,
    SUM(CASE WHEN LENGTH(password) >= 60 THEN 1 ELSE 0 END) as bcrypt_validos
FROM usuarios;

-- 4️⃣ LISTAR USUARIOS CON INFO DE PASSWORD (SIN HASH COMPLETO)
-- ========================================
SELECT 
    id,
    username,
    nombre,
    role,
    LEFT(password, 10) as password_prefix,
    LENGTH(password) as password_length,
    CASE 
        WHEN password REGEXP '^\\$2y\\$' THEN '✅ bcrypt ($2y$) - CORRECTO'
        WHEN password REGEXP '^\\$2a\\$' THEN '✅ bcrypt ($2a$) - CORRECTO'
        WHEN password REGEXP '^\\$argon2id\\$' THEN '✅ argon2id - CORRECTO'
        WHEN LENGTH(password) = 32 THEN '❌ POSIBLE MD5 (32 chars) - INSEGURO'
        WHEN LENGTH(password) = 40 THEN '❌ POSIBLE SHA1 (40 chars) - INSEGURO'
        WHEN LENGTH(password) < 30 THEN '❌ POSIBLE PLAIN TEXT - PELIGRO'
        ELSE '⚠️ DESCONOCIDO'
    END as password_type,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as fecha_creacion
FROM usuarios
ORDER BY id;

-- 5️⃣ VERIFICAR USUARIOS ADMIN
-- ========================================
SELECT 
    id,
    username,
    role,
    nombre,
    LENGTH(password) as pwd_len,
    LEFT(password, 10) as pwd_prefix
FROM usuarios 
WHERE role = 'admin' OR username = 'admin';

-- 6️⃣ VERIFICAR DUPLICADOS
-- ========================================
SELECT username, COUNT(*) as duplicados 
FROM usuarios 
GROUP BY username 
HAVING COUNT(*) > 1;

-- 7️⃣ DETECTAR USUARIOS SIN BCRYPT (NECESITAN REHASH)
-- ========================================
SELECT 
    id,
    username,
    nombre,
    role,
    LEFT(password, 10) as password_prefix,
    LENGTH(password) as password_length,
    '❌ REQUIERE REHASH' as estado
FROM usuarios
WHERE password NOT REGEXP '^\\$2[ay]\\$'
  AND password NOT REGEXP '^\\$argon2id\\$';

-- ========================================
-- 🛠️ SCRIPTS DE CORRECCIÓN (EJECUTAR SOLO SI ES NECESARIO)
-- ========================================

-- OPCIÓN A: Resetear password de admin (bcrypt de "Admin123!")
-- Descomenta para ejecutar:
/*
UPDATE usuarios 
SET password = '$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG'
WHERE username = 'admin';
*/

-- OPCIÓN B: Resetear password de vendedor1 (bcrypt de "Vend123!")
-- Genera tu propio hash con PHP:
-- php -r "echo password_hash('TuPassword123', PASSWORD_BCRYPT);"
/*
UPDATE usuarios 
SET password = 'PEGAR_HASH_AQUI'
WHERE username = 'vendedor1';
*/

-- OPCIÓN C: Verificar normalización de charset/collation
-- Solo si es necesario:
/*
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios 
    MODIFY password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    MODIFY username VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY nombre VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
*/

-- ========================================
-- 📊 VERIFICACIÓN POST-CORRECCIÓN
-- ========================================
-- Ejecutar después de aplicar correcciones:

SELECT 
    'Verificación Final' as paso,
    COUNT(*) as total,
    SUM(CASE WHEN password REGEXP '^\\$2[ay]\\$' THEN 1 ELSE 0 END) as bcrypt_ok,
    SUM(CASE WHEN password NOT REGEXP '^\\$2[ay]\\$' AND password NOT REGEXP '^\\$argon2id\\$' THEN 1 ELSE 0 END) as pendientes
FROM usuarios;

