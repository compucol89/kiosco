# ⚠️ INSTRUCCIONES DE EJECUCIÓN: NORMALIZACIÓN SEGURA

**FECHA:** 21/10/2025  
**SISTEMA:** Tayrona Almacén – Kiosco POS  
**MODO:** Manual con confirmación explícita

---

## 🚨 IMPORTANTE: LEER ANTES DE EJECUTAR

Este proceso **modificará datos en la base de datos**. Aunque es seguro y reversible, requiere:

1. ✅ Backup de tabla `productos` (recomendado)
2. ✅ Confirmación explícita por lote
3. ✅ Validación entre lotes
4. ✅ Rol admin activo

---

## 🎯 PRE-CHECK OBLIGATORIO

### 1. Verificar Archivos
```bash
# Deben existir:
docs/pricing/NORMALIZACION_PROPUESTA.csv  ✅
docs/pricing/NORMALIZACION_PROGRESS.csv   ✅
docs/pricing/NORMALIZACION_CHANGELOG.md   ✅
api/normalizacion_segura.php              ✅
```

### 2. Backup de Tabla (RECOMENDADO)
```sql
-- Ejecutar en phpMyAdmin o MySQL
CREATE TABLE productos_backup_normalizacion_20251021 AS 
SELECT * FROM productos;

-- Verificar backup
SELECT COUNT(*) FROM productos_backup_normalizacion_20251021;
```

### 3. Verificar Sesión Admin
- Ir a Dashboard → Configuración → Usuarios
- Confirmar que tu usuario tiene rol `admin`

---

## 🔧 FLUJO DE EJECUCIÓN

### FASE 1: LOTE 0 - PRUEBA PILOTO (3 productos)

#### Paso 1: Modo PRUEBA (simula sin cambiar BD)
```
URL: http://localhost/kiosco/api/normalizacion_segura.php?confirmar=SI_EJECUTAR_NORMALIZACION&modo=prueba&lote=0
```

**Resultado esperado:**
```
📦 Procesando LOTE 0 (3 productos)
[1/3] ID:87  | Cerveza Andes Roja 473ml
  🔧 [SIMULACIÓN] Cambiaría: "Bebidas" → "Bebidas Alcohólicas"
  ✅ [SIMULACIÓN] Exitoso

...

📊 RESUMEN:
✅ Exitosos: 3
❌ Errores: 0
```

#### Paso 2: Revisar Simulación
- Si todo OK → continuar al Paso 3
- Si hay errores → revisar y ajustar

#### Paso 3: Modo REAL (ejecuta cambios)
```
URL: http://localhost/kiosco/api/normalizacion_segura.php?confirmar=SI_EJECUTAR_NORMALIZACION&modo=real&lote=0
```

**Resultado esperado:**
```
🔴 MODO REAL: Se realizarán cambios en la base de datos

[1/3] ID:87  | Cerveza Andes Roja 473ml
  ✅ ACTUALIZADO: "Bebidas" → "Bebidas Alcohólicas"

...

📊 RESUMEN:
✅ Exitosos: 3
```

#### Paso 4: Validación Inmediata
```sql
-- Verificar cambios
SELECT id, nombre, categoria 
FROM productos 
WHERE id IN (87, 99, 112);
```

**Esperado:** Todos con `categoria = 'Bebidas Alcohólicas'`

#### Paso 5: Test en POS
```
1. Ir a: http://localhost:3000/pos?__sim=2025-10-25T19:30:00
2. Buscar: "Cerveza Andes Roja"
3. Verificar badge: [+10%] ✅
```

**Si Lote 0 es exitoso → Continuar con Lotes 1-6**  
**Si Lote 0 falla → DETENER y revisar**

---

### FASE 2: LOTES 1-6 (84 productos restantes)

**Para cada lote:**

1. **Simular primero:**
   ```
   ?confirmar=SI_EJECUTAR_NORMALIZACION&modo=prueba&lote=N
   ```

2. **Si OK, ejecutar:**
   ```
   ?confirmar=SI_EJECUTAR_NORMALIZACION&modo=real&lote=N
   ```

3. **Validar rápida:**
   ```sql
   SELECT COUNT(*) FROM productos WHERE categoria = 'Bebidas Alcohólicas';
   ```
   Debe incrementar en ~15 por lote.

4. **Continuar siguiente lote**

**Lotes disponibles:**
- Lote 0: 3 productos (PILOTO)
- Lote 1: 15 productos
- Lote 2: 15 productos
- Lote 3: 15 productos
- Lote 4: 15 productos
- Lote 5: 15 productos
- Lote 6: 9 productos (FINAL)

---

## 📊 TRACKING DE PROGRESO

### Ver Progress CSV
```bash
# Windows
Get-Content docs/pricing/NORMALIZACION_PROGRESS.csv -Tail 20

# Linux/Mac
tail -20 docs/pricing/NORMALIZACION_PROGRESS.csv
```

### Actualizar Changelog
Después de cada lote, editar:
```
docs/pricing/NORMALIZACION_CHANGELOG.md
```

Completar sección del lote con:
- Fecha/hora
- Responsable
- Resultado (exitoso/errores)
- Observaciones

---

## ⚠️ MANEJO DE ERRORES

### Si un producto falla:
1. **NO detener el lote** (continúa con los demás)
2. Anotar ID del producto con error
3. Al final del lote, revisar manualmente ese producto
4. Opciones:
   - Editarlo desde UI de Productos
   - Investigar causa del error
   - Omitir si es falso positivo

### Si todo un lote falla:
1. **DETENER ejecución**
2. Revisar logs de error
3. Verificar conexión a BD
4. Revisar permisos de usuario
5. Consultar con equipo técnico

---

## 🔄 ROLLBACK (REVERSIÓN)

### Si necesitas revertir cambios:

#### Opción A: Restaurar desde backup
```sql
-- Restaurar tabla completa
DROP TABLE productos;
RENAME TABLE productos_backup_normalizacion_20251021 TO productos;
```

#### Opción B: Reversión selectiva
```sql
-- Ver cambios realizados (desde Progress CSV)
-- Revertir manualmente los que necesites

UPDATE productos
SET categoria = '(categoria_original)'
WHERE id = (id_a_revertir);
```

#### Opción C: Script de reversión
El `NORMALIZACION_PROGRESS.csv` contiene la `categoria_anterior` de cada producto para facilitar reversión.

---

## ✅ VALIDACIÓN FINAL

### Después de completar todos los lotes:

1. **Conteo total:**
   ```sql
   SELECT COUNT(*) as total_alcoholicas
   FROM productos
   WHERE categoria = 'Bebidas Alcohólicas'
     AND activo = 1;
   ```
   **Esperado:** ~87

2. **Muestra aleatoria:**
   ```sql
   SELECT id, nombre, categoria
   FROM productos
   WHERE id IN (87, 256, 447, 773, 853, 967)
   ORDER BY id;
   ```
   **Esperado:** Todos con `Bebidas Alcohólicas`

3. **Test en POS (horario real):**
   - Esperar viernes 18:00+ o sábado 18:00+
   - Buscar productos corregidos
   - Verificar badge [+10%]

4. **Completar documentación:**
   - `NORMALIZACION_VALIDACION.md`
   - `NORMALIZACION_RESUMEN_FINAL.md`

---

## 📋 CHECKLIST DE EJECUCIÓN

- [ ] Backup de tabla `productos` creado
- [ ] Pre-check completado (archivos, sesión admin)
- [ ] Lote 0 (piloto) simulado exitosamente
- [ ] Lote 0 (piloto) ejecutado exitosamente
- [ ] Test en POS del Lote 0 OK
- [ ] Lotes 1-6 simulados
- [ ] Lotes 1-6 ejecutados
- [ ] Validación final: ~87 productos
- [ ] Test en POS de productos aleatorios
- [ ] Progress CSV completo
- [ ] Changelog actualizado
- [ ] Documentación de validación completada

---

## 🚨 SEGURIDAD

### El script incluye:
- ✅ Confirmación explícita requerida
- ✅ Modo prueba/real separado
- ✅ Procesamiento por lotes (máx 15)
- ✅ Logging automático de cada cambio
- ✅ Validación de productos activos
- ✅ Omisión inteligente (si ya tiene categoría correcta)
- ✅ Manejo de errores sin detener lote completo

### El script NO:
- ❌ NO se ejecuta automáticamente
- ❌ NO modifica estructura de BD
- ❌ NO toca otros campos (solo `categoria`)
- ❌ NO afecta productos inactivos
- ❌ NO procesa más de 15 productos por ejecución

---

## 📞 SOPORTE

**Si algo sale mal:**
1. Detener ejecución inmediatamente
2. Revisar `NORMALIZACION_PROGRESS.csv`
3. Revisar logs de PHP: `api/logs/error.log`
4. Consultar documentación completa: `docs/pricing/`

**Archivos clave:**
- `api/normalizacion_segura.php` (script de ejecución)
- `docs/pricing/NORMALIZACION_PROGRESS.csv` (tracking)
- `docs/pricing/NORMALIZACION_CHANGELOG.md` (bitácora)
- `docs/pricing/NORMALIZACION_PROPUESTA.csv` (datos fuente)

---

## 🎯 TIEMPO ESTIMADO

| Fase | Tiempo |
|------|--------|
| Pre-check + Backup | 10 min |
| Lote 0 (piloto) | 10 min |
| Lotes 1-6 (ejecución + validación) | 30-40 min |
| Validación final | 10 min |
| **TOTAL** | **60-70 min** |

---

**¿Listo para ejecutar? Seguir este documento paso a paso.** ⚡

**⚠️ RECORDAR: Siempre simular antes de ejecutar en modo real.**

