# 📁 DYNAMIC PRICING SYSTEM - DOCUMENTATION

**Sistema:** Tayrona Almacén – Kiosco POS  
**Feature:** Time-Based Dynamic Pricing  
**Version:** 1.0  
**Last Updated:** 21/10/2025

---

## ✅ ESTADO ACTUAL: NORMALIZACIÓN COMPLETADA

**Fecha de ejecución:** 21/10/2025  
**Estado:** ✅ **COMPLETADO Y VERIFICADO**  
**Productos actualizados:** 80 productos con categoría "Bebidas Alcohólicas"  
**Cobertura alcanzada:** 92.0%  
**Tiempo de ejecución:** 15 minutos

👉 **Ver reporte completo de ejecución:** [`NORMALIZACION_EJECUTADA_EXITOSAMENTE.md`](./NORMALIZACION_EJECUTADA_EXITOSAMENTE.md)

**Próximo paso:** Validar en POS con simulador de horario.

---

## 📚 DOCUMENTATION INDEX

### 🔍 Audit & Analysis
| File | Purpose | Audience |
|------|---------|----------|
| **[alcoholic_mapping_audit.md](./alcoholic_mapping_audit.md)** | Full technical audit of product categorization | Developers, Tech Lead |
| **[AUDIT_SUMMARY_EXECUTIVE.md](./AUDIT_SUMMARY_EXECUTIVE.md)** | Executive summary with key findings | Management, Non-technical |
| **[audit_queries.sql](./audit_queries.sql)** | Ready-to-use SQL queries for audit | Database Admin, Developers |

### 🎯 Action & Implementation
| File | Purpose | Audience |
|------|---------|----------|
| **[NORMALIZACION_EJECUTADA_EXITOSAMENTE.md](./NORMALIZACION_EJECUTADA_EXITOSAMENTE.md)** | ✅ **Reporte final de ejecución** (NUEVO) | Everyone |
| **[NORMALIZACION_RESUMEN_FINAL.md](./NORMALIZACION_RESUMEN_FINAL.md)** | Executive summary with metrics | Management |
| **[NORMALIZACION_CHANGELOG.md](./NORMALIZACION_CHANGELOG.md)** | Detailed execution log | Developers, Operations |
| **[NORMALIZACION_PROGRESS.csv](./NORMALIZACION_PROGRESS.csv)** | Product-by-product tracking | Operations |
| ~~**[QUICK_START_NORMALIZACION.md](./QUICK_START_NORMALIZACION.md)**~~ | ⚡ 15-min quick start (COMPLETADO) | Everyone |
| **[CHECKLIST_EXPRESS_2MIN.md](./CHECKLIST_EXPRESS_2MIN.md)** | 2-min SQL checklist to detect issues | Database Admin, Operations |
| **[MINI_GUIA_NORMALIZACION_UI.md](./MINI_GUIA_NORMALIZACION_UI.md)** | Step-by-step UI correction guide | Operations, Product Team |
| **[ACTION_PLAN.md](./ACTION_PLAN.md)** | Complete 5-phase plan (detailed) | Operations, Product Team |

### 🔙 Parent Documentation
| File | Purpose |
|------|---------|
| **[/docs/DYNAMIC_PRICING_SYSTEM.md](../DYNAMIC_PRICING_SYSTEM.md)** | Complete technical documentation |
| **[/DYNAMIC_PRICING_QUICK_START.md](../../DYNAMIC_PRICING_QUICK_START.md)** | Quick start guide |
| **[/DYNAMIC_PRICING_FRONTEND_CONFIG.md](../../DYNAMIC_PRICING_FRONTEND_CONFIG.md)** | Frontend configuration guide |

---

## 🚀 QUICK START

### ⚡ If you need to fix categorization NOW (start here):
1. Open **[QUICK_START_NORMALIZACION.md](./QUICK_START_NORMALIZACION.md)** (15 min total)
2. Run query from **[CHECKLIST_EXPRESS_2MIN.md](./CHECKLIST_EXPRESS_2MIN.md)** (2 min)
3. Follow **[MINI_GUIA_NORMALIZACION_UI.md](./MINI_GUIA_NORMALIZACION_UI.md)** (10-30 min)

### If you want to understand the system:
1. Read **[AUDIT_SUMMARY_EXECUTIVE.md](./AUDIT_SUMMARY_EXECUTIVE.md)** (5 min)
2. Read **[alcoholic_mapping_audit.md](./alcoholic_mapping_audit.md)** (15 min)

### If you need a detailed plan:
1. Open **[ACTION_PLAN.md](./ACTION_PLAN.md)** (complete 5-phase plan)
2. Run queries from **[audit_queries.sql](./audit_queries.sql)**

### If you're a developer:
1. Read **[alcoholic_mapping_audit.md](./alcoholic_mapping_audit.md)** (full technical details)
2. Review `api/pricing_engine.php` (slugify logic)
3. Review `api/pricing_config.php` (rules configuration)

---

## 🎯 KEY FINDINGS SUMMARY

### How it Works
```
Product Category (DB) → slugify() → Match Rules → Apply Adjustment
```

### What Matches
✅ "Bebidas Alcohólicas" → `"bebidas-alcoholicas"` → +10% (Fri/Sat 18:00+)

### What Doesn't Match
❌ "Bebidas" (wrong category)  
❌ "Licores" (different category)  
❌ NULL or "" (no category)

### Current Status
- ✅ System is operational
- ⚠️ Data quality needs improvement (70-85% coverage)
- 🎯 Target: 95%+ coverage after corrections

---

## 📊 AUDIT RESULTS (Estimated)

| Metric | Current | Target |
|--------|---------|--------|
| Products with valid category | 80-90% | > 95% |
| Alcoholic products correctly categorized | 70-85% | > 95% |
| False positives | 5-10% | < 2% |

---

## 🔧 FILES IN THIS FOLDER

```
docs/pricing/
├── README.md                           ← YOU ARE HERE
│
├── ⚡ PRACTICAL TOOLS (START HERE)
│   ├── QUICK_START_NORMALIZACION.md         ← 15-min complete flow
│   ├── CHECKLIST_EXPRESS_2MIN.md            ← 2-min SQL checklist
│   ├── MINI_GUIA_NORMALIZACION_UI.md        ← UI step-by-step guide
│   ├── MINI_GUIA_CORRECCION_UI.md           ← UI correction guide (detailed)
│   └── NORMALIZACION_PROPUESTA.csv          ← CSV with 87 products to correct
│
├── 📊 AUDIT & ANALYSIS
│   ├── alcoholic_mapping_audit.md      ← Full technical audit (5 pages)
│   ├── AUDIT_SUMMARY_EXECUTIVE.md      ← Executive summary (1 page)
│   ├── audit_queries.sql               ← SQL queries (11 queries)
│   ├── NORMALIZACION_DIAGNOSTICO.md    ← Normalization diagnostic report
│   └── NORMALIZACION_VALIDACION.md     ← Validation template (post-execution)
│
└── 🎯 DETAILED PLANNING
    ├── ACTION_PLAN.md                       ← Complete 5-phase plan
    └── NORMALIZACION_RESUMEN_FINAL.md       ← Final summary with metrics
```

---

## 🚨 IMPORTANT NOTES

### Data Quality
- System depends on **correct product categorization**
- String-based matching (no numeric IDs)
- `slugify()` function is robust but requires base data quality

### Security
- All pricing logic is **server-side only**
- Anti-tampering validation on checkout
- Logging enabled in `api/logs/pricing_adjustments.log`

### Maintenance
- Quarterly audit recommended
- Standard categories documented in ACTION_PLAN.md
- Dropdown validation recommended for future (not implemented yet)

---

## 📞 SUPPORT

**Questions?**
- Technical: See `docs/DYNAMIC_PRICING_SYSTEM.md`
- Business: See `AUDIT_SUMMARY_EXECUTIVE.md`
- Errors: Check `api/logs/pricing_adjustments.log`

**Testing:**
- Use `?__sim=YYYY-MM-DDTHH:mm:ss` to simulate time
- Example: `?__sim=2025-10-25T19:30:00` (Friday 7:30 PM)

---

## 🔄 VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-10-21 | Initial audit and documentation |

---

✅ **Audit Completed**  
⏳ **Action Plan Pending** (see ACTION_PLAN.md)  
🎯 **Target: 95%+ Coverage**

