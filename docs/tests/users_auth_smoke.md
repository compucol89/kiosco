# 🧪 PLAN DE PRUEBAS: USUARIOS Y AUTENTICACIÓN

**Fecha:** 21 de Octubre, 2025  
**Sistema:** Tayrona Almacén - Kiosco POS  
**Tipo:** Pruebas Manuales de Smoke Testing  
**Responsable:** QA / Desarrollador

---

## 📋 RESUMEN

Este documento contiene las pruebas manuales que deben ejecutarse después de implementar los fixes de seguridad en el sistema de usuarios y autenticación.

**Duración estimada:** 30-45 minutos  
**Prerequisitos:** Sistema corriendo en local (Laragon) + Postman instalado

---

## ✅ CHECKLIST RÁPIDO

Marcar con `[x]` cuando esté completado:

- [ ] **Test 1:** Login válido con credenciales correctas
- [ ] **Test 2:** Login inválido con contraseñas incorrectas  
- [ ] **Test 3:** Protección de endpoints sin token
- [ ] **Test 4:** Validación de tokens inválidos
- [ ] **Test 5:** Verificación de roles (admin vs vendedor)
- [ ] **Test 6:** Logout e invalidación de sesión
- [ ] **Test 7:** CORS - origins permitidos
- [ ] **Test 8:** CORS - origins bloqueados
- [ ] **Test 9:** Rate limiting (si implementado)
- [ ] **Test 10:** Frontend - Route guards funcionando

---

## 🔐 TEST SUITE 1: AUTENTICACIÓN BÁSICA

### TEST 1.1: Login Exitoso con Credenciales Válidas

**Objetivo:** Verificar que un usuario puede iniciar sesión correctamente.

**Pasos:**
1. Abrir `http://localhost:3000`
2. Debería mostrar página de Login
3. Ingresar credenciales válidas:
   - Usuario: `admin`
   - Contraseña: `admin123`
4. Click en "Iniciar Sesión"

**Resultado Esperado:**
- ✅ Redirección al Dashboard
- ✅ Token guardado en localStorage
- ✅ Usuario mostrado en TopBar
- ✅ Menú completo visible (admin tiene acceso a todo)
- ✅ Console log: "Login exitoso para usuario: admin [ID: 1]"

**Cómo Verificar Token:**
- F12 → Application → Local Storage
- Verificar que existe: `authToken` con valor de 64 caracteres
- Verificar que existe: `currentUser` con datos del usuario

---

### TEST 1.2: Login Fallido - Usuario No Existe

**Objetivo:** Verificar manejo de usuarios inexistentes.

**Pasos:**
1. En Login Page, ingresar:
   - Usuario: `usuario_que_no_existe`
   - Contraseña: `cualquier_cosa`
2. Click en "Iniciar Sesión"

**Resultado Esperado:**
- ❌ NO redirecciona
- ❌ Muestra error: "Credenciales inválidas"
- ❌ NO guarda token en localStorage
- ✅ Console del servidor log: "Intento de login fallido... (usuario no encontrado)"

---

### TEST 1.3: Login Fallido - Contraseña Incorrecta

**Objetivo:** Verificar validación de contraseña.

**Pasos:**
1. En Login Page, ingresar:
   - Usuario: `admin`
   - Contraseña: `password_incorrecta`
2. Click en "Iniciar Sesión"

**Resultado Esperado:**
- ❌ NO redirecciona
- ❌ Muestra error: "Credenciales inválidas"
- ❌ NO guarda token en localStorage
- ✅ Console del servidor log: "Intento de login fallido... (contraseña incorrecta)"
- ✅ Log NO debe mostrar el hash de la contraseña

---

## 🔒 TEST SUITE 2: VALIDACIÓN DE TOKENS

### TEST 2.1: Acceso Sin Token

**Objetivo:** Verificar que endpoints requieren autenticación.

**Herramienta:** Postman o curl

**Pasos:**
```bash
curl http://localhost/kiosco/api/usuarios.php
```

**Resultado Esperado:**
```json
{
  "success": false,
  "message": "No autorizado - Token requerido"
}
```
- ✅ Status Code: 401 Unauthorized

---

### TEST 2.2: Acceso Con Token Inválido

**Objetivo:** Verificar validación de tokens malformados.

**Pasos:**
```bash
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer token_falso_123"
```

**Resultado Esperado:**
```json
{
  "success": false,
  "message": "Token inválido"
}
```
- ✅ Status Code: 401 Unauthorized

---

### TEST 2.3: Acceso Con Token Válido

**Objetivo:** Verificar que token válido permite acceso.

**Pasos:**
1. Hacer login y copiar token de localStorage
2. Usar Postman:
```bash
curl http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <PEGAR_TOKEN_AQUÍ>"
```

**Resultado Esperado:**
```json
[
  {
    "id": 1,
    "username": "admin",
    "nombre": "Administrador",
    "role": "admin",
    "isAdmin": true
  },
  ...
]
```
- ✅ Status Code: 200 OK
- ✅ Array de usuarios devuelto

---

## 👥 TEST SUITE 3: ROLES Y PERMISOS

### TEST 3.1: Admin - Acceso Completo

**Objetivo:** Verificar que admin ve todos los módulos.

**Pasos:**
1. Login como `admin` / `admin123`
2. Verificar menú lateral

**Resultado Esperado:**
- ✅ Ve: Dashboard, Control de Caja, Historial de Turnos
- ✅ Ve: Punto de Venta, Reporte de Ventas
- ✅ Ve: Inventario, Productos
- ✅ Ve: Análisis, Usuarios, Configuración
- ✅ Puede acceder a TODOS los módulos

---

### TEST 3.2: Vendedor - Acceso Limitado

**Objetivo:** Verificar restricciones de vendedor.

**Prerequisito:** Crear usuario vendedor si no existe:
- Ir a Usuarios → Nuevo Usuario
- Username: `vendedor1`
- Password: `vendedor123`
- Rol: Vendedor

**Pasos:**
1. Logout
2. Login como `vendedor1` / `vendedor123`
3. Verificar menú lateral

**Resultado Esperado:**
- ✅ Ve: Dashboard, Punto de Venta
- ❌ NO ve: Control de Caja, Usuarios, Configuración
- ❌ NO puede acceder a módulos restringidos
- ✅ Si intenta acceder a `/usuarios` → mensaje "No tienes permisos"

---

### TEST 3.3: Cajero - Acceso Intermedio

**Objetivo:** Verificar permisos de cajero.

**Prerequisito:** Crear usuario cajero:
- Username: `cajero1`
- Password: `cajero123`
- Rol: Cajero

**Pasos:**
1. Logout
2. Login como `cajero1` / `cajero123`
3. Verificar menú lateral

**Resultado Esperado:**
- ✅ Ve: Dashboard, Control de Caja, Punto de Venta
- ❌ NO ve: Productos, Usuarios, Configuración
- ✅ Puede abrir/cerrar caja
- ✅ Puede procesar ventas

---

### TEST 3.4: Vendedor Intenta CRUD Usuarios (Backend)

**Objetivo:** Verificar que backend rechaza vendedor haciendo CRUD.

**Pasos:**
1. Login como `vendedor1`
2. Copiar token de localStorage
3. En Postman:
```bash
curl -X POST http://localhost/kiosco/api/usuarios.php \
  -H "Authorization: Bearer <TOKEN_VENDEDOR>" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "hacker",
    "password": "123",
    "nombre": "Hacker",
    "role": "admin"
  }'
```

**Resultado Esperado:**
- ✅ Status Code: 403 Forbidden (si role check implementado)
- ✅ O 401 si token no se valida contra BD
- ❌ NO debe crear el usuario

**Nota:** Este test puede fallar actualmente si role check aún no está completo en backend. Es ESPERADO hasta completar fix-2.

---

## 🔐 TEST SUITE 4: SESIONES Y LOGOUT

### TEST 4.1: Logout Limpia Sesión

**Objetivo:** Verificar que logout elimina token y usuario.

**Pasos:**
1. Login como cualquier usuario
2. Verificar localStorage tiene: `authToken` y `currentUser`
3. Click en "Cerrar Sesión" en menú
4. Verificar localStorage

**Resultado Esperado:**
- ✅ Redirección a Login Page
- ✅ localStorage: `authToken` eliminado
- ✅ localStorage: `currentUser` eliminado
- ✅ Si intenta volver a Dashboard → redirección a Login

---

### TEST 4.2: Token Expirado (Manual)

**Objetivo:** Simular token expirado.

**Pasos:**
1. Login y copiar token
2. Cerrar navegador
3. Esperar 8+ horas (o manipular timestamp en BD si se implementa tabla sesiones)
4. Volver a abrir navegador
5. Ir a `http://localhost:3000`

**Resultado Esperado (cuando se implemente expiración):**
- ✅ Debe forzar re-login
- ✅ Token antiguo rechazado
- ✅ Usuario redirigido a Login

**Nota:** Este test solo funciona si se implementa tabla `sesiones` con expiración.

---

## 🌐 TEST SUITE 5: CORS (CROSS-ORIGIN)

### TEST 5.1: Origin Permitido

**Objetivo:** Verificar que localhost:3000 está en whitelist.

**Pasos:**
1. En navegador, abrir DevTools (F12)
2. Ir a Network tab
3. Hacer cualquier request desde frontend (ej: login)
4. Ver headers de response

**Resultado Esperado:**
- ✅ Header: `Access-Control-Allow-Origin: http://localhost:3000`
- ✅ Request exitoso, no hay error de CORS

---

### TEST 5.2: Origin Bloqueado

**Objetivo:** Verificar que origins no permitidos son rechazados.

**Pasos:**
1. Crear archivo HTML simple:
```html
<!-- test_cors.html -->
<script>
fetch('http://localhost/kiosco/api/usuarios.php', {
  headers: {
    'Authorization': 'Bearer test_token'
  }
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
</script>
```
2. Servir este HTML desde un puerto diferente (ej: 8080)
3. Abrir en navegador

**Resultado Esperado:**
- ❌ Error de CORS en console
- ❌ "Access to fetch... has been blocked by CORS policy"
- ✅ Log en servidor: "CORS: Origin no autorizado bloqueado..."

---

## ⏱️ TEST SUITE 6: RATE LIMITING (SI IMPLEMENTADO)

### TEST 6.1: Múltiples Intentos Fallidos

**Objetivo:** Verificar límite de intentos de login.

**Pasos:**
1. Intentar login 6 veces seguidas con password incorrecta:
   - Intento 1-5: Usuario `admin`, Password `wrong`
   - Verificar respuesta

**Resultado Esperado (si rate limiting implementado):**
- Intentos 1-5: "Credenciales inválidas"
- Intento 6: "Demasiados intentos. Intenta en 15 minutos"
- ✅ Status Code 429 (Too Many Requests)

**Nota:** Este test solo funciona si se implementa fix-6 (rate limiting).

---

## 🎨 TEST SUITE 7: FRONTEND - GUARDS Y UX

### TEST 7.1: Deep Link Sin Auth

**Objetivo:** Verificar que deep links redirigen a login.

**Pasos:**
1. Asegurarse de estar deslogueado (localStorage limpio)
2. Pegar URL directa en navegador:
   `http://localhost:3000/#/usuarios`
3. Presionar Enter

**Resultado Esperado:**
- ✅ Redirección automática a Login Page
- ✅ NO muestra página de usuarios

---

### TEST 7.2: UI Oculta Botones Según Rol

**Objetivo:** Verificar que PermissionGuard oculta elementos.

**Pasos:**
1. Login como `vendedor1`
2. Navegar a Punto de Venta
3. Buscar botones de administración

**Resultado Esperado:**
- ❌ NO ve botón "Configuración"
- ❌ NO ve botón "Gestión de Usuarios"
- ✅ Solo ve opciones permitidas para su rol

---

### TEST 7.3: Recarga de Página Mantiene Sesión

**Objetivo:** Verificar que AuthContext rehidrata correctamente.

**Pasos:**
1. Login como cualquier usuario
2. Navegar a Dashboard
3. Presionar F5 (recargar página)

**Resultado Esperado:**
- ✅ Usuario sigue logueado
- ✅ Dashboard se carga correctamente
- ✅ No redirecciona a Login
- ✅ localStorage conserva `authToken` y `currentUser`

---

## 🔍 TEST SUITE 8: LOGS Y AUDITORÍA

### TEST 8.1: Logs de Login Exitoso

**Objetivo:** Verificar logging de autenticación.

**Pasos:**
1. Hacer login exitoso como `admin`
2. Verificar logs del servidor

**Resultado Esperado:**
- ✅ Log: "Intento de autenticación para usuario: admin"
- ✅ Log: "Login exitoso para usuario: admin [ID: 1]"
- ❌ NO debe loguear hash de contraseña

**Ubicación logs:**
- Laragon: `C:\laragon\bin\apache\apache-X.X\logs\error.log`
- Linux: `/var/log/apache2/error.log`

---

### TEST 8.2: Logs de Login Fallido

**Objetivo:** Verificar logging de intentos fallidos.

**Pasos:**
1. Intentar login con password incorrecta
2. Verificar logs del servidor

**Resultado Esperado:**
- ✅ Log: "Intento de autenticación para usuario: admin"
- ✅ Log: "Intento de login fallido para usuario: admin (contraseña incorrecta)"
- ❌ NO debe loguear el hash de contraseña
- ❌ NO debe loguear la contraseña en texto plano

---

## 📊 RESULTADOS

### Formato de Reporte

```markdown
## Resultados de Pruebas - [FECHA]

**Ejecutado por:** [Nombre]  
**Ambiente:** Local / Staging / Producción  
**Build:** [Versión]

### Resumen
- ✅ Pasadas: X/30
- ❌ Fallidas: Y/30
- ⏭️ Omitidas: Z/30

### Tests Fallidos

#### TEST X.X: [Nombre del Test]
- **Resultado:** FALLO
- **Problema:** [Descripción del error]
- **Screenshot:** [Si aplica]
- **Logs:** [Extracto relevante]
- **Prioridad:** CRÍTICO / ALTO / MEDIO

### Comentarios Adicionales
[Observaciones generales]
```

---

## 🐛 REPORTE DE BUGS

Si encuentras un bug durante las pruebas, usar este template:

```markdown
### BUG #XXX: [Título Corto]

**Severidad:** 🔴 Crítica / 🟠 Alta / 🟡 Media / 🟢 Baja

**Descripción:**
[Qué pasó]

**Pasos para Reproducir:**
1. [Paso 1]
2. [Paso 2]
3. [Paso 3]

**Resultado Esperado:**
[Qué debería pasar]

**Resultado Actual:**
[Qué pasó en realidad]

**Screenshots:**
[Si aplica]

**Logs:**
```
[Extracto de logs]
```

**Ambiente:**
- OS: Windows 10 / Linux / Mac
- Browser: Chrome 120 / Firefox 115
- Backend: PHP 8.0.30
- Database: MySQL 8.0.35
```

---

## ✅ CRITERIOS DE ACEPTACIÓN

Para considerar el sistema **LISTO PARA PRODUCCIÓN**, deben cumplirse:

### Obligatorios (MUST)
- ✅ Todos los tests de Suite 1 (Autenticación) PASADOS
- ✅ Todos los tests de Suite 2 (Tokens) PASADOS
- ✅ Todos los tests de Suite 3 (Roles) PASADOS
- ✅ Todos los tests de Suite 4 (Logout) PASADOS
- ✅ Todos los tests de Suite 7 (Frontend Guards) PASADOS

### Recomendados (SHOULD)
- ✅ Tests de Suite 5 (CORS) PASADOS
- ✅ Tests de Suite 8 (Logs) PASADOS

### Opcionales (COULD)
- ⏭️ Tests de Suite 6 (Rate Limiting) - si fue implementado

---

## 📞 CONTACTO

Si tienes dudas sobre algún test o encuentras comportamiento inesperado:

- **Lead Developer:** [Nombre]
- **QA Lead:** [Nombre]
- **Issue Tracker:** [URL de GitHub/Jira]

---

**Plan de pruebas creado por:** Cursor AI Agent  
**Última actualización:** 21 de Octubre, 2025  
**Versión:** 1.0.0

