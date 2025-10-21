# ⚡ QUICK START: NORMALIZACIÓN DE CATEGORÍAS (15 MINUTOS)

**Para:** Operadores, Product Managers, cualquier rol con acceso a Productos.  
**Requisito:** Acceso a MySQL + UI de Productos.  
**Objetivo:** Asegurar que TODOS los productos alcohólicos reciban ajuste de precio.

---

## 🎯 EL FLUJO COMPLETO EN 3 PASOS

```
1. DETECTAR    → Query SQL (2 min)
   ↓
2. NORMALIZAR  → Editar en UI (10-30 min según cantidad)
   ↓
3. VALIDAR     → Test en POS (2 min)
```

---

## 📋 PASO 1: DETECTAR (2 MINUTOS)

### Ejecutar Query

**Ir a:** phpMyAdmin → Seleccionar BD `kiosco_db` → SQL

**Copiar y pegar:**
```sql
SELECT 
    p.id,
    p.nombre,
    COALESCE(p.categoria, '❌ SIN CATEGORÍA') as categoria,
    CASE 
        WHEN p.categoria LIKE '%alcoh%' THEN '✅'
        ELSE '🔴'
    END as ok
FROM productos p
WHERE p.nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra)'
  AND p.activo = 1
  AND p.stock > 0
ORDER BY ok DESC;
```

### Resultado Esperado

```
ID  | Nombre                  | Categoría            | OK
----|-------------------------|----------------------|-----
123 | Cerveza Quilmes 1L      | Bebidas Alcohólicas  | ✅
456 | Fernet Branca 750ml     | Bebidas              | 🔴  ← CORREGIR
789 | Vodka Smirnoff 1L       | (sin categoría)      | 🔴  ← CORREGIR
```

### Anotar IDs con 🔴

**Ejemplo:**
```
IDs a corregir: 456, 789, 1023, 1156
```

---

## 🔧 PASO 2: NORMALIZAR (10-30 MIN)

### Para Cada ID de la Lista

1. **Abrir UI:**  
   `Dashboard → Productos`

2. **Buscar producto:**  
   🔍 `456` (escribir ID en buscador)

3. **Editar:**  
   Click en `📝 Editar`

4. **Cambiar categoría:**
   ```
   Categoría: [___________________]
              ↓
   Categoría: [Bebidas Alcohólicas]  ← Copiar/pegar
   ```

5. **Guardar:**  
   Click en `💾 Guardar`

6. **Verificar:**  
   Debe aparecer: `✅ Producto actualizado correctamente`

7. **Siguiente:**  
   Repetir para próximo ID

---

## ✅ PASO 3: VALIDAR (2 MIN)

### Test Rápido en POS (DEV)

1. **Abrir POS con simulador:**
   ```
   http://localhost:3000/pos?__sim=2025-10-25T19:30:00
   ```
   *(Simula viernes 19:30 para activar regla)*

2. **Buscar producto corregido:**
   ```
   🔍 "Fernet Branca"
   ```

3. **Verificar badge de ajuste:**
   ```
   Fernet Branca 750ml
   
   $3,500  [tachado gris]
   $3,850  [naranja]  [+10%]  ← DEBE APARECER ESTO
   ```

4. **Si aparece el badge:** ✅ Correcto
5. **Si NO aparece:** ⚠️ Revisar spelling de categoría

---

## 🎯 TEXTO CANÓNICO (COPIAR SIEMPRE)

```
Bebidas Alcohólicas
```

**⚠️ Con mayúsculas y tilde en la "o".**

---

## 📊 CONTEO RÁPIDO

**¿Cuántos productos hay que corregir?**

```sql
-- Query ultra-rápida de conteo
SELECT COUNT(*) as total_a_corregir
FROM productos
WHERE nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron)'
  AND activo = 1
  AND (categoria IS NULL OR categoria NOT LIKE '%alcoh%');
```

**Resultado:**
```
total_a_corregir: 12  ← Tiempo estimado: 12-20 minutos
```

---

## ⚠️ TROUBLESHOOTING EXPRESS

### Problema: "Badge no aparece en POS"

**Checklist:**
- [ ] Sistema activado: `pricing_config.php` → `enabled => true`
- [ ] Horario correcto: Vie/Sáb 18:00-23:59 (o usar `?__sim=...`)
- [ ] Categoría exacta: "Bebidas Alcohólicas" (con mayúscula y tilde)
- [ ] Cache limpio: Refrescar POS (Ctrl+Shift+R)

### Problema: "Algunos productos sí, otros no"

**Causa:** Typo en categoría.

**Solución:**
```sql
-- Ver variantes de categorías alcohólicas
SELECT DISTINCT categoria
FROM productos
WHERE categoria LIKE '%alcoh%' OR categoria LIKE '%bebida%';
```

**Si aparecen variantes:**
```
"Bebidas Alcohólicas"  ✅ Correcto
"Bebidas alcoholicas"  ⚠️ Sin mayúscula (funciona pero mejor unificar)
"Bebida Alcohólica"    ❌ Sin "s" final (NO funciona)
```

**Normalizar todas a:** `"Bebidas Alcohólicas"`

---

## 📁 DOCUMENTACIÓN COMPLETA

**Si necesitas más detalles:**

- **Checklist de 2 min:** `CHECKLIST_EXPRESS_2MIN.md`
- **Guía paso a paso:** `MINI_GUIA_NORMALIZACION_UI.md`
- **Audit completo:** `alcoholic_mapping_audit.md`
- **Action plan 5 fases:** `ACTION_PLAN.md`

---

## ✅ CHECKLIST FINAL

**Después de normalizar:**

- [ ] Re-ejecutar query de detección → debe mostrar solo ✅
- [ ] Test en DEV con `?__sim=...` → badge visible
- [ ] Esperar viernes real 18:00+ → verificar en producción
- [ ] Documentar IDs corregidos (para registro)

---

## 🎯 RESULTADO ESPERADO

### Antes
```
12 productos alcohólicos mal categorizados
→ NO reciben ajuste de precio
→ Pérdida de revenue
```

### Después
```
0 productos mal categorizados
→ TODOS reciben ajuste de +10% en horario premium
→ Revenue optimizado
```

---

## ⏱️ TIMELINE

```
09:00 - Ejecutar query SQL (2 min)
09:02 - Normalizar productos (10-30 min según cantidad)
09:32 - Test en DEV (2 min)
09:34 - ✅ DONE
```

**Viernes 18:00 - Validar en producción**

---

✅ **Quick Start completo → Listo para ejecutar → 15 minutos total**

**¿Listo? → Ejecutar query → Normalizar → Validar → ¡A producción! 🚀**

