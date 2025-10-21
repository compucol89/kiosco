# 🎯 ACTION PLAN: ALCOHOLIC BEVERAGES CATEGORIZATION

**Date:** 21/10/2025  
**Goal:** Ensure all alcoholic products are correctly categorized for dynamic pricing  
**Timeline:** 1 week  
**Risk:** MEDIUM → LOW (after completion)

---

## 📋 PHASE 1: DATA COLLECTION (Day 1)

### Step 1.1: Run SQL Audit Queries
**File:** `docs/pricing/audit_queries.sql`

```bash
# Abrir phpMyAdmin o cliente MySQL
# Ejecutar queries en este orden:

1. Query #11 → Métricas generales (baseline)
2. Query #2  → Productos alcohólicos y su estado
3. Query #3  → Productos mal categorizados (para corrección)
4. Query #4  → Falsos positivos (para corrección)
5. Query #5  → Productos sin categoría (prioridad alta)
```

**Output esperado:**
- CSV con IDs de productos a corregir
- Conteo de productos por estado
- Lista de categorías alternativas existentes

**Time estimate:** 30 minutos

---

### Step 1.2: Analizar Resultados

**Crear tabla de trabajo:**

| ID  | Nombre Producto      | Categoría Actual | Categoría Correcta      | Prioridad |
|-----|----------------------|------------------|-------------------------|-----------|
| 123 | Cerveza Quilmes 1L   | NULL             | Bebidas Alcohólicas     | ALTA      |
| 456 | Fernet Branca 750ml  | Bebidas          | Bebidas Alcohólicas     | ALTA      |
| 789 | Agua Villavicencio   | Bebidas Alcohólicas | Bebidas              | MEDIA     |

**Time estimate:** 1 hora

---

## 🔧 PHASE 2: MANUAL CORRECTION (Days 2-3)

### Step 2.1: Fix High Priority (Alcoholic products in wrong category)

**Via UI: Productos → Search → Edit**

1. Abrir lista de productos a corregir
2. Para cada producto:
   - Buscar por ID o nombre
   - Editar → Categoría: "Bebidas Alcohólicas"
   - Guardar
3. Marcar como completado en tabla de trabajo

**Batch approach (if many products):**
```sql
-- Opción alternativa: UPDATE directo (solo si tienes acceso DB)
-- CUIDADO: Hacer backup antes!

UPDATE productos
SET categoria = 'Bebidas Alcohólicas'
WHERE id IN (123, 456, 789, ...)  -- IDs de Query #3
  AND activo = 1;
```

**Time estimate:** 2-4 horas (dependiendo de cantidad)

---

### Step 2.2: Fix False Positives (Non-alcoholic in alcoholic category)

**Via UI: Productos → Search → Edit**

```
ID 789 | Agua Villavicencio  
Categoría actual: "Bebidas Alcohólicas"  
→ Cambiar a: "Bebidas"
```

**Time estimate:** 1-2 horas

---

### Step 2.3: Assign Categories to NULL Products

**Via UI: Productos → Filtrar "Sin Categoría"**

```
Para cada producto sin categoría:
- Revisar nombre/descripción
- Asignar categoría apropiada:
  - Cerveza/Vino/Fernet → "Bebidas Alcohólicas"
  - Gaseosa/Agua → "Bebidas"
  - Galletas/Snacks → "Almacén"
  - etc.
```

**Time estimate:** 2-3 horas

---

## ✅ PHASE 3: VALIDATION (Day 4)

### Step 3.1: Re-run Audit Queries

```sql
-- Ejecutar nuevamente Query #11 (métricas)
-- Comparar con baseline de Phase 1

Expected improvements:
✅ "Productos SIN categoría" → Cerca de 0
✅ "Alcohólicos MAL categorizados" → Cerca de 0
✅ "Alcohólicos BIEN categorizados" → Aumentado significativamente
```

**Time estimate:** 30 minutos

---

### Step 3.2: Test Dynamic Pricing

**Manual test via POS:**

1. Activar sistema de pricing (si está OFF)
2. Simular horario de regla: `?__sim=2025-10-25T19:30:00` (viernes 19:30)
3. Buscar productos alcohólicos corregidos
4. Verificar que muestren:
   - Badge `[+10%]`
   - Precio original tachado
   - Precio ajustado en naranja

**Expected:**
```
Cerveza Quilmes 1L
$1,000  [tachado]
$1,100  [naranja]  [+10%]  ✅
```

**Time estimate:** 30 minutos

---

### Step 3.3: Check Logs

**File:** `api/logs/pricing_adjustments.log`

```bash
# Buscar entradas recientes
tail -100 api/logs/pricing_adjustments.log

# Verificar:
✅ Productos ajustados correctamente
✅ Sin errores de matching
⚠️ Alertas de productos sin categoría (debería ser ~0)
```

**Time estimate:** 15 minutos

---

## 📊 PHASE 4: DOCUMENTATION (Day 5)

### Step 4.1: Create Standard Categories Document

**File:** `docs/categorias_estandar.md`

```markdown
# CATEGORÍAS ESTÁNDAR DE PRODUCTOS

## Bebidas
- **Bebidas Alcohólicas**: Cervezas, vinos, licores, fernet, whisky, vodka, etc.
- **Bebidas**: Gaseosas, aguas, jugos (sin alcohol)

## Almacén
- **Almacén**: Productos de despensa general
- **Snacks**: Galletitas, papas fritas, etc.

## Limpieza
- **Limpieza**: Detergentes, lavandinas, etc.

...
```

**Time estimate:** 1 hora

---

### Step 4.2: Update Team Documentation

**Agregar a docs de training:**

```markdown
## ⚠️ IMPORTANTE: Categorización de Productos

Al dar de alta productos alcohólicos:
1. Categoría DEBE ser: "Bebidas Alcohólicas" (con tilde, mayúscula)
2. Si no aparece en dropdown, escribir exactamente: "Bebidas Alcohólicas"
3. NO usar: "Bebidas", "Alcoholes", "Licores" para productos alcohólicos

Motivo: Sistema de precios dinámicos depende de esta categoría.
```

**Time estimate:** 30 minutos

---

## 🚀 PHASE 5: OPTIONAL IMPROVEMENTS (Days 6-7)

### Step 5.1: Add Alternative Category Rules

**If products exist in "Licores" or "Vinos" categories:**

Edit `api/pricing_config.php`:

```php
'rules' => [
    // ... existing rules ...
    
    // New rule for "Licores" category
    [
        'id' => 'licores-friday',
        'type' => 'category',
        'category_slug' => 'licores',
        'days' => ['fri'],
        'from' => '18:00',
        'to' => '23:59',
        'percent_inc' => 10.0,
    ],
    
    // New rule for "Vinos" category
    [
        'id' => 'vinos-friday',
        'type' => 'category',
        'category_slug' => 'vinos',
        'days' => ['fri'],
        'from' => '18:00',
        'to' => '23:59',
        'percent_inc' => 10.0,
    ],
],
```

**Time estimate:** 30 minutos

---

### Step 5.2: Add Logging for No-Matches (opcional)

**Edit `api/pricing_engine.php`** (avanzado):

```php
// En applyPricingRules(), al final:
if (!$ajusteAplicado && esProductoProbablementeAlcoholico($producto)) {
    error_log("⚠️ PRICING: Producto alcohólico sin match - ID: {$producto['id']}, Nombre: {$producto['nombre']}, Categoria: {$producto['categoria_slug']}");
}
```

**Time estimate:** 1 hora (incluye testing)

---

## 📈 SUCCESS METRICS

### Before (Baseline)
- Products without category: **~15-20%**
- Alcoholic products correctly categorized: **~70-75%**
- False positives: **~5-10%**

### After (Target)
- Products without category: **< 5%** ✅
- Alcoholic products correctly categorized: **> 95%** ✅
- False positives: **< 2%** ✅

### Business Impact
- **Revenue increase:** Correct pricing adjustments applied to 95%+ of alcoholic products
- **Data quality:** Improved categorization benefits other features (reports, analytics)
- **Maintenance:** Reduced manual corrections needed in future

---

## 🎯 ROLLBACK PLAN

**If issues arise during correction:**

```sql
-- 1. Restore from backup (if available)
RESTORE TABLE productos FROM backup_20251021;

-- 2. Or revert specific changes
UPDATE productos
SET categoria = '(previous_value)'  -- Needs manual tracking
WHERE id IN (...);

-- 3. Disable dynamic pricing temporarily
-- In pricing_config.php: enabled => false
```

---

## 📞 CONTACTS & RESOURCES

**Files:**
- `/docs/pricing/alcoholic_mapping_audit.md` (Full audit)
- `/docs/pricing/audit_queries.sql` (SQL queries)
- `api/pricing_config.php` (Rules configuration)
- `api/pricing_engine.php` (Pricing logic)

**Logs:**
- `api/logs/pricing_adjustments.log`
- `api/logs/error.log`

**Support:**
- Documentation: `docs/DYNAMIC_PRICING_SYSTEM.md`
- Testing: Use `?__sim=YYYY-MM-DDTHH:mm:ss` parameter

---

## ✅ COMPLETION CHECKLIST

**Phase 1: Data Collection**
- [ ] Run SQL queries
- [ ] Export results to CSV
- [ ] Create work table with IDs

**Phase 2: Corrections**
- [ ] Fix alcoholic products in wrong category
- [ ] Fix false positives
- [ ] Assign categories to NULL products

**Phase 3: Validation**
- [ ] Re-run audit queries (compare metrics)
- [ ] Test dynamic pricing in POS
- [ ] Check logs for errors

**Phase 4: Documentation**
- [ ] Create standard categories document
- [ ] Update team training docs

**Phase 5: Optional (if time allows)**
- [ ] Add rules for alternative categories
- [ ] Add no-match logging

---

**Expected Total Time:** 12-20 hours  
**Recommended Schedule:** 2-4 hours/day over 5-7 days  
**Risk After Completion:** LOW ✅

