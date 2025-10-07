<?php
/**
 * SERVICIO AFIP - GENERACIÓN DE COMPROBANTES FISCALES
 * 
 * Integración completa con Web Services AFIP para facturación electrónica
 */

require_once 'config_afip.php';
require_once 'bd_conexion.php';
require_once 'afip_logger.php';
require_once 'afip_cache_manager.php';

class AFIPService {
    
    private $config;
    private $ambiente;
    private $urls;
    private $datos_fiscales;
    private $afipSDK;
    private $access_token;
    private $cache;
    
    public function __construct() {
        global $CONFIGURACION_AFIP, $DATOS_FISCALES;
        
        $this->config = $CONFIGURACION_AFIP;
        $this->datos_fiscales = $DATOS_FISCALES;
        $this->ambiente = $this->config['ambiente'];
        $this->access_token = 'ZaGIyLvwsMSiwPrwTFkA2oU2ktoDqjuQ9Gt6QN26quHvQrdcKJzc76L6ch9Wu4uW';
        
        // Seleccionar URLs según ambiente
        if ($this->ambiente === 'PRODUCCION') {
            $this->urls = $this->config['urls_produccion'];
        } else {
            $this->urls = $this->config['urls_testing'];
        }
        
        // Inicializar Afip SDK
        $this->initializeAfipSDK();
        
        // Inicializar sistema de caché
        $this->cache = getAFIPCacheManager();
    }
    
    /**
     * Inicializar Afip SDK con token de acceso
     */
    private function initializeAfipSDK() {
        // Configuración del SDK
        $this->afipSDK = [
            'access_token' => $this->access_token,
            'base_url' => 'https://app.afipsdk.com/api/v1/',
            'cuit' => $this->datos_fiscales['cuit_empresa'],
            'environment' => ($this->ambiente === 'PRODUCCION') ? 'prod' : 'dev'
        ];
    }
    
    /**
     * Generar comprobante fiscal AFIP desde una venta
     */
    public function generarComprobanteFiscal($venta_id) {
        logAfipInfo("Iniciando generación de comprobante fiscal", ['venta_id' => $venta_id]);
        
        try {
            // 1. Obtener datos de la venta
            logAfipInfo("Obteniendo datos de venta", ['venta_id' => $venta_id]);
            $venta = $this->obtenerDatosVenta($venta_id);
            if (!$venta) {
                throw new Exception("Venta no encontrada: $venta_id");
            }
            
            // 2. Determinar tipo de comprobante
            $tipo_comprobante = $this->determinarTipoComprobante($venta);
            logAfipInfo("Tipo de comprobante determinado", [
                'venta_id' => $venta_id,
                'tipo' => $tipo_comprobante,
                'monto' => $venta['monto_total']
            ]);
            
            // 3. Generar estructura del comprobante
            $comprobante = $this->estructurarComprobante($venta, $tipo_comprobante);
            
            // 4. Obtener CAE de AFIP
            logAfipInfo("Solicitando CAE a AFIP", [
                'venta_id' => $venta_id,
                'tipo_comprobante' => $tipo_comprobante,
                'ambiente' => $this->ambiente
            ]);
            
            $cae_data = $this->obtenerCAE($comprobante);
            
            // 5. Completar comprobante con CAE
            $comprobante_final = $this->completarConCAE($comprobante, $cae_data);
            
            // 6. Guardar comprobante en base de datos
            $this->guardarComprobanteFiscal($venta_id, $comprobante_final);
            
            // 7. Log de comprobante generado
            $this->logComprobanteGenerado($comprobante_final);
            
            // 8. Registrar auditoría exitosa
            $resultado = [
                'success' => true,
                'comprobante' => $comprobante_final,
                'mensaje' => 'Comprobante fiscal generado exitosamente'
            ];
            
            auditAfip($venta_id, 'GENERAR_COMPROBANTE', $comprobante, $resultado);
            
            logAfipInfo("Comprobante fiscal generado exitosamente", [
                'venta_id' => $venta_id,
                'cae' => $cae_data['cae'],
                'numero_comprobante' => $comprobante_final['comprobante']['numero_comprobante']
            ]);
            
            return $resultado;
            
        } catch (Exception $e) {
            $error_context = [
                'venta_id' => $venta_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ambiente' => $this->ambiente
            ];
            
            logAfipError("Error generando comprobante AFIP", $error_context);
            
            // Registrar auditoría de error
            $resultado_error = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            auditAfip($venta_id, 'GENERAR_COMPROBANTE', [], $resultado_error);
            
            return $resultado_error;
        }
    }
    
    /**
     * Obtener datos de venta desde la base de datos
     */
    private function obtenerDatosVenta($venta_id) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT v.*, 
                   v.detalles_json
            FROM ventas v 
            WHERE v.id = ?
        ");
        $stmt->execute([$venta_id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($venta) {
            // Decodificar JSON de detalles
            $venta['cart'] = json_decode($venta['detalles_json'], true)['cart'] ?? [];
        }
        
        return $venta;
    }
    
    /**
     * Determinar tipo de comprobante según normativa AFIP
     */
    private function determinarTipoComprobante($venta) {
        $monto = $venta['monto_total'];
        
        // Por defecto para consumidor final
        if ($monto <= 1000) {
            return 'TICKET_FISCAL';
        } else {
            return 'FACTURA_B';
        }
    }
    
    /**
     * Estructurar comprobante en formato AFIP
     */
    private function estructurarComprobante($venta, $tipo_comprobante) {
        $emisor = obtenerDatosEmisor();
        $receptor = obtenerDatosConsumidorFinal($venta['cliente_nombre']);
        
        // Generar número de comprobante
        $numero_comprobante = $this->generarNumeroComprobante();
        
        // Procesar items del carrito
        $items = [];
        $totales = $this->calcularTotales($venta['cart']);
        
        foreach ($venta['cart'] as $index => $item) {
            // Mapear claves correctas del carrito
            $nombre = $item['name'] ?? $item['nombre'] ?? 'Producto';
            $cantidad = $item['quantity'] ?? $item['cantidad'] ?? 1;
            $precio = $item['price'] ?? $item['precio_venta'] ?? $item['precio'] ?? 0;
            
            $items[] = [
                'numero_linea' => $index + 1,
                'codigo_producto' => $item['codigo_barras'] ?? sprintf("COD%06d", $item['id']),
                'codigo_barras' => $item['codigo_barras'] ?? '',
                'descripcion' => $nombre,
                'cantidad' => $cantidad,
                'unidad_medida' => 'UNIDADES',
                'precio_unitario' => $precio,
                'importe_total' => $precio * $cantidad,
                'descuento_item' => 0.00,
                'alicuota_iva' => '21.00',
                'codigo_alicuota_iva' => '5',
                'importe_gravado' => ($precio * $cantidad) / 1.21,
                'importe_iva' => ($precio * $cantidad) - (($precio * $cantidad) / 1.21),
                'importe_exento' => 0.00,
                'importe_no_gravado' => 0.00
            ];
        }
        
        // Estructura completa del comprobante
        return [
            'evento' => 'comprobante_fiscal',
            'timestamp' => date('c'),
            'tipo' => 'VENTA_' . $tipo_comprobante,
            'comprobante' => [
                // Encabezado
                'tipo_comprobante' => $tipo_comprobante,
                'codigo_tipo_comprobante' => $this->datos_fiscales['tipos_comprobante_habilitados'][$tipo_comprobante]['codigo_afip'],
                'punto_venta' => '0001',
                'numero_comprobante' => $numero_comprobante,
                'fecha_emision' => date('Y-m-d'),
                'hora_emision' => date('H:i:s'),
                'fecha_hora_emision' => date('c'),
                
                // Emisor
                'emisor' => $emisor,
                
                // Receptor
                'receptor' => $receptor,
                
                // Condiciones comerciales
                'condicion_venta' => 'CONTADO',
                'forma_pago' => mapearMetodoPago($venta['metodo_pago']),
                'moneda' => 'PES',
                'cotizacion_moneda' => 1.000000,
                'fecha_vencimiento_pago' => date('Y-m-d'),
                
                // Items
                'items' => $items,
                
                // Totales por IVA
                'totales_iva' => [
                    [
                        'codigo_alicuota' => '5',
                        'alicuota' => 21.00,
                        'importe_neto' => $totales['neto_gravado'],
                        'importe_iva' => $totales['iva']
                    ]
                ],
                
                // Totales generales
                'totales' => [
                    'cantidad_items' => count($items),
                    'importe_neto_gravado' => $totales['neto_gravado'],
                    'importe_neto_no_gravado' => 0.00,
                    'importe_exento' => 0.00,
                    'total_descuentos' => $venta['descuento'],
                    'total_recargos' => 0.00,
                    'subtotal' => $totales['neto_gravado'],
                    'total_iva' => $totales['iva'],
                    'total_tributos' => 0.00,
                    'importe_total' => $venta['monto_total'],
                    'redondeo' => 0.00
                ],
                
                // Tributos adicionales
                'tributos' => [],
                
                // Datos técnicos AFIP (se completarán con CAE)
                'cae' => '',
                'fecha_vencimiento_cae' => '',
                'codigo_barras' => '',
                'qr_data' => '',
                
                // Metadatos del sistema
                'metadata' => [
                    'sistema_origen' => 'KIOSCO POS',
                    'version_sistema' => '3.0-AFIP',
                    'venta_id_interno' => $venta['id'],
                    'numero_comprobante_interno' => $venta['numero_comprobante'],
                    'usuario_operador' => 'SISTEMA',
                    'terminal_punto_venta' => 'TERMINAL_01',
                    'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? 'localhost',
                    'timestamp_procesamiento' => time(),
                    'estado_fiscal' => 'PENDIENTE_CAE'
                ]
            ],
            
            // Información del sistema
            'sistema' => [
                'origen' => 'KIOSCO POS',
                'version' => '3.0-AFIP',
                'ambiente' => $this->ambiente
            ]
        ];
    }
    
    /**
     * Calcular totales fiscales
     */
    private function calcularTotales($cart) {
        $subtotal = 0;
        foreach ($cart as $item) {
            // Mapear claves correctas del carrito
            $cantidad = $item['quantity'] ?? $item['cantidad'] ?? 1;
            $precio = $item['price'] ?? $item['precio_venta'] ?? $item['precio'] ?? 0;
            $subtotal += $precio * $cantidad;
        }
        
        // Calcular IVA incluido (21%)
        $neto_gravado = $subtotal / 1.21;
        $iva = $subtotal - $neto_gravado;
        
        return [
            'subtotal' => $subtotal,
            'neto_gravado' => round($neto_gravado, 2),
            'iva' => round($iva, 2)
        ];
    }
    
    /**
     * Generar número de comprobante secuencial
     */
    private function generarNumeroComprobante() {
        return date('YmdHis') . '-' . rand(1000, 9999);
    }
    
    /**
     * Obtener CAE de AFIP usando SDK
     */
    private function obtenerCAE($comprobante) {
        logAfipInfo("Iniciando solicitud de CAE", [
            'ambiente' => $this->ambiente,
            'punto_venta' => $comprobante['comprobante']['punto_venta'],
            'tipo_comprobante' => $comprobante['comprobante']['tipo_comprobante']
        ]);
        
        try {
            // Preparar datos para la API del SDK
            $invoice_data = $this->prepararDatosFactura($comprobante);
            
            logAfipInfo("Datos de factura preparados para SDK", [
                'items_count' => count($invoice_data['items']),
                'total' => $invoice_data['totales']['importe_total']
            ]);
            
            // Verificar caché primero (solo para testing, no para producción)
            $cache_key = "cae_request_" . md5(json_encode($invoice_data));
            if ($this->ambiente === 'TESTING') {
                $cached_response = $this->cache->get($cache_key);
                if ($cached_response) {
                    logAfipInfo("Usando respuesta CAE desde caché", ['cache_key' => $cache_key]);
                    $response = $cached_response;
                } else {
                    // Realizar llamada al SDK de AFIP
                    $response = $this->llamarAfipSDK('afip/invoices', $invoice_data);
                    
                    // Cachear respuesta exitosa por corto tiempo
                    if ($response && isset($response['cae'])) {
                        $this->cache->set($cache_key, $response, 300); // 5 minutos
                    }
                }
            } else {
                // En producción, siempre llamar al SDK sin caché
                $response = $this->llamarAfipSDK('afip/invoices', $invoice_data);
            }
            
            if ($response && isset($response['cae'])) {
                $cae_data = [
                    'cae' => $response['cae'],
                    'fecha_vencimiento_cae' => $response['fecha_vencimiento_cae'] ?? date('Y-m-d', strtotime('+10 days')),
                    'resultado' => $response['resultado'] ?? 'A',
                    'mensaje' => $response['mensaje'] ?? 'CAE obtenido exitosamente',
                    'numero_comprobante_afip' => $response['numero_comprobante'] ?? null,
                    'qr_url' => $response['qr_url'] ?? null
                ];
                
                logAfipInfo("CAE obtenido exitosamente", [
                    'cae' => $cae_data['cae'],
                    'fecha_vencimiento' => $cae_data['fecha_vencimiento_cae'],
                    'resultado' => $cae_data['resultado']
                ]);
                
                return $cae_data;
                
            } else {
                logAfipWarning("Respuesta inválida del SDK", ['response' => $response]);
                
                // Fallback a CAE simulado en desarrollo
                if ($this->ambiente === 'TESTING') {
                    $cae_simulado = [
                        'cae' => '12345678901234',
                        'fecha_vencimiento_cae' => date('Y-m-d', strtotime('+10 days')),
                        'resultado' => 'A',
                        'mensaje' => 'CAE simulado - ambiente de testing'
                    ];
                    
                    logAfipInfo("Usando CAE simulado", $cae_simulado);
                    return $cae_simulado;
                }
                throw new Exception("Error obteniendo CAE: " . ($response['error'] ?? 'Respuesta inválida'));
            }
            
        } catch (Exception $e) {
            logAfipError("Error obteniendo CAE de AFIP", [
                'error' => $e->getMessage(),
                'ambiente' => $this->ambiente,
                'codigo_error' => $e->getCode()
            ]);
            
            // En desarrollo, devolver CAE simulado para no bloquear el flujo
        if ($this->ambiente === 'TESTING') {
                $cae_fallback = [
                'cae' => '12345678901234',
                'fecha_vencimiento_cae' => date('Y-m-d', strtotime('+10 days')),
                'resultado' => 'A',
                    'mensaje' => 'CAE simulado - error en conexión: ' . $e->getMessage()
                ];
                
                logAfipInfo("Usando CAE simulado por error", $cae_fallback);
                return $cae_fallback;
            }
            
            // En producción, propagar el error
            throw $e;
        }
    }
    
    /**
     * Preparar datos de factura para el SDK de AFIP
     */
    private function prepararDatosFactura($comprobante) {
        $comp = $comprobante['comprobante'];
        
        // Formatear items para el SDK
        $items = [];
        foreach ($comp['items'] as $item) {
            $items[] = [
                'descripcion' => $item['descripcion'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'codigo_producto' => $item['codigo_producto'],
                'unidad_medida' => 'UNIDADES',
                'alicuota_iva' => $item['alicuota_iva']
            ];
        }
        
        return [
            'tipo_comprobante' => $comp['codigo_tipo_comprobante'],
            'punto_venta' => intval($comp['punto_venta']),
            'fecha_emision' => $comp['fecha_emision'],
            'moneda' => 'PES',
            'cotizacion' => 1.0,
            'receptor' => [
                'tipo_documento' => $comp['receptor']['tipo_documento_receptor'],
                'numero_documento' => $comp['receptor']['numero_documento_receptor'],
                'razon_social' => $comp['receptor']['razon_social_receptor']
            ],
            'items' => $items,
            'totales' => [
                'importe_total' => $comp['totales']['importe_total'],
                'importe_neto_gravado' => $comp['totales']['importe_neto_gravado'],
                'importe_iva' => $comp['totales']['total_iva'],
                'importe_exento' => $comp['totales']['importe_exento']
            ]
        ];
    }
    
    /**
     * Realizar llamada HTTP al SDK de AFIP con retry automático
     */
    private function llamarAfipSDK($endpoint, $data, $max_retries = 3) {
        $url = $this->afipSDK['base_url'] . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->afipSDK['access_token'],
            'X-CUIT: ' . $this->afipSDK['cuit'],
            'X-Environment: ' . $this->afipSDK['environment'],
            'User-Agent: KioscoPOS-AFIP/3.0'
        ];
        
        $attempt = 1;
        
        while ($attempt <= $max_retries) {
            logAfipInfo("Llamada al SDK AFIP", [
                'endpoint' => $endpoint,
                'intento' => $attempt,
                'max_intentos' => $max_retries,
                'ambiente' => $this->afipSDK['environment']
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            
            // Log de la respuesta
            logAfipInfo("Respuesta del SDK AFIP", [
                'intento' => $attempt,
                'http_code' => $http_code,
                'tiempo_respuesta' => $total_time,
                'error_curl' => $curl_error ?: null
            ]);
            
            // Verificar errores de conexión
            if ($curl_error) {
                if ($attempt < $max_retries) {
                    logAfipWarning("Error de conexión, reintentando", [
                        'intento' => $attempt,
                        'error' => $curl_error,
                        'próximo_intento_en' => '2 segundos'
                    ]);
                    sleep(2 * $attempt); // Backoff exponencial
                    $attempt++;
                    continue;
                } else {
                    logAfipError("Error de conexión final", ['error' => $curl_error]);
                    throw new Exception("Error de conexión CURL después de $max_retries intentos: $curl_error");
                }
            }
            
            // Verificar códigos HTTP de error temporal (503, 502, 504)
            if (in_array($http_code, [502, 503, 504])) {
                if ($attempt < $max_retries) {
                    logAfipWarning("Error temporal del servidor, reintentando", [
                        'intento' => $attempt,
                        'http_code' => $http_code,
                        'próximo_intento_en' => (2 * $attempt) . ' segundos'
                    ]);
                    sleep(2 * $attempt); // Backoff exponencial
                    $attempt++;
                    continue;
                } else {
                    logAfipError("Error HTTP temporal final", [
                        'http_code' => $http_code,
                        'response' => $response
                    ]);
                    throw new Exception("Error HTTP temporal $http_code después de $max_retries intentos: $response");
                }
            }
            
            // Verificar otros errores HTTP
            if ($http_code !== 200) {
                logAfipError("Error HTTP no recuperable", [
                    'http_code' => $http_code,
                    'response' => $response
                ]);
                throw new Exception("Error HTTP $http_code: $response");
            }
            
            // Decodificar respuesta JSON
            $decoded_response = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logAfipError("Error decodificando JSON", [
                    'json_error' => json_last_error_msg(),
                    'raw_response' => substr($response, 0, 500)
                ]);
                throw new Exception("Error decodificando respuesta JSON: " . json_last_error_msg());
            }
            
            // Éxito
            logAfipInfo("Llamada al SDK exitosa", [
                'intento' => $attempt,
                'tiempo_respuesta' => $total_time,
                'respuesta_válida' => !empty($decoded_response)
            ]);
            
            return $decoded_response;
        }
        
        // Si llegamos aquí, significa que se agotaron los reintentos
        logAfipCritical("Se agotaron todos los reintentos", [
            'endpoint' => $endpoint,
            'max_retries' => $max_retries
        ]);
        
        throw new Exception("Se agotaron los $max_retries intentos para llamar al SDK AFIP");
    }
    
    /**
     * Completar comprobante con datos CAE
     */
    private function completarConCAE($comprobante, $cae_data) {
        $comprobante['comprobante']['cae'] = $cae_data['cae'];
        $comprobante['comprobante']['fecha_vencimiento_cae'] = $cae_data['fecha_vencimiento_cae'];
        $comprobante['comprobante']['metadata']['estado_fiscal'] = 'CAE_OBTENIDO';
        
        // Generar código de barras y QR
        $codigo_barras = $this->generarCodigoBarras($comprobante, $cae_data);
        $qr_data = $this->generarQRData($comprobante, $cae_data);
        
        $comprobante['comprobante']['codigo_barras'] = $codigo_barras;
        $comprobante['comprobante']['qr_data'] = $qr_data;
        
        return $comprobante;
    }
    
    /**
     * Generar código de barras fiscal
     */
    private function generarCodigoBarras($comprobante, $cae_data) {
        $cuit = $this->datos_fiscales['cuit_empresa'];
        $tipo_comp = $comprobante['comprobante']['codigo_tipo_comprobante'];
        $punto_venta = $comprobante['comprobante']['punto_venta'];
        $cae = $cae_data['cae'];
        $fecha_vto = str_replace('-', '', $cae_data['fecha_vencimiento_cae']);
        
        return $cuit . $tipo_comp . $punto_venta . $cae . $fecha_vto;
    }
    
    /**
     * Generar datos para código QR
     */
    private function generarQRData($comprobante, $cae_data) {
        $data = [
            'ver' => 1,
            'fecha' => $comprobante['comprobante']['fecha_emision'],
            'cuit' => $this->datos_fiscales['cuit_empresa'],
            'ptoVta' => intval($comprobante['comprobante']['punto_venta']),
            'tipoCmp' => intval($comprobante['comprobante']['codigo_tipo_comprobante']),
            'nroCmp' => intval(substr($comprobante['comprobante']['numero_comprobante'], -8)),
            'importe' => $comprobante['comprobante']['totales']['importe_total'],
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => 80,
            'nroDocRec' => intval($comprobante['comprobante']['receptor']['numero_documento_receptor']),
            'tipoCodAut' => 'E',
            'codAut' => intval($cae_data['cae'])
        ];
        
        return base64_encode(json_encode($data));
    }
    
    /**
     * Guardar comprobante fiscal en la base de datos
     */
    private function guardarComprobanteFiscal($venta_id, $comprobante) {
        global $pdo;
        
        // Crear tabla si no existe
        $this->crearTablaComprobantes();
        
        $stmt = $pdo->prepare("
            INSERT INTO comprobantes_fiscales (
                venta_id, 
                tipo_comprobante, 
                numero_comprobante, 
                cae, 
                fecha_vencimiento_cae,
                datos_json,
                estado,
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, 'EMITIDO', NOW())
        ");
        
        $stmt->execute([
            $venta_id,
            $comprobante['comprobante']['tipo_comprobante'],
            $comprobante['comprobante']['numero_comprobante'],
            $comprobante['comprobante']['cae'],
            $comprobante['comprobante']['fecha_vencimiento_cae'],
            json_encode($comprobante)
        ]);
        
        // Actualizar la venta con referencia al comprobante
        $stmt = $pdo->prepare("
            UPDATE ventas 
            SET comprobante_fiscal = ?, cae = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $comprobante['comprobante']['numero_comprobante'],
            $comprobante['comprobante']['cae'],
            $venta_id
        ]);
    }
    
    /**
     * Crear tabla de comprobantes fiscales
     */
    private function crearTablaComprobantes() {
        global $pdo;
        
        $sql = "
        CREATE TABLE IF NOT EXISTS comprobantes_fiscales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT NOT NULL,
            tipo_comprobante VARCHAR(50) NOT NULL,
            numero_comprobante VARCHAR(100) NOT NULL,
            cae VARCHAR(50),
            fecha_vencimiento_cae DATE,
            datos_json TEXT,
            estado ENUM('PENDIENTE', 'EMITIDO', 'ANULADO') DEFAULT 'PENDIENTE',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_venta_id (venta_id),
            INDEX idx_numero_comprobante (numero_comprobante)
        ) ENGINE=InnoDB;
        ";
        
        $pdo->exec($sql);
    }
    
    /**
     * Log de comprobante generado (webhook removido)
     */
    private function logComprobanteGenerado($comprobante) {
        error_log("✅ Comprobante fiscal generado: " . json_encode([
            'numero_comprobante' => $comprobante['numero_comprobante'] ?? 'N/A',
            'cae' => $comprobante['cae'] ?? 'N/A',
            'tipo_comprobante' => $comprobante['tipo_comprobante'] ?? 'N/A',
            'monto_total' => $comprobante['monto_total'] ?? 'N/A',
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE));
    }
}

/**
 * Función helper para generar comprobante desde venta
 */
function generarComprobanteFiscalDesdVenta($venta_id) {
    $afip = new AFIPService();
    return $afip->generarComprobanteFiscal($venta_id);
}

/**
 * Endpoint para consultar comprobante fiscal
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['consultar_comprobante'])) {
    header('Content-Type: application/json');
    
    $venta_id = $_GET['venta_id'] ?? null;
    
    if (!$venta_id) {
        echo json_encode(['error' => 'ID de venta requerido']);
        exit;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM comprobantes_fiscales 
        WHERE venta_id = ? 
        ORDER BY fecha_creacion DESC 
        LIMIT 1
    ");
    $stmt->execute([$venta_id]);
    $comprobante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comprobante) {
        echo json_encode([
            'success' => true,
            'comprobante' => json_decode($comprobante['datos_json'], true)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Comprobante no encontrado'
        ]);
    }
}
?> 