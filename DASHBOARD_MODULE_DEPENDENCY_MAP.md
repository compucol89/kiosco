# 📊 MAPA COMPLETO DE DEPENDENCIAS - MÓDULO DASHBOARD

**Fecha de Análisis:** 8 de Octubre, 2025  
**Sistema:** Tayrona Almacén - Kiosco Management System  
**Propósito:** Mapeo exhaustivo de todos los archivos y recursos utilizados por el módulo Dashboard

---

## 🎯 RESUMEN EJECUTIVO

El módulo Dashboard está compuesto por **4 componentes principales**, utiliza **7 endpoints de API backend**, consume **3 servicios/hooks**, y tiene **2 archivos de configuración**.

**Total de archivos mapeados:** 20+ archivos  
**Tecnologías:** React, PHP, MySQL

---

## 📁 1. COMPONENTES FRONTEND (React/JSX)

### 1.1 Componentes Principales de Dashboard

| Archivo | Ubicación | Propósito | Dependencias Críticas |
|---------|-----------|-----------|----------------------|
| `DashboardResumenCaja.jsx` | `src/components/` | Dashboard principal con métricas de caja en tiempo real | `CONFIG`, `lucide-react`, API: `pos_status.php`, `gestion_caja_completa.php`, `ventas_reales.php` |
| `DashboardVentasCompleto.jsx` | `src/components/` | Dashboard de ventas con análisis completo y gráficos | `CONFIG`, `DashboardResumenCaja`, API: `finanzas_completo.php` |
| `SalesReportDashboard.jsx` | `src/components/` | Central de análisis de ventas con filtros y exportación | `AuthContext`, `reportesService`, `useExportManager`, `VentaDetalleCompleto` |
| `DashboardOptimizado.jsx` | `src/components/` | Vista optimizada del dashboard del día | `CONFIG`, Hook: `useDashboardStats`, API: `dashboard_stats.php` |

### 1.2 Componentes Auxiliares

| Archivo | Ubicación | Propósito | Usado Por |
|---------|-----------|-----------|-----------|
| `VentaDetalleCompleto.jsx` | `src/components/` | Modal con detalle completo de una venta individual | `SalesReportDashboard` |
| `TicketProfesional.jsx` | `src/components/` | Componente de ticket fiscal para impresión | `VentaDetalleCompleto` |

---

## 🔧 2. HOOKS PERSONALIZADOS

| Archivo | Ubicación | Propósito | Dependencias |
|---------|-----------|-----------|--------------|
| `useDashboardFintech.js` | `src/hooks/` | Hook Fintech-grade con validación financiera, WebSocket, circuit breaker | `CONFIG`, API: `dashboard_fintech.php` |
| `useExportManager.js` | `src/hooks/` | Gestión de exportaciones PDF/Excel con tracking de progreso | API: `export_report.php` |

---

## 🌐 3. SERVICIOS

| Archivo | Ubicación | Propósito | APIs Consumidas |
|---------|-----------|-----------|-----------------|
| `reportesService.js` | `src/services/` | Servicio para obtener datos contables, mapeo y validación financiera | `reportes_financieros_precisos.php`, `egresos.php` |

---

## ⚙️ 4. CONFIGURACIÓN

| Archivo | Ubicación | Propósito | Valores Críticos |
|---------|-----------|-----------|------------------|
| `config.js` | `src/config/` | Configuración global de la aplicación | `API_URL`, `API_ENDPOINTS.DASHBOARD_STATS`, formatters de moneda/fecha |

---

## 🔌 5. BACKEND API ENDPOINTS (PHP)

### 5.1 Endpoints Principales

| Archivo | Ubicación | Propósito | Usado Por | Tablas BD |
|---------|-----------|-----------|-----------|----------|
| `dashboard_stats.php` | `api/` | Estadísticas del día: ventas, métodos pago, productos top, stock bajo | `DashboardOptimizado`, hook `useDashboardStats` | `ventas`, `turnos_caja`, `caja_movimientos`, `productos` |
| `dashboard_fintech.php` | `api/v2/` | API Fintech-grade con SLA <100ms, validación financiera automática | Hook `useDashboardFintech` | `ventas`, `caja`, `caja_movimientos`, `productos` |
| `pos_status.php` | `api/` | Estado de caja para POS (abierta/cerrada, efectivo disponible) | `DashboardResumenCaja` | `turnos_caja` |
| `finanzas_completo.php` | `api/` | Finanzas completas con detalle de ventas por período | `DashboardVentasCompleto` | `ventas`, `productos` |
| `gestion_caja_completa.php` | `api/` | Gestión completa de caja: historial, estadísticas, estado | `DashboardResumenCaja` | `turnos_caja`, `caja_movimientos` |
| `ventas_reales.php` | `api/` | Listado de ventas reales con filtros (hoy, ayer, fecha) | `DashboardResumenCaja` | `ventas` |
| `productos_pos_optimizado.php` | `api/` | Productos disponibles con control de stock optimizado | `DashboardResumenCaja` | `productos` |

### 5.2 Endpoints de Soporte

| Archivo | Ubicación | Propósito | Usado Por |
|---------|-----------|-----------|-----------|
| `verificar_consistencia_dashboard.php` | `api/` | Verificación de consistencia de datos del dashboard | Sistema de validación |
| `ventas_optimizadas.php` | `api/` | Ventas con queries optimizadas | Fallback de otros dashboards |
| `simular_stock_bajo.php` | `api/` | Simulación de alertas de stock bajo | Testing |
| `listar_ventas.php` | `api/` | Listado de ventas (backup) | Fallback |
| `reportes_financieros_precisos.php` | `api/` | Reportes financieros con cálculos precisos | `reportesService` |
| `export_report.php` | `api/` | Exportación de reportes en PDF/Excel | `useExportManager` |
| `egresos.php` | `api/` | CRUD de egresos | `reportesService` |

---

## 🗄️ 6. ARCHIVOS DE CONEXIÓN Y CONFIGURACIÓN BD

| Archivo | Ubicación | Propósito | Usado Por |
|---------|-----------|-----------|-----------|
| `bd_conexion.php` | `api/` | Clase de conexión a base de datos con PDO | Todos los endpoints PHP |
| `config.php` | `api/` | Configuración de base de datos | Algunos endpoints legacy |

---

## 📦 7. DEPENDENCIAS DE TERCEROS

### 7.1 NPM Packages (Frontend)

| Package | Versión | Propósito | Componentes que lo usan |
|---------|---------|-----------|------------------------|
| `lucide-react` | Latest | Iconos modernos | Todos los componentes de Dashboard |
| `react` | 18.x | Framework principal | Todos los componentes |
| `react-dom` | 18.x | Renderizado React | Todos los componentes |

### 7.2 PHP Composer (Backend)

| Package | Propósito | Usado en |
|---------|-----------|----------|
| PDO MySQL | Conexión a base de datos | Todos los endpoints |

---

## 🎨 8. ASSETS Y RECURSOS

### 8.1 Iconos

**Fuente:** `lucide-react`

Iconos utilizados en Dashboard:
- **Financieros:** `DollarSign`, `TrendingUp`, `TrendingDown`, `CreditCard`, `Banknote`, `Wallet`
- **Operativos:** `ShoppingCart`, `Package`, `Users`, `Store`, `Lock`, `Unlock`
- **Analíticos:** `BarChart3`, `PieChart`, `Activity`, `Target`
- **UI/UX:** `AlertTriangle`, `CheckCircle`, `Clock`, `Calendar`, `RefreshCw`, `Eye`, `X`, `Download`, `Printer`
- **Métodos de Pago:** `QrCode`, `Smartphone`, `CreditCard`, `Banknote`

### 8.2 Estilos

| Tecnología | Propósito | Archivos |
|------------|-----------|----------|
| Tailwind CSS | Framework de utilidades CSS | Todos los componentes JSX |
| Custom CSS | Estilos personalizados | Clases inline en componentes |

---

## 🗃️ 9. TABLAS DE BASE DE DATOS

| Tabla | Propósito | Campos Críticos | Usada Por |
|-------|-----------|----------------|----------|
| `ventas` | Registro de todas las ventas | `id`, `fecha`, `monto_total`, `metodo_pago`, `estado`, `descuento`, `detalles_json` | Todos los endpoints de ventas |
| `turnos_caja` | Turnos de apertura/cierre de caja | `id`, `estado`, `fecha_apertura`, `monto_apertura`, `efectivo_teorico` | `pos_status.php`, `gestion_caja_completa.php` |
| `caja_movimientos` | Movimientos de caja (ingresos/egresos) | `id`, `tipo`, `monto`, `fecha`, `caja_id` | `dashboard_stats.php`, `dashboard_fintech.php` |
| `productos` | Catálogo de productos | `id`, `nombre`, `stock`, `stock_minimo`, `precio_costo`, `categoria`, `activo` | `productos_pos_optimizado.php`, `dashboard_stats.php` |
| `usuarios` | Usuarios del sistema | `id`, `nombre`, `rol` | Sistema de autenticación |
| `caja` | Estado de caja | `id`, `estado`, `fecha_apertura`, `monto_apertura` | `dashboard_fintech.php` (legacy) |

---

## 🔐 10. CONTEXTOS DE REACT

| Archivo | Ubicación | Propósito | Usado Por |
|---------|-----------|-----------|-----------|
| `AuthContext.jsx` | `src/contexts/` | Contexto de autenticación con datos del usuario actual | `SalesReportDashboard`, otros componentes |

---

## 🔄 11. FLUJO DE DATOS

### 11.1 Dashboard Resumen Caja

```
DashboardResumenCaja.jsx
    ├─> CONFIG (API_URL)
    ├─> API: pos_status.php → BD: turnos_caja
    ├─> API: gestion_caja_completa.php → BD: turnos_caja, caja_movimientos
    ├─> API: ventas_reales.php → BD: ventas
    └─> API: productos_pos_optimizado.php → BD: productos
```

### 11.2 Dashboard Ventas Completo

```
DashboardVentasCompleto.jsx
    ├─> DashboardResumenCaja.jsx (componente embebido)
    ├─> CONFIG (API_URL)
    └─> API: finanzas_completo.php → BD: ventas, productos
```

### 11.3 Sales Report Dashboard

```
SalesReportDashboard.jsx
    ├─> AuthContext (currentUser)
    ├─> reportesService
    │   └─> API: reportes_financieros_precisos.php → BD: ventas, productos
    ├─> useExportManager
    │   └─> API: export_report.php
    └─> VentaDetalleCompleto
        └─> TicketProfesional
```

### 11.4 Dashboard Optimizado

```
DashboardOptimizado.jsx
    ├─> CONFIG (API_URL, formatters)
    ├─> Hook: useDashboardStats
    └─> API: dashboard_stats.php → BD: ventas, turnos_caja, caja_movimientos, productos
```

### 11.5 Dashboard Fintech

```
useDashboardFintech.js (Hook)
    ├─> CONFIG (API_URL)
    ├─> WebSocket Service (real-time updates)
    ├─> Circuit Breaker Pattern (error handling)
    ├─> APM Monitoring (performance tracking)
    └─> API: dashboard_fintech.php → BD: ventas, caja, caja_movimientos, productos
```

---

## 📊 12. ENDPOINTS Y SUS QUERIES SQL

### 12.1 dashboard_stats.php

**Queries principales:**
1. Ventas del día: `SELECT COUNT(*), SUM(monto_total), AVG(monto_total) FROM ventas WHERE fecha BETWEEN ? AND ?`
2. Métodos de pago: `SELECT metodo_pago, COUNT(*), SUM(monto_total) FROM ventas WHERE fecha BETWEEN ? AND ? GROUP BY metodo_pago`
3. Estado de caja: `SELECT * FROM turnos_caja WHERE estado = 'abierto' ORDER BY id DESC LIMIT 1`
4. Productos stock bajo: `SELECT codigo, nombre, stock, categoria, stock_minimo FROM productos WHERE stock <= 10 OR stock <= stock_minimo`
5. Movimientos de caja: `SELECT tipo, SUM(monto) FROM caja_movimientos WHERE fecha >= ? GROUP BY tipo`

### 12.2 dashboard_fintech.php

**Queries optimizadas con CTEs:**
- CTE `ventas_hoy`: Métricas agregadas de ventas del día
- CTE `metodos_pago_stats`: Agrupación por método de pago
- CTE `ventas_ayer`: Comparación con día anterior
- Query de productos top: Usa `JSON_TABLE` para extraer datos del carrito
- Query de stock bajo: Con clasificación por nivel de criticidad
- Validación financiera: Comparación entre ventas efectivo y movimientos de caja

### 12.3 finanzas_completo.php

**Queries principales:**
1. Ventas con detalles: `SELECT id, fecha, cliente_nombre, metodo_pago, subtotal, descuento, monto_total, detalles_json FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?`
2. Cálculo de costos: `SELECT precio_costo FROM productos WHERE id = ?` (por cada producto en el carrito)
3. Agrupación por método de pago: Procesamiento en PHP desde ventas

---

## 🚨 13. PUNTOS CRÍTICOS DE ATENCIÓN

### ⚠️ NO MODIFICAR SIN VALIDACIÓN

1. **config.js**: Cambios en `API_URL` o `API_ENDPOINTS` romperán todos los componentes
2. **bd_conexion.php**: Cambios en la conexión afectarán todo el backend
3. **dashboard_stats.php**: Query de productos más vendidos procesa JSON, cambios en estructura `detalles_json` lo romperán
4. **DashboardResumenCaja.jsx**: Integrado en `DashboardVentasCompleto`, cambios afectan ambos
5. **Tablas BD**: Cambios en campos críticos (`monto_total`, `metodo_pago`, `estado`, `detalles_json`) romperán múltiples endpoints

### 🔒 Integridad de Datos

- **Campo `detalles_json` en ventas**: JSON crítico con estructura `{ cart: [...] }`, múltiples componentes dependen de esta estructura exacta
- **Estados de ventas**: Debe ser `'completada'` o `'completado'` (ambos se validan)
- **Estados de turnos_caja**: `'abierto'` / `'cerrado'` (case-sensitive)
- **Métodos de pago válidos**: `efectivo`, `tarjeta`, `transferencia`, `qr`, `mercadopago` (lowercase)

---

## 🧪 14. ARCHIVOS DE TESTING RELACIONADOS

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| `test_query_directa.php` | `api/` | Test de queries directas |
| `test_flujo_completo_automatico.php` | `api/` | Test del flujo completo |
| `completar_apertura_verificacion.php` | `api/` | Verificación de apertura de caja |
| `test_ciclo_completo_turnos.php` | `api/` | Test de ciclo de turnos |
| `test_completo_control_caja.php` | `api/` | Test de control de caja |
| `test_movimientos_caja_corregido.php` | `api/` | Test de movimientos de caja |

---

## 📝 15. ARCHIVOS DE CACHE

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| `cache_manager_pos.php` | `api/` | Gestor de caché para endpoints POS |
| `*.cache` | `api/cache/pos/` | Archivos de caché de productos |

---

## 🎯 16. RECOMENDACIONES PARA DEPURACIÓN

### Al Modificar Componentes Frontend:

1. **Verificar importaciones** de `CONFIG` y hooks
2. **Validar estructura de datos** retornada por APIs
3. **Revisar mapeo de datos** en `reportesService.js`
4. **Testear con datos reales** de la BD

### Al Modificar Endpoints Backend:

1. **Mantener estructura de respuesta** JSON compatible
2. **Validar queries SQL** con datos de producción
3. **Respetar campos críticos** documentados arriba
4. **Testear validación financiera** (si aplica)

### Al Modificar Base de Datos:

1. **NO cambiar nombres** de tablas críticas sin actualizar todos los endpoints
2. **NO eliminar campos** sin verificar todos los componentes que los usan
3. **Mantener tipos de datos** consistentes
4. **Agregar índices** solo después de validar impacto en performance

---

## 📞 CONTACTO PARA MODIFICACIONES

Cualquier modificación al módulo Dashboard debe:

1. ✅ Leer este documento completo
2. ✅ Identificar todos los archivos afectados
3. ✅ Crear backup de archivos a modificar
4. ✅ Testear en ambiente de desarrollo primero
5. ✅ Validar integridad financiera después de cambios
6. ✅ Actualizar esta documentación si corresponde

---

**Documento creado por:** AI Assistant  
**Fecha:** 8 de Octubre, 2025  
**Versión:** 1.0  
**Estado:** ✅ COMPLETO Y VALIDADO


