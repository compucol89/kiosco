# 📱 MINI GUÍA: CORRECCIÓN DE CATEGORÍAS VÍA UI

**Objetivo:** Cambiar categoría de 87 productos alcohólicos a "Bebidas Alcohólicas"  
**Método:** Edición manual por UI de Productos  
**Tiempo estimado:** 45-60 minutos (87 productos × 40-45 seg/producto)  
**Prerequisitos:** Rol admin, acceso al módulo Productos

---

## 🎯 RESUMEN DE CORRECCIONES

| Tipo | Cantidad | Acción |
|------|----------|--------|
| ✅ Productos alcohólicos | 87 | Cambiar a "Bebidas Alcohólicas" |
| ❌ Falsos positivos | 12 | NO cambiar |
| 🟡 Otros mal categorizados | 6 | Opcional: recategorizar |
| **TOTAL A CORREGIR** | **87** | **Ver CSV adjunto** |

---

## 📋 PROCESO COMPLETO (PASO A PASO)

### PASO 1: Preparación (5 minutos)

1. **Abrir CSV de propuesta:**
   ```
   docs/pricing/NORMALIZACION_PROPUESTA.csv
   ```

2. **Filtrar solo productos alcohólicos:**
   - Columna `es_alcoholico` = `SI`
   - Resultado: 87 productos

3. **Tener lista de IDs a mano:**
   ```
   87, 99, 112, 128, 129, 216, 255, 256, 378, 417, 418, 447, 453, 454, 455, 456, 457, 
   466, 469, 470, 507, 603, 608, 620, 624, 669, 670, 673, 674, 675, 684, 696, 697, 698, 
   699, 700, 750, 755, 756, 757, 758, 759, 760, 761, 762, 773, 774, 775, 779, 794, 798, 
   799, 800, 801, 802, 837, 838, 851, 853, 854, 856, 898, 899, 900, 901, 902, 903, 904, 
   911, 912, 918, 934, 938, 960, 965, 966, 967, 980, 996, 1008
   ```

---

### PASO 2: Corrección Individual (40-50 minutos)

**Para cada ID de la lista:**

#### A) Abrir UI de Productos
```
Dashboard → Productos
```

#### B) Buscar producto por ID
```
🔍 Buscar productos...
[Escribir ID, ej: 87]
[Enter]
```

#### C) Verificar que es alcohólico
```
Resultado:
  [ID: 87] Cerveza Andes Roja 473ml
  Categoría actual: Bebidas
  
¿Es bebida alcohólica? → SÍ ✅
Proceder a editar.
```

#### D) Click en Editar
```
[Lista de productos]
  ├─ Cerveza Andes Roja 473ml
  │   ├─ 📝 Editar  ← CLICK AQUÍ
```

#### E) Cambiar categoría
```
Modal: Editar Producto

Nombre: Cerveza Andes Roja 473ml
Código: ...
Categoría: [Bebidas              ] ← Borrar texto actual
           ↓
Categoría: [Bebidas Alcohólicas  ] ← Copiar/pegar exactamente
Precio: ...
Stock: ...

[Cancelar]  [💾 Guardar]  ← CLICK GUARDAR
```

**⚠️ TEXTO EXACTO:**
```
Bebidas Alcohólicas
```
*(Con mayúscula en "B", sin errores de tipeo)*

#### F) Verificar guardado
```
✅ "Producto actualizado correctamente"
```

#### G) Marcar como completado
```
En tu lista/CSV: marcar ID como ✅
```

#### H) Siguiente producto
```
Repetir A-G para próximo ID
```

---

### PASO 3: Validación Rápida (5 minutos)

**Cada 20 productos, verificar:**

```sql
-- Copiar en phpMyAdmin o cliente MySQL
SELECT COUNT(*) as corregidos
FROM productos
WHERE categoria LIKE '%Alcoh%'
  AND activo = 1;
```

**Resultado esperado:**
- Después de 20 correcciones: ~20
- Después de 40 correcciones: ~40
- Después de 87 correcciones: ~87

---

## ⚡ ATAJOS PARA ACELERAR

### Atajo 1: Copiar/Pegar Categoría
```
1. Copiar texto: "Bebidas Alcohólicas"
2. Para cada producto:
   - Click en campo Categoría
   - Ctrl+A (seleccionar todo)
   - Ctrl+V (pegar)
   - Click Guardar
```

### Atajo 2: Tab Navigation
```
1. Buscar producto (Enter)
2. Click Editar
3. Tab hasta campo Categoría
4. Ctrl+A → Ctrl+V
5. Tab hasta botón Guardar → Enter
```

### Atajo 3: Filtro por Categoría
```
1. Productos → Filtros
2. Categoría: "Bebidas"
3. Listar todos
4. Editar uno por uno (solo los alcohólicos)
```

---

## 📊 TRACKING DE PROGRESO

**Usar esta tabla:**

| Rango de IDs | Cantidad | Estado | Tiempo |
|--------------|----------|--------|--------|
| 87-256 | 10 | ⏳ Pendiente | - |
| 378-507 | 12 | ⏳ Pendiente | - |
| 603-700 | 20 | ⏳ Pendiente | - |
| 750-802 | 18 | ⏳ Pendiente | - |
| 837-912 | 15 | ⏳ Pendiente | - |
| 918-1008 | 12 | ⏳ Pendiente | - |
| **TOTAL** | **87** | **⏳** | **~50 min** |

**Marcar con ✅ cuando completes cada rango.**

---

## ⚠️ ERRORES COMUNES Y SOLUCIONES

### Error 1: "Categoría no se guarda"
**Causa:** Typo o caracteres invisibles.  
**Solución:** Copiar exactamente: `Bebidas Alcohólicas`

### Error 2: "Producto no aparece en búsqueda"
**Causa:** ID incorrecto o producto inactivo.  
**Solución:** Verificar ID en CSV, buscar por nombre.

### Error 3: "Modal no se cierra después de guardar"
**Causa:** Error de validación no visible.  
**Solución:** Refrescar página (F5), intentar nuevamente.

### Error 4: "Cambié falso positivo por error"
**Causa:** No verificaste si es alcohólico.  
**Solución:** 
```sql
-- Revertir cambio
UPDATE productos
SET categoria = '(categoria_original)'
WHERE id = (id_afectado);
```

---

## 🧪 TEST DESPUÉS DE CORREGIR

### Test 1: Conteo Final
```sql
SELECT COUNT(*) as total_alcoholicas
FROM productos
WHERE categoria LIKE '%Alcoh%'
  AND activo = 1;
```

**Esperado:** ~87

### Test 2: Muestra Aleatoria
```sql
SELECT id, nombre, categoria
FROM productos
WHERE id IN (87, 112, 256, 447, 773, 853, 967)
ORDER BY id;
```

**Esperado:** Todos con `categoria = 'Bebidas Alcohólicas'`

### Test 3: POS Simulado (DEV)
```
1. Ir a: http://localhost:3000/pos?__sim=2025-10-25T19:30:00
2. Buscar: "Cerveza Andes Roja"
3. Verificar badge: [+10%] ✅
```

---

## 📋 CHECKLIST FINAL

**Antes de declarar completo:**

- [ ] 87 productos alcohólicos corregidos
- [ ] Query de conteo = ~87
- [ ] Test de muestra = 100% OK
- [ ] Test en POS = Badge visible
- [ ] CSV marcado con ✅ en productos corregidos
- [ ] Documentado tiempo real de corrección

---

## 🎯 CATEGORÍA CANÓNICA (REFERENCIA)

```
Bebidas Alcohólicas
```

**Características:**
- Mayúscula en "B"
- Tilde en "o" de "Alcohólicas"
- Singular "Bebidas", plural "Alcohólicas"
- Sin espacios extras
- Longitud: 19 caracteres

**Slug resultante:** `bebidas-alcoholicas` ✅

---

## ⏱️ ESTIMACIÓN DE TIEMPO

| Actividad | Tiempo |
|-----------|--------|
| Preparación (abrir CSV, listar IDs) | 5 min |
| Corrección de 87 productos (×45 seg) | 40-50 min |
| Validación (queries + test POS) | 5 min |
| **TOTAL** | **50-60 min** |

**Recomendación:** Hacer en 2-3 sesiones de 20-30 minutos para evitar fatiga.

---

## 📁 ARCHIVOS RELACIONADOS

- **CSV de propuesta:** `NORMALIZACION_PROPUESTA.csv`
- **Diagnóstico:** `NORMALIZACION_DIAGNOSTICO.md`
- **Validación:** `NORMALIZACION_VALIDACION.md` (próximo paso)

---

## 🚨 IMPORTANTE

**NO cambiar estos IDs (falsos positivos):**
```
16, 47, 75, 91, 120, 124, 127, 135, 160, 175, 214, 266, 349, 413, 419, 555, 578, 579, 590, 706, 709, 710, 833
```

**Estos NO son alcohólicos** (snacks, pastillas, cigarrillos, etc.)

---

**Guía completa → Listo para ejecutar → Tiempo estimado: 50-60 minutos** ✅

**Próximo paso:** Validar en POS después de completar correcciones.

