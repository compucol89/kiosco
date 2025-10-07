# 🚀 REQUERIMIENTOS TÉCNICOS PARA DEPLOY - TAYRONA KIOSCO POS

## 📋 INFORMACIÓN DEL SISTEMA

**Sistema:** Tayrona Kiosco POS v1.0.1  
**Arquitectura:** React Frontend + PHP API Backend + MySQL Database  
**Tipo:** Sistema de Punto de Venta para Kioscos/Almacenes en Argentina  
**Fecha de Análisis:** 5 de Octubre, 2025  

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### Frontend
- **Framework:** React 18.2.0
- **Build Tool:** React Scripts 5.0.1
- **UI Framework:** Tailwind CSS 3.3.3
- **Routing:** React Router DOM 7.5.1
- **Estado:** Context API + Hooks personalizados
- **HTTP Client:** Axios 1.8.4

### Backend
- **Lenguaje:** PHP 8.1+
- **Base de Datos:** MySQL 8.0+ / MariaDB 10.6+
- **API:** REST API con endpoints PHP
- **Arquitectura:** MVC Pattern

### Servicios Externos
- **AFIP (Facturación Electrónica):** Integración con Web Services AFIP
- **MercadoPago:** Datos configurados para pagos digitales
- **AI Services:** Configuración para OpenAI, Anthropic, Google AI (opcional)

---

## 💻 REQUERIMIENTOS DE SOFTWARE

### Servidor Web
```bash
# Opción 1: Apache 2.4+
- mod_rewrite habilitado
- mod_ssl habilitado (para HTTPS)
- mod_headers habilitado

# Opción 2: Nginx 1.18+
- Configuración para PHP-FPM
- Soporte para rewrite rules
```

### PHP
```bash
Versión: PHP 8.1 o superior
Extensiones requeridas:
- php-pdo
- php-pdo-mysql
- php-json
- php-mbstring
- php-curl
- php-openssl
- php-zip
- php-xml
- php-session
- php-gd (para manejo de imágenes)
- php-fileinfo
```

### Base de Datos
```bash
MySQL 8.0+ o MariaDB 10.6+
Configuraciones:
- InnoDB storage engine
- UTF8MB4 charset
- sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'
- innodb_lock_wait_timeout = 10
```

### Node.js (Para Build)
```bash
Node.js: 18.0.0+
NPM: 8.0.0+
```

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Tablas Principales
```sql
-- Tablas core del sistema
productos              -- Catálogo de productos
usuarios               -- Usuarios del sistema
ventas                 -- Registro de ventas
turnos_caja           -- Control de turnos de caja
movimientos_caja_detallados -- Movimientos de efectivo
historial_turnos_caja -- Histórico de turnos

-- Tablas de configuración
configuracion         -- Configuración del sistema
system_cache_invalidation -- Cache del sistema
dashboard_performance_log -- Log de performance
```

### Índices Críticos (Ya implementados)
```sql
-- Performance optimizada para <25ms
idx_ventas_dashboard_daily
idx_ventas_payment_methods
idx_productos_stock_critical
idx_caja_estado_current
idx_caja_movimientos_daily
```

---

## 📁 ESTRUCTURA DE DIRECTORIOS

```
kiosco/
├── api/                    # Backend PHP
│   ├── *.php              # Endpoints de API
│   ├── logs/              # Logs del sistema
│   ├── cache/             # Cache de datos
│   └── queue/             # Cola de trabajos
├── build/                 # Frontend compilado
├── src/                   # Código fuente React
├── uploads/               # Archivos subidos
├── img/                   # Imágenes de productos
├── database/              # Scripts de migración
├── node_modules/          # Dependencias Node.js
├── package.json           # Dependencias frontend
├── composer.json          # Dependencias PHP
├── config_production.php  # Configuración de producción
└── Dockerfile            # Configuración Docker
```

---

## 🔧 CONFIGURACIÓN REQUERIDA

### Variables de Entorno
```bash
# Base de datos
DB_HOST=localhost
DB_NAME=kiosco_db
DB_USER=kiosco_user
DB_PASS=password_seguro

# URLs del sistema
FRONTEND_URL=https://tu-dominio.com
API_URL=https://tu-dominio.com/api

# Configuración PHP
PHP_MEMORY_LIMIT=256M
PHP_MAX_EXECUTION_TIME=60
PHP_POST_MAX_SIZE=50M
PHP_UPLOAD_MAX_FILESIZE=20M
```

### Configuración Apache (.htaccess)
```apache
RewriteEngine On

# Redirigir todo a HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# SPA routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule . /index.html [L]

# Headers de seguridad
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### Configuración Nginx
```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name tu-dominio.com;
    root /var/www/kiosco;
    index index.html index.php;

    # SSL Configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/private.key;

    # Static files
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API endpoints
    location /api/ {
        try_files $uri $uri/ @php;
    }

    location @php {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔒 CONFIGURACIÓN DE SEGURIDAD

### SSL/TLS
```bash
# Certificado SSL requerido para:
- Comunicación con AFIP
- Seguridad de datos financieros
- Cumplimiento normativo argentino
```

### Headers de Seguridad
```php
// Ya implementados en config_production.php
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

### Rate Limiting
```php
// Implementado: 100 requests por minuto por IP
// Configuración automática en config_production.php
```

---

## 📊 REQUERIMIENTOS DE PERFORMANCE

### Recursos Mínimos
```bash
CPU: 2 cores (recomendado 4 cores)
RAM: 4GB (recomendado 8GB)
Almacenamiento: 20GB SSD
Ancho de banda: 10 Mbps
```

### Optimizaciones Implementadas
```bash
- Índices de base de datos optimizados (<25ms)
- Sistema de cache inteligente
- Compresión gzip automática
- Query optimization con prepared statements
- Connection pooling para MySQL
```

---

## 🔌 SERVICIOS EXTERNOS

### AFIP (Obligatorio para Argentina)
```bash
Configuración:
- CUIT: 30718850874 (Tayrona Group)
- Ambiente: TESTING/PRODUCCION
- Certificados digitales requeridos
- Web Services: WSAA, WSFE, WSFEX
```

### MercadoPago (Opcional)
```bash
Configurado:
- CVU: 0000003100078171460356
- Alias: Paga86
- Comisión: 2.99%
```

### AI Services (Opcional)
```bash
Soportados:
- OpenAI GPT-4
- Anthropic Claude
- Google Gemini
- Groq Llama
```

---

## 📦 PROCESO DE INSTALACIÓN

### 1. Preparar Servidor
```bash
# Ubuntu 24.04 (Recomendado)
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php8.1 php8.1-fpm mysql-server -y
sudo apt install php8.1-pdo php8.1-mysql php8.1-json php8.1-mbstring -y
sudo apt install php8.1-curl php8.1-openssl php8.1-zip php8.1-xml -y
```

### 2. Configurar Base de Datos
```sql
CREATE DATABASE kiosco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'kiosco_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON kiosco_db.* TO 'kiosco_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Subir Archivos
```bash
# Subir todo el contenido del proyecto
# Asegurar permisos correctos
sudo chown -R www-data:www-data /var/www/kiosco
sudo chmod -R 755 /var/www/kiosco
sudo chmod -R 777 /var/www/kiosco/uploads
sudo chmod -R 777 /var/www/kiosco/api/logs
```

### 4. Configurar Producción
```bash
# Copiar configuración de producción
cp config_production.php config.php

# Editar configuración de base de datos
nano api/bd_conexion.php
```

### 5. Ejecutar Migraciones
```sql
-- Ejecutar scripts de migración
source database/migrations/dashboard_fintech_indexes.sql
source database/migrations/pos_optimization_indexes.sql
source database/optimized_indexes.sql
```

### 6. Build del Frontend
```bash
npm install
npm run build
```

---

## 🐳 OPCIÓN DOCKER

### Dockerfile Incluido
```bash
# Build de la imagen
docker build -t tayrona-pos .

# Ejecutar contenedor
docker run -d \
  -p 3000:3000 \
  -e DB_HOST=host.docker.internal \
  -e DB_NAME=kiosco_db \
  -e DB_USER=kiosco_user \
  -e DB_PASS=password \
  tayrona-pos
```

---

## 📱 RECOMENDACIONES DE SERVIDORES ALTERNATIVOS

### 🥇 OPCIÓN 1: DigitalOcean (Recomendado)
```bash
Ventajas:
✅ Droplets con Ubuntu 24.04 preconfigurado
✅ SSD de alta velocidad
✅ Backup automático
✅ Firewall incluido
✅ Monitoring integrado
✅ $12/mes para 2GB RAM, 1 CPU, 50GB SSD

Plan recomendado: Basic Droplet $24/mes (4GB RAM, 2 CPUs)
```

### 🥈 OPCIÓN 2: AWS Lightsail
```bash
Ventajas:
✅ Precio fijo predecible
✅ Integración con otros servicios AWS
✅ Snapshots automáticos
✅ Load balancer disponible
✅ CDN incluido

Plan recomendado: $20/mes (2GB RAM, 1 CPU, 60GB SSD)
```

### 🥉 OPCIÓN 3: Vultr
```bash
Ventajas:
✅ Muy económico
✅ SSD NVMe ultra rápido
✅ 25+ ubicaciones globales
✅ IPv6 incluido
✅ Firewall gratuito

Plan recomendado: $12/mes (2GB RAM, 1 CPU, 55GB SSD)
```

### 🏆 OPCIÓN PREMIUM: Linode/Akamai
```bash
Ventajas:
✅ Performance excepcional
✅ Red global premium
✅ Soporte 24/7
✅ Object Storage incluido
✅ Kubernetes disponible

Plan recomendado: $24/mes (4GB RAM, 2 CPUs, 80GB SSD)
```

### 💰 OPCIÓN ECONÓMICA: Hostinger VPS
```bash
Ventajas:
✅ Muy económico ($4.99/mes)
✅ Panel de control fácil
✅ Backup semanal
✅ Soporte en español
✅ SSL gratis

Plan recomendado: VPS 2 - $8.99/mes (4GB RAM, 2 CPUs)
```

---

## 🔧 SERVICIOS GESTIONADOS (Más Fácil)

### 🚀 OPCIÓN SUPER FÁCIL: Railway
```bash
Ventajas:
✅ Deploy automático desde Git
✅ Base de datos MySQL incluida
✅ SSL automático
✅ Escalado automático
✅ $5/mes por servicio

Proceso:
1. Push código a GitHub
2. Conectar Railway a GitHub
3. Deploy automático
4. ¡Listo!
```

### 🌟 OPCIÓN MODERNA: Vercel + PlanetScale
```bash
Frontend en Vercel (Gratis):
✅ Deploy automático
✅ CDN global
✅ SSL automático
✅ Preview deployments

Backend en Railway ($5/mes):
✅ PHP support
✅ MySQL incluida
✅ Auto-deploy
```

### 🔥 OPCIÓN TODO-EN-UNO: Cloudflare Pages + D1
```bash
Ventajas:
✅ Gratis hasta 100,000 requests/día
✅ Base de datos SQLite serverless
✅ CDN global incluido
✅ Workers para backend
✅ SSL automático

Nota: Requiere migración de MySQL a SQLite
```

---

## 📋 CHECKLIST DE DEPLOY

### Pre-Deploy
- [ ] Servidor configurado con PHP 8.1+
- [ ] MySQL 8.0+ instalado y configurado
- [ ] SSL/TLS configurado
- [ ] Firewall configurado (puertos 80, 443)
- [ ] Dominio apuntando al servidor

### Durante Deploy
- [ ] Archivos subidos al servidor
- [ ] Permisos configurados correctamente
- [ ] Base de datos creada
- [ ] Usuario de base de datos creado
- [ ] Configuración de producción aplicada
- [ ] Migraciones ejecutadas
- [ ] Frontend compilado

### Post-Deploy
- [ ] Verificar conexión a base de datos
- [ ] Probar endpoints de API
- [ ] Verificar carga del frontend
- [ ] Comprobar funcionalidad de caja
- [ ] Validar integración AFIP (testing)
- [ ] Configurar backups automáticos
- [ ] Configurar monitoring

---

## 🆘 SOPORTE Y TROUBLESHOOTING

### Logs Importantes
```bash
# Logs de PHP
/var/log/php/error.log
/path/to/kiosco/api/logs/production.log

# Logs de Apache
/var/log/apache2/error.log
/var/log/apache2/access.log

# Logs de MySQL
/var/log/mysql/error.log
```

### Comandos Útiles de Diagnóstico
```bash
# Verificar estado de servicios
systemctl status apache2
systemctl status mysql
systemctl status php8.1-fpm

# Verificar conexión a base de datos
mysql -u kiosco_user -p kiosco_db

# Verificar permisos
ls -la /var/www/kiosco/
```

### Contacto de Soporte
```bash
Desarrollador: Tayrona POS Team
Email: infocompucol@gmail.com
Repositorio: https://github.com/compucol89/tommyposV1.0.git
```

---

## 📈 ESTIMACIÓN DE COSTOS MENSUALES

| Proveedor | Plan | Precio | RAM | CPU | Storage | Recomendado |
|-----------|------|--------|-----|-----|---------|-------------|
| **DigitalOcean** | Basic | $24/mes | 4GB | 2 CPU | 80GB SSD | ⭐⭐⭐⭐⭐ |
| **Railway** | Pro | $5/mes | 2GB | 1 CPU | 50GB | ⭐⭐⭐⭐⭐ |
| **Vultr** | Regular | $12/mes | 2GB | 1 CPU | 55GB SSD | ⭐⭐⭐⭐ |
| **AWS Lightsail** | Medium | $20/mes | 2GB | 1 CPU | 60GB SSD | ⭐⭐⭐⭐ |
| **Hostinger** | VPS 2 | $9/mes | 4GB | 2 CPU | 80GB SSD | ⭐⭐⭐ |

**Recomendación:** Railway para máxima simplicidad, DigitalOcean para control total.

---

## 🎯 CONCLUSIÓN

El sistema Tayrona Kiosco POS está listo para producción con:

✅ **Arquitectura robusta** React + PHP + MySQL  
✅ **Performance optimizada** <25ms en consultas críticas  
✅ **Seguridad enterprise** Headers, SSL, Rate limiting  
✅ **Integración AFIP** Lista para facturación argentina  
✅ **Deploy flexible** Múltiples opciones de servidor  

**Recomendación final:** Usar **Railway** para deploy más simple, o **DigitalOcean** para control completo del servidor.

---

*Documento generado automáticamente el 5 de Octubre, 2025*  
*Sistema analizado: Tayrona Kiosco POS v1.0.1*
