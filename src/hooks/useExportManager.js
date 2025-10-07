import { useState, useCallback } from 'react';

/**
 * 📤 HOOK PARA GESTIÓN DE EXPORTACIONES
 * 
 * Características:
 * - Exportación PDF y Excel
 * - Templates personalizables
 * - Progress tracking
 * - Error handling
 */

const useExportManager = () => {
    const [isExporting, setIsExporting] = useState(false);
    const [exportProgress, setExportProgress] = useState(0);
    const [exportError, setExportError] = useState(null);

    // 📄 Exportar a PDF
    const exportToPDF = useCallback(async (data, options = {}) => {
        try {
            setIsExporting(true);
            setExportProgress(10);
            setExportError(null);

            const {
                filename = `reporte-ventas-${new Date().toISOString().split('T')[0]}.pdf`,
                template = 'standard',
                includeCharts = true,
                orientation = 'portrait'
            } = options;

            setExportProgress(30);

            // Preparar datos para el reporte
            const reportData = {
                ...data,
                metadata: {
                    generatedAt: new Date().toISOString(),
                    generatedBy: 'Sales Report Dashboard',
                    template,
                    orientation
                }
            };

            setExportProgress(50);

            // Simular llamada al backend para generar PDF
            const response = await fetch('/kiosco/api/export_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    format: 'pdf',
                    data: reportData,
                    options: {
                        filename,
                        template,
                        includeCharts,
                        orientation
                    }
                })
            });

            setExportProgress(80);

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            // Descargar el archivo
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            setExportProgress(100);

            return {
                success: true,
                filename,
                size: blob.size
            };

        } catch (error) {
            console.error('Error exportando PDF:', error);
            setExportError(error.message);
            return {
                success: false,
                error: error.message
            };
        } finally {
            setIsExporting(false);
            setTimeout(() => setExportProgress(0), 1000);
        }
    }, []);

    // 📊 Exportar a Excel
    const exportToExcel = useCallback(async (data, options = {}) => {
        try {
            setIsExporting(true);
            setExportProgress(10);
            setExportError(null);

            const {
                filename = `reporte-ventas-${new Date().toISOString().split('T')[0]}.xlsx`,
                includeMetrics = true,
                includeTransactions = true,
                includeCharts = false
            } = options;

            setExportProgress(30);

            // Preparar estructura para Excel
            const excelData = {
                sheets: {
                    ...(includeMetrics && {
                        'Métricas': prepareMetricsSheet(data)
                    }),
                    ...(includeTransactions && {
                        'Transacciones': prepareTransactionsSheet(data)
                    }),
                    'Métodos de Pago': preparePaymentMethodsSheet(data)
                },
                metadata: {
                    generatedAt: new Date().toISOString(),
                    generatedBy: 'Sales Report Dashboard'
                }
            };

            setExportProgress(60);

            // Simular llamada al backend para generar Excel
            const response = await fetch('/kiosco/api/export_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    format: 'excel',
                    data: excelData,
                    options: {
                        filename,
                        includeMetrics,
                        includeTransactions,
                        includeCharts
                    }
                })
            });

            setExportProgress(80);

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            // Descargar el archivo
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            setExportProgress(100);

            return {
                success: true,
                filename,
                size: blob.size
            };

        } catch (error) {
            console.error('Error exportando Excel:', error);
            setExportError(error.message);
            return {
                success: false,
                error: error.message
            };
        } finally {
            setIsExporting(false);
            setTimeout(() => setExportProgress(0), 1000);
        }
    }, []);

    // 📋 Preparar datos de métricas para Excel
    const prepareMetricsSheet = (data) => {
        if (!data?.ventas) return [];

        return [
            ['Métrica', 'Valor', 'Formato'],
            ['Total Ventas', data.ventas.ingresos_totales || 0, 'Moneda'],
            ['Cantidad Ventas', data.ventas.cantidad_ventas || 0, 'Número'],
            ['Ticket Promedio', data.ventas.ticket_promedio || 0, 'Moneda'],
            ['Productos Vendidos', data.ventasDetalladas?.reduce((sum, v) => sum + (v.cantidad || 0), 0) || 0, 'Número'],
            ['Crecimiento', data.ventas.crecimiento || 0, 'Porcentaje']
        ];
    };

    // 📋 Preparar datos de transacciones para Excel
    const prepareTransactionsSheet = (data) => {
        if (!data?.ventasDetalladas) return [];

        const headers = ['Fecha', 'Número', 'Cliente', 'Método Pago', 'Total', 'Productos'];
        const rows = data.ventasDetalladas.map(venta => [
            new Date(venta.fecha).toLocaleDateString('es-AR'),
            venta.numero_comprobante || `V${String(venta.id).padStart(4, '0')}`,
            venta.cliente_nombre || 'Consumidor Final',
            venta.metodo_pago || 'Efectivo',
            parseFloat(venta.total || 0),
            venta.cantidad || 0
        ]);

        return [headers, ...rows];
    };

    // 📋 Preparar datos de métodos de pago para Excel
    const preparePaymentMethodsSheet = (data) => {
        if (!data?.metodosPago) return [];

        const metodos = data.metodosPago;
        const total = data.ventas?.ingresos_totales || 0;

        return [
            ['Método', 'Monto', 'Porcentaje'],
            ['Efectivo', metodos.efectivo || 0, total > 0 ? ((metodos.efectivo || 0) / total * 100).toFixed(2) : 0],
            ['Tarjeta', metodos.tarjeta || 0, total > 0 ? ((metodos.tarjeta || 0) / total * 100).toFixed(2) : 0],
            ['Transferencia', metodos.transferencia || 0, total > 0 ? ((metodos.transferencia || 0) / total * 100).toFixed(2) : 0],
            ['QR/Digital', (metodos.mercadopago || 0) + (metodos.qr || 0), total > 0 ? (((metodos.mercadopago || 0) + (metodos.qr || 0)) / total * 100).toFixed(2) : 0]
        ];
    };

    // 🔄 Resetear estado de exportación
    const resetExportState = useCallback(() => {
        setIsExporting(false);
        setExportProgress(0);
        setExportError(null);
    }, []);

    return {
        // Estados
        isExporting,
        exportProgress,
        exportError,
        
        // Funciones
        exportToPDF,
        exportToExcel,
        resetExportState,
        
        // Utilidades
        prepareMetricsSheet,
        prepareTransactionsSheet,
        preparePaymentMethodsSheet
    };
};

export default useExportManager;
