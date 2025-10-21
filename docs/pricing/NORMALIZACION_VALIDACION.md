# ✅ VALIDACIÓN: NORMALIZACIÓN DE CATEGORÍAS

**Fecha validación:** [COMPLETAR DESPUÉS DE EJECUTAR CORRECCIONES]  
**Responsable:** [NOMBRE]  
**Estado:** ⏳ PENDIENTE DE EJECUCIÓN

---

## 🎯 OBJETIVO

Validar que los 87 productos alcohólicos corregidos **reciben ajuste de precio dinámico** en horarios premium.

---

## 📋 PRE-REQUISITOS

- [ ] Las 87 correcciones de categoría están completadas
- [ ] Query de verificación muestra ~87 productos en "Bebidas Alcohólicas"
- [ ] Sistema de pricing dinámico está activado (`pricing_config.php` → `enabled => true`)

---

## 🧪 TEST 1: VALIDACIÓN EN BASE DE DATOS

### Query de Verificación
```sql
-- Productos alcohólicos correctamente categorizados
SELECT 
    id,
    nombre,
    categoria,
    precio_venta
FROM productos
WHERE categoria LIKE '%Alcoh%'
  AND activo = 1
ORDER BY id
LIMIT 20;
```

### Resultado Esperado
```
Total: ~87 productos
Categoría: "Bebidas Alcohólicas" (todos)
```

### Resultado Real
```
[COMPLETAR DESPUÉS DE EJECUTAR]

Total encontrado: ___
Categoría consistente: [ ] SÍ  [ ] NO
```

---

## 🧪 TEST 2: SIMULACIÓN EN POS (HORARIO REGLA ACTIVA)

### Configuración de Test
```
URL: http://localhost:3000/pos?__sim=2025-10-25T19:30:00
Simulación: Viernes 25/10/2025 a las 19:30
Regla aplicable: alcoholic-friday (+10%, 18:00-23:59)
```

### Productos de Prueba (Muestra de 10)

| ID | Nombre | Precio Base | Precio Esperado (+10%) | Badge Esperado |
|----|--------|-------------|------------------------|----------------|
| 87 | Cerveza Andes Roja 473ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 112 | Fernet Branca Menta 750ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 256 | Cerveza Corona Lata 410ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 447 | Ron Santa Teresa 750ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 773 | Smirnoff Vodka 700ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 774 | Ballantines Finest Whisky 700 Cc | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 853 | Aperol 750ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 898 | Rutini Vino Malbec 750ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 918 | Sidra Real 1024 Cc | [PRECIO] | [PRECIO × 1.10] | [+10%] |
| 967 | Havana Club Ron 750ml | [PRECIO] | [PRECIO × 1.10] | [+10%] |

### Instrucciones de Test

1. **Abrir POS con simulador:**
   ```
   http://localhost:3000/pos?__sim=2025-10-25T19:30:00
   ```

2. **Para cada producto de la tabla:**
   - Buscar por nombre en POS
   - Anotar precio mostrado
   - Verificar presencia de badge `[+10%]`
   - Verificar precio original tachado
   - Verificar precio ajustado en naranja

3. **Completar tabla con resultados reales:**

| ID | Precio Mostrado | Badge Visible | ✅/❌ |
|----|-----------------|---------------|-------|
| 87 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 112 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 256 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 447 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 773 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 774 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 853 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 898 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 918 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |
| 967 | [COMPLETAR] | [ ] SÍ [ ] NO | [ ] |

---

## 🧪 TEST 3: SIMULACIÓN FUERA DE HORARIO

### Configuración
```
URL: http://localhost:3000/pos?__sim=2025-10-26T11:00:00
Simulación: Domingo 26/10/2025 a las 11:00
Regla aplicable: NINGUNA (domingo no está en reglas)
```

### Resultado Esperado
```
Productos alcohólicos:
- Badge [+10%]: NO visible
- Precio: Base normal (sin ajuste)
- Precio tachado: NO visible
```

### Resultado Real
```
[COMPLETAR]

Badge visible: [ ] SÍ  [ ] NO
Precio ajustado: [ ] SÍ  [ ] NO

✅ Correcto si NO hay badge ni ajuste fuera de horario
```

---

## 🧪 TEST 4: LOGS DEL SISTEMA

### Revisar Logs de Pricing
```bash
# Windows
Get-Content api/logs/pricing_adjustments.log -Tail 50

# Linux/Mac
tail -50 api/logs/pricing_adjustments.log
```

### Buscar Entradas Como
```
[2025-10-25 19:30:00] PRICING: Aplicado +10% a producto ID 87 (Cerveza Andes Roja)
[2025-10-25 19:30:00] PRICING: Regla 'alcoholic-friday' aplicada
```

### Resultado
```
[COMPLETAR]

Logs encontrados: [ ] SÍ  [ ] NO
Productos con ajuste loggeados: ___
Errores encontrados: [ ] SÍ  [ ] NO
```

---

## 📊 MÉTRICAS DE VALIDACIÓN

### Cobertura Alcanzada

| Métrica | Antes | Después | Objetivo | ✅/❌ |
|---------|-------|---------|----------|-------|
| Productos alcohólicos categorizados | 0 | [__] | 87 | [ ] |
| % Cobertura | 0.0% | [__]% | 95%+ | [ ] |
| Productos con ajuste en horario | 0 | [__] | 87 | [ ] |
| Badge visible en POS | NO | [__] | SÍ | [ ] |

### Cálculo de Cobertura
```
Cobertura = (Productos corregidos / Total candidatos) × 100
Cobertura = ([__] / 87) × 100 = [__]%
```

---

## 🎯 CRITERIOS DE ACEPTACIÓN

**Para considerar validación EXITOSA:**

- [ ] ≥ 85 productos alcohólicos en categoría "Bebidas Alcohólicas"
- [ ] Badge `[+10%]` visible en POS (horario simulado viernes 18:00+)
- [ ] Precio ajustado correctamente (+10% sobre base)
- [ ] SIN badge fuera de horario (domingo 11:00)
- [ ] Logs de pricing sin errores críticos
- [ ] Cobertura ≥ 95% de productos alcohólicos reales

---

## ⚠️ PROBLEMAS ENCONTRADOS (SI APLICA)

### Problema 1: [TÍTULO]
**Descripción:** [DESCRIBIR]  
**Productos afectados:** [IDs]  
**Causa probable:** [ANÁLISIS]  
**Solución:** [ACCIÓN TOMADA]

### Problema 2: [TÍTULO]
**Descripción:** [DESCRIBIR]  
**Productos afectados:** [IDs]  
**Causa probable:** [ANÁLISIS]  
**Solución:** [ACCIÓN TOMADA]

---

## 📸 EVIDENCIAS (OPCIONAL)

### Screenshot 1: POS con Badge Visible
```
[ADJUNTAR CAPTURA]
Producto: Cerveza Andes Roja
Badge: [+10%] ✅
Precio original: $X tachado
Precio ajustado: $X×1.10 en naranja
```

### Screenshot 2: Lista de Productos Corregidos (phpMyAdmin)
```
[ADJUNTAR CAPTURA]
Query: SELECT * FROM productos WHERE categoria LIKE '%Alcoh%'
Total: 87 productos ✅
```

---

## ✅ VALIDACIÓN COMPLETADA

**Fecha:** [__/__/2025]  
**Responsable:** [NOMBRE]  
**Resultado:** [ ] EXITOSA  [ ] CON OBSERVACIONES  [ ] FALLIDA

**Observaciones:**
```
[COMPLETAR]
```

**Firma:** ___________________

---

## 📁 PRÓXIMO PASO

**Generar:** `NORMALIZACION_RESUMEN_FINAL.md` con métricas finales.

---

**Template de validación → Completar después de ejecutar correcciones** ⏳

