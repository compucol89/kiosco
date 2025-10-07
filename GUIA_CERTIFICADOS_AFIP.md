# 🔐 GUÍA PARA OBTENER CERTIFICADOS AFIP

## 📋 PASOS PARA OBTENER CERTIFICADOS:

### **1. Generar Solicitud de Certificado (CSR)**

Ejecuta en tu computadora (Windows):
```bash
# Abrir PowerShell o Git Bash
openssl req -new -newkey rsa:2048 -nodes -keyout afip.key -out afip.csr -subj "/C=AR/O=HAROLD ZULUAGA/CN=20944515411/serialNumber=CUIT 20944515411"
```

Esto genera 2 archivos:
- `afip.key` (clave privada) - **GUARDAR MUY BIEN**
- `afip.csr` (solicitud de certificado)

---

### **2. Subir CSR a AFIP**

1. Entrar a: https://auth.afip.gob.ar/contribuyente
2. Login con Clave Fiscal
3. **Administrador de Relaciones de Clave Fiscal**
4. **Nueva Relación**
5. Buscar: **"Factura Electrónica" o "wsfe"**
6. **Generar Solicitud**
7. Pegar el contenido de `afip.csr`
8. Enviar

---

### **3. Descargar Certificado de AFIP**

1. AFIP procesará la solicitud
2. Descargar el certificado (.crt)
3. Guardar como `afip.crt`

---

### **4. Copiar Certificados al Sistema**

```bash
# Copiar archivos a:
C:\laragon\www\kiosco\api\certificados\

Archivos necesarios:
- afip.crt (certificado de AFIP)
- afip.key (clave privada que generaste)
```

---

### **5. Configurar en el Sistema**

Los archivos ya estarán en:
```
api/certificados/afip.crt
api/certificados/afip.key
```

El sistema los usará automáticamente.

---

## ⚠️ IMPORTANTE:

- **afip.key** es SECRETO - nunca compartir
- Los certificados vencen cada **1 año**
- Renovar antes del vencimiento
- Guardar backup de .key en lugar seguro

---

## 🚀 MIENTRAS TANTO:

El sistema funciona con **CAE simulado** para todas las ventas.
Cuando tengas los certificados, automáticamente usará AFIP real.

---

## 💡 ALTERNATIVA RÁPIDA:

Si ya los tienes en otro lugar, solo cópialos a:
```
C:\laragon\www\kiosco\api\certificados\
```






