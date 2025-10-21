# 📱 MINI GUÍA: NORMALIZAR PRODUCTOS DESDE LA UI (PASO A PASO)

**Objetivo:** Cambiar categoría de productos alcohólicos a "Bebidas Alcohólicas".  
**Prerrequisito:** Lista de IDs de `CHECKLIST_EXPRESS_2MIN.md`.  
**Tiempo por producto:** 30-60 segundos.

---

## 🎯 MÉTODO 1: EDICIÓN INDIVIDUAL (RECOMENDADO PARA <20 PRODUCTOS)

### Paso 1: Abrir Módulo de Productos
```
Dashboard → Menú lateral → 📦 Productos
```

### Paso 2: Buscar Producto por ID o Nombre
```
🔍 Buscar productos...
[Escribir ID o nombre del producto]
```

**Ejemplo:**
```
Buscar: "456"  (ID del producto)
o
Buscar: "Fernet Branca"  (nombre del producto)
```

### Paso 3: Click en Editar
```
[Lista de productos]
  ├─ Fernet Branca 750ml
  │   ├─ 📝 Editar  ← CLICK AQUÍ
  │   └─ 🗑️ Eliminar
```

### Paso 4: Cambiar Categoría
```
Modal: Editar Producto

Nombre: Fernet Branca 750ml
Código: FER-001
Categoría: [Bebidas         ] ← Cambiar esto
           ↓
Categoría: [Bebidas Alcohólicas] ← Escribir exactamente así
Precio: $3,500
Stock: 12
...

[Cancelar]  [💾 Guardar]  ← CLICK GUARDAR
```

**⚠️ IMPORTANTE:**
- Escribir **exactamente**: `Bebidas Alcohólicas` (con mayúsculas y tilde)
- Si hay dropdown, seleccionar de la lista
- Si no existe en dropdown, escribir a mano

### Paso 5: Verificar Guardado
```
✅ "Producto actualizado correctamente"
```

### Paso 6: Repetir para Siguiente Producto
```
Ir al siguiente ID de la lista
```

---

## 🎯 MÉTODO 2: FILTRO + EDICIÓN MASIVA (SI HAY MUCHOS)

### Paso 1: Filtrar por Categoría Incorrecta
```
Productos → Filtros avanzados
  ├─ Categoría: [Bebidas]  ← Seleccionar categoría incorrecta
  └─ [Aplicar]
```

### Paso 2: Verificar que Son Alcohólicos
```
[Lista filtrada]
  ├─ Cerveza Brahma 1L      ✅ Es alcohólica
  ├─ Fernet Branca 750ml    ✅ Es alcohólica
  ├─ Vodka Smirnoff 1L      ✅ Es alcohólica
  └─ Gaseosa Coca-Cola 2L   ❌ NO es alcohólica (omitir)
```

### Paso 3: Editar Uno por Uno
```
Para cada producto alcohólico:
  1. Click en Editar
  2. Categoría: "Bebidas Alcohólicas"
  3. Guardar
```

---

## 🎯 MÉTODO 3: UPDATE MASIVO EN BD (SOLO SI SON +50 PRODUCTOS)

**⚠️ ADVERTENCIA:** Requiere backup y acceso a BD. Solo para usuarios avanzados.

### Paso 1: BACKUP
```sql
-- HACER BACKUP PRIMERO
CREATE TABLE productos_backup_20251021 AS SELECT * FROM productos;
```

### Paso 2: UPDATE Masivo por IDs
```sql
-- Cambiar categoría de productos específicos
UPDATE productos
SET categoria = 'Bebidas Alcohólicas'
WHERE id IN (
    456,  -- Fernet Branca
    789,  -- Vodka Smirnoff
    1023, -- Ron Havana
    -- ... agregar más IDs de la lista
)
AND activo = 1;
```

### Paso 3: Verificar
```sql
-- Ver productos actualizados
SELECT id, nombre, categoria 
FROM productos 
WHERE id IN (456, 789, 1023);
```

**Expected:**
```
ID: 456 | Fernet Branca 750ml | Bebidas Alcohólicas ✅
ID: 789 | Vodka Smirnoff 1L   | Bebidas Alcohólicas ✅
ID: 1023| Ron Havana Club 700ml| Bebidas Alcohólicas ✅
```

---

## 📋 PLANTILLA DE SEGUIMIENTO

**Usar esta tabla para trackear progreso:**

| ✅ | ID  | Nombre Producto      | Cat. Original | Cat. Nueva          | Notas |
|----|-----|----------------------|---------------|---------------------|-------|
| ✅ | 456 | Fernet Branca 750ml  | Bebidas       | Bebidas Alcohólicas | OK    |
| ✅ | 789 | Vodka Smirnoff 1L    | NULL          | Bebidas Alcohólicas | OK    |
| ⏳ | 1023| Ron Havana Club      | Licores       | Bebidas Alcohólicas | Pdte  |
| ⏳ | 1156| Cerveza Brahma 1L    | Bebidas       | Bebidas Alcohólicas | Pdte  |

---

## 🧪 VALIDACIÓN DESPUÉS DE CORREGIR

### Test 1: Re-ejecutar Query de Checklist
```sql
-- Debe mostrar solo "✅ OK"
SELECT 
    id, nombre, categoria,
    CASE WHEN categoria LIKE '%alcoh%' THEN '✅ OK' ELSE '❌' END as estado
FROM productos
WHERE id IN (456, 789, 1023, 1156);  -- IDs corregidos
```

### Test 2: Probar en POS (DEV)
```
1. Ir a: http://localhost:3000/pos?__sim=2025-10-25T19:30:00
   (Simula viernes 19:30)

2. Buscar productos corregidos:
   🔍 "Fernet Branca"

3. Verificar badge:
   Fernet Branca 750ml
   $3,500 [tachado]
   $3,850 [naranja]  [+10%]  ✅
```

### Test 3: Verificar en Producción (Viernes Real)
```
Esperar a viernes 18:00+ real
  ↓
Ir a POS
  ↓
Buscar productos corregidos
  ↓
Verificar ajuste de precio
```

---

## ⚠️ ERRORES COMUNES Y SOLUCIONES

### Error 1: "Producto no se actualiza"
**Causa:** Campo bloqueado o sin permisos.  
**Solución:** Verificar rol de usuario (debe ser admin).

### Error 2: "Categoría vuelve a cambiar sola"
**Causa:** Algún script/importación sobrescribe datos.  
**Solución:** Revisar si hay importaciones automáticas activas.

### Error 3: "Badge no aparece en POS"
**Causa:** Cache no actualizado.  
**Solución:**
```
1. Backend: Invalidar cache de productos
   POST /api/cache/invalidate_productos.php

2. Frontend: Refrescar (F5) o Ctrl+Shift+R
```

### Error 4: "Algunos productos sí, otros no"
**Causa:** Typo en categoría ("Bebida Alcohólica" sin 's').  
**Solución:** Verificar spelling exacto: **"Bebidas Alcohólicas"**.

---

## 📊 CHECKLIST DE FINALIZACIÓN

Después de normalizar todos los productos:

- [ ] Re-ejecutar query de verificación (0 productos ≠ "OK")
- [ ] Test en DEV con `?__sim=...` (ver badge +10%)
- [ ] Esperar horario real (vie/sáb 18:00+) y verificar
- [ ] Documentar IDs corregidos en tabla de seguimiento
- [ ] Archivar backup de BD (si se hizo UPDATE masivo)

---

## 🎯 TEXTO CANÓNICO (COPIAR/PEGAR)

**Usar siempre este texto exacto:**
```
Bebidas Alcohólicas
```

**Variantes que también funcionan (pero mejor usar la canónica):**
- "Bebidas alcohólicas" (sin mayúscula)
- "BEBIDAS ALCOHOLICAS" (sin tilde)
- "Bebidas  Alcohólicas" (espacios extras)

**Todas "slugifican" a:** `bebidas-alcoholicas` ✅

---

## 📞 SOPORTE

**Si algo no funciona:**
1. Verificar en `api/logs/pricing_adjustments.log`
2. Revisar que `pricing_config.php` tenga `enabled => true`
3. Confirmar que endpoint devuelve `categoria` en JSON

**Contacto:**
- Docs completas: `/docs/pricing/alcoholic_mapping_audit.md`
- Testing: `DYNAMIC_PRICING_TESTING.md`

---

## ⏱️ TIEMPO ESTIMADO TOTAL

| Cantidad de productos | Tiempo de normalización |
|-----------------------|-------------------------|
| 5 productos           | 5 minutos               |
| 10 productos          | 10-15 minutos           |
| 20 productos          | 20-30 minutos           |
| 50 productos          | 1 hora (o UPDATE masivo)|
| 100+ productos        | UPDATE masivo recomendado|

---

✅ **Guía completa → Listo para normalizar productos → Test y validación incluidos**

**Siguiente paso:** Ejecutar checklist → Normalizar → Validar → ¡Listo! 🚀

