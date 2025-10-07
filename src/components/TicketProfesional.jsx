import React, { useRef, useState } from 'react';
import { X, Printer } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import AFIPStatusIndicator from './AFIPStatusIndicator';

const TicketProfesional = ({ venta, onClose, show = true }) => {
    const { currentUser } = useAuth();
    const printRef = useRef();
    const [updatedVenta, setUpdatedVenta] = useState(venta);

    if (!show || !venta) return null;
    
    // Usar datos actualizados si están disponibles
    const currentVenta = updatedVenta || venta;

    // ========== 🧾 UTILIDADES FISCALES AFIP ==========
    
    // Generar imagen QR desde datos base64
    const generarImagenQR = (qrData) => {
        if (!qrData) return null;
        
        try {
            // Crear URL QR usando una API de QR codes
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrData)}`;
            return qrUrl;
        } catch (error) {
            console.error('Error generando QR:', error);
            return null;
        }
    };

    // Verificar si la venta tiene comprobante fiscal
    const tieneComprobanteFiscal = () => {
        // Verificar nueva estructura de datos fiscales
        if (currentVenta.datos_fiscales) {
            return currentVenta.datos_fiscales.cae && currentVenta.datos_fiscales.estado_fiscal === 'AUTORIZADO';
        }
        // Fallback a estructura anterior
        return currentVenta.comprobante_fiscal && currentVenta.comprobante_fiscal.cae;
    };
    
    // Verificar si hay facturación en proceso
    const tieneFacturacionEnProceso = () => {
        return currentVenta.datos_fiscales && 
               ['PROCESSING', 'QUEUE_ERROR', 'SYSTEM_ERROR'].includes(currentVenta.datos_fiscales.estado_fiscal);
    };

    // Obtener datos fiscales
    const obtenerDatosFiscales = () => {
        if (!tieneComprobanteFiscal()) return null;
        
        // Usar nueva estructura de datos fiscales
        if (currentVenta.datos_fiscales && currentVenta.datos_fiscales.estado_fiscal === 'AUTORIZADO') {
            const fiscal = currentVenta.datos_fiscales;
            return {
                cae: fiscal.cae,
                numero_comprobante_fiscal: fiscal.numero_comprobante_fiscal,
                tipo_comprobante: fiscal.tipo_comprobante,
                codigo_barras: fiscal.codigo_barras,
                qr_url: generarImagenQR(fiscal.qr_data),
                fecha_vencimiento_cae: fiscal.fecha_vencimiento_cae,
                punto_venta: fiscal.punto_venta
            };
        }
        
        // Fallback a estructura anterior
        if (currentVenta.comprobante_fiscal) {
            const fiscal = currentVenta.comprobante_fiscal;
            return {
                cae: fiscal.cae,
                numero_comprobante_fiscal: fiscal.numero_comprobante_fiscal,
                tipo_comprobante: fiscal.tipo_comprobante,
                codigo_barras: fiscal.codigo_barras,
                qr_url: generarImagenQR(fiscal.qr_data)
            };
        }
        
        return null;
    };
    
    // Callback para actualizar estado de facturación desde el indicador
    const handleStatusUpdate = (statusData) => {
        if (statusData.status === 'completed' && statusData.datos_fiscales) {
            // Actualizar venta con datos fiscales completados
            setUpdatedVenta({
                ...currentVenta,
                datos_fiscales: {
                    ...statusData.datos_fiscales,
                    estado_fiscal: 'AUTORIZADO'
                }
            });
        }
    };
    
    // ========== FIN UTILIDADES FISCALES ==========

    // Función para formatear moneda sin decimales innecesarios
    const formatCurrencyClean = (amount) => {
        if (!amount) return '$0';
        const num = parseFloat(amount);
        if (num % 1 === 0) {
            // Es un número entero, no mostrar decimales
            return `$${num.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
        } else {
            // Tiene decimales, mostrar 2 dígitos
            return `$${num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    };

    // Función para formatear moneda con 2 decimales obligatorios (para cálculos legales)
    const formatCurrencyPrecise = (amount) => {
        const num = parseFloat(amount) || 0;
        return `$${num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    // Generar número de comprobante alfanumérico corto
    const generarNumeroComprobante = () => {
        if (venta.numero_comprobante) return venta.numero_comprobante;
        
        const fecha = new Date(venta.fecha || new Date());
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = fecha.toLocaleDateString('es', { month: 'short' }).toUpperCase();
        const año = fecha.getFullYear().toString().slice(-2);
        const secuencia = (venta.venta_id || venta.id || Math.floor(Math.random() * 999) + 1).toString().padStart(3, '0');
        
        return `VTA-${dia}${mes}${año}${secuencia}`;
    };

    // Formatear fecha y hora según especificación
    const formatearFechaHora = () => {
        const fecha = new Date(venta.fecha || new Date());
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const año = fecha.getFullYear();
        const hora = fecha.getHours().toString().padStart(2, '0');
        const minutos = fecha.getMinutes().toString().padStart(2, '0');
        
        return `${dia}/${mes}/${año} ${hora}:${minutos} hs`;
    };

    // Obtener nombre del usuario actual
    const obtenerNombreUsuario = () => {
        return currentUser?.username || currentUser?.name || 'Administrador';
    };

    // Calcular totales según especificaciones
    const calcularTotales = () => {
        const subtotal = parseFloat(venta.subtotal || 0);
        const descuento = parseFloat(venta.discount || 0);
        const totalNeto = subtotal - descuento;
        
        // Cálculo correcto del IVA según Ley 27.743
        // IVA = (Precio Final × 21) / 121
        const iva = (totalNeto * 21) / 121;
        const otrosImpuestos = 0; // Default según especificación
        const totalAPagar = totalNeto; // El total ya incluye IVA
        
        return {
            subtotal,
            descuento,
            totalNeto,
            iva,
            otrosImpuestos,
            totalAPagar
        };
    };

    // Obtener método de pago formateado
    const obtenerMetodoPago = () => {
        const metodo = venta.paymentMethod || venta.metodo_pago;
        const metodos = {
            'efectivo': 'EFECTIVO',
            'tarjeta': 'TARJETA',
            'transferencia': 'TRANSFERENCIA',
            'mercadopago': 'MERCADO PAGO',
            'qr': 'QR'
        };
        return metodos[metodo] || metodo?.toUpperCase() || 'EFECTIVO';
    };

    // Manejar impresión
    const handlePrint = () => {
        const printWindow = window.open('', '_blank', 'width=300,height=600');
        
        const estilosImpresion = `
            <style>
                @page {
                    size: 58mm auto;
                    margin: 2mm;
                }
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 9px;
                    line-height: 1.1;
                    margin: 0;
                    padding: 1mm;
                    width: 54mm;
                }
                .center { text-align: center; }
                .left { text-align: left; }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                .separator { 
                    border-top: 1px dashed #000; 
                    margin: 2mm 0; 
                }
                .no-wrap { white-space: nowrap; }
                .line { 
                    display: flex; 
                    justify-content: space-between; 
                }
                .product-line {
                    display: block;
                    margin: 1px 0;
                }
                /* Estilos para sección fiscal */
                .fiscal-section {
                    text-align: center;
                    margin: 2mm 0;
                }
                .fiscal-section img {
                    max-width: 20mm;
                    max-height: 20mm;
                    margin: 1mm auto;
                    display: block;
                }
                .qr-label {
                    font-size: 7px;
                    font-weight: bold;
                    margin-bottom: 1mm;
                }
                .cae-info {
                    font-size: 8px;
                    font-family: monospace;
                    margin: 1mm 0;
                }
            </style>
        `;

        printWindow.document.write(`
            <html>
                <head>
                    <title>Ticket - ${generarNumeroComprobante()}</title>
                    ${estilosImpresion}
                </head>
                <body>
                    ${printRef.current.innerHTML}
                    <script>
                        window.onload = function() { 
                            window.print(); 
                            window.close(); 
                        }
                    </script>
                </body>
            </html>
        `);
        
        printWindow.document.close();
    };

    const totales = calcularTotales();
    const productos = currentVenta.cart || currentVenta.productos || [];
    const numeroComprobante = generarNumeroComprobante();

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-[350px] max-h-[90vh] overflow-y-auto">
                {/* Contenido del ticket */}
                <div ref={printRef} className="p-3" style={{ fontFamily: 'Courier New, monospace', fontSize: '9px', lineHeight: '1.1' }}>
                    
                    {/* ENCABEZADO */}
                    <div className="text-center mb-2">
                        <div className="text-sm font-bold">TAYRONA STORE</div>
                        <div>Paraguay 3809 - Palermo, CABA</div>
                        <div>CUIT/CUIL: 30-71885087-4</div>
                        <div>WhatsApp: 11 3313-8651</div>
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* CABECERA DEL COMPROBANTE */}
                    <div className="text-center bg-gray-100 py-1 px-2 font-bold text-xs border border-dashed border-black mb-2">
                        {tieneComprobanteFiscal() ? 
                            `COMPROBANTE FISCAL - ${obtenerDatosFiscales()?.tipo_comprobante || 'FACTURA'}` : 
                            'TICKET NO VÁLIDO COMO FACTURA'
                        }
                    </div>
                    
                    <div className="mb-2 text-xs">
                        <div>Comprobante #: <span className="font-bold">{numeroComprobante}</span></div>
                        <div>Fecha: {formatearFechaHora()}</div>
                        <div>Cliente: {venta.cliente?.name || venta.cliente_nombre || 'Consumidor Final'}</div>
                        <div>Atendido por: {obtenerNombreUsuario()}</div>
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* CUERPO DEL TICKET - PRODUCTOS */}
                    <div className="mb-2">
                        {/* Encabezado de tabla */}
                        <div className="text-xs font-bold mb-1">
                            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr 1fr', gap: '2px' }}>
                                <span>DESCRIPCIÓN</span>
                                <span className="text-center">CANT</span>
                                <span className="text-right">PRECIO</span>
                                <span className="text-right">IMPORTE</span>
                            </div>
                        </div>
                        
                        <div className="border-t border-gray-300 mb-1"></div>
                        
                        {/* Productos */}
                        {productos.map((item, index) => {
                            const esElegible = item.aplica_descuento_forma_pago !== false;
                            return (
                                <div key={index} className="text-xs mb-1">
                                    <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr 1fr', gap: '2px', alignItems: 'start' }}>
                                        <span className="truncate" title={item.name || item.nombre}>
                                            {!esElegible && '🔒 '}
                                            {(item.name || item.nombre || '').slice(0, esElegible ? 18 : 16)}
                                        </span>
                                        <span className="text-center">{item.quantity || item.cantidad}</span>
                                        <span className="text-right">{formatCurrencyClean(item.price || item.precio_unitario)}</span>
                                        <span className="text-right font-medium">
                                            {formatCurrencyClean((item.price || item.precio_unitario) * (item.quantity || item.cantidad))}
                                        </span>
                                    </div>
                                    {!esElegible && (
                                        <div className="text-xs text-gray-500 ml-1">
                                            (Exento de descuentos)
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* PIE DE TICKET - CÁLCULOS */}
                    <div className="text-xs space-y-1">
                        <div className="flex justify-between">
                            <span>SUBTOTAL:</span>
                            <span>{formatCurrencyClean(totales.subtotal)}</span>
                        </div>
                        
                        {/* 🎯 DESGLOSE DE DESCUENTOS MEJORADO */}
                        {venta.desglose_descuentos && venta.desglose_descuentos.descuento_aplicado > 0 && (
                            <>
                                {venta.desglose_descuentos.subtotal_elegible > 0 && (
                                    <div className="flex justify-between text-xs text-gray-600">
                                        <span>Subtotal c/descuento:</span>
                                        <span>{formatCurrencyClean(venta.desglose_descuentos.subtotal_elegible)}</span>
                                    </div>
                                )}
                                
                                {venta.desglose_descuentos.subtotal_exento > 0 && (
                                    <div className="flex justify-between text-xs text-gray-600">
                                        <span>Subtotal sin descuento:</span>
                                        <span>{formatCurrencyClean(venta.desglose_descuentos.subtotal_exento)}</span>
                                    </div>
                                )}
                                
                                <div className="flex justify-between text-green-600 font-bold">
                                    <span>🎉 DESCUENTO {Math.round(venta.desglose_descuentos.porcentaje_descuento)}% ({venta.paymentMethod?.toUpperCase() || venta.metodo_pago?.toUpperCase() || 'EFECTIVO'}):</span>
                                    <span>-{formatCurrencyClean(venta.desglose_descuentos.descuento_aplicado)}</span>
                                </div>
                            </>
                        )}
                        
                        {/* Fallback para descuentos tradicionales o datos del modal */}
                        {(!venta.desglose_descuentos || !venta.desglose_descuentos.descuento_aplicado) && (
                            <>
                                {/* Detectar descuento desde el nuevo modal */}
                                {venta.discount > 0 && (
                                    <div className="flex justify-between text-green-600 font-bold">
                                        <span>🎉 DESCUENTO ({Math.round((venta.discount / venta.subtotal) * 100)}%) ({venta.paymentMethod?.toUpperCase() || 'EFECTIVO'}):</span>
                                        <span>-{formatCurrencyClean(venta.discount)}</span>
                                    </div>
                                )}
                                
                                {/* Detectar descuento desde diferencia subtotal-total */}
                                {!venta.discount && totales.descuento > 0 && (
                                    <div className="flex justify-between text-green-600 font-bold">
                                        <span>🎉 DESCUENTO ({Math.round((totales.descuento / totales.subtotal) * 100)}%):</span>
                                        <span>-{formatCurrencyClean(totales.descuento)}</span>
                                    </div>
                                )}
                            </>
                        )}
                        
                        <div className="flex justify-between font-bold">
                            <span>TOTAL NETO:</span>
                            <span>{formatCurrencyClean(totales.totalNeto)}</span>
                        </div>
                        
                        <div className="border-t border-gray-300 pt-1 mt-1">
                            <div className="flex justify-between text-xs">
                                <span>IVA 21% incluido:</span>
                                <span>{formatCurrencyPrecise(totales.iva)}</span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span>Otros impuestos nac. indirectos:</span>
                                <span>{formatCurrencyPrecise(totales.otrosImpuestos)}</span>
                            </div>
                        </div>
                        
                        <div className="border-t border-black pt-1 mt-1">
                            <div className="flex justify-between font-bold text-sm">
                                <span>TOTAL A PAGAR:</span>
                                <span>{formatCurrencyClean(totales.totalAPagar)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* PAGO */}
                    <div className="text-xs space-y-1">
                        <div className="flex justify-between">
                            <span>FORMA DE PAGO:</span>
                            <span className="font-bold">{obtenerMetodoPago()}</span>
                        </div>
                        
                        <div className="flex justify-between">
                            <span>RECIBIDO:</span>
                            <span>{formatCurrencyClean(venta.cashReceived || venta.amountPaid || totales.totalAPagar)}</span>
                        </div>
                        
                        {(venta.changeDue || venta.change) > 0 && (
                            <div className="flex justify-between font-bold">
                                <span>CAMBIO:</span>
                                <span>{formatCurrencyClean(venta.changeDue || venta.change)}</span>
                            </div>
                        )}
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* INDICADOR DE ESTADO AFIP */}
                    {tieneFacturacionEnProceso() && (
                        <AFIPStatusIndicator 
                            venta={currentVenta} 
                            onStatusUpdate={handleStatusUpdate}
                        />
                    )}
                    
                    {/* INFORMACIÓN FISCAL AFIP */}
                    {tieneComprobanteFiscal() && (
                        <>
                            <div className="text-center text-xs font-bold mb-2 bg-green-50 p-2 border border-green-200">
                                ✅ COMPROBANTE AUTORIZADO POR AFIP
                            </div>
                            
                            {/* DATOS FISCALES OPTIMIZADOS - LAYOUT LATERAL COMPACTO */}
                            <div style={{
                                display: 'flex',
                                alignItems: 'flex-start',
                                gap: '12px',
                                margin: '8px 0',
                                padding: '6px 0'
                            }}>
                                {/* CÓDIGO QR AFIP */}
                                {obtenerDatosFiscales()?.qr_url && (
                                    <div style={{
                                        flexShrink: 0
                                    }}>
                                        <img 
                                            src={obtenerDatosFiscales()?.qr_url} 
                                            alt="QR AFIP" 
                                            style={{
                                                width: '60px',
                                                height: '60px',
                                                display: 'block'
                                            }}
                                        />
                                    </div>
                                )}
                                
                                {/* DATOS FISCALES EN TEXTO PLANO */}
                                <div style={{
                                    flex: 1,
                                    fontSize: '9px',
                                    lineHeight: '1.3',
                                    color: '#000000'
                                }}>
                                    <div style={{ marginBottom: '2px' }}>
                                        <strong>CAE:</strong> {obtenerDatosFiscales()?.cae || 'Pendiente'}
                                    </div>
                                    <div style={{ marginBottom: '2px' }}>
                                        <strong>Comprobante:</strong> {obtenerDatosFiscales()?.numero_comprobante_fiscal || 'N/A'}
                                    </div>
                                    <div style={{ marginBottom: '2px' }}>
                                        <strong>Tipo:</strong> {obtenerDatosFiscales()?.tipo_comprobante || 'FACTURA'}
                                    </div>
                                    <div style={{ marginBottom: '2px' }}>
                                        <strong>P. Venta:</strong> {obtenerDatosFiscales()?.punto_venta || '0001'}
                                    </div>
                                    {obtenerDatosFiscales()?.fecha_vencimiento_cae && (
                                        <div>
                                            <strong>Venc. CAE:</strong> {obtenerDatosFiscales()?.fecha_vencimiento_cae}
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            <div className="border-t border-dashed border-black my-2"></div>
                        </>
                    )}

                    {/* LEYENDA LEGAL */}
                    <div className="text-center text-xs font-bold mb-2">
                        * Régimen de Transparencia Fiscal al<br/>
                        Consumidor – Ley 27.743 *
                    </div>
                    
                    <div className="border-t border-dashed border-black my-2"></div>
                    
                    {/* CIERRE */}
                    <div className="text-center text-xs space-y-1">
                        <div className="font-bold">¡GRACIAS POR SU COMPRA!</div>
                        <div>Conserve este comprobante</div>
                        {tieneComprobanteFiscal() && (
                            <div className="text-green-600 font-bold">COMPROBANTE FISCAL VÁLIDO</div>
                        )}
                        <div className="text-gray-600 mt-2">Sistema: Tayrona POS v1.0</div>
                    </div>
                </div>
                
                {/* Botones de acción (solo en pantalla) */}
                <div className="p-4 border-t bg-gray-50 flex gap-3 no-print">
                    <button
                        onClick={handlePrint}
                        className="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 flex items-center justify-center gap-2"
                    >
                        <Printer size={16} />
                        Imprimir
                    </button>
                    <button
                        onClick={onClose}
                        className="flex-1 bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 flex items-center justify-center gap-2"
                    >
                        <X size={16} />
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
};

export default TicketProfesional; 