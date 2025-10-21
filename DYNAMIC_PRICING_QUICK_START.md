# 💰 QUICK START: DYNAMIC PRICING

**5 minutos para activar precios dinámicos en tu POS**

---

## ⚡ ACTIVACIÓN RÁPIDA

### 1. Verificar que existe la categoría

En tu base de datos, verificar que exista una categoría que quieras ajustar. Por ejemplo:
- `bebidas-alcoholicas`
- `bebidas`
- `snacks`
- etc.

### 2. Editar configuración

Abrir `api/pricing_config.php`:

```php
return [
    'enabled' => true,  // ✅ ACTIVAR AQUÍ
    
    'rules' => [
        [
            'id'          => 'mi-primera-regla',
            'name'        => 'Mi primera regla',
            'enabled'     => true,
            
            'type'        => 'category',
            'category_slug' => 'TU-CATEGORIA',  // Cambiar por tu categoría
            
            'days'        => ['fri', 'sat'],  // Días (mon-sun)
            'from'        => '18:00',         // Desde las 18:00
            'to'          => '23:59',         // Hasta las 23:59
            'percent_inc' => 10.0,            // +10%
        ]
    ]
];
```

### 3. Guardar y probar

1. Guardar archivo
2. Abrir el POS en el navegador
3. Consultar productos
4. Verificar que el precio cambió (si estás en el horario configurado)

---

## 🎯 EJEMPLOS COMUNES

### Bebidas alcohólicas viernes y sábado

```php
'category_slug' => 'bebidas-alcoholicas',
'days' => ['fri', 'sat'],
'from' => '18:00',
'to' => '23:59',
'percent_inc' => 10.0,
```

### Happy Hour (descuento)

```php
'category_slug' => 'bebidas',
'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
'from' => '14:00',
'to' => '17:00',
'percent_inc' => -15.0,  // Negativo = descuento
```

### Horario nocturno (todos los días)

```php
'category_slug' => 'snacks',
'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
'from' => '20:00',
'to' => '23:59',
'percent_inc' => 5.0,
```

---

## 🔄 DESACTIVAR

```php
// En pricing_config.php
'enabled' => false,  // ✅ Cambiar a false
```

---

## 📊 VER LOGS

```
api/logs/pricing_adjustments.log
```

---

## 📚 DOCUMENTACIÓN COMPLETA

`docs/DYNAMIC_PRICING_SYSTEM.md`

---

**¡Listo!** Ya tenés precios dinámicos funcionando. 🚀

