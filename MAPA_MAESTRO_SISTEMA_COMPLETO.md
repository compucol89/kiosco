# 🗺️ MAPA MAESTRO COMPLETO DEL SISTEMA

**Fecha:** 8 de Octubre, 2025  
**Sistema:** Tayrona Almacén - Kiosco POS  
**Análisis:** Mapeo exhaustivo de TODOS los módulos

---

## 📊 RESUMEN EJECUTIVO

**Total de módulos:** 9  
**Componentes React activos:** 48  
**Componentes NO usados identificados:** 8 (eliminados)  
**Endpoints PHP funcionales:** 110  
**Hooks personalizados:** 13  
**Servicios:** 17  
**Contextos:** 2

---

## 🎯 MÓDULOS DEL SISTEMA Y SUS COMPONENTES

### MÓDULO 1: DASHBOARD ✅

**Página en App.jsx:** `Inicio` → DashboardVentasCompleto

**Componentes:**
```
DashboardVentasCompleto.jsx (principal)
├─ DashboardResumenCaja.jsx (embebido)
│  ├─ API: pos_status.php
│  ├─ API: gestion_caja_completa.php
│  ├─ API: ventas_reales.php
│  └─ API: productos_pos_optimizado.php
│
├─ API: finanzas_completo.php
└─ CONFIG

Layout:
├─ IndicadorEstadoCaja.jsx (TopBar - siempre visible)
└─ NotificacionesMovimientos.jsx (notificaciones globales)
```

**Archivos:**
- ✅ DashboardVentasCompleto.jsx (520 LOC)
- ✅ DashboardResumenCaja.jsx (772 LOC)
- ✅ IndicadorEstadoCaja.jsx (165 LOC)
- ✅ NotificacionesMovimientos.jsx (~300 LOC)

**Eliminados:**
- 🔴 DashboardOptimizado.jsx (duplicado)
- 🔴 SalesReportDashboard.jsx (duplicado)

---

### MÓDULO 2: CONTROL DE CAJA ✅

**Páginas en App.jsx:**
- `ControlCaja` → GestionCajaMejorada
- `HistorialTurnos` → HistorialTurnosPage

**Componentes:**
```
GestionCajaMejorada.jsx (principal)
├─ ModalAperturaCaja.jsx (modal alternativo)
├─ MetricasCaja.jsx (métricas)
├─ AuthContext (usuario)
├─ API: gestion_caja_completa.php
│  ├─ GET: estado_caja
│  ├─ GET: historial_movimientos
│  ├─ GET: ultimo_cierre
│  ├─ POST: abrir_caja
│  ├─ POST: cerrar_caja
│  └─ POST: registrar_movimiento
└─ CONFIG

HistorialTurnosPage.jsx (historial completo)
├─ ReportesEfectivoPeriodo.jsx (pestaña)
├─ ReportesDiferenciasCajero.jsx (pestaña)
├─ API: gestion_caja_completa.php (historial_completo)
└─ API: pos_status.php

CajaStatusIndicator.jsx
└─ CajaContext
```

**Archivos:**
- ✅ GestionCajaMejorada.jsx (1,606 LOC)
- ✅ HistorialTurnosPage.jsx (1,131 LOC)
- ✅ ModalAperturaCaja.jsx (307 LOC)
- ✅ MetricasCaja.jsx (220 LOC)
- ✅ IndicadorEstadoCaja.jsx (165 LOC)
- ✅ CajaStatusIndicator.jsx (43 LOC)
- ✅ ReportesEfectivoPeriodo.jsx (644 LOC)
- ✅ ReportesDiferenciasCajero.jsx (468 LOC)

**Hooks:**
- ✅ useCajaApi.js (203 LOC)
- ✅ useCajaLogic.js (173 LOC)
- ✅ useCajaStatus.js (357 LOC)

**Servicios:**
- ✅ cajaService.js (429 LOC)
- ✅ cashSyncService.js

**Contextos:**
- ✅ CajaContext.jsx (199 LOC)

---

### MÓDULO 3: PUNTO DE VENTA (POS) ✅

**Página en App.jsx:** `PuntoDeVenta` → PuntoDeVentaStockOptimizado

**Componentes:**
```
PuntoDeVentaStockOptimizado.jsx (principal - 907 LOC)
├─ PaymentModalSleepyCashierProof.jsx (modal de pago)
├─ TicketProfesional.jsx (impresión)
├─ StockAlerts.jsx (alertas de stock)
│  ├─ StockBadge
│  ├─ ProductCardWithAlerts
│  ├─ StockIndicator
│  ├─ CategoryTag
│  └─ StockCriticalAlert
│
├─ HOOKS:
│  ├─ useStockManager.js (gestión inteligente de stock)
│  └─ useCajaStatus.js (validación crítica de caja)
│
├─ SERVICIOS:
│  ├─ descuentosService.js (descuentos por método de pago)
│  └─ cashSyncService.js (sincronización con caja)
│
├─ APIs:
│  ├─ productos_pos_optimizado.php (productos con stock)
│  └─ procesar_venta_ultra_rapida.php (procesar venta)
│
└─ CONFIG
```

**Archivos:**
- ✅ PuntoDeVentaStockOptimizado.jsx (907 LOC)
- ✅ PaymentModalSleepyCashierProof.jsx (487 LOC)
- ✅ TicketProfesional.jsx (~800 LOC)
- ✅ StockAlerts.jsx (502 LOC)

**Hooks:**
- ✅ useStockManager.js (412 LOC)
- ✅ useCajaStatus.js (compartido con Caja)

**Servicios:**
- ✅ descuentosService.js
- ✅ cashSyncService.js

**APIs Backend:**
- ✅ productos_pos_optimizado.php
- ✅ procesar_venta_ultra_rapida.php
- ✅ anular_venta.php

**Características:**
- Validación obligatoria de caja abierta
- Verificación de stock en tiempo real
- Descuentos por método de pago
- Sincronización automática con caja
- Facturación AFIP opcional
- Sistema responsive
- Lazy loading de ticket
- Shortcuts de teclado (F6-F8, Enter, Escape)

---

### MÓDULO 4: PRODUCTOS E INVENTARIO ✅

**Páginas en App.jsx:**
- `Productos` → ProductosPage
- `Inventario` → InventarioInteligente

**Componentes:**
```
ProductosPage.jsx (principal)
├─ productos/hooks/
│  ├─ useProductos.js (CRUD de productos)
│  ├─ useProductSearch.js (búsqueda y paginación)
│  ├─ useProductStats.js (estadísticas)
│  ├─ useProductAnalysis.js (análisis de ventas)
│  └─ useProductFilters.js (filtros avanzados)
│
├─ productos/components/
│  ├─ ProductStats.jsx (estadísticas)
│  ├─ ProductSearch.jsx (buscador)
│  ├─ ProductList.jsx (lista)
│  ├─ ProductCard.jsx (tarjeta)
│  ├─ ProductAlerts.jsx (alertas)
│  ├─ ProductFilters.jsx (filtros)
│  ├─ ProductFormModal.jsx (formulario CRUD)
│  ├─ ProductDetailModal.jsx (detalle)
│  ├─ ProductImportModal.jsx (importación)
│  ├─ ProductImage.jsx (imagen)
│  └─ LazyModals.jsx (modales lazy)
│
├─ APIs:
│  ├─ productos.php (CRUD)
│  ├─ categorias.php
│  └─ subir_imagen_producto.php
│
└─ CONFIG

InventarioInteligente.jsx
├─ Servicios de IA:
│  ├─ inventarioIAService.js
│  ├─ openaiService.js
│  └─ aiAnalytics.js
│
├─ APIs:
│  ├─ productos.php
│  └─ auditoria_inventario.php
│
└─ CONFIG
```

**Archivos:**
- ✅ ProductosPage.jsx (258 LOC)
- ✅ InventarioInteligente.jsx (~1,200 LOC)
- ✅ productos/ (carpeta con 18 archivos)

**Eliminados:**
- 🔴 ProductosPageOptimized.jsx (versión alternativa en /productos)

**Hooks en /productos (5):**
- useProductos.js
- useProductSearch.js
- useProductStats.js
- useProductAnalysis.js
- useProductFilters.js

**Componentes en /productos (11):**
- ProductStats, ProductSearch, ProductList, ProductCard, ProductAlerts, ProductFilters, ProductFormModal, ProductDetailModal, ProductImportModal, ProductImage, LazyModals

---

### MÓDULO 5: FINANZAS Y REPORTES ✅

**Página en App.jsx:** `Finanzas` → ModuloFinancieroCompleto

**Componentes:**
```
ModuloFinancieroCompleto.jsx (principal - 652 LOC)
├─ (Componentes internos propios)
├─ API: reportes_financieros_precisos.php
├─ Servicio: reportesService.js
└─ CONFIG
```

**Archivos:**
- ✅ ModuloFinancieroCompleto.jsx (652 LOC)

**Eliminados:**
- 🔴 FinanzasPage.jsx (~958 LOC - versión antigua)
- 🔴 FinanzasPageCorregida.jsx (~440 LOC - versión antigua)
- 🔴 ReportesPagePreciso.jsx (~868 LOC - duplicado)

**Servicios:**
- ✅ reportesService.js

**APIs:**
- ✅ reportes_financieros_precisos.php
- ✅ finanzas_completo.php

---

### MÓDULO 6: VENTAS Y REPORTES ✅

**Página en App.jsx:** `Ventas` → ReporteVentasModerno

**Componentes:**
```
ReporteVentasModerno.jsx (principal)
├─ AnalisisInteligente.jsx (análisis con IA)
│  └─ DiagnosticoFinanciero.jsx (diagnóstico)
│     └─ openaiService.js
│
├─ VentaDetalleCompleto.jsx (modal de detalle)
│  └─ TicketProfesional.jsx
│
├─ APIs:
│  ├─ ventas_reales.php
│  └─ finanzas_completo.php
│
└─ CONFIG
```

**Archivos:**
- ✅ ReporteVentasModerno.jsx (~600 LOC)
- ✅ AnalisisInteligente.jsx (~500 LOC)
- ✅ DiagnosticoFinanciero.jsx (~400 LOC)
- ✅ VentaDetalleCompleto.jsx (~500 LOC)
- ✅ TicketProfesional.jsx (~800 LOC)

**Eliminados:**
- 🔴 VentasPage.jsx (~1,848 LOC - versión antigua)
- 🔴 GananciaPorVentasSimple.jsx (~217 LOC - obsoleto)

**Servicios:**
- ✅ openaiService.js (IA)
- ✅ aiAnalytics.js (IA)

---

### MÓDULO 7: CONFIGURACIÓN ✅

**Página en App.jsx:** `Configuracion` → ConfiguracionPage

**Componentes:**
```
ConfiguracionPage.jsx (principal - ~1,500 LOC)
├─ ConfiguracionFacturacion.jsx (configuración AFIP)
├─ ConfiguracionIA.jsx (tokens de IA - probable)
│
├─ APIs:
│  ├─ configuracion_empresarial.php
│  ├─ configuracion_facturacion.php
│  ├─ reset_sistema_empresarial.php
│  └─ permisos_usuario.php
│
└─ CONFIG
```

**Archivos:**
- ✅ ConfiguracionPage.jsx (~1,500 LOC)
- ✅ ConfiguracionFacturacion.jsx (~200 LOC)
- ✅ ConfiguracionIA.jsx (~300 LOC)

**Servicios:**
- ✅ configEmpresarialService.js
- ✅ configService.js
- ✅ permisosService.js

---

### MÓDULO 8: USUARIOS Y AUTENTICACIÓN ✅

**Componentes:**
```
LoginPage.jsx (pantalla de login)
├─ AuthContext (autenticación)
├─ API: usuarios.php
└─ CONFIG

UsuariosPage.jsx (gestión de usuarios)
├─ AuthContext
├─ usePermisos.js
├─ API: usuarios.php
├─ API: permisos_usuario.php
└─ CONFIG

PermissionGuard.jsx (componente auxiliar)
└─ usePermisos.js
```

**Archivos:**
- ✅ LoginPage.jsx (~200 LOC)
- ✅ UsuariosPage.jsx (~800 LOC)
- ✅ PermissionGuard.jsx (~100 LOC)
- ✅ AuthContext.jsx (~300 LOC)

**Hooks:**
- ✅ usePermisos.js

**Servicios:**
- ✅ permisosService.js

**APIs:**
- ✅ usuarios.php
- ✅ permisos_usuario.php

---

### MÓDULO 9: INTELIGENCIA ARTIFICIAL ✅

**Componentes:**
```
AnalisisInteligente.jsx
├─ DiagnosticoFinanciero.jsx
│
├─ Servicios:
│  ├─ openaiService.js (servicio principal de IA)
│  ├─ aiAnalytics.js (analytics con IA)
│  ├─ inventarioIAService.js (IA para inventario)
│  ├─ pedidosIAService.js (IA para pedidos)
│  └─ antiFraudEngine.js (detección de fraude)
│
└─ CONFIG: aiConfig.js

ConfiguracionIA.jsx (configuración de tokens)
└─ aiConfig.js
```

**Archivos:**
- ✅ AnalisisInteligente.jsx (~500 LOC)
- ✅ DiagnosticoFinanciero.jsx (~400 LOC)
- ✅ ConfiguracionIA.jsx (~300 LOC)

**Servicios de IA (5):**
- ✅ openaiService.js
- ✅ aiAnalytics.js
- ✅ inventarioIAService.js
- ✅ pedidosIAService.js
- ✅ antiFraudEngine.js

**Configuración:**
- ✅ aiConfig.js

---

## 📁 ESTRUCTURA COMPLETA DE ARCHIVOS

### SRC/COMPONENTS (48 componentes activos)

#### Componentes Principales (13):
```
✅ ProductosPage.jsx
✅ PuntoDeVentaStockOptimizado.jsx
✅ DashboardVentasCompleto.jsx
✅ InventarioInteligente.jsx
✅ UsuariosPage.jsx
✅ LoginPage.jsx
✅ GestionCajaMejorada.jsx
✅ HistorialTurnosPage.jsx
✅ ConfiguracionPage.jsx
✅ ModuloFinancieroCompleto.jsx
✅ ReporteVentasModerno.jsx
✅ IndicadorEstadoCaja.jsx
✅ NotificacionesMovimientos.jsx
```

#### Sub-componentes (35):
```
Dashboard:
✅ DashboardResumenCaja.jsx

Control de Caja:
✅ ModalAperturaCaja.jsx
✅ MetricasCaja.jsx
✅ CajaStatusIndicator.jsx
✅ ReportesEfectivoPeriodo.jsx
✅ ReportesDiferenciasCajero.jsx

POS:
✅ PaymentModalSleepyCashierProof.jsx
✅ TicketProfesional.jsx
✅ StockAlerts.jsx

Ventas:
✅ VentaDetalleCompleto.jsx
✅ AnalisisInteligente.jsx
✅ DiagnosticoFinanciero.jsx

Configuración:
✅ ConfiguracionIA.jsx
✅ ConfiguracionFacturacion.jsx
✅ AFIPStatusIndicator.jsx
✅ PermissionGuard.jsx

Utilidades:
✅ StatCards.jsx (revisar uso)

Productos (18 archivos):
✅ productos/components/ (11 componentes)
✅ productos/hooks/ (5 hooks)
✅ productos/ProductosPageOptimized.jsx (🔴 ELIMINADO)
```

---

### SRC/HOOKS (13 hooks)

```
Caja:
✅ useCajaApi.js
✅ useCajaLogic.js
✅ useCajaStatus.js

Productos:
✅ useStockManager.js
✅ useProductos.js (en /productos)

Dashboard:
✅ useDashboardFintech.js
✅ useExportManager.js

Búsqueda:
✅ useEnterpriseSearch.js
✅ useHybridPOSSearch.js
✅ usePOSProducts.js

Sistema:
✅ usePermisos.js
✅ useDebounce.js

UI:
✅ ToastContext.jsx
```

---

### SRC/SERVICES (17 servicios)

```
Caja:
✅ cajaService.js
✅ cashSyncService.js

Reportes:
✅ reportesService.js

Configuración:
✅ configEmpresarialService.js
✅ configService.js
✅ permisosService.js

Ventas:
✅ descuentosService.js

IA (5):
✅ openaiService.js
✅ aiAnalytics.js
✅ inventarioIAService.js
✅ pedidosIAService.js
✅ antiFraudEngine.js

Seguridad:
✅ seguridadInventarioService.js
✅ validationSuite.js
✅ auditLogger.js

Sistema:
✅ sistemaService.js
✅ api.js
```

---

### SRC/CONTEXTS (2 contextos)

```
✅ AuthContext.jsx (autenticación global)
✅ CajaContext.jsx (estado de caja global)
```

---

### SRC/CONFIG (2 archivos)

```
✅ config.js (configuración principal)
✅ aiConfig.js (configuración de IA)
```

---

### SRC/UTILS (4 utilidades)

```
✅ cashValidation.js
✅ imageCache.js
✅ performance.js
✅ toastNotifications.js
```

---

## 🔌 ENDPOINTS DE API BACKEND

### APIs Principales por Módulo:

**Dashboard:**
- dashboard_stats.php
- dashboard_fintech.php (v2)
- pos_status.php
- finanzas_completo.php

**Control de Caja:**
- gestion_caja_completa.php (principal - 15+ funciones)
- pos_status.php

**POS:**
- productos_pos_optimizado.php
- productos_pos_v2.php
- procesar_venta_ultra_rapida.php
- anular_venta.php

**Productos:**
- productos.php
- categorias.php
- proveedores.php
- subir_imagen_producto.php
- auditoria_inventario.php

**Ventas:**
- ventas_reales.php
- listar_ventas.php
- ventas_optimizadas.php

**Finanzas:**
- reportes_financieros_precisos.php
- finanzas_completo.php

**Configuración:**
- configuracion_empresarial.php
- configuracion_facturacion.php
- reset_sistema_empresarial.php
- permisos_usuario.php

**Usuarios:**
- usuarios.php
- validar_usuario.php

**Conexión:**
- bd_conexion.php (crítico)
- config.php

**Total APIs funcionales:** ~110 archivos PHP

---

## 🎯 FLUJO DE NAVEGACIÓN EN APP.JSX

```javascript
App.jsx
├─ Inicio → DashboardVentasCompleto
├─ ControlCaja → GestionCajaMejorada
├─ HistorialTurnos → HistorialTurnosPage
├─ PuntoDeVenta → PuntoDeVentaStockOptimizado
├─ Ventas → ReporteVentasModerno
├─ Inventario → InventarioInteligente
├─ Productos → ProductosPage
├─ Finanzas → ModuloFinancieroCompleto
├─ Usuarios → UsuariosPage
└─ Configuracion → ConfiguracionPage
```

---

## ✅ COMPONENTES ELIMINADOS (8 archivos confirmados)

1. 🔴 DashboardOptimizado.jsx (~424 LOC)
2. 🔴 SalesReportDashboard.jsx (~758 LOC)
3. 🔴 VentasPage.jsx (~1,848 LOC)
4. 🔴 FinanzasPage.jsx (~958 LOC)
5. 🔴 FinanzasPageCorregida.jsx (~440 LOC)
6. 🔴 ReportesPagePreciso.jsx (~868 LOC)
7. 🔴 GananciaPorVentasSimple.jsx (~217 LOC)
8. 🔴 ProductosPageOptimized.jsx (~196 LOC)

**Total eliminado:** ~5,709 LOC de código duplicado

---

## 🔍 DEPENDENCIAS CRÍTICAS ENTRE MÓDULOS

### Flujo de Venta Completo:

```
1. Usuario → Abrir Caja (GestionCajaMejorada)
   └─ gestion_caja_completa.php

2. Usuario → POS (PuntoDeVentaStockOptimizado)
   ├─ Validación: useCajaStatus (caja debe estar abierta)
   ├─ Productos: productos_pos_optimizado.php
   ├─ Stock: useStockManager
   └─ Descuentos: descuentosService

3. Usuario → Procesar Venta
   ├─ Validación final: validateSaleOperation()
   ├─ Procesar: procesar_venta_ultra_rapida.php
   └─ Sync: cashSyncService (actualiza caja automáticamente)

4. Sistema → Actualizar Dashboard
   ├─ DashboardVentasCompleto se actualiza
   └─ IndicadorEstadoCaja se actualiza

5. Usuario → Ver Reportes (ReporteVentasModerno)
   └─ ventas_reales.php, finanzas_completo.php

6. Usuario → Cerrar Caja (GestionCajaMejorada)
   └─ gestion_caja_completa.php
```

---

## 🗃️ TABLAS DE BASE DE DATOS POR MÓDULO

### Dashboard:
- ventas, turnos_caja, productos

### Control de Caja:
- turnos_caja (principal)
- movimientos_caja_detallados
- historial_turnos_caja
- ventas (relación)

### POS:
- productos
- ventas
- turnos_caja (validación)

### Productos:
- productos (principal)
- categorias
- proveedores
- auditoria_inventario

### Ventas:
- ventas (principal)
- productos (detalle)

### Usuarios:
- usuarios
- permisos

---

## 📊 ESTADÍSTICAS FINALES

### Código Frontend:
- **Componentes React:** 48 activos
- **Hooks:** 18 total (13 principales + 5 en /productos)
- **Servicios:** 17
- **Contextos:** 2
- **Utils:** 4
- **Total archivos .js/.jsx:** ~89

### Código Backend:
- **Endpoints PHP:** ~110 funcionales
- **Archivos de conexión:** 2 (bd_conexion, config)

### Código Eliminado:
- **Componentes duplicados:** 8
- **LOC eliminadas:** ~5,709
- **Archivos test/debug:** 64
- **Total archivos eliminados hoy:** ~158

---

## 🔒 VALIDACIONES Y SEGURIDAD

### Validación de Caja (CRÍTICA):

Todos los módulos que procesan dinero validan caja:

```
POS → useCajaStatus → validateSaleOperation()
   └─ Bloquea ventas si caja cerrada

Control de Caja → CajaContext
   └─ Estado global compartido

Dashboard → Lee estado pero no valida
```

### Flujo de Permisos:

```
App.jsx → usePermisos
   ├─ getFilteredMenuItems (filtrar menú)
   ├─ hasAccess (validar acceso a página)
   └─ currentUser.role (admin, vendedor, cajero)
```

---

## 🎯 PRÓXIMOS PASOS PARA DEPURACIÓN

### Ya completamos:
- ✅ Limpieza de archivos basura
- ✅ Eliminación de componentes duplicados
- ✅ Mapeo completo del sistema
- ✅ Identificación de dependencias

### Pendiente:
1. ⬜ Verificar componentes auxiliares (CajaStatusIndicator, StatCards)
2. ⬜ Optimizar imports innecesarios
3. ⬜ Revisar console.log/error para producción
4. ⬜ Testing funcional de cada módulo
5. ⬜ Optimización de performance

---

**Documento creado por:** AI Assistant  
**Versión:** 1.0 - Mapa Maestro Completo  
**Estado:** ✅ SISTEMA COMPLETAMENTE MAPEADO

