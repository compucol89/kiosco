# 🏪 Tayrona Almacén - Sistema POS Kiosco

Sistema de Punto de Venta completo y optimizado para kioscos, almacenes y minimarkets en Argentina.

---

## 📋 Índice

- [Descripción](#-descripción)
- [Características Principales](#-características-principales)
- [Stack Tecnológico](#%EF%B8%8F-stack-tecnológico)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Módulos del Sistema](#-módulos-del-sistema)
- [Requisitos Previos](#-requisitos-previos)
- [Instalación](#-instalación)
- [Configuración](#%EF%B8%8F-configuración)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Base de Datos](#%EF%B8%8F-base-de-datos)
- [API Endpoints](#-api-endpoints)
- [Integración AFIP](#-integración-afip)
- [Seguridad](#-seguridad)
- [Sistema de Permisos](#-sistema-de-permisos)
- [Flujo de Trabajo](#-flujo-de-trabajo)
- [Componentes Principales](#-componentes-principales)
- [Hooks Personalizados](#-hooks-personalizados)
- [Servicios](#-servicios)
- [Uso del Sistema](#-uso-del-sistema)
- [Mantenimiento](#%EF%B8%8F-mantenimiento)
- [Troubleshooting](#-troubleshooting)
- [Licencia](#-licencia)

---

## 🎯 Descripción

**Tayrona Almacén** es un sistema POS (Point of Sale) completo desarrollado específicamente para negocios minoristas en Argentina como kioscos, almacenes y minimarkets.

El sistema está diseñado con una arquitectura moderna, separando claramente el frontend (React) del backend (PHP), permitiendo escalabilidad, mantenibilidad y un rendimiento óptimo.

### Diseñado para Argentina

- ✅ Facturación electrónica AFIP integrada
- ✅ Formato de moneda argentino (ARS)
- ✅ Comprobantes fiscales (Factura A, B, C)
- ✅ Gestión de CUIT y datos fiscales
- ✅ Normativa contable argentina

---

## ✨ Características Principales

### 💰 Control de Caja Profesional
- Apertura y cierre de caja con validación de efectivo
- Registro detallado de movimientos (ingresos/egresos)
- Historial completo de turnos de caja
- Reportes de diferencias por cajero
- Sincronización automática con ventas
- Validación obligatoria de caja abierta para operar

### 🛒 Punto de Venta (POS)
- Interfaz rápida y optimizada para ventas
- Búsqueda inteligente de productos con autocompletado
- Control de stock en tiempo real con alertas
- Múltiples métodos de pago (efectivo, tarjeta, transferencia)
- Descuentos automáticos por método de pago
- Generación de tickets profesionales
- Shortcuts de teclado (F6-F8, Enter, Escape)
- Facturación AFIP opcional integrada

### 📦 Gestión de Inventario
- CRUD completo de productos
- Gestión de categorías y proveedores
- Control de stock con alertas automáticas
- Auditoría de movimientos de inventario
- Importación masiva de productos
- Análisis de ventas por producto
- Búsqueda enterprise-grade (<25ms)
- Sistema de imágenes con caché optimizado

### 📊 Reportes y Analytics
- Dashboard completo con métricas en tiempo real
- Reportes de ventas detallados por período
- Análisis financiero con P&L
- Reportes de efectivo por período
- Estadísticas de productos más vendidos
- Análisis de rentabilidad por producto
- Exportación a Excel (XLSX)

### 🤖 Inteligencia Artificial
- Análisis inteligente de ventas con OpenAI
- Diagnóstico financiero automatizado
- Predicciones de inventario
- Detección de patrones de venta
- Recomendaciones de stock
- Sistema anti-fraude

### 👥 Gestión de Usuarios
- Sistema de autenticación seguro
- 3 roles de usuario: Admin, Vendedor, Cajero
- Permisos granulares por módulo
- Gestión completa de usuarios
- Auditoría de acciones

### 🧾 Facturación AFIP
- Integración completa con Web Services AFIP
- Generación automática de comprobantes fiscales
- Soporte para Factura A, B y C
- Sistema híbrido (real + fallback simulado)
- Caché inteligente de tokens de acceso
- Procesamiento asíncrono opcional
- Logs detallados de operaciones AFIP

---

## 🛠️ Stack Tecnológico Completo

### 🎨 Frontend (React SPA)

#### Core Framework
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **React** | 18.2.0 | Framework principal de UI | Virtual DOM, Hooks, Componentes funcionales |
| **React DOM** | 18.2.0 | Renderizado en navegador | Integración con el DOM |
| **React Router DOM** | 7.5.1 | Navegación SPA | Enrutamiento client-side |
| **React Scripts** | 5.0.1 | Build tools (Webpack, Babel) | Create React App |

#### Estilos y UI
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **Tailwind CSS** | 3.3.3 | Framework CSS utility-first | Mobile-first, responsive |
| **PostCSS** | 8.4.31 | Procesador CSS | Transformaciones CSS |
| **Autoprefixer** | 10.4.16 | Prefijos CSS automáticos | Compatibilidad multi-browser |
| **Lucide React** | 0.501.0 | Sistema de iconos modernos | 1000+ iconos SVG optimizados |
| **React Icons** | 5.5.0 | Biblioteca adicional de iconos | Font Awesome, Material, etc. |
| **React Feather** | 2.0.10 | Iconos Feather | Iconos minimalistas |

#### Gráficos y Visualización
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **Chart.js** | 4.4.9 | Motor de gráficos | Canvas-based, responsivo |
| **React Chart.js 2** | 5.3.0 | Wrapper React para Chart.js | Componentes React |
| **ChartJS Plugin Datalabels** | 2.2.0 | Labels en gráficos | Anotaciones y etiquetas |

#### HTTP y Comunicación
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **Axios** | 1.8.4 | Cliente HTTP | Promesas, interceptores |
| **Node Fetch** | 3.3.2 | Fetch API para Node | Polyfill para SSR |

#### Documentos y Exportación
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **jsPDF** | 3.0.1 | Generación de PDFs | Tickets, reportes |
| **jsPDF AutoTable** | 5.0.2 | Tablas en PDFs | Reportes tabulares |
| **XLSX** | 0.18.5 | Exportación a Excel | SheetJS - lectura/escritura |

#### Utilidades y Componentes
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **React Toastify** | 11.0.5 | Notificaciones toast | Mensajes emergentes |
| **React Webcam** | 7.2.0 | Acceso a cámara | Captura de evidencias |
| **JSBarcode** | 3.12.1 | Generación de códigos de barras | Tickets y productos |

#### Build y Desarrollo
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **Cross-env** | 7.0.3 | Variables de entorno cross-platform | Windows/Linux/Mac |
| **Prettier** | 3.6.2 | Formateador de código | Code style consistente |
| **Rimraf** | 5.0.10 | Limpieza de archivos | Cross-platform rm -rf |
| **Serve** | 14.2.4 | Servidor estático | Servir build en producción |

**Total de dependencias frontend:** 28 paquetes NPM

---

### ⚙️ Backend (PHP API)

#### Core
| Tecnología | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **PHP** | 8.0+ | Lenguaje servidor | OOP, tipado fuerte |
| **PDO** | Incluido en PHP | Abstracción de base de datos | Prepared statements, seguridad |
| **Composer** | 2.0+ | Gestor de dependencias PHP | Autoloading PSR-4 |

#### Extensiones PHP Requeridas
```bash
php-pdo          # PHP Data Objects
php-pdo_mysql    # Driver MySQL para PDO
php-mysqli       # MySQL Improved (legacy)
php-json         # Manejo de JSON
php-mbstring     # Strings multibyte
php-curl         # Cliente HTTP
php-xml          # Procesamiento XML (AFIP)
php-openssl      # Encriptación SSL/TLS
php-zip          # Compresión (opcional)
php-gd           # Procesamiento de imágenes
```

#### Bibliotecas PHP
| Biblioteca | Versión | Propósito | Notas |
|------------|---------|-----------|-------|
| **AFIP SDK** | 1.2 | Integración con AFIP | Facturación electrónica |

**Total de dependencias backend:** 1 paquete (Composer)

---

### 🗄️ Base de Datos

#### Motor de Base de Datos
| Tecnología | Versión | Propósito | Características |
|------------|---------|-----------|-----------------|
| **MySQL** | 8.0+ | Base de datos relacional | ACID, transacciones, índices |
| **MariaDB** | 10.6+ | Alternativa compatible | Fork de MySQL |

#### Especificaciones
- **Charset:** UTF-8MB4 (soporte Unicode completo, emojis)
- **Collation:** utf8mb4_unicode_ci
- **Motor:** InnoDB (transaccional, foreign keys)
- **Zona horaria:** America/Argentina/Buenos_Aires (UTC-3)

#### Conexión PDO
```php
// Configuración PDO
[
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Arrays asociativos
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4', // Charset UTF8MB4
    PDO::ATTR_EMULATE_PREPARES => false,                 // Prepared statements nativos
    PDO::ATTR_TIMEOUT => 30                              // Timeout 30 segundos
]
```

---

### 🖥️ Infraestructura y Servidor

#### Entorno de Desarrollo (Windows)
| Componente | Tecnología | Versión | Descripción |
|------------|------------|---------|-------------|
| **Stack Local** | Laragon | Latest | Apache + PHP + MySQL todo-en-uno |
| **Servidor Web** | Apache | 2.4+ | mod_rewrite habilitado |
| **PHP** | PHP | 8.0+ | Con extensiones requeridas |
| **Base de Datos** | MySQL | 8.0+ | Puerto 3306 |
| **Node.js** | Node.js | 18.0+ | Runtime JavaScript |
| **NPM** | NPM | 8.0+ | Gestor de paquetes |
| **Git** | Git Bash | Latest | Control de versiones |

#### Entorno de Producción (Linux)
| Componente | Tecnología | Versión | Descripción |
|------------|------------|---------|-------------|
| **Servidor Web** | NGINX / Apache | 1.18+ / 2.4+ | Servidor HTTP |
| **PHP-FPM** | PHP-FPM | 8.0+ | FastCGI Process Manager |
| **Base de Datos** | MySQL | 8.0+ | Servidor MySQL |
| **SSL/TLS** | Let's Encrypt | - | Certificados HTTPS gratuitos |

#### Requisitos del Sistema

**Desarrollo (Local):**
```
Sistema Operativo: Windows 10/11
RAM: 4GB mínimo, 8GB recomendado
Disco: 2GB espacio libre
Navegador: Chrome/Firefox/Edge (últimas versiones)
```

**Producción (Servidor):**
```
Sistema Operativo: Linux (Ubuntu 20.04+, CentOS 7+)
RAM: 2GB mínimo, 4GB recomendado
Disco: 10GB espacio libre
CPU: 1 core mínimo, 2 cores recomendado
Ancho de banda: 100 Mbps
```

---

### 🔌 Conexión a Base de Datos

El sistema utiliza una **arquitectura de conexión centralizada** con el patrón Singleton.

#### Archivos de Configuración

##### 1. `api/db_config.php` - Credenciales (ÚNICO ARCHIVO)
```php
<?php
/**
 * ARCHIVO ÚNICO DE CONFIGURACIÓN DE BASE DE DATOS
 * Modificar solo este archivo para cambiar credenciales
 */

// 🏠 CONFIGURACIÓN LOCAL (LARAGON)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ⚙️ OPCIONES DE CONEXIÓN PDO
$GLOBALS['DB_OPTIONS'] = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 30
];

// 🌍 CONFIGURACIÓN ADICIONAL
define('DB_TIMEZONE', 'America/Argentina/Buenos_Aires');
define('DB_PERSISTENT', false);
?>
```

##### 2. `api/bd_conexion.php` - Clase de Conexión
```php
<?php
/**
 * Conexión única a base de datos MySQL
 * Patrón Singleton para reutilizar conexión
 */

require_once __DIR__ . '/db_config.php';

class Conexion {
    private static $conexion = null;
    
    /**
     * Obtiene conexión PDO a la base de datos
     * Singleton: reutiliza la misma conexión
     */
    public static function obtenerConexion() {
        try {
            // Si ya existe conexión, reutilizarla
            if (self::$conexion !== null) {
                return self::$conexion;
            }
            
            // Crear DSN (Data Source Name)
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            // Crear conexión PDO
            self::$conexion = new PDO(
                $dsn, 
                DB_USER, 
                DB_PASS, 
                $GLOBALS['DB_OPTIONS']
            );
            
            // Configurar zona horaria Argentina (UTC-3)
            self::$conexion->exec("SET time_zone = '-03:00'");
            
            return self::$conexion;
            
        } catch (PDOException $e) {
            error_log("[BD_CONEXION] ❌ Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Cierra la conexión
     */
    public static function cerrarConexion() {
        self::$conexion = null;
    }
}
?>
```

#### Flujo de Conexión

```
┌─────────────────────────────────────────────────┐
│  Endpoint API (ej: productos.php)              │
└─────────────────┬───────────────────────────────┘
                  │ require_once 'bd_conexion.php'
                  ▼
┌─────────────────────────────────────────────────┐
│  bd_conexion.php                                │
│  - Carga db_config.php                          │
│  - Clase Conexion (Singleton)                   │
└─────────────────┬───────────────────────────────┘
                  │ require_once 'db_config.php'
                  ▼
┌─────────────────────────────────────────────────┐
│  db_config.php                                  │
│  - DB_HOST, DB_NAME, DB_USER, DB_PASS          │
│  - DB_OPTIONS (PDO configuration)               │
└─────────────────┬───────────────────────────────┘
                  │ Constantes definidas
                  ▼
┌─────────────────────────────────────────────────┐
│  Conexion::obtenerConexion()                    │
│  1. Verifica si existe conexión (Singleton)     │
│  2. Si no existe, crea nueva con PDO            │
│  3. Configura timezone Argentina                │
│  4. Retorna objeto PDO                          │
└─────────────────┬───────────────────────────────┘
                  │ Objeto PDO listo
                  ▼
┌─────────────────────────────────────────────────┐
│  Endpoint ejecuta queries con PDO               │
│  $pdo = Conexion::obtenerConexion();           │
│  $stmt = $pdo->prepare("SELECT * FROM ...");   │
│  $stmt->execute();                              │
└─────────────────────────────────────────────────┘
```

#### Uso en Endpoints API

Todos los ~110 endpoints PHP usan el mismo patrón:

```php
<?php
// Ejemplo: api/productos.php

// Incluir conexión
require_once 'bd_conexion.php';

// Obtener conexión PDO (Singleton)
$pdo = Conexion::obtenerConexion();

// Ejecutar query con prepared statement
$stmt = $pdo->prepare("
    SELECT * FROM productos 
    WHERE activo = 1 
    ORDER BY nombre ASC
");
$stmt->execute();

// Obtener resultados
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retornar JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $productos
]);
?>
```

#### Ventajas de esta Arquitectura

✅ **Un solo archivo de credenciales** - Fácil de cambiar en producción  
✅ **Patrón Singleton** - Reutiliza la misma conexión PDO  
✅ **Prepared Statements** - Protección contra SQL Injection  
✅ **Manejo de excepciones** - Errores bien manejados  
✅ **Centralizado** - Todos los endpoints usan la misma clase  
✅ **Configuración por ambiente** - Local, Hostinger, Railway, etc.  

#### Configuración para Producción

Para cambiar de ambiente **local → producción**, solo editar `api/db_config.php`:

```php
// ========================================================================
// 📌 PARA HOSTINGER
// ========================================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_kiosco');     // ⚠️ CAMBIAR
define('DB_USER', 'u123456789_admin');      // ⚠️ CAMBIAR
define('DB_PASS', 'TU_PASSWORD_SEGURO');    // ⚠️ CAMBIAR
define('DB_CHARSET', 'utf8mb4');
```

**Un solo cambio, todo el sistema se actualiza automáticamente.**

---

### 📊 Comunicación Frontend ↔ Backend

```
┌──────────────────────────────────────────────────────────────┐
│                    FRONTEND (React)                          │
│  http://localhost:3000                                       │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  src/config/config.js                                        │
│  ├─ API_URL: 'http://localhost/kiosco'                      │
│  └─ API_ENDPOINTS: { ... }                                   │
│                                                               │
│  src/services/api.js                                         │
│  └─ Axios HTTP Client                                        │
│                                                               │
└─────────────────┬────────────────────────────────────────────┘
                  │
                  │ HTTP Request (GET/POST/PUT/DELETE)
                  │ JSON Payload
                  │
                  ▼
┌──────────────────────────────────────────────────────────────┐
│                    BACKEND (PHP API)                         │
│  http://localhost/kiosco/api/                                │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  index.php (Entry Point)                                     │
│  └─ Enruta /api/* a archivos PHP correspondientes            │
│                                                               │
│  api/productos.php, api/ventas_reales.php, etc.             │
│  ├─ require_once 'bd_conexion.php'                          │
│  ├─ $pdo = Conexion::obtenerConexion()                      │
│  ├─ Ejecuta queries SQL                                      │
│  └─ Retorna JSON                                             │
│                                                               │
└─────────────────┬────────────────────────────────────────────┘
                  │
                  │ PDO Connection
                  │ Prepared Statements
                  │
                  ▼
┌──────────────────────────────────────────────────────────────┐
│                  BASE DE DATOS (MySQL)                        │
│  localhost:3306                                              │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  kiosco_db                                                   │
│  ├─ productos                                                │
│  ├─ ventas                                                   │
│  ├─ turnos_caja                                              │
│  ├─ usuarios                                                 │
│  └─ ... (~15 tablas)                                         │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

#### Ejemplo de Flujo Completo

1. **Frontend** hace petición:
```javascript
// src/services/api.js
const response = await axios.get(
    'http://localhost/kiosco/api/productos.php'
);
```

2. **Backend** procesa:
```php
// api/productos.php
$pdo = Conexion::obtenerConexion();
$stmt = $pdo->prepare("SELECT * FROM productos");
$stmt->execute();
$productos = $stmt->fetchAll();
echo json_encode(['success' => true, 'data' => $productos]);
```

3. **Frontend** recibe:
```javascript
console.log(response.data.productos);
// Array de productos
```

---

### 🚀 Versiones del Proyecto

| Componente | Versión Actual |
|------------|----------------|
| **Proyecto** | v1.0.1 |
| **Frontend** | React 18.2.0 |
| **Backend** | PHP 8.0+ |
| **Base de Datos** | MySQL 8.0+ |

---

## 🏗 Arquitectura del Sistema

### Arquitectura Three-Tier

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                      │
│                        (Frontend)                            │
├─────────────────────────────────────────────────────────────┤
│  React SPA + PWA                                            │
│  - 48 Componentes React                                     │
│  - 13 Hooks personalizados                                  │
│  - 2 Contextos globales (Auth, Caja)                        │
│  - 17 Servicios                                             │
│  - Responsive Design (Mobile-first)                         │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTP/REST API
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE APLICACIÓN                        │
│                        (Backend)                             │
├─────────────────────────────────────────────────────────────┤
│  PHP 8.0+ Backend API                                       │
│  - ~110 Endpoints RESTful                                   │
│  - Autenticación y autorización                             │
│  - Lógica de negocio                                        │
│  - Integración AFIP                                         │
│  - Sistema de caché                                         │
│  - Validaciones y seguridad                                 │
└─────────────────────────────────────────────────────────────┘
                            ↕ PDO/MySQL
┌─────────────────────────────────────────────────────────────┐
│                      CAPA DE DATOS                           │
│                    (Base de Datos)                           │
├─────────────────────────────────────────────────────────────┤
│  MySQL 8.0 Database                                         │
│  - ~15 Tablas principales                                   │
│  - Índices optimizados                                      │
│  - Transacciones ACID                                       │
│  - Auditoría completa                                       │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos

```
Usuario → Frontend (React) → API REST (PHP) → Base de Datos (MySQL)
                                   ↓
                            AFIP Web Services
                                   ↓
                          Factura Electrónica
```

---

## 📱 Módulos del Sistema

El sistema está organizado en **9 módulos principales**:

### 1️⃣ Dashboard
- **Ruta:** `/` (Inicio)
- **Componente:** `DashboardVentasCompleto.jsx`
- **Descripción:** Panel principal con métricas en tiempo real
- **Características:**
  - Resumen de ventas del día
  - Estado de caja actual
  - Productos con bajo stock
  - Gráficos de ventas
  - Métricas financieras

### 2️⃣ Control de Caja
- **Rutas:** `/control-caja`, `/historial-turnos`
- **Componentes:** `GestionCajaMejorada.jsx`, `HistorialTurnosPage.jsx`
- **Descripción:** Gestión completa del flujo de efectivo
- **Características:**
  - Apertura/cierre de caja
  - Registro de movimientos
  - Validación de efectivo
  - Historial de turnos
  - Reportes de diferencias

### 3️⃣ Punto de Venta (POS)
- **Ruta:** `/punto-de-venta`
- **Componente:** `PuntoDeVentaStockOptimizado.jsx`
- **Descripción:** Interfaz principal de ventas
- **Características:**
  - Búsqueda rápida de productos
  - Carrito de compras
  - Múltiples métodos de pago
  - Facturación AFIP
  - Impresión de tickets

### 4️⃣ Productos e Inventario
- **Rutas:** `/productos`, `/inventario`
- **Componentes:** `ProductosPage.jsx`, `InventarioInteligente.jsx`
- **Descripción:** Gestión completa de productos
- **Características:**
  - CRUD de productos
  - Control de stock
  - Categorías y proveedores
  - Importación masiva
  - Auditoría de inventario

### 5️⃣ Reportes de Ventas
- **Ruta:** `/ventas`
- **Componente:** `ReporteVentasModerno.jsx`
- **Descripción:** Análisis detallado de ventas
- **Características:**
  - Filtros por fecha
  - Detalle de cada venta
  - Anulación de ventas
  - Exportación a Excel
  - Análisis con IA

### 6️⃣ Análisis Financiero
- **Ruta:** `/finanzas`
- **Componente:** `ModuloFinancieroCompleto.jsx`
- **Descripción:** Reportes financieros completos
- **Características:**
  - P&L (Profit & Loss)
  - Flujo de caja
  - Rentabilidad por producto
  - Gastos vs Ingresos
  - Gráficos financieros

### 7️⃣ Usuarios
- **Ruta:** `/usuarios`
- **Componente:** `UsuariosPage.jsx`
- **Descripción:** Gestión de usuarios y permisos
- **Características:**
  - CRUD de usuarios
  - Asignación de roles
  - Gestión de permisos
  - Auditoría de acceso

### 8️⃣ Configuración
- **Ruta:** `/configuracion`
- **Componente:** `ConfiguracionPage.jsx`
- **Descripción:** Configuración general del sistema
- **Características:**
  - Datos empresariales
  - Configuración AFIP
  - Configuración de IA (OpenAI)
  - Parámetros del sistema

### 9️⃣ Inteligencia Artificial
- **Módulo transversal**
- **Servicios:** `openaiService.js`, `aiAnalytics.js`, `inventarioIAService.js`
- **Descripción:** Capacidades de IA distribuidas en todo el sistema
- **Características:**
  - Análisis inteligente de ventas
  - Diagnóstico financiero
  - Predicciones de inventario
  - Detección de fraude
  - Recomendaciones automáticas

---

## 📋 Requisitos Previos

### Software Necesario

```bash
# Node.js y NPM
Node.js >= 18.0.0
NPM >= 8.0.0

# PHP
PHP >= 8.0
  - Extensiones: pdo_mysql, mysqli, json, mbstring, curl

# Base de Datos
MySQL >= 5.7 o MySQL 8.0+
MariaDB >= 10.6

# Servidor Web
Apache >= 2.4 con mod_rewrite
o NGINX >= 1.18

# Composer
Composer >= 2.0
```

### Recomendado para Windows

- **Laragon** (incluye Apache, PHP, MySQL, Node.js)
- **Git** para control de versiones

---

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/compucol89/tommyposV1.0.git kiosco
cd kiosco
```

### 2. Instalar Dependencias Frontend

```bash
npm install
```

### 3. Instalar Dependencias Backend

```bash
composer install
```

### 4. Configurar Base de Datos

Crear base de datos en MySQL:

```sql
CREATE DATABASE kiosco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Importar Estructura de Base de Datos

```bash
# Importar el archivo SQL de estructura (ubicar tu archivo .sql)
mysql -u root -p kiosco_db < database/schema.sql
```

### 6. Configurar Archivo de Conexión

Editar `api/db_config.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### 7. Compilar Frontend

```bash
# Desarrollo
npm start

# Producción
npm run build
```

### 8. Iniciar el Sistema

#### Desarrollo (con Laragon):
1. Iniciar Laragon
2. El proyecto estará disponible en: `http://localhost/kiosco`

#### Desarrollo (servidor PHP integrado):
```bash
# Terminal 1: Frontend
npm start

# Terminal 2: Backend
php -S localhost:8000
```

---

## ⚙️ Configuración

### Configuración de API (Frontend)

Archivo: `src/config/config.js`

```javascript
const CONFIG = {
  API_URL: process.env.NODE_ENV === 'production' 
    ? window.location.origin
    : 'http://localhost/kiosco',
  
  API_ENDPOINTS: {
    PRODUCTOS: '/api/productos.php',
    VENTAS: '/api/ventas_reales.php',
    USUARIOS: '/api/usuarios.php',
    // ... más endpoints
  },
  
  VERSION: '1.0.1',
  APP_NAME: 'Tayrona Almacén',
  CURRENCY: 'ARS',
  CURRENCY_SYMBOL: '$'
};
```

### Configuración AFIP

Archivo: `api/config_afip.php`

```php
<?php
$CONFIGURACION_AFIP = [
    'ambiente' => 'PRODUCCION', // PRODUCCION o TESTING
    // ... configuración AFIP
];

$DATOS_FISCALES = [
    'cuit_empresa' => '20123456789',
    'razon_social' => 'Tu Razón Social',
    'domicilio_comercial' => 'Tu Dirección',
    // ... más datos fiscales
];
```

### Configuración de IA (OpenAI)

Archivo: `src/config/aiConfig.js`

```javascript
const AI_CONFIG = {
  OPENAI_API_KEY: 'tu-api-key-aqui',
  model: 'gpt-4',
  enabled: true
};
```

---

## 📁 Estructura del Proyecto

```
kiosco/
├── 📱 src/                           # Frontend React
│   ├── components/                   # Componentes React (48 archivos)
│   │   ├── DashboardVentasCompleto.jsx
│   │   ├── PuntoDeVentaStockOptimizado.jsx
│   │   ├── GestionCajaMejorada.jsx
│   │   ├── ProductosPage.jsx
│   │   ├── productos/                # Módulo de productos
│   │   │   ├── components/           # 11 componentes
│   │   │   └── hooks/                # 5 hooks específicos
│   │   └── ... (más componentes)
│   │
│   ├── hooks/                        # Hooks personalizados (13)
│   │   ├── useCajaApi.js
│   │   ├── useCajaStatus.js
│   │   ├── useStockManager.js
│   │   ├── useProductos.js
│   │   └── ... (más hooks)
│   │
│   ├── services/                     # Servicios (17)
│   │   ├── api.js
│   │   ├── cajaService.js
│   │   ├── openaiService.js
│   │   ├── aiAnalytics.js
│   │   ├── inventarioIAService.js
│   │   └── ... (más servicios)
│   │
│   ├── contexts/                     # Contextos React (2)
│   │   ├── AuthContext.jsx           # Autenticación global
│   │   └── CajaContext.jsx           # Estado de caja global
│   │
│   ├── config/                       # Configuración
│   │   ├── config.js                 # Config principal
│   │   └── aiConfig.js               # Config de IA
│   │
│   ├── utils/                        # Utilidades (4)
│   │   ├── cashValidation.js
│   │   ├── imageCache.js
│   │   ├── performance.js
│   │   └── toastNotifications.js
│   │
│   ├── App.jsx                       # Componente raíz
│   ├── index.js                      # Entry point
│   └── index.css                     # Estilos globales
│
├── 🔧 api/                           # Backend PHP
│   ├── bd_conexion.php               # Conexión a BD
│   ├── db_config.php                 # Credenciales BD
│   ├── config.php                    # Config general
│   │
│   ├── 📦 Productos
│   │   ├── productos.php
│   │   ├── productos_pos_optimizado.php
│   │   └── subir_imagen_producto.php
│   │
│   ├── 💰 Ventas
│   │   ├── ventas_reales.php
│   │   ├── procesar_venta_ultra_rapida.php
│   │   └── anular_venta.php
│   │
│   ├── 🏦 Caja
│   │   ├── gestion_caja_completa.php
│   │   └── pos_status.php
│   │
│   ├── 📊 Reportes
│   │   ├── reportes_financieros_precisos.php
│   │   ├── dashboard_stats.php
│   │   └── finanzas_completo.php
│   │
│   ├── 👥 Usuarios
│   │   ├── usuarios.php
│   │   └── permisos_usuario.php
│   │
│   ├── 🧾 AFIP (15+ archivos)
│   │   ├── afip_hibrido_inteligente.php
│   │   ├── afip_sdk_real.php
│   │   ├── afip_directo.php
│   │   └── config_afip.php
│   │
│   ├── ⚙️ Configuración
│   │   ├── configuracion_empresarial.php
│   │   └── reset_sistema_empresarial.php
│   │
│   └── ... (~110 archivos PHP total)
│
├── 🗄️ database/                     # Base de datos
│   ├── migrations/                   # Migraciones
│   └── schema.sql                    # Estructura de BD
│
├── 📚 docs/                          # Documentación
│   ├── MAPA_MAESTRO_SISTEMA_COMPLETO.md
│   ├── ANALISIS_CONFIGURACION_BD.md
│   └── ... (más documentación)
│
├── 🖼️ img/                          # Imágenes
│   ├── productos/                    # Imágenes de productos
│   └── no-image.svg
│
├── 📦 public/                        # Assets públicos
│   ├── index.html
│   ├── favicon.ico
│   └── img/
│
├── 🏗️ build/                         # Build de producción
│   └── ... (generado con npm run build)
│
├── 📝 Archivos de Configuración
│   ├── package.json                  # Dependencias NPM
│   ├── composer.json                 # Dependencias PHP
│   ├── tailwind.config.js            # Config Tailwind
│   ├── postcss.config.js             # Config PostCSS
│   └── index.php                     # Entry point PHP
│
└── 📖 README.md                      # Este archivo
```

---

## 🗄️ Base de Datos

### Tablas Principales

#### Productos
```sql
productos
├── id (PK)
├── codigo_barra
├── nombre
├── precio_venta
├── precio_costo
├── stock_actual
├── stock_minimo
├── categoria_id (FK)
├── proveedor_id (FK)
└── imagen_url
```

#### Ventas
```sql
ventas
├── id (PK)
├── fecha_hora
├── total
├── metodo_pago
├── descuento
├── usuario_id (FK)
├── turno_caja_id (FK)
├── factura_afip_numero
└── factura_afip_cae
```

#### Control de Caja
```sql
turnos_caja
├── id (PK)
├── usuario_id (FK)
├── fecha_apertura
├── fecha_cierre
├── monto_inicial
├── monto_final
├── diferencia
└── estado (abierto/cerrado)

movimientos_caja_detallados
├── id (PK)
├── turno_caja_id (FK)
├── tipo (ingreso/egreso)
├── monto
├── concepto
└── fecha_hora
```

#### Usuarios
```sql
usuarios
├── id (PK)
├── nombre
├── usuario (username)
├── password (hash)
├── role (admin/vendedor/cajero)
└── activo
```

### Relaciones

```
usuarios (1) ──── (N) ventas
usuarios (1) ──── (N) turnos_caja
turnos_caja (1) ──── (N) ventas
turnos_caja (1) ──── (N) movimientos_caja_detallados
productos (1) ──── (N) detalle_ventas
categorias (1) ──── (N) productos
proveedores (1) ──── (N) productos
```

---

## 🌐 API Endpoints

### Dashboard
```
GET  /api/dashboard_stats.php              # Estadísticas generales
GET  /api/v2/dashboard_fintech.php         # Dashboard financiero
GET  /api/finanzas_completo.php            # Finanzas completas
```

### Productos
```
GET    /api/productos.php                  # Listar productos
POST   /api/productos.php                  # Crear producto
PUT    /api/productos.php?id={id}          # Actualizar producto
DELETE /api/productos.php?id={id}          # Eliminar producto
GET    /api/productos_pos_optimizado.php   # Productos para POS
POST   /api/subir_imagen_producto.php      # Subir imagen
```

### Ventas
```
GET   /api/ventas_reales.php               # Listar ventas
POST  /api/procesar_venta_ultra_rapida.php # Procesar venta
POST  /api/anular_venta.php                # Anular venta
```

### Control de Caja
```
GET   /api/gestion_caja_completa.php?action=estado_caja
POST  /api/gestion_caja_completa.php?action=abrir_caja
POST  /api/gestion_caja_completa.php?action=cerrar_caja
POST  /api/gestion_caja_completa.php?action=registrar_movimiento
GET   /api/gestion_caja_completa.php?action=historial_movimientos
GET   /api/gestion_caja_completa.php?action=historial_completo
GET   /api/pos_status.php                  # Estado de caja
```

### Usuarios
```
GET   /api/usuarios.php                    # Listar usuarios
POST  /api/usuarios.php                    # Crear usuario
PUT   /api/usuarios.php                    # Actualizar usuario
DELETE /api/usuarios.php                   # Eliminar usuario
POST  /api/validar_usuario.php             # Login
GET   /api/permisos_usuario.php            # Permisos
```

### Reportes
```
GET   /api/reportes_financieros_precisos.php  # Reportes financieros
GET   /api/finanzas_completo.php              # Análisis financiero
```

### Configuración
```
GET   /api/configuracion_empresarial.php      # Config empresa
POST  /api/configuracion_empresarial.php      # Actualizar config
POST  /api/reset_sistema_empresarial.php      # Reset sistema
```

### AFIP
```
POST  /api/afip_hibrido_inteligente.php       # Generar comprobante
GET   /api/afip_directo.php                   # AFIP directo
POST  /api/afip_sdk_real.php                  # AFIP real
```

---

## 🧾 Integración AFIP

### Métodos de Integración

El sistema cuenta con **3 métodos de integración AFIP**:

#### 1. AFIP Híbrido Inteligente (Recomendado)
- **Archivo:** `api/afip_hibrido_inteligente.php`
- **Descripción:** Intenta usar AFIP real, si falla usa simulador válido
- **Ventajas:** Máxima confiabilidad, sin interrupciones
- **Uso:** Producción

#### 2. AFIP Directo
- **Archivo:** `api/afip_directo.php`
- **Descripción:** Integración directa con Web Services AFIP
- **Ventajas:** Rápido, sin dependencias externas
- **Uso:** Producción cuando hay certificados válidos

#### 3. AFIP SDK Real
- **Archivo:** `api/afip_sdk_real.php`
- **Descripción:** Usa la librería oficial de AFIP SDK
- **Ventajas:** Oficial, bien mantenido
- **Uso:** Producción con certificados

### Configuración AFIP

1. **Obtener certificados AFIP** (.crt y .key)
2. Colocarlos en: `api/certificados/`
3. Configurar `api/config_afip.php`:

```php
$CONFIGURACION_AFIP = [
    'ambiente' => 'PRODUCCION', // PRODUCCION o TESTING
    'urls_produccion' => [
        'wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms',
        'wsfe' => 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'
    ]
];

$DATOS_FISCALES = [
    'cuit_empresa' => '20123456789',
    'razon_social' => 'Tu Razón Social',
    'punto_venta' => 1,
    'condicion_iva' => 'MONOTRIBUTO'
];
```

### Tipos de Comprobantes

- **Factura A:** Para responsables inscriptos
- **Factura B:** Para consumidores finales
- **Factura C:** Para monotributistas (sin IVA discriminado)

### Proceso de Facturación

```
1. Venta realizada → PuntoDeVentaStockOptimizado
2. Si factura AFIP activada → procesar_venta_ultra_rapida.php
3. Llamar servicio AFIP → afip_hibrido_inteligente.php
4. Generar comprobante → CAE + Número
5. Guardar en BD → ventas.factura_afip_numero, ventas.factura_afip_cae
6. Imprimir ticket → TicketProfesional.jsx (incluye datos fiscales)
```

---

## 🔒 Seguridad

### Medidas Implementadas

#### Autenticación
- Hashing de contraseñas con `password_hash()` (bcrypt)
- Validación de credenciales
- Sistema de sesiones PHP
- Contexto de autenticación React (AuthContext)

#### Autorización
- Sistema de roles (admin, vendedor, cajero)
- Permisos granulares por módulo
- Validación en frontend y backend
- Guards de rutas

#### Protección de Datos
- Preparación de queries con PDO (previene SQL injection)
- Validación de inputs en frontend y backend
- Sanitización de datos
- Headers de seguridad HTTP

#### CORS
- Configuración CORS para APIs
- Whitelisting de dominios permitidos

#### Auditoría
- Logs de operaciones AFIP
- Auditoría de inventario
- Registro de movimientos de caja
- Historial de acciones de usuarios

---

## 👥 Sistema de Permisos

### Roles

#### 🔴 Admin (Administrador)
- **Acceso:** Todos los módulos
- **Permisos:**
  - ✅ Dashboard
  - ✅ Control de Caja
  - ✅ Historial de Turnos
  - ✅ Punto de Venta
  - ✅ Reporte de Ventas
  - ✅ Inventario
  - ✅ Productos
  - ✅ Análisis Financiero
  - ✅ Usuarios (CRUD)
  - ✅ Configuración

#### 🔵 Vendedor
- **Acceso:** Módulos operativos
- **Permisos:**
  - ✅ Dashboard
  - ✅ Punto de Venta
  - ✅ Productos (solo lectura)
  - ❌ Control de Caja
  - ❌ Reportes Financieros
  - ❌ Usuarios
  - ❌ Configuración

#### 🟢 Cajero
- **Acceso:** Módulos de caja y venta
- **Permisos:**
  - ✅ Dashboard
  - ✅ Control de Caja
  - ✅ Historial de Turnos
  - ✅ Punto de Venta
  - ❌ Productos (CRUD)
  - ❌ Reportes Financieros
  - ❌ Usuarios
  - ❌ Configuración

### Implementación

Frontend: `src/hooks/usePermisos.js`
```javascript
const { hasAccess, getFilteredMenuItems } = usePermisos(currentUser);
```

Backend: `api/permisos_usuario.php`
```php
// Validación de permisos en cada endpoint
```

---

## 🔄 Flujo de Trabajo

### Flujo Operativo Diario

```
1. LOGIN
   Usuario ingresa credenciales → LoginPage.jsx
   ↓
   Validación → api/validar_usuario.php
   ↓
   Sesión iniciada → AuthContext

2. APERTURA DE CAJA
   Cajero/Admin → GestionCajaMejorada.jsx
   ↓
   Registrar monto inicial → api/gestion_caja_completa.php
   ↓
   Turno de caja ABIERTO → Estado global (CajaContext)

3. VENTAS
   Vendedor → PuntoDeVentaStockOptimizado.jsx
   ↓
   Validación: Caja ABIERTA → useCajaStatus.js
   ↓
   Agregar productos → Búsqueda optimizada
   ↓
   Seleccionar método de pago → Descuentos automáticos
   ↓
   Procesar venta → api/procesar_venta_ultra_rapida.php
   ↓
   (Opcional) Facturar AFIP → api/afip_hibrido_inteligente.php
   ↓
   Imprimir ticket → TicketProfesional.jsx
   ↓
   Sincronizar caja → cashSyncService.js

4. CIERRE DE CAJA
   Cajero/Admin → GestionCajaMejorada.jsx
   ↓
   Contar efectivo → Validación contra ventas
   ↓
   Cerrar turno → api/gestion_caja_completa.php
   ↓
   Generar reporte de cierre → PDF

5. REPORTES
   Admin → Módulos de reportes
   ↓
   Análisis de ventas, finanzas, inventario
   ↓
   Exportar a Excel → XLSX
```

---

## 🧩 Componentes Principales

### Frontend (48 componentes activos)

#### Páginas Principales (13)
1. `DashboardVentasCompleto.jsx` - Dashboard principal
2. `PuntoDeVentaStockOptimizado.jsx` - POS
3. `GestionCajaMejorada.jsx` - Control de caja
4. `HistorialTurnosPage.jsx` - Historial de turnos
5. `ProductosPage.jsx` - Gestión de productos
6. `InventarioInteligente.jsx` - Inventario con IA
7. `ReporteVentasModerno.jsx` - Reportes de ventas
8. `ModuloFinancieroCompleto.jsx` - Análisis financiero
9. `UsuariosPage.jsx` - Gestión de usuarios
10. `ConfiguracionPage.jsx` - Configuración
11. `LoginPage.jsx` - Login
12. `IndicadorEstadoCaja.jsx` - Indicador de caja (topbar)
13. `NotificacionesMovimientos.jsx` - Notificaciones

#### Sub-componentes (35)
- **Dashboard:** `DashboardResumenCaja.jsx`
- **Caja:** `ModalAperturaCaja.jsx`, `MetricasCaja.jsx`, `CajaStatusIndicator.jsx`, `ReportesEfectivoPeriodo.jsx`, `ReportesDiferenciasCajero.jsx`
- **POS:** `PaymentModalSleepyCashierProof.jsx`, `TicketProfesional.jsx`, `StockAlerts.jsx`
- **Ventas:** `VentaDetalleCompleto.jsx`, `AnalisisInteligente.jsx`, `DiagnosticoFinanciero.jsx`
- **Config:** `ConfiguracionIA.jsx`, `ConfiguracionFacturacion.jsx`, `AFIPStatusIndicator.jsx`, `PermissionGuard.jsx`
- **Productos:** 18 archivos en carpeta `productos/` (11 componentes + 5 hooks + helpers)

---

## 🎣 Hooks Personalizados

### Control de Caja (3)
- `useCajaApi.js` - API calls de caja
- `useCajaLogic.js` - Lógica de negocio de caja
- `useCajaStatus.js` - Estado y validación de caja

### Productos (2)
- `useStockManager.js` - Gestión inteligente de stock
- `useProductos.js` - CRUD de productos

### Dashboard (2)
- `useDashboardFintech.js` - Métricas financieras
- `useExportManager.js` - Exportación de datos

### Búsqueda (3)
- `useEnterpriseSearch.js` - Búsqueda enterprise-grade
- `useHybridPOSSearch.js` - Búsqueda híbrida POS
- `usePOSProducts.js` - Productos para POS

### Sistema (3)
- `usePermisos.js` - Sistema de permisos
- `useDebounce.js` - Debouncing de inputs
- `ToastContext.jsx` - Notificaciones toast

---

## 🔧 Servicios

### APIs (2)
- `api.js` - Cliente HTTP base
- `cajaService.js` - Servicio de caja

### Reportes (1)
- `reportesService.js` - Generación de reportes

### Configuración (3)
- `configEmpresarialService.js` - Config empresarial
- `configService.js` - Config general
- `permisosService.js` - Gestión de permisos

### Ventas (1)
- `descuentosService.js` - Descuentos automáticos

### Inteligencia Artificial (5)
- `openaiService.js` - Integración OpenAI
- `aiAnalytics.js` - Analytics con IA
- `inventarioIAService.js` - IA para inventario
- `pedidosIAService.js` - IA para pedidos
- `antiFraudEngine.js` - Detección de fraude

### Seguridad (3)
- `seguridadInventarioService.js` - Seguridad de inventario
- `validationSuite.js` - Suite de validaciones
- `auditLogger.js` - Logger de auditoría

### Sistema (2)
- `sistemaService.js` - Utilidades del sistema
- `cashSyncService.js` - Sincronización de caja

---

## 📖 Uso del Sistema

### 1. Primer Inicio

1. **Crear usuario administrador** (directamente en BD o con script)
2. **Ingresar al sistema** con credenciales de admin
3. **Configurar datos empresariales** en Configuración
4. **Configurar AFIP** (si se requiere facturación electrónica)
5. **Crear categorías** de productos
6. **Crear usuarios** adicionales (vendedores, cajeros)

### 2. Gestión Diaria

#### Apertura de Turno
1. Ir a **Control de Caja**
2. Hacer clic en **Abrir Caja**
3. Ingresar monto inicial de efectivo
4. Confirmar apertura

#### Realizar Ventas
1. Ir a **Punto de Venta**
2. Buscar productos (por nombre o código de barras)
3. Agregar al carrito
4. Seleccionar método de pago
5. (Opcional) Activar factura AFIP
6. Confirmar venta
7. Imprimir ticket

#### Cierre de Turno
1. Ir a **Control de Caja**
2. Hacer clic en **Cerrar Caja**
3. Contar efectivo real
4. Ingresar monto contado
5. Sistema calcula diferencia
6. Confirmar cierre
7. Descargar reporte de cierre

### 3. Gestión de Productos

#### Agregar Producto
1. Ir a **Productos**
2. Hacer clic en **Nuevo Producto**
3. Completar datos:
   - Código de barras
   - Nombre
   - Precio de venta
   - Precio de costo
   - Stock inicial
   - Categoría
   - Proveedor
4. (Opcional) Subir imagen
5. Guardar

#### Actualizar Stock
1. Ir a **Inventario**
2. Buscar producto
3. Ajustar stock
4. Registrar motivo
5. Guardar

### 4. Reportes

#### Reporte de Ventas
1. Ir a **Reporte de Ventas**
2. Seleccionar rango de fechas
3. (Opcional) Filtrar por método de pago
4. Ver detalle de ventas
5. (Opcional) Exportar a Excel

#### Análisis Financiero
1. Ir a **Análisis**
2. Seleccionar período
3. Ver P&L, flujo de caja, rentabilidad
4. (Opcional) Usar análisis con IA

---

## 🛠️ Mantenimiento

### Backup de Base de Datos

```bash
# Exportar base de datos
mysqldump -u root -p kiosco_db > backup_kiosco_$(date +%Y%m%d).sql

# Restaurar base de datos
mysql -u root -p kiosco_db < backup_kiosco_YYYYMMDD.sql
```

### Limpieza de Logs

Los logs se guardan en `api/logs/`:
- `afip_errors.log` - Errores de AFIP
- `afip_operations.log` - Operaciones AFIP
- `audit_backup_*.log` - Auditoría (se rotan diariamente)

**Recomendación:** Limpiar logs antiguos cada mes.

### Actualización del Sistema

```bash
# 1. Backup de BD
mysqldump -u root -p kiosco_db > backup.sql

# 2. Pull de últimos cambios
git pull origin main

# 3. Actualizar dependencias
npm install
composer update

# 4. Rebuild frontend
npm run build

# 5. Verificar funcionamiento
```

### Optimización de Base de Datos

```sql
-- Optimizar tablas
OPTIMIZE TABLE productos, ventas, turnos_caja;

-- Analizar tablas
ANALYZE TABLE productos, ventas, turnos_caja;

-- Verificar integridad
CHECK TABLE productos, ventas, turnos_caja;
```

---

## 🐛 Troubleshooting

### Problemas Comunes

#### Error de Conexión a BD
```
Error: Could not connect to MySQL
```
**Solución:**
1. Verificar que MySQL esté corriendo
2. Revisar credenciales en `api/db_config.php`
3. Verificar que la BD existe

#### Frontend no carga
```
Página en blanco
```
**Solución:**
1. Verificar que se compiló el build: `npm run build`
2. Verificar que Apache/NGINX está corriendo
3. Revisar consola del navegador (F12)

#### Caja no se puede abrir
```
No se puede abrir caja
```
**Solución:**
1. Verificar que no haya un turno abierto
2. Revisar permisos del usuario
3. Verificar conexión API

#### AFIP no funciona
```
Error al generar comprobante AFIP
```
**Solución:**
1. Verificar certificados en `api/certificados/`
2. Revisar configuración en `api/config_afip.php`
3. Verificar logs en `api/logs/afip_errors.log`
4. Intentar con sistema híbrido (fallback automático)

#### Stock negativo
```
Stock no puede ser negativo
```
**Solución:**
1. Ir a **Inventario**
2. Ajustar stock manualmente
3. Registrar el motivo del ajuste

### Logs del Sistema

#### Frontend (Navegador)
```javascript
// Abrir consola del navegador: F12
// Buscar errores en la pestaña Console
```

#### Backend (PHP)
```bash
# Laragon:
C:\laragon\bin\apache\apache-2.4.X\logs\error.log

# Linux:
/var/log/apache2/error.log
/var/log/nginx/error.log
```

#### AFIP
```bash
# Logs de AFIP
api/logs/afip_errors.log
api/logs/afip_operations.log
```

---

## 📄 Licencia

Este proyecto está bajo la licencia **MIT**.

```
MIT License

Copyright (c) 2025 Tayrona POS Team

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📞 Contacto y Soporte

### Equipo de Desarrollo

- **Email:** infocompucol@gmail.com
- **GitHub:** [compucol89/tommyposV1.0](https://github.com/compucol89/tommyposV1.0)

### Reporte de Bugs

Si encuentras algún bug, por favor:

1. Abre un Issue en GitHub
2. Describe el problema detalladamente
3. Incluye pasos para reproducirlo
4. Adjunta screenshots si es posible
5. Indica tu entorno (OS, versión PHP, MySQL, etc.)

### Contribuciones

¡Las contribuciones son bienvenidas!

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📊 Estadísticas del Proyecto

### Código

- **Componentes React:** 48 activos
- **Hooks personalizados:** 13 principales + 5 en productos = 18 total
- **Servicios:** 17
- **Contextos:** 2
- **Utilidades:** 4
- **Endpoints PHP:** ~110 funcionales
- **Total archivos frontend:** ~89 archivos .js/.jsx
- **Total archivos backend:** ~110 archivos .php

### Módulos

- **Módulos principales:** 9
- **Páginas:** 13
- **Sub-componentes:** 35
- **Dependencias NPM:** 28
- **Dependencias PHP:** 1 (AFIP SDK)

---

## 🎯 Roadmap

### Versión 1.1 (Próximamente)
- [ ] App móvil con React Native
- [ ] Multi-tienda (varias sucursales)
- [ ] Sincronización en la nube
- [ ] Reportes avanzados con BI

### Versión 1.2
- [ ] Integración con Mercado Pago
- [ ] Programa de fidelización de clientes
- [ ] Pedidos online
- [ ] Delivery integrado

### Versión 2.0
- [ ] Migración a TypeScript
- [ ] GraphQL API
- [ ] Microservicios
- [ ] PWA offline-first

---

## 🙏 Agradecimientos

Este sistema fue desarrollado con dedicación para ayudar a pequeños comercios en Argentina a digitalizar sus operaciones.

**Tecnologías utilizadas:**
- React.js
- PHP
- MySQL
- Tailwind CSS
- Chart.js
- AFIP SDK
- Y muchas más...

---

<div align="center">

**🏪 Tayrona Almacén - Sistema POS Kiosco v1.0.1**

Desarrollado con ❤️ para comercios argentinos

[🌟 Star en GitHub](https://github.com/compucol89/tommyposV1.0) • [📧 Contacto](mailto:infocompucol@gmail.com)

</div>
