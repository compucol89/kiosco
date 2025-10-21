# 🔐 CONFIGURACIÓN DE VARIABLES DE ENTORNO (.env)

**Sistema:** Tayrona Almacén - Kiosco POS  
**Propósito:** Configurar API Key y otras variables sensibles  
**Archivo:** `.env.local` (desarrollo) y `.env.production` (producción)

---

## 📋 TEMPLATE DE CONFIGURACIÓN

### Archivo: `.env.local` (Desarrollo)

Crear este archivo en la raíz del proyecto para desarrollo local:

```bash
# ================================================
# KIOSCO POS - CONFIGURACIÓN DE DESARROLLO
# ================================================

# API Key (shared secret con backend)
# En desarrollo, usar este valor por defecto:
REACT_APP_API_KEY=kiosco-api-2025-cambiar-en-produccion

# Backend URL (Laragon local)
REACT_APP_API_URL=http://localhost/kiosco

# Ambiente
NODE_ENV=development

# Features (opcional)
REACT_APP_ENABLE_AFIP=true
REACT_APP_ENABLE_AI_ANALYTICS=true
REACT_APP_ENABLE_DEVICE_FINGERPRINT=true

# Debug (solo en desarrollo)
REACT_APP_DEBUG_MODE=true
REACT_APP_SHOW_API_LOGS=true
```

---

### Archivo: `.env.production` (Producción)

Crear este archivo en el servidor (NUNCA comitear):

```bash
# ================================================
# KIOSCO POS - CONFIGURACIÓN DE PRODUCCIÓN
# ================================================

# API Key (GENERAR UNA NUEVA Y ÚNICA)
# Ejecutar: php -r "echo bin2hex(random_bytes(32));"
# La misma key debe estar en el backend como API_SHARED_KEY
REACT_APP_API_KEY=TU_KEY_GENERADA_64_CARACTERES_AQUI

# Backend URL (dominio real)
REACT_APP_API_URL=https://tudominio.com

# Ambiente
NODE_ENV=production

# Features
REACT_APP_ENABLE_AFIP=true
REACT_APP_ENABLE_AI_ANALYTICS=true
REACT_APP_ENABLE_DEVICE_FINGERPRINT=true

# Debug (DESACTIVADO en producción)
REACT_APP_DEBUG_MODE=false
REACT_APP_SHOW_API_LOGS=false
```

---

## 🔧 INSTRUCCIONES DE SETUP

### 1. Desarrollo Local

```bash
# Paso 1: Crear archivo .env.local en raíz del proyecto
touch .env.local

# Paso 2: Copiar contenido del template de desarrollo (arriba)
nano .env.local
# Pegar el contenido y guardar

# Paso 3: Reiniciar servidor de desarrollo
npm start
```

### 2. Producción

```bash
# Paso 1: Generar API Key única
php -r "echo bin2hex(random_bytes(32));"
# Copiar el resultado (64 caracteres)

# Paso 2: Configurar en servidor backend
# Opción A: Variable de entorno del sistema
export API_SHARED_KEY="tu-key-generada-aquí"
echo 'export API_SHARED_KEY="tu-key-generada-aquí"' >> /etc/environment

# Opción B: En .htaccess (menos recomendado)
# SetEnv API_SHARED_KEY "tu-key-generada-aquí"

# Paso 3: Reiniciar Apache
sudo systemctl restart apache2

# Paso 4: Crear .env.production en proyecto frontend
nano .env.production
# Pegar contenido del template de producción (arriba)
# Reemplazar TU_KEY_GENERADA... con la key generada en Paso 1

# Paso 5: Build con configuración de producción
npm run build

# Paso 6: Subir carpeta build/ al servidor
```

---

## 🔐 GENERAR API KEY

### Opción 1: PHP (Recomendado)
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Opción 2: Node.js
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

### Opción 3: OpenSSL
```bash
openssl rand -hex 32
```

**Resultado esperado:** String de 64 caracteres hexadecimales  
**Ejemplo:** `a3f8d2c1b9e7f4a6d8c2e9b4f7a1d3c5e8b2f4a9d7c1e3b6f9a2d4c8e1b3f6a9`

---

## 📁 .gitignore

**IMPORTANTE:** Agregar estos archivos a `.gitignore` para NO comitearlos:

```bash
# En .gitignore (si no está ya):

# Environment variables
.env
.env.local
.env.development
.env.test
.env.production
.env.staging

# Logs
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Misc
.DS_Store
```

---

## ✅ VERIFICAR CONFIGURACIÓN

### Test 1: Verificar que la key se carga

```javascript
// En cualquier componente React:
console.log('API Key configurada:', !!process.env.REACT_APP_API_KEY);
console.log('Longitud:', process.env.REACT_APP_API_KEY?.length);
// Debe mostrar: true y 64 (en desarrollo) o longitud de tu key (producción)
```

### Test 2: Verificar que se envía en requests

```javascript
// En DevTools → Network → Headers:
// Debe aparecer:
// X-Api-Key: kiosco-api-2025-cambiar-en-produccion (o tu key de prod)
```

### Test 3: Verificar que backend acepta la key

```bash
# Login debe funcionar:
curl -X POST http://localhost/kiosco/api/auth.php \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: kiosco-api-2025-cambiar-en-produccion" \
  -d '{"username":"admin","password":"admin123"}'

# Debe retornar token y usuario
```

---

## 🚨 PROBLEMAS COMUNES

### Problema: "API key required" en desarrollo

**Causa:** Archivo .env.local no existe o no tiene la variable

**Solución:**
```bash
# Crear .env.local:
echo 'REACT_APP_API_KEY=kiosco-api-2025-cambiar-en-produccion' > .env.local

# Reiniciar:
npm start
```

### Problema: "Invalid API key" en producción

**Causa:** Key del frontend no coincide con backend

**Solución:**
```bash
# Backend - verificar variable:
echo $API_SHARED_KEY

# Frontend - verificar .env.production:
cat .env.production | grep REACT_APP_API_KEY

# Deben ser idénticas
```

### Problema: Variables no se cargan

**Causa:** Nombre incorrecto (deben empezar con `REACT_APP_`)

**Solución:**
```bash
# ❌ Incorrecto:
API_KEY=abc123

# ✅ Correcto:
REACT_APP_API_KEY=abc123
```

---

## 🔄 ROTACIÓN DE API KEY

### Cada 3-6 meses, rotar la key:

```bash
# 1. Generar nueva key
NEW_KEY=$(php -r "echo bin2hex(random_bytes(32));")
echo "Nueva key: $NEW_KEY"

# 2. Actualizar backend
export API_SHARED_KEY="$NEW_KEY"
sudo systemctl restart apache2

# 3. Actualizar frontend
echo "REACT_APP_API_KEY=$NEW_KEY" > .env.production

# 4. Rebuild
npm run build

# 5. Deploy (subir build/)

# 6. Verificar logs por requests con key antigua:
tail -f /var/log/apache2/error.log | grep "Invalid API key"
```

---

## 📊 AMBIENTES MÚLTIPLES

### Staging

```bash
# .env.staging
REACT_APP_API_KEY=staging-key-diferente-a-prod
REACT_APP_API_URL=https://staging.tudominio.com
NODE_ENV=staging
```

### Testing

```bash
# .env.test
REACT_APP_API_KEY=test-key-solo-para-tests
REACT_APP_API_URL=http://localhost:8080
NODE_ENV=test
```

---

## 🎯 MEJORES PRÁCTICAS

✅ **SÍ:**
- Usar keys diferentes para cada ambiente
- Generar keys con comandos criptográficos
- Rotar keys periódicamente
- Usar variables de entorno en servidor
- Documentar proceso de rotación
- Hacer backup de keys en password manager

❌ **NO:**
- Comitear archivos .env a git
- Reusar keys entre ambientes
- Usar keys débiles tipo "12345"
- Compartir keys por email/Slack
- Loguear keys completas
- Hardcodear keys en el código

---

## 📝 CHECKLIST DE SEGURIDAD

- [ ] .env.local creado para desarrollo
- [ ] .env.production creado en servidor
- [ ] API keys generadas criptográficamente
- [ ] Keys diferentes por ambiente
- [ ] .gitignore incluye archivos .env
- [ ] Backend configurado con misma key
- [ ] Tests pasados (key se envía y backend acepta)
- [ ] Documentado en password manager
- [ ] Fecha de rotación agendada (3-6 meses)

---

## 📞 SOPORTE

Si tienes problemas con la configuración:

1. Verificar que archivo .env existe:
   ```bash
   ls -la .env.local
   ```

2. Verificar contenido:
   ```bash
   cat .env.local
   ```

3. Verificar que React lee la variable:
   ```javascript
   console.log(process.env.REACT_APP_API_KEY);
   ```

4. Verificar logs del backend:
   ```bash
   tail -f /var/log/apache2/error.log | grep "API Key"
   ```

---

**Configuración documentada por:** Cursor AI Agent  
**Fecha:** 21 de Octubre, 2025  
**Estado:** ✅ LISTO PARA USAR  
**Mantenimiento:** Rotar keys cada 3-6 meses

