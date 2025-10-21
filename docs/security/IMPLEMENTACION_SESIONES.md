# 🔐 GUÍA DE IMPLEMENTACIÓN: VALIDACIÓN DE TOKENS CON EXPIRACIÓN

**Fecha:** 21 de Octubre, 2025  
**Sistema:** Tayrona Almacén - Kiosco POS  
**Estado:** ⏸️ PROPUESTO - Requiere Aprobación para Ejecutar

---

## 📋 RESUMEN

Este documento explica cómo implementar el **FIX #3 CRÍTICO**: Validación de tokens con expiración real usando tabla `sesiones`.

**Tiempo estimado:** 2-3 horas  
**Complejidad:** Media  
**Riesgo:** Medio (requiere cambio de schema)

---

## 🎯 OBJETIVO

Actualmente, el sistema:
- ❌ Genera tokens aleatorios pero NUNCA los valida después
- ❌ No tiene concepto de expiración de sesión
- ❌ Un token robado funciona para siempre
- ❌ Logout no invalida realmente el token

**Después de este fix:**
- ✅ Tokens se guardan en BD con timestamp de expiración
- ✅ Cada request valida token contra BD
- ✅ Tokens expiran automáticamente después de 8 horas
- ✅ Logout invalida token realmente
- ✅ Se puede forzar logout de todas las sesiones

---

## 📊 ARQUITECTURA PROPUESTA

```
┌─────────────┐          ┌──────────────┐          ┌─────────────┐
│   Cliente   │          │   Backend    │          │     BD      │
│  (Browser)  │          │  (PHP 8.0)   │          │   (MySQL)   │
└─────────────┘          └──────────────┘          └─────────────┘
       │                         │                         │
       │  1. POST /auth.php      │                         │
       │  {user, pass}           │                         │
       ├────────────────────────>│                         │
       │                         │  2. Verificar password  │
       │                         ├────────────────────────>│
       │                         │  SELECT * FROM usuarios │
       │                         │  WHERE username=?       │
       │                         │<────────────────────────┤
       │                         │  Usuario válido         │
       │                         │                         │
       │                         │  3. Generar token       │
       │                         │  $token = random(64)    │
       │                         │                         │
       │                         │  4. Guardar sesión      │
       │                         ├────────────────────────>│
       │                         │  INSERT INTO sesiones   │
       │                         │  (usuario_id, token,    │
       │                         │   expires_at)           │
       │                         │<────────────────────────┤
       │                         │  Sesión guardada        │
       │  5. Return token        │                         │
       │<────────────────────────┤                         │
       │  {token, user}          │                         │
       │                         │                         │
       │  6. GET /usuarios.php   │                         │
       │  Auth: Bearer <token>   │                         │
       ├────────────────────────>│                         │
       │                         │  7. Validar token       │
       │                         ├────────────────────────>│
       │                         │  SELECT * FROM sesiones │
       │                         │  WHERE token=?          │
       │                         │    AND is_active=1      │
       │                         │    AND expires_at>NOW() │
       │                         │<────────────────────────┤
       │                         │  Sesión válida          │
       │                         │                         │
       │                         │  8. Actualizar activity │
       │                         ├────────────────────────>│
       │                         │  UPDATE sesiones        │
       │                         │  SET last_activity=NOW()│
       │                         │<────────────────────────┤
       │                         │                         │
       │                         │  9. Continuar request   │
       │  10. Respuesta          │  (endpoint normal)      │
       │<────────────────────────┤                         │
       │                         │                         │
```

---

## 🔧 PASOS DE IMPLEMENTACIÓN

### PASO 1: Ejecutar Script SQL (REQUIERE APROBACIÓN)

**⚠️ IMPORTANTE:** Hacer backup de la BD antes de ejecutar.

```bash
# 1. Backup de la base de datos
mysqldump -u root -p kiosco > kiosco_backup_$(date +%Y%m%d).sql

# 2. Ejecutar script de creación de tablas
mysql -u root -p kiosco < docs/security/schema_sesiones_propuesto.sql

# 3. Verificar que se crearon correctamente
mysql -u root -p kiosco -e "SHOW TABLES LIKE 'sesiones';"
mysql -u root -p kiosco -e "SHOW TABLES LIKE 'audit_log';"
mysql -u root -p kiosco -e "SHOW TABLES LIKE 'login_attempts';"
```

---

### PASO 2: Actualizar `api/auth.php` para Guardar Token en BD

**Archivo:** `api/auth.php`  
**Ubicación:** Después de verificar password correcta

```php
// ANTES (línea ~87):
// Generar un token simple
$token = bin2hex(random_bytes(32));

// Eliminar la contraseña de la información del usuario
unset($usuario['password']);

// DESPUÉS:
// Generar un token simple
$token = bin2hex(random_bytes(32));

// 🔐 FIX CRÍTICO: Guardar token en tabla sesiones
try {
    $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours')); // 8 horas de validez
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, expires_at, last_activity)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $usuario['id'],
        $token,
        $ip,
        $userAgent,
        $expiresAt
    ]);
    
    $sessionId = $pdo->lastInsertId();
    
    error_log("Sesión creada #$sessionId para usuario {$usuario['username']} - expira: $expiresAt");
    
    // Registrar en audit log
    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_log (usuario_id, username, accion, modulo, resultado, ip_address, user_agent)
        VALUES (?, ?, 'login', 'auth', 'exito', ?, ?)
    ");
    $stmtAudit->execute([$usuario['id'], $usuario['username'], $ip, $userAgent]);
    
    // Registrar intento exitoso
    $stmtAttempt = $pdo->prepare("
        INSERT INTO login_attempts (username, ip_address, user_agent, success)
        VALUES (?, ?, ?, TRUE)
    ");
    $stmtAttempt->execute([$usuario['username'], $ip, $userAgent]);
    
} catch (PDOException $e) {
    error_log("Error guardando sesión: " . $e->getMessage());
    // No fallar login por esto, solo loguear
}

// Eliminar la contraseña de la información del usuario
unset($usuario['password']);
```

---

### PASO 3: Actualizar `auth_middleware.php` para Validar Contra BD

**Archivo:** `api/auth_middleware.php`  
**Función:** `validateTokenSimple()` - Reemplazar completamente

```php
/**
 * 🔐 VALIDACIÓN REAL DE TOKEN CONTRA BD
 * 
 * @param string $token Token a validar
 * @param PDO $pdo Conexión a base de datos
 * @return array|false Usuario si es válido, false si no
 */
function validateTokenSimple($token, $pdo) {
    try {
        // Buscar token en tabla sesiones
        $stmt = $pdo->prepare("
            SELECT 
                s.id AS session_id,
                s.usuario_id,
                s.expires_at,
                s.is_active,
                u.id,
                u.username,
                u.nombre,
                u.role
            FROM sesiones s
            INNER JOIN usuarios u ON s.usuario_id = u.id
            WHERE s.token = ?
              AND s.is_active = TRUE
              AND s.expires_at > NOW()
            LIMIT 1
        ");
        
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("Token inválido o expirado: " . substr($token, 0, 10) . "...");
            return false;
        }
        
        // Actualizar last_activity
        $stmtUpdate = $pdo->prepare("
            UPDATE sesiones 
            SET last_activity = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$result['session_id']]);
        
        // Retornar datos del usuario
        return [
            'id' => $result['id'],
            'username' => $result['username'],
            'nombre' => $result['nombre'],
            'role' => $result['role']
        ];
        
    } catch (PDOException $e) {
        error_log("Error validando token: " . $e->getMessage());
        return false;
    }
}
```

---

### PASO 4: Crear Endpoint de Logout

**Nuevo archivo:** `api/logout.php`

```php
<?php
/**
 * File: api/logout.php
 * Endpoint para cerrar sesión e invalidar token
 * Exists to properly terminate user sessions
 * Related files: api/auth.php, api/auth_middleware.php, src/contexts/AuthContext.jsx
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'bd_conexion.php';
require_once 'auth_middleware.php';

// Manejar CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Obtener token del header
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!empty($auth) && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = trim($matches[1]);
        
        $pdo = Conexion::obtenerConexion();
        
        // Marcar sesión como inactiva
        $stmt = $pdo->prepare("
            UPDATE sesiones 
            SET is_active = FALSE 
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            error_log("Logout: Token invalidado correctamente");
            
            // Registrar en audit log
            $stmtAudit = $pdo->prepare("
                INSERT INTO audit_log (usuario_id, username, accion, modulo, resultado, ip_address)
                SELECT 
                    s.usuario_id,
                    u.username,
                    'logout',
                    'auth',
                    'exito',
                    ?
                FROM sesiones s
                INNER JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.token = ?
                LIMIT 1
            ");
            $stmtAudit->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $token]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
        } else {
            error_log("Logout: Token no encontrado o ya inactivo");
            echo json_encode([
                'success' => true,
                'message' => 'Sesión ya cerrada'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Token no proporcionado'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error en logout: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cerrar sesión'
    ]);
}
```

---

### PASO 5: Actualizar Frontend `AuthContext.jsx`

**Archivo:** `src/contexts/AuthContext.jsx`  
**Función:** `logout()`

```javascript
// Cerrar sesión
const logout = async () => {
  // 🔐 FIX: Llamar al endpoint de logout para invalidar token en servidor
  const token = localStorage.getItem('authToken');
  
  if (token) {
    try {
      await axios.post(
        `${CONFIG.API_URL}/api/logout.php`,
        {},
        {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        }
      );
      console.log('✅ Sesión invalidada en servidor');
    } catch (error) {
      console.error('Error al invalidar sesión en servidor:', error);
      // Continuar con logout local de todos modos
    }
  }
  
  // Limpiar localStorage
  localStorage.removeItem('currentUser');
  localStorage.removeItem('authToken');
  localStorage.removeItem('caja_estado');
  localStorage.removeItem('dashboard_last_date');
  
  // Actualizar estado
  setCurrentUser(null);
  setIsAuthenticated(false);
  
  console.log('✅ Logout completado');
};
```

---

### PASO 6: Agregar Verificación de Token al Montar

**Archivo:** `src/contexts/AuthContext.jsx`  
**useEffect inicial** - Agregar verificación con backend

```javascript
useEffect(() => {
  const storedUser = localStorage.getItem('currentUser');
  const storedToken = localStorage.getItem('authToken');
  
  if (storedUser && storedToken) {
    try {
      const user = JSON.parse(storedUser);
      
      // 🔐 FIX: Verificar que el token sigue siendo válido en el servidor
      axios.get(
        `${CONFIG.API_URL}/api/verify_token.php`,
        {
          headers: {
            'Authorization': `Bearer ${storedToken}`
          }
        }
      )
      .then(response => {
        if (response.data && response.data.valid) {
          setCurrentUser(user);
          setIsAuthenticated(true);
          console.log('✅ Token verificado - sesión restaurada');
        } else {
          // Token inválido o expirado
          console.warn('⚠️ Token expirado - forzando re-login');
          localStorage.removeItem('currentUser');
          localStorage.removeItem('authToken');
        }
      })
      .catch(error => {
        console.error('Error verificando token:', error);
        // Si el servidor no responde, permitir sesión local (fallback)
        // En producción, considerar forzar re-login
        setCurrentUser(user);
        setIsAuthenticated(true);
      });
      
    } catch (error) {
      console.error('Error parsing stored user:', error);
      localStorage.removeItem('currentUser');
      localStorage.removeItem('authToken');
    }
  }
  
  setLoading(false);
}, []);
```

---

### PASO 7: Crear Endpoint de Verificación de Token

**Nuevo archivo:** `api/verify_token.php`

```php
<?php
/**
 * File: api/verify_token.php
 * Endpoint para verificar validez de token
 * Exists to check if token is still valid on page reload
 * Related files: api/auth_middleware.php, src/contexts/AuthContext.jsx
 */

header('Content-Type: application/json');
require_once 'cors_middleware.php';
require_once 'auth_middleware.php';

try {
    // Intentar obtener usuario actual
    $usuario = getCurrentUser();
    
    if ($usuario) {
        echo json_encode([
            'valid' => true,
            'user' => $usuario
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'Token inválido o expirado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error verificando token: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'Error al verificar token'
    ]);
}
```

---

## 🧪 PLAN DE PRUEBAS

### Test 1: Login Crea Sesión en BD

```bash
# 1. Login
curl -X POST http://localhost/kiosco/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Respuesta esperada:
# {"success":true,"token":"0a1b2c...","user":{...}}

# 2. Verificar en BD que se creó sesión
mysql -u root -p kiosco -e "SELECT * FROM sesiones ORDER BY id DESC LIMIT 1;"

# Debe mostrar:
# - token igual al recibido
# - usuario_id = 1 (admin)
# - expires_at = +8 horas desde now
# - is_active = 1
```

### Test 2: Token Válido Permite Acceso

```bash
# Usar token del test anterior
TOKEN="<copiar_token_aquí>"

curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer $TOKEN"

# Debe retornar: Array de usuarios (200 OK)
```

### Test 3: Token Inválido es Rechazado

```bash
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer token_falso_123"

# Debe retornar: 401 Unauthorized
```

### Test 4: Logout Invalida Token

```bash
TOKEN="<copiar_token_válido>"

# 1. Logout
curl -X POST http://localhost/kiosco/api/logout.php \
  -H "Authorization: Bearer $TOKEN"

# Debe retornar: {"success":true}

# 2. Intentar usar mismo token
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer $TOKEN"

# Debe retornar: 401 Unauthorized
```

### Test 5: Token Expirado es Rechazado

```bash
# Simular token expirado (manipular BD)
mysql -u root -p kiosco -e "
  UPDATE sesiones 
  SET expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) 
  WHERE token = '<token>';
"

# Intentar usar token
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <token>"

# Debe retornar: 401 Unauthorized
```

### Test 6: Frontend - Recarga Mantiene Sesión Válida

**Pasos manuales:**
1. Login en frontend
2. Verificar que token está en localStorage
3. Recargar página (F5)
4. Verificar que sigue logueado
5. Verificar en Network tab que se llamó `verify_token.php`
6. Verificar que devolvió `valid: true`

### Test 7: Frontend - Token Expirado Fuerza Re-Login

**Pasos manuales:**
1. Login en frontend
2. Simular expiración en BD (comando anterior)
3. Recargar página (F5)
4. Debe redirigir a Login
5. localStorage debe estar limpio

---

## 📊 MONITOREO Y MANTENIMIENTO

### Queries Útiles

```sql
-- Ver sesiones activas
SELECT * FROM sesiones_activas;

-- Ver sesiones por usuario
SELECT 
    u.username,
    COUNT(*) AS sesiones_activas,
    MAX(s.last_activity) AS ultima_actividad
FROM sesiones s
INNER JOIN usuarios u ON s.usuario_id = u.id
WHERE s.is_active = TRUE AND s.expires_at > NOW()
GROUP BY u.username;

-- Ver intentos de login fallidos recientes (última hora)
SELECT 
    username,
    ip_address,
    COUNT(*) AS intentos,
    MAX(created_at) AS ultimo_intento
FROM login_attempts
WHERE success = FALSE 
  AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY username, ip_address
HAVING COUNT(*) >= 3
ORDER BY intentos DESC;

-- Ver actividad de auditoría por usuario
SELECT 
    username,
    accion,
    modulo,
    COUNT(*) AS veces
FROM audit_log
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY username, accion, modulo
ORDER BY veces DESC
LIMIT 20;
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

1. **Performance:** 
   - Cada request ahora hace query a tabla sesiones
   - Los índices creados minimizan impacto
   - Considerar cache de sesiones si el volumen es muy alto

2. **Sesiones Concurrentes:**
   - Actualmente, un usuario puede tener múltiples sesiones activas
   - Si se desea limitar, agregar validación en auth.php

3. **Expiración:**
   - Default: 8 horas
   - Ajustable según necesidad del negocio
   - "Remember me" requeriría tokens de larga duración

4. **Limpieza:**
   - Evento automático limpia sesiones expiradas diariamente
   - No afecta sesiones activas

---

## 🔄 ROLLBACK (SI ES NECESARIO)

Si algo falla y necesitas volver atrás:

```sql
-- 1. Remover validación de tabla sesiones
-- (Volver auth_middleware.php a versión anterior)

-- 2. Eliminar tablas (SOLO SI ES ABSOLUTAMENTE NECESARIO)
DROP EVENT IF EXISTS cleanup_expired_sessions;
DROP VIEW IF EXISTS sesiones_activas;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS sesiones;

-- 3. Restaurar backup
mysql -u root -p kiosco < kiosco_backup_YYYYMMDD.sql
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] ✅ Backup de base de datos realizado
- [ ] ✅ Script SQL ejecutado sin errores
- [ ] ✅ Tablas creadas correctamente
- [ ] ✅ `auth.php` actualizado para guardar token en BD
- [ ] ✅ `auth_middleware.php` actualizado para validar contra BD
- [ ] ✅ `logout.php` creado y funcionando
- [ ] ✅ `verify_token.php` creado y funcionando
- [ ] ✅ `AuthContext.jsx` actualizado con logout real
- [ ] ✅ `AuthContext.jsx` actualizado con verificación al montar
- [ ] ✅ Test 1-7 ejecutados exitosamente
- [ ] ✅ Logs de auditoría funcionando
- [ ] ✅ Evento de limpieza activo
- [ ] ✅ Documentación actualizada

---

**Implementación preparada por:** Cursor AI Agent  
**Estado:** ⏸️ Pendiente de Aprobación del Usuario  
**Próximo paso:** Obtener aprobación y ejecutar PASO 1


