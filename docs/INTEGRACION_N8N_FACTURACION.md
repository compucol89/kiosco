# 🔗 Integración n8n para Facturación Automática

## 📋 **Descripción**

Este sistema permite que **n8n** consuma las ventas del POS y las procese con AFIP de forma **desacoplada** y **escalable**.

---

## 🎯 **Arquitectura del Sistema**

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   POS APP   │────────>│   API REST  │<────────│     n8n     │
│  (Frontend) │  venta  │  (Backend)  │  poll   │ (Automation)│
└─────────────┘         └─────────────┘         └──────┬──────┘
                              │                         │
                              │                         │
                              v                         v
                        ┌─────────────┐         ┌─────────────┐
                        │   MySQL     │         │  AFIP API   │
                        │  (ventas)   │         │ (WebService)│
                        └─────────────┘         └─────────────┘
```

### **Flujo:**
1. **Venta en POS** → Se guarda en BD sin CAE
2. **n8n** → Consulta ventas pendientes cada X minutos
3. **n8n** → Por cada venta, llama a AFIP y obtiene CAE
4. **n8n** → Actualiza la venta con el CAE en la BD
5. **POS** → Puede imprimir la factura con el CAE

---

## 🚀 **APIs Disponibles**

### **1. Obtener Ventas Pendientes de Facturación**

**Endpoint:**
```
GET /api/n8n_ventas_pendientes.php
```

**Parámetros opcionales:**
- `limite`: Cantidad de ventas a devolver (default: 50)
- `desde_id`: ID mínimo para paginación (default: 0)
- `fecha_desde`: Fecha mínima (default: últimos 7 días)

**Ejemplo de request:**
```bash
GET http://tu-servidor.com/api/n8n_ventas_pendientes.php?limite=10
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "total_pendientes": 25,
  "cantidad_devuelta": 10,
  "ventas": [
    {
      "id": 123,
      "cliente_nombre": "Consumidor Final",
      "fecha": "2025-10-12 19:30:00",
      "metodo_pago": "efectivo",
      "subtotal": "1000.00",
      "descuento": "100.00",
      "monto_total": "900.00",
      "numero_comprobante": "V20251012193000456",
      "tipo_comprobante": "ticket",
      "condicion_fiscal": "consumidor_final",
      "productos": [
        {
          "id": 45,
          "name": "Coca-Cola 500ml",
          "quantity": 2,
          "price": "500.00"
        }
      ]
    }
  ],
  "paginacion": {
    "limite": 10,
    "desde_id": 0,
    "proximo_desde_id": 132
  }
}
```

---

### **2. Marcar Venta como Facturada**

**Endpoint:**
```
POST /api/n8n_marcar_facturada.php
```

**Headers:**
```
Content-Type: application/json
```

**Body (ejemplo):**
```json
{
  "venta_id": 123,
  "cae": "75123456789012",
  "comprobante_fiscal": "Factura B 00001-00000123",
  "comprobante_numero": "00001-00000123",
  "vencimiento_cae": "2025-10-22",
  "punto_venta": 1,
  "numero_comprobante_afip": 123
}
```

**Campos requeridos:**
- `venta_id`: ID de la venta
- `cae`: Código de Autorización Electrónico de AFIP

**Campos opcionales:**
- `comprobante_fiscal`: Texto descriptivo del comprobante
- `comprobante_numero`: Número de comprobante (ej: 00001-00000123)
- `vencimiento_cae`: Fecha de vencimiento del CAE
- `punto_venta`: Punto de venta de AFIP
- `numero_comprobante_afip`: Número secuencial del comprobante

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Venta facturada correctamente",
  "venta_id": 123,
  "cae": "75123456789012",
  "comprobante_fiscal": "Factura B 00001-00000123",
  "fecha_actualizacion": "2025-10-12 19:35:00"
}
```

---

### **3. Obtener Info de una Venta Específica**

**Endpoint:**
```
GET /api/n8n_info_venta.php?id=123
```

**Respuesta:**
```json
{
  "success": true,
  "venta": {
    "id": 123,
    "cliente_nombre": "Consumidor Final",
    "monto_total": "900.00",
    "cae": "75123456789012",
    "comprobante_fiscal": "Factura B 00001-00000123",
    "estado_facturacion": {
      "facturada": true,
      "tiene_cae": true,
      "tiene_comprobante_fiscal": true
    },
    "productos": [...]
  }
}
```

---

## ⚙️ **Configuración de n8n**

### **Workflow Recomendado:**

```
┌─────────────────────┐
│  1. Trigger         │
│  (Schedule: cada    │
│   5 minutos)        │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  2. HTTP Request    │
│  GET /ventas_       │
│      pendientes     │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  3. Split In Batches│
│  (procesar de a 1)  │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  4. Function        │
│  (preparar datos    │
│   para AFIP)        │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  5. HTTP Request    │
│  POST a AFIP        │
│  (obtener CAE)      │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  6. HTTP Request    │
│  POST /marcar_      │
│       facturada     │
└─────────────────────┘
```

---

### **Configuración de Nodos:**

#### **Nodo 1: Schedule Trigger**
```json
{
  "mode": "everyX",
  "value": 5,
  "unit": "minutes"
}
```

#### **Nodo 2: HTTP Request (Ventas Pendientes)**
```json
{
  "method": "GET",
  "url": "https://tu-servidor.com/api/n8n_ventas_pendientes.php",
  "queryParameters": {
    "limite": 20
  },
  "responseFormat": "json"
}
```

#### **Nodo 3: Split In Batches**
```json
{
  "batchSize": 1,
  "inputFieldName": "ventas"
}
```

#### **Nodo 4: Function (Preparar datos AFIP)**
```javascript
// Ejemplo: transformar datos para AFIP
const venta = $input.item.json;

return {
  json: {
    venta_id: venta.id,
    importe_total: parseFloat(venta.monto_total),
    tipo_comprobante: venta.tipo_comprobante === 'ticket' ? 'B' : 'A',
    punto_venta: 1,
    concepto: 1, // Productos
    fecha_servicio_desde: venta.fecha.split(' ')[0],
    fecha_servicio_hasta: venta.fecha.split(' ')[0],
    fecha_vencimiento: venta.fecha.split(' ')[0],
    items: venta.productos.map(p => ({
      descripcion: p.name,
      cantidad: p.quantity,
      precio_unitario: p.price,
      importe_total: p.quantity * p.price
    }))
  }
};
```

#### **Nodo 5: HTTP Request (AFIP)**
```json
{
  "method": "POST",
  "url": "https://api-afip.com/comprobantes",
  "headers": {
    "Authorization": "Bearer {{$env.AFIP_TOKEN}}"
  },
  "body": "={{$json}}"
}
```

#### **Nodo 6: HTTP Request (Marcar Facturada)**
```json
{
  "method": "POST",
  "url": "https://tu-servidor.com/api/n8n_marcar_facturada.php",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "venta_id": "={{$node['Split In Batches'].json.id}}",
    "cae": "={{$node['HTTP Request AFIP'].json.cae}}",
    "comprobante_fiscal": "={{$node['HTTP Request AFIP'].json.comprobante}}",
    "vencimiento_cae": "={{$node['HTTP Request AFIP'].json.fecha_vencimiento}}"
  }
}
```

---

## 🔐 **Seguridad**

### **Recomendaciones:**

1. **Usar HTTPS** en producción
2. **Agregar autenticación** con API Key:
   - Agregar header `X-API-Key` en las APIs
   - Validar el key en cada endpoint
3. **Rate limiting** en n8n para evitar saturar AFIP
4. **Logs de auditoría** para trackear facturaciones

---

## 📊 **Monitoreo**

### **Queries útiles para debugging:**

```sql
-- Ventas pendientes de facturación
SELECT COUNT(*) as pendientes
FROM ventas
WHERE estado = 'completado'
AND (cae IS NULL OR cae = '');

-- Ventas facturadas hoy
SELECT COUNT(*) as facturadas_hoy
FROM ventas
WHERE cae IS NOT NULL
AND DATE(fecha) = CURDATE();

-- Ver última venta facturada
SELECT id, fecha, monto_total, cae, comprobante_fiscal
FROM ventas
WHERE cae IS NOT NULL
ORDER BY id DESC
LIMIT 1;
```

---

## 🚨 **Manejo de Errores**

### **En n8n:**

1. **Error de AFIP** → Reintenta 3 veces con delay de 30 segundos
2. **Error de API** → Log del error y continuar con la siguiente venta
3. **Timeout** → Aumentar el timeout a 60 segundos

### **Nodo Error Trigger:**
```json
{
  "continueOnFail": true,
  "retryOnFail": true,
  "maxTries": 3,
  "waitBetweenTries": 30
}
```

---

## ✅ **Testing**

### **1. Test local con cURL:**

```bash
# Obtener ventas pendientes
curl http://localhost/kiosco/api/n8n_ventas_pendientes.php

# Marcar como facturada
curl -X POST http://localhost/kiosco/api/n8n_marcar_facturada.php \
  -H "Content-Type: application/json" \
  -d '{
    "venta_id": 123,
    "cae": "TEST123456789",
    "comprobante_fiscal": "Test Factura"
  }'

# Ver info de venta
curl http://localhost/kiosco/api/n8n_info_venta.php?id=123
```

---

## 📈 **Ventajas de este Enfoque**

✅ **Desacoplado**: El POS no depende de AFIP
✅ **Escalable**: n8n puede procesar miles de ventas
✅ **Robusto**: Reintentos automáticos ante errores
✅ **Auditable**: Logs completos en n8n
✅ **Flexible**: Fácil de modificar sin tocar el POS
✅ **Mantenible**: La lógica de AFIP está en un solo lugar

---

## 🔄 **Próximos Pasos**

1. ✅ Crear workflow en n8n
2. ✅ Configurar credenciales de AFIP
3. ✅ Hacer pruebas en ambiente de desarrollo
4. ✅ Desplegar a producción
5. ✅ Monitorear ejecuciones

---

**¿Necesitas ayuda con la configuración de n8n?** Consulta la documentación oficial: https://docs.n8n.io

