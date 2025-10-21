# 🔐 AUDITORÍA COMPLETA: SISTEMA DE USUARIOS Y AUTENTICACIÓN

**Fecha:** 21 de Octubre, 2025  
**Sistema:** Tayrona Almacén - Kiosco POS  
**Auditor:** Cursor AI Agent  
**Alcance:** Frontend (React) + Backend (PHP) + Base de Datos (MySQL)

---

## 📋 RESUMEN EJECUTIVO

### Estado General
❌ **CRÍTICO** - Se encontraron **4 vulnerabilidades críticas** que requieren atención inmediata.

### Hallazgos por Severidad
- 🔴 **Críticas:** 4 vulnerabilidades
- 🟠 **Altas:** 3 vulnerabilidades  
- 🟡 **Medias:** 4 issues
- 🟢 **Bajas:** 2 mejoras sugeridas

### Resumen de Riesgo
| Categoría | Estado | Descripción |
|-----------|--------|-------------|
| **Autenticación** | 🔴 CRÍTICO | Tokens sin validación real, sin expiración |
| **Autorización** | 🔴 CRÍTICO | Backend NO valida roles - confía 100% en frontend |
| **Inyección SQL** | ✅ SEGURO | Se usan prepared statements correctamente |
| **Passwords** | ✅ SEGURO | Se usa `password_hash()` (bcrypt) correctamente |
| **CORS** | 🟠 ALTO | Completamente abierto a cualquier origen |
| **Rate Limiting** | 🟡 MEDIO | No existe protección contra fuerza bruta |
| **Auditoría** | 🟡 MEDIO | Logs limitados, no hay audit trail |

---

## 🔴 VULNERABILIDADES CRÍTICAS (STOP THE WORLD)

### CRÍTICO #1: Autenticación sin Validación Real

**Archivo:** `api/usuarios.php`  
**Líneas:** 86-106  
**Severidad:** 🔴 CRÍTICA

**Problema:**
```php
// Línea 89 en usuarios.php
function verificarAutorizacion() {
    // Descomentar esta línea para permitir todas las solicitudes durante desarrollo
    return true;  // ❌ CRÍTICO: AUTH COMPLETAMENTE DESHABILITADA
    
    $headers = getallheaders();
    // ... código nunca ejecutado
}
```

**Impacto:**
- ✅ Cualquiera puede acceder a CRUD de usuarios sin autenticación
- ✅ Un atacante puede listar todos los usuarios
- ✅ Un atacante puede crear usuarios admin
- ✅ Un atacante puede eliminar usuarios
- ✅ **ACCESO TOTAL SIN CREDENCIALES**

**Evidencia:**
```bash
# Cualquiera puede hacer esto sin auth:
curl http://localhost/kiosco/api/usuarios.php
# Devuelve: Array con TODOS los usuarios

curl -X POST http://localhost/kiosco/api/usuarios.php \
  -H "Content-Type: application/json" \
  -d '{"username":"hacker","password":"123","nombre":"Hacker","role":"admin"}'
# Crea un usuario admin sin validación
```

**Fix Requerido:**
1. Eliminar `return true;` en línea 89
2. Implementar validación real de token
3. Verificar rol antes de cada operación CRUD

---

### CRÍTICO #2: Tokens Sin Validación ni Expiración

**Archivo:** `api/auth.php`  
**Líneas:** 88-101  
**Severidad:** 🔴 CRÍTICA

**Problema:**
```php
// Línea 88 en auth.php
$token = bin2hex(random_bytes(32)); // Genera token random

// Devuelve token al cliente
echo json_encode([
    'success' => true,
    'user' => $usuario,
    'token' => $token  // ❌ Token nunca se valida después
]);

// ❌ NO hay tabla de sesiones
// ❌ NO hay validación de tokens
// ❌ NO hay expiración
// ❌ Token es solo decorativo
```

**Impacto:**
- Tokens generados NUNCA se validan en requests posteriores
- No hay manera de invalidar tokens (imposible hacer logout real)
- No hay expiración - un token robado funciona para siempre
- Sistema de "seguridad" es solo cosmético

**Evidencia:**
```javascript
// Frontend guarda token en localStorage:
localStorage.setItem('authToken', response.data.token);

// Pero el backend NUNCA valida este token
// UsuariosPage envía: Authorization: Bearer <token>
// Pero usuarios.php hace: return true; (ignora el token)
```

**Fix Requerido:**
1. Crear tabla `sesiones` en BD
2. Almacenar tokens con timestamp de expiración
3. Validar token en CADA request
4. Implementar logout que invalide token

---

### CRÍTICO #3: Sin Validación de Roles en Backend

**Archivo:** `api/usuarios.php` (y otros endpoints)  
**Severidad:** 🔴 CRÍTICA

**Problema:**
- Backend NO valida que el usuario tenga rol `admin` para CRUD de usuarios
- Confía 100% en que el frontend ocultó los botones
- Un vendedor puede usar Postman/curl para crear usuarios admin

**Impacto:**
```bash
# Escenario de ataque:
# 1. Vendedor hace login normal
curl -X POST http://localhost/kiosco/api/auth.php \
  -d '{"username":"vendedor1","password":"pass123"}'
# Recibe token de vendedor

# 2. Usa Postman para crear usuario admin (backend no valida rol)
curl -X POST http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <cualquier-cosa>" \
  -d '{"username":"nuevo_admin","password":"123","role":"admin","nombre":"Hacker Admin"}'
# ✅ ÉXITO - Vendedor acaba de crear un admin
```

**Fix Requerido:**
1. Crear middleware `auth_middleware.php`
2. Validar token + extraer usuario
3. Verificar que `role = 'admin'` antes de CRUD usuarios
4. Aplicar a TODOS los endpoints sensibles

---

### CRÍTICO #4: Frontend AuthContext Sin Verificación de Expiración

**Archivo:** `src/contexts/AuthContext.jsx`  
**Líneas:** 143-163  
**Severidad:** 🔴 CRÍTICA

**Problema:**
```javascript
// Línea 143
useEffect(() => {
  const storedUser = localStorage.getItem('currentUser');
  if (storedUser) {
    const user = JSON.parse(storedUser);
    setCurrentUser(user);
    setIsAuthenticated(true);  // ❌ Sin verificar validez
  }
}, []);
```

**Impacto:**
- Usuario cierra sesión → localStorage aún tiene datos
- Usuario puede re-inyectar usuario antiguo
- No hay verificación con backend
- Sesión persiste indefinidamente

**Fix Requerido:**
1. Verificar token con backend al montar
2. Manejar expiración de token
3. Forzar re-login si token inválido
4. Limpiar todo localStorage en logout

---

## 🟠 VULNERABILIDADES ALTAS

### ALTO #1: Logs Sensibles de Contraseñas

**Archivo:** `api/auth.php`  
**Línea:** 73  
**Severidad:** 🟠 ALTA

**Problema:**
```php
// Línea 73
error_log("Hash almacenado: " . $usuario['password']);
// ❌ Loguea hash de contraseña en archivo de error
```

**Impacto:**
- Hashes de contraseñas en archivos de log en texto plano
- Si logs son comprometidos → contraseñas en riesgo
- Viola principios de privacidad

**Fix:** Remover este log completamente.

---

### ALTO #2: CORS Completamente Abierto

**Archivo:** `api/cors_middleware.php`  
**Línea:** 9  
**Severidad:** 🟠 ALTA

**Problema:**
```php
// Línea 9
header("Access-Control-Allow-Origin: *");  // ❌ Permite CUALQUIER origen
```

**Impacto:**
- Cualquier sitio web puede hacer requests a tu API
- Permite ataques CSRF desde sitios maliciosos
- No hay whitelist de dominios permitidos

**Fix Requerido:**
```php
// Whitelist de dominios permitidos
$allowed_origins = [
    'http://localhost:3000',  // Desarrollo
    'https://tudominio.com',  // Producción
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
```

---

### ALTO #3: Endpoint `validar_usuario.php` No Se Usa

**Archivo:** `api/validar_usuario.php`  
**Severidad:** 🟠 ALTA

**Problema:**
- Existe endpoint `validar_usuario.php` con validación de contraseñas
- Frontend usa `auth.php` en su lugar
- Endpoint huérfano puede confundir o ser explotado

**Impacto:**
- Código muerto que puede tener vulnerabilidades
- Confusión sobre qué endpoint es el correcto
- Posible punto de entrada alternativo no mantenido

**Fix:** Eliminar `validar_usuario.php` o unificar con `auth.php`.

---

## 🟡 ISSUES MEDIAS

### MEDIO #1: Sin Rate Limiting en Login

**Archivo:** `api/auth.php`  
**Severidad:** 🟡 MEDIA

**Problema:**
- Sin límite de intentos de login
- Atacante puede intentar miles de contraseñas
- Sin cooldown ni bloqueo temporal

**Fix:** Implementar rate limiting simple con archivo o tabla temporal.

---

### MEDIO #2: Sin Logs de Auditoría

**Archivo:** `api/auth.php` y otros  
**Severidad:** 🟡 MEDIA

**Problema:**
- Logs mínimos de intentos de login
- No hay audit trail de acciones administrativas
- Imposible rastrear actividad sospechosa

**Fix:** Crear tabla `audit_log` y registrar acciones críticas.

---

### MEDIO #3: Mensajes de Error Revelan Información

**Archivo:** `api/auth.php`  
**Líneas:** 64, 78  
**Severidad:** 🟡 MEDIA

**Problema:**
```php
// Diferencia entre "usuario no existe" y "contraseña incorrecta"
if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
}

if (!password_verify($password, $usuario['password'])) {
    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
}
```

**Comentario:** Esto está BIEN implementado (mensaje genérico), pero los error_log() revelan detalles.

**Fix:** Remover error_log() con detalles específicos.

---

### MEDIO #4: Device Fingerprinting Sin Usar en Backend

**Archivo:** `src/components/LoginPage.jsx`  
**Líneas:** 15-29, 44-79  
**Severidad:** 🟡 MEDIA

**Problema:**
- Frontend recolecta device fingerprint
- Backend (`auth.php`) NO lo usa en validación
- Funcionalidad incompleta

**Fix:** Integrar validación de dispositivo en `auth.php` si se desea usar esta feature.

---

## 🟢 MEJORAS SUGERIDAS (LOW PRIORITY)

### BAJO #1: Usar JWT en vez de Tokens Random

**Severidad:** 🟢 BAJA

**Sugerencia:**
- Cambiar de tokens random a JWT (JSON Web Tokens)
- JWT incluye expiración y puede ser stateless
- Más estándar y seguro

---

### BAJO #2: Agregar 2FA Opcional

**Severidad:** 🟢 BAJA

**Sugerencia:**
- Para usuarios admin, agregar autenticación de 2 factores
- Email/SMS con código temporal
- Aumenta seguridad significativamente

---

## ✅ ASPECTOS POSITIVOS

### Lo Que Está BIEN Implementado:

1. ✅ **Password Hashing:** Se usa `password_hash()` con bcrypt correctamente
2. ✅ **Prepared Statements:** TODAS las queries usan PDO prepared statements
3. ✅ **Sanitización:** Se sanitizan inputs en varios puntos
4. ✅ **Mensajes de Error:** No revelan detalles específicos (genéricos)
5. ✅ **Estructura de Roles:** Sistema de permisos bien diseñado en frontend
6. ✅ **PermissionGuard:** Frontend oculta UI correctamente por rol
7. ✅ **Protección de Admin:** No se puede eliminar último usuario admin

---

## 🔧 PLAN DE REMEDIACIÓN

### Prioridad 1 (INMEDIATO - Próximas 24hrs)

1. **Activar validación de auth en `usuarios.php`**
   - Cambiar `return true;` por validación real
   - Verificar token + rol admin

2. **Crear `auth_middleware.php`**
   - Middleware centralizado de autenticación
   - Aplicar a TODOS los endpoints sensibles

3. **Implementar validación de tokens con expiración**
   - Crear tabla `sesiones`
   - Validar token en cada request
   - Expiración de 8 horas

4. **Remover logs sensibles**
   - Eliminar línea 73 de `auth.php`
   - Ajustar otros error_log()

### Prioridad 2 (Esta Semana)

5. **Restringir CORS**
   - Whitelist de dominios permitidos
   - Remover `*` de Access-Control-Allow-Origin

6. **Rate limiting simple**
   - Archivo temporal con contador por IP
   - 5 intentos máximo en 15 minutos

7. **Logs de auditoría básicos**
   - Tabla `audit_log`
   - Registrar login, logout, CRUD usuarios

### Prioridad 3 (Próximo Mes)

8. **Eliminar código muerto**
   - Decidir si mantener `validar_usuario.php`
   - Documentar endpoints activos

9. **Integrar device fingerprinting**
   - Si se decide usar, integrar completamente
   - Si no, remover del frontend

---

## 📊 ARCHIVOS AFECTADOS

| Archivo | Tipo | Issues Encontrados | Prioridad |
|---------|------|-------------------|-----------|
| `api/usuarios.php` | Backend | Auth deshabilitada, sin validación rol | 🔴 CRÍTICA |
| `api/auth.php` | Backend | Tokens sin validación, logs sensibles | 🔴 CRÍTICA |
| `src/contexts/AuthContext.jsx` | Frontend | Sin verificación de expiración | 🔴 CRÍTICA |
| `api/cors_middleware.php` | Backend | CORS abierto | 🟠 ALTA |
| `api/validar_usuario.php` | Backend | Código muerto | 🟠 ALTA |
| `src/components/LoginPage.jsx` | Frontend | Device fingerprint no integrado | 🟡 MEDIA |
| `api/permisos_usuario.php` | Backend | Solo frontend usa permisos | 🟡 MEDIA |

---

## 🧪 PLAN DE PRUEBAS POST-FIX

### Test 1: Autenticación Básica
```bash
# 1. Login válido
curl -X POST http://localhost/kiosco/api/auth.php \
  -d '{"username":"admin","password":"admin123"}'
# Expect: success=true, token generado

# 2. Login inválido
curl -X POST http://localhost/kiosco/api/auth.php \
  -d '{"username":"admin","password":"wrong"}'
# Expect: success=false, "Credenciales inválidas"
```

### Test 2: Token Validation
```bash
# 3. Request sin token
curl http://localhost/kiosco/api/usuarios.php
# Expect: 401 Unauthorized

# 4. Request con token inválido
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer invalid_token_123"
# Expect: 401 Unauthorized

# 5. Request con token válido
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <token_real>"
# Expect: 200 OK, lista de usuarios
```

### Test 3: Role-Based Access
```bash
# 6. Vendedor intenta crear usuario
# Login como vendedor
TOKEN=$(curl -X POST http://localhost/kiosco/api/auth.php \
  -d '{"username":"vendedor1","password":"pass"}' | jq -r '.token')

# Intenta crear usuario
curl -X POST http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"username":"test","password":"123","role":"admin","nombre":"Test"}'
# Expect: 403 Forbidden
```

### Test 4: Token Expiration
```bash
# 7. Usar token expirado (simular con timestamp manual)
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <token_8hrs_antiguo>"
# Expect: 401 Unauthorized, "Token expirado"
```

### Test 5: Rate Limiting
```bash
# 8. Intentar login 6 veces seguidas
for i in {1..6}; do
  curl -X POST http://localhost/kiosco/api/auth.php \
    -d '{"username":"admin","password":"wrong"}'
done
# Expect: Primeros 5 = "Credenciales inválidas"
# 6to intento = 429 Too Many Requests, "Intenta en X minutos"
```

### Test 6: Logout
```bash
# 9. Logout invalida token
curl -X POST http://localhost/kiosco/api/logout.php \
  -H "Authorization: Bearer <token>"
# Expect: 200 OK

# 10. Token ya no funciona
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <mismo_token>"
# Expect: 401 Unauthorized
```

---

## 💾 CAMBIOS EN BASE DE DATOS PROPUESTOS

### Nueva Tabla: `sesiones`
```sql
CREATE TABLE sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Nueva Tabla: `audit_log`
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    username VARCHAR(50) NULL,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    detalles JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    resultado ENUM('exito','fallo') DEFAULT 'exito',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Nueva Tabla: `login_attempts`
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_username_time (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Nota:** Estos cambios NO se implementarán en esta auditoría (según constraint de no alterar schema), pero se proponen para aprobación futura.

---

## 📝 PRÓXIMOS PASOS

1. ✅ **Revisar este informe** con el equipo
2. ✅ **Aprobar plan de remediación** (prioridades 1, 2, 3)
3. ✅ **Aprobar cambios de schema** (tablas propuestas)
4. ✅ **Implementar fixes** (siguiente fase)
5. ✅ **Ejecutar plan de pruebas** (manual + automatizado)
6. ✅ **Documentar cambios** en CHANGELOG
7. ✅ **Deploy a producción** (con rollback plan)

---

## 🔒 RECOMENDACIONES GENERALES

### Corto Plazo (1-2 semanas)
- Implementar TODOS los fixes de Prioridad 1
- Activar autenticación real
- Agregar validación de roles en backend
- Restringir CORS

### Mediano Plazo (1 mes)
- Migrar a JWT para tokens
- Implementar rate limiting robusto
- Crear sistema de audit trail completo
- Agregar monitoreo de seguridad

### Largo Plazo (3-6 meses)
- Considerar 2FA para admins
- Penetration testing profesional
- Code review de seguridad periódico
- Capacitación del equipo en OWASP Top 10

---

**Auditoría completada por:** Cursor AI Agent  
**Estado:** ✅ COMPLETA  
**Fecha de entrega:** 21 de Octubre, 2025  
**Próxima revisión:** Después de implementar fixes de Prioridad 1

---

## 📚 REFERENCIAS

- [OWASP Top 10 - 2021](https://owasp.org/www-project-top-ten/)
- [PHP Password Hashing Best Practices](https://www.php.net/manual/en/function.password-hash.php)
- [JWT Introduction](https://jwt.io/introduction)
- [CORS Best Practices](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [SQL Injection Prevention](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)

