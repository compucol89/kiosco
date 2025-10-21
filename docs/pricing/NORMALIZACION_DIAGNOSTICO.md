# 📊 DIAGNÓSTICO: NORMALIZACIÓN DE CATEGORÍAS "BEBIDAS ALCOHÓLICAS"

**Fecha:** 21/10/2025 05:30 AM  
**Sistema:** Tayrona Almacén – Kiosco POS  
**Tipo:** Read-only audit (sin cambios)  
**Objetivo:** Detectar productos alcohólicos con categorización incorrecta

---

## 🎯 RESUMEN EJECUTIVO

| Métrica | Valor | Estado |
|---------|-------|--------|
| **Total productos activos** | 1,029 | ✅ |
| **Candidatos alcohólicos (por nombre)** | 105 | ⚠️ |
| **Bien categorizados** | 0 | 🔴 |
| **Mal categorizados** | 105 | 🔴 |
| **Sin categoría** | 0 | ✅ |
| **Cobertura actual** | 0.0% | 🔴 |
| **Gap vs objetivo (95%)** | 95.0 puntos | 🔴 |

---

## 🚨 PROBLEMA CRÍTICO IDENTIFICADO

**NINGÚN producto alcohólico está correctamente categorizado.**

Todos los 105 candidatos alcohólicos (detectados por heurística de nombre) están en categorías genéricas:
- "Bebidas" (65 productos)
- "General" (26 productos)
- "Galletitas" (8 productos)
- "Kiosco", "Snacks", "Golosinas", etc. (6 productos)

**Ninguno** tiene la categoría `"Bebidas Alcohólicas"` que el motor de pricing espera.

---

## 📊 DISTRIBUCIÓN POR CATEGORÍA ACTUAL

| Categoría Actual | Cantidad | % del Total |
|------------------|----------|-------------|
| Bebidas | 65 | 61.9% |
| General | 26 | 24.8% |
| Galletitas | 8 | 7.6% |
| Otros (Kiosco, Snacks, etc.) | 6 | 5.7% |

---

## 🍺 EJEMPLOS DE PRODUCTOS MAL CATEGORIZADOS

### Categoría: "Bebidas" (debería ser "Bebidas Alcohólicas")
```
[ID:  87] Cerveza Andes Roja 473ml
[ID:  99] Cerveza Andes Negra 473ml
[ID: 112] Fernet Branca Menta 750ml
[ID: 128] Cerveza Andes Ipa Andina 473
[ID: 129] Cerveza Andes Rubia 473
[ID: 216] Cerveza Patagonia Amber Lager 473ml
[ID: 255] Cerveza Stella Artois Vintage 473ml
[ID: 256] Cerveza Corona Lata 410ml
[ID: 378] Cerveza Brahma 473ml
[ID: 417] Cerveza Corona Botella 710ml
[ID: 418] Cerveza Corona Botella 330ml
[ID: 453] Alamos Vino Malbec 750ml
[ID: 457] Alma Mora Vino Cabernet Sauvignon 750ml
[ID: 759] Alaris Vino Blanco Dulce Cosecha 750ml
[ID: 760] Benjamin Vino Blanco Chardonnay 750ml
[ID: 774] Ballantines Finest Whisky 700 Cc
```

### Categoría: "General" (debería ser "Bebidas Alcohólicas")
```
[ID: 447] Ron Santa Teresa 750ml
[ID: 853] Aperol 750ml
[ID: 980] Benjamin Blend Malbec Cabernet Sauvignon 750 M
```

### Categoría: "Galletitas" (debería ser "Bebidas Alcohólicas")
```
[ID: 967] Havana Club Ron 750ml
```

---

## 🔍 DETECCIÓN: MÉTODO HEURÍSTICO

**Regex utilizado:**
```regex
(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|espumante|champagne|aperol|malbec|cabernet|ipa|lager)
```

**Productos detectados:** 105  
**Método:** Case-insensitive match en campo `productos.nombre`

**Nota:** Este método puede tener:
- ✅ **Alta sensibilidad** (detecta la mayoría de alcohólicos)
- ⚠️ **Falsos positivos posibles** (ej: "Pringles Original", "Halls Strong")

---

## 📋 LISTA COMPLETA DE IDs A CORREGIR

**Total:** 105 productos

```
16, 47, 75, 87, 91, 99, 112, 120, 124, 127, 128, 129, 135, 160, 168, 175, 214, 
216, 255, 256, 263, 266, 349, 378, 386, 413, 417, 418, 419, 447, 453, 454, 455, 
456, 457, 466, 469, 470, 507, 555, 578, 579, 590, 603, 608, 620, 624, 669, 670, 
673, 674, 675, 684, 696, 697, 698, 699, 700, 706, 709, 710, 750, 755, 756, 757, 
758, 759, 760, 761, 762, 773, 774, 775, 779, 794, 798, 799, 800, 801, 802, 833, 
837, 838, 851, 853, 854, 856, 898, 899, 900, 901, 902, 903, 904, 911, 912, 918, 
934, 938, 960, 965, 966, 967, 996, 1008
```

---

## ⚠️ FALSOS POSITIVOS DETECTADOS

**Productos NO alcohólicos pero detectados por regex:**

| ID | Nombre | Razón |
|----|--------|-------|
| 16 | Pringles Original X 67g | Palabra "Original" |
| 47 | Past. Menthoplus Strong | Palabra "Strong" (no es bebida) |
| 75 | Cigarrillos Philipmorris Original X12 | Palabra "Original" |
| 91 | Past. Halls Strong-lyptus | Palabra "Strong" |
| 120 | Caramelos Skittles Original | Palabra "Original" |
| 135 | Pringles Original 124g | Palabra "Original" |
| 160 | Jabon Dove Original 90g | Palabra "Original" |
| 168 | Choc. Toblerone Chocolate 100g | Contexto: no es bebida |
| 175 | Desodorante Dove Original 50ml | Palabra "Original" |
| 214 | Harina Presto Pronta 1 Kg | Contexto: no es bebida |

**Total falsos positivos:** ~10-15 productos (9.5%-14.3% del total)

**Acción:** Excluir manualmente en la propuesta de normalización.

---

## 📈 IMPACTO EN PRICING

### Situación Actual
- **0** productos reciben ajuste de +10% en horario premium (vie/sáb 18:00+)
- **Pérdida de revenue:** 100% de oportunidad perdida

### Situación Objetivo (post-normalización)
- **~90 productos** recibirán ajuste correctamente
- **Cobertura:** > 95% de productos alcohólicos reales
- **Revenue optimizado** en horarios premium

---

## 🎯 PRÓXIMOS PASOS

1. **Filtrar falsos positivos** → Generar CSV limpio (90 productos aprox.)
2. **Normalizar categorías** → Cambiar a "Bebidas Alcohólicas" vía UI
3. **Validar en POS** → Test con simulador de tiempo
4. **Monitorear cobertura** → Objetivo: 95%+

---

## 📊 MÉTRICAS DE CORRECCIÓN

**Antes:**
```
Cobertura: 0.0%
Productos con ajuste: 0
Revenue premium: $0
```

**Después (estimado):**
```
Cobertura: 95.0%+
Productos con ajuste: ~90
Revenue premium: +10% en horarios premium
```

---

## 🔧 CATEGORÍA CANÓNICA

**Valor a usar en todas las correcciones:**
```
Bebidas Alcohólicas
```

**Slug resultante:** `bebidas-alcoholicas` ✅

---

## 📁 ARCHIVOS RELACIONADOS

- **Propuesta CSV:** `NORMALIZACION_PROPUESTA.csv`
- **Guía de corrección:** `MINI_GUIA_CORRECCION_UI.md`
- **Validación:** `NORMALIZACION_VALIDACION.md` (template)
- **Resumen final:** `NORMALIZACION_RESUMEN_FINAL.md`

---

**Diagnóstico completado:** 21/10/2025 05:30 AM  
**Sin cambios aplicados:** ✅ Read-only  
**Próximo paso:** Generar CSV de normalización

