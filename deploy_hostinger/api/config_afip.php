<?php
/**
 * CONFIGURACIÓN FISCAL AFIP/ARCA - KIOSCO POS
 * 
 * Datos fiscales y tributarios para comprobantes electrónicos
 */

// ========== DATOS DEL CONTRIBUYENTE/EMPRESA ==========
$DATOS_FISCALES = [
    // === IDENTIFICACIÓN FISCAL ===
    'cuit_empresa' => '20944515411', // ✅ CUIT REAL DE PRODUCCIÓN
    'razon_social' => 'HAROLD ZULUAGA', // ✅ RAZÓN SOCIAL REAL
    'nombre_fantasia' => 'Tayrona Group',
    'actividad_principal' => '477300', // Código AFIP: Venta al por menor en kioscos
    
    // === CONDICIÓN ANTE EL IVA ===
    'condicion_iva' => 'RESPONSABLE_MONOTRIBUTO', // ✅ MONOTRIBUTO - No discrimina IVA
    // 'RESPONSABLE_INSCRIPTO'    - Para empresas que facturan con IVA
    // 'RESPONSABLE_MONOTRIBUTO'  - Para monotributistas ✅ HAROLD ZULUAGA
    // 'EXENTO'                   - Para entidades exentas
    // 'NO_RESPONSABLE'           - Para pequeños contribuyentes
    
    // === DOMICILIO FISCAL ===
    'domicilio' => [
        'calle' => 'Paraguay',
        'numero' => '3809',
        'piso' => '',
        'departamento' => '',
        'localidad' => 'Ciudad Autónoma de Buenos Aires',
        'provincia' => 'Capital Federal',
        'codigo_postal' => 'C1425',
        'pais' => 'Argentina'
    ],
    
    // === PUNTOS DE VENTA AUTORIZADOS ===
    'puntos_venta' => [
        '0003' => [
            'numero' => '0003',
            'descripcion' => 'Terminal Principal',
            'tipo' => 'ELECTRONICO',
            'ubicacion' => 'Local Central'
        ]
        // Punto de venta 3 habilitado en AFIP
    ],
    
    // === CONFIGURACIÓN DE COMPROBANTES ===
    'tipos_comprobante_habilitados' => [
        'TICKET_FISCAL' => [
            'codigo_afip' => '83',
            'descripcion' => 'Comprobante de Venta del Exterior',
            'requiere_cae' => false,
            'usa_controlador_fiscal' => true
        ],
        'FACTURA_B' => [
            'codigo_afip' => '6',
            'descripcion' => 'Factura B',
            'requiere_cae' => true,
            'usa_controlador_fiscal' => false
        ],
        'FACTURA_C' => [
            'codigo_afip' => '11',
            'descripcion' => 'Factura C',
            'requiere_cae' => true,
            'usa_controlador_fiscal' => false
        ]
    ],
    
    // === CONFIGURACIÓN DE IVA ===
    'alicuotas_iva' => [
        '0' => [
            'codigo_afip' => '3',
            'porcentaje' => 0.00,
            'descripcion' => 'Exento'
        ],
        '10.5' => [
            'codigo_afip' => '4',
            'porcentaje' => 10.50,
            'descripcion' => 'IVA 10.5%'
        ],
        '21' => [
            'codigo_afip' => '5',
            'porcentaje' => 21.00,
            'descripcion' => 'IVA 21%'
        ],
        '27' => [
            'codigo_afip' => '6',
            'porcentaje' => 27.00,
            'descripcion' => 'IVA 27%'
        ]
    ]
];

// ========== CONFIGURACIÓN DE PRODUCTOS ==========
$CONFIGURACION_PRODUCTOS = [
    // === ALÍCUOTAS POR DEFECTO ===
    'iva_por_defecto' => '21', // 21% para la mayoría de productos de kiosco
    
    // === CATEGORÍAS DE PRODUCTOS ===
    'categorias_fiscales' => [
        'ALIMENTOS' => [
            'iva_default' => '21',
            'descripcion' => 'Alimentos y bebidas'
        ],
        'CIGARRILLOS' => [
            'iva_default' => '21',
            'descripcion' => 'Cigarrillos y tabaco',
            'impuestos_especiales' => ['IMPUESTO_TABACO']
        ],
        'MEDICAMENTOS' => [
            'iva_default' => '0',
            'descripcion' => 'Medicamentos (exentos)'
        ],
        'REVISTAS' => [
            'iva_default' => '0',
            'descripcion' => 'Libros y revistas (exentos)'
        ]
    ]
];

// ========== CONFIGURACIÓN AFIP WEB SERVICES ==========
$CONFIGURACION_AFIP = [
    // === AMBIENTE ===
    'ambiente' => 'PRODUCCION', // TESTING o PRODUCCION
    
    // === URLs DE SERVICIOS AFIP ===
    'urls_testing' => [
        'wsaa' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms',
        'wsfe' => 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx',
        'wsfex' => 'https://wswhomo.afip.gov.ar/wsfexv1/service.asmx'
    ],
    'urls_produccion' => [
        'wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms',
        'wsfe' => 'https://servicios1.afip.gov.ar/wsfev1/service.asmx',
        'wsfex' => 'https://servicios1.afip.gov.ar/wsfexv1/service.asmx'
    ],
    
    // === CERTIFICADOS ===
    'certificados' => [
        'archivo_certificado' => 'certificados/cert.pem',
        'archivo_clave_privada' => 'certificados/clave.key',
        'passphrase' => '', // Si la clave privada tiene contraseña
        'validez_ticket' => 2400 // Tiempo de validez del ticket en segundos
    ]
];

// ========== CONFIGURACIÓN DE CLIENTE CONSUMIDOR FINAL ==========
$CONSUMIDOR_FINAL = [
    'tipo_documento' => 'CUIT',
    'numero_documento' => '20999999999', // CUIT genérico para Consumidor Final
    'razon_social' => 'CONSUMIDOR FINAL',
    'condicion_iva' => 'CONSUMIDOR_FINAL',
    'domicilio' => 'CONSUMIDOR FINAL'
];

// ========== MAPEO DE MÉTODOS DE PAGO ==========
$METODOS_PAGO_AFIP = [
    'efectivo' => 'EFECTIVO',
    'tarjeta' => 'TARJETA_DEBITO',
    'transferencia' => 'TRANSFERENCIA_BANCARIA',
    'mercadopago' => 'BILLETERA_DIGITAL',
    'cheque' => 'CHEQUE',
    'cuenta_corriente' => 'CUENTA_CORRIENTE'
];

// ========== DATOS MERCADO PAGO - TAYRONA GROUP ==========
$DATOS_MERCADOPAGO = [
    'cvu' => '0000003100078171460356',
    'alias' => 'Paga86',
    'cuit_titular' => '30718850874',
    'razon_social_titular' => 'TAYRONA GROUP',
    'activo' => true,
    'comision_mp' => 0.0299 // 2.99% comisión estándar MP
];

/**
 * Obtener configuración fiscal para el emisor
 */
function obtenerDatosEmisor() {
    global $DATOS_FISCALES;
    
    if (!isset($DATOS_FISCALES) || !is_array($DATOS_FISCALES)) {
        // Valores HAROLD ZULUAGA si no está configurado correctamente
        return [
            'cuit_emisor' => '20944515411',
            'razon_social_emisor' => 'HAROLD ZULUAGA',
            'nombre_fantasia_emisor' => 'Harold Zuluaga',
            'domicilio_comercial' => 'Paraguay 3809',
            'localidad_emisor' => 'Ciudad Autónoma de Buenos Aires',
            'provincia_emisor' => 'Capital Federal',
            'codigo_postal_emisor' => 'C1425',
            'condicion_iva_emisor' => 'RESPONSABLE_INSCRIPTO',
            'actividad_economica' => '477300',
            'punto_venta' => '0003'
        ];
    }
    
    $domicilio = $DATOS_FISCALES['domicilio'] ?? [];
    
    return [
        'cuit_emisor' => $DATOS_FISCALES['cuit_empresa'] ?? '20944515411',
        'razon_social_emisor' => $DATOS_FISCALES['razon_social'] ?? 'HAROLD ZULUAGA',
        'nombre_fantasia_emisor' => $DATOS_FISCALES['nombre_fantasia'] ?? 'Harold Zuluaga',
        'domicilio_comercial' => implode(' ', array_filter([
            $domicilio['calle'] ?? 'Paraguay',
            $domicilio['numero'] ?? '3809',
            !empty($domicilio['piso']) ? 'Piso ' . $domicilio['piso'] : '',
            !empty($domicilio['departamento']) ? 'Dto ' . $domicilio['departamento'] : ''
        ])),
        'localidad_emisor' => $domicilio['localidad'] ?? 'Ciudad Autónoma de Buenos Aires',
        'provincia_emisor' => $domicilio['provincia'] ?? 'Capital Federal',
        'codigo_postal_emisor' => $domicilio['codigo_postal'] ?? 'C1425',
        'condicion_iva_emisor' => $DATOS_FISCALES['condicion_iva'] ?? 'RESPONSABLE_INSCRIPTO',
        'actividad_economica' => $DATOS_FISCALES['actividad_principal'] ?? '477300',
        'punto_venta' => '0001'
    ];
}

/**
 * Obtener datos del consumidor final
 */
function obtenerDatosConsumidorFinal($nombre_cliente = 'CONSUMIDOR FINAL') {
    global $CONSUMIDOR_FINAL;
    
    // Valores por defecto
    $defaults = [
        'tipo_documento' => 'CUIT',
        'numero_documento' => '20999999999',
        'razon_social' => 'CONSUMIDOR FINAL',
        'condicion_iva' => 'CONSUMIDOR_FINAL',
        'domicilio' => 'CONSUMIDOR FINAL'
    ];
    
    $config = isset($CONSUMIDOR_FINAL) && is_array($CONSUMIDOR_FINAL) ? $CONSUMIDOR_FINAL : $defaults;
    
    return [
        'tipo_documento_receptor' => $config['tipo_documento'] ?? $defaults['tipo_documento'],
        'numero_documento_receptor' => $config['numero_documento'] ?? $defaults['numero_documento'],
        'razon_social_receptor' => $nombre_cliente ?: ($config['razon_social'] ?? $defaults['razon_social']),
        'condicion_iva_receptor' => $config['condicion_iva'] ?? $defaults['condicion_iva'],
        'domicilio_receptor' => $config['domicilio'] ?? $defaults['domicilio']
    ];
}

/**
 * Mapear método de pago a formato AFIP
 */
function mapearMetodoPago($metodo_pago) {
    global $METODOS_PAGO_AFIP;
    return $METODOS_PAGO_AFIP[$metodo_pago] ?? 'EFECTIVO';
}

/**
 * Obtener alícuota de IVA por código
 */
function obtenerAlicuotaIVA($codigo_alicuota) {
    global $DATOS_FISCALES;
    return $DATOS_FISCALES['alicuotas_iva'][$codigo_alicuota] ?? $DATOS_FISCALES['alicuotas_iva']['21'];
}

/**
 * Determinar tipo de comprobante según cliente
 */
function determinarTipoComprobante($condicion_iva_cliente, $monto_total) {
    // Lógica para determinar qué tipo de comprobante emitir
    if ($condicion_iva_cliente === 'CONSUMIDOR_FINAL') {
        if ($monto_total <= 1000) {
            return 'TICKET_FISCAL';
        } else {
            return 'FACTURA_B';
        }
    } elseif ($condicion_iva_cliente === 'RESPONSABLE_INSCRIPTO') {
        return 'FACTURA_A';
    } else {
        return 'FACTURA_C';
    }
}

/**
 * Validar configuración fiscal
 */
function validarConfiguracionFiscal() {
    global $DATOS_FISCALES;
    
    $errores = [];
    
    // Validar CUIT REAL
    if ($DATOS_FISCALES['cuit_empresa'] !== '20944515411') {
        $errores[] = '⚠️ CUIT debe ser 20944515411 (HAROLD ZULUAGA)';
    }
    
    // Validar razón social
    if ($DATOS_FISCALES['razon_social'] !== 'HAROLD ZULUAGA') {
        $errores[] = '⚠️ Razón social debe ser HAROLD ZULUAGA';
    }
    
    // Validar condición IVA
    if ($DATOS_FISCALES['condicion_iva'] !== 'RESPONSABLE_INSCRIPTO') {
        $errores[] = '⚠️ HAROLD ZULUAGA debe ser Responsable Inscripto';
    }
    
    return $errores;
}

// === INSTRUCCIONES DE CONFIGURACIÓN ===
/*
📋 INSTRUCCIONES PARA CONFIGURAR DATOS FISCALES:

1. 🏢 DATOS DE LA EMPRESA:
   - Modificar 'cuit_empresa' con el CUIT real
   - Modificar 'razon_social' con la razón social real
   - Completar 'domicilio' con la dirección fiscal real

2. 🧾 CONDICIÓN ANTE EL IVA:
   - Si es Responsable Inscripto: 'RESPONSABLE_INSCRIPTO'
   - Si es Monotributista: 'RESPONSABLE_MONOTRIBUTO'
   - Si está exento: 'EXENTO'

3. 🏪 PUNTOS DE VENTA:
   - Configurar con los números autorizados por AFIP
   - Cada terminal debe tener su punto de venta

4. 🔐 CERTIFICADOS AFIP (para producción):
   - Generar certificado digital en AFIP
   - Subir archivos .crt y .key a carpeta 'certificados/'
   - Configurar rutas en 'certificados'

5. 🧪 AMBIENTE:
   - Usar 'TESTING' para pruebas
   - Cambiar a 'PRODUCCION' para comprobantes reales

⚠️ IMPORTANTE: Backup de este archivo antes de modificar
*/
?> 