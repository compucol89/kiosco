// src/components/productos/components/ProductImportModal.jsx
// Modal optimizado para importación masiva de productos
// Soporte para CSV/Excel con validación y preview
// RELEVANT FILES: ProductFormModal.jsx, ProductosPage.jsx

import React, { useState } from 'react';
import { X, Upload, Download, CheckCircle, AlertCircle, FileSpreadsheet } from 'lucide-react';
import axios from 'axios';
import CONFIG from '../../../config/config';

const ProductImportModal = ({ onClose, onImport }) => {
  const [archivo, setArchivo] = useState(null);
  const [procesando, setProcesando] = useState(false);
  const [resultado, setResultado] = useState(null);
  const [preview, setPreview] = useState([]);

  // Manejar selección de archivo
  const handleArchivoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setArchivo(file);
      setResultado(null);
      previewArchivo(file);
    }
  };

  // Preview del archivo
  const previewArchivo = (file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const text = e.target.result;
        const lines = text.split('\n').filter(line => line.trim());
        
        if (lines.length > 0) {
          // Parseado simple para preview
          const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
          const rows = lines.slice(1, 6).map(line => {
            const values = line.split(',').map(v => v.trim().replace(/"/g, ''));
            return headers.reduce((obj, header, index) => {
              obj[header] = values[index] || '';
              return obj;
            }, {});
          });
          
          setPreview({ headers, rows, total: lines.length - 1 });
        }
      } catch (error) {
        console.error('Error al parsear archivo:', error);
        alert('Error al leer el archivo. Verifique el formato.');
      }
    };
    reader.readAsText(file);
  };

  // Procesar importación
  const procesarImportacion = async () => {
    if (!archivo) return;

    setProcesando(true);
    setResultado(null);

    try {
      const formData = new FormData();
      formData.append('archivo', archivo);

      const response = await axios.post(`${CONFIG.API_URL}/api/importar_productos.php`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      if (response.data?.success) {
        setResultado({
          exito: true,
          importados: response.data.importados || 0,
          errores: response.data.errores || [],
          duplicados: response.data.duplicados || 0,
          mensaje: response.data.message
        });
      } else {
        throw new Error(response.data?.message || 'Error en la importación');
      }
    } catch (error) {
      console.error('Error en importación:', error);
      setResultado({
        exito: false,
        mensaje: error.message || 'Error al procesar la importación',
        errores: [error.message]
      });
    }

    setProcesando(false);
  };

  // Descargar plantilla
  const descargarPlantilla = () => {
    const headers = [
      'nombre',
      'categoria', 
      'precio_venta',
      'precio_costo',
      'stock',
      'codigo',
      'descripcion',
      'stock_minimo',
      'aplica_descuento_forma_pago'
    ];

    const ejemplos = [
      [
        'Coca Cola 500ml',
        'Bebidas',
        '350.00',
        '200.00',
        '50',
        '7790895001234',
        'Gaseosa Coca Cola 500ml',
        '10',
        'true'
      ],
      [
        'Agua Mineral 1.5L',
        'Bebidas',
        '180.00',
        '100.00',
        '30',
        '7790895005678',
        'Agua mineral natural 1.5 litros',
        '5',
        'true'
      ],
      [
        'Papas Fritas 150g',
        'Snacks',
        '420.00',
        '250.00',
        '25',
        '7790895009012',
        'Papas fritas sabor original 150g',
        '8',
        'false'
      ]
    ];

    const csvContent = [
      headers.join(','),
      ...ejemplos.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'plantilla_productos.csv';
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[95vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-green-100 rounded-lg">
              <Upload className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">Importar Productos</h2>
              <p className="text-gray-600">Carga masiva desde archivo CSV o Excel</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6">
          
          {/* Instrucciones */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="font-medium text-blue-900 mb-2">📋 Instrucciones</h3>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• Descarga la plantilla CSV para ver el formato requerido</li>
              <li>• Los campos obligatorios son: nombre, categoria, precio_venta, precio_costo, stock</li>
              <li>• Los precios deben usar punto como separador decimal (ej: 123.45)</li>
              <li>• Los valores booleanos usar: true/false</li>
              <li>• Máximo 1000 productos por importación</li>
            </ul>
          </div>

          {/* Plantilla */}
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div className="flex items-center gap-3">
              <FileSpreadsheet className="w-8 h-8 text-green-600" />
              <div>
                <h4 className="font-medium text-gray-900">Plantilla CSV</h4>
                <p className="text-sm text-gray-600">Descarga el formato requerido con ejemplos</p>
              </div>
            </div>
            <button
              onClick={descargarPlantilla}
              className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
            >
              <Download className="w-4 h-4" />
              Descargar Plantilla
            </button>
          </div>

          {/* Upload */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Seleccionar Archivo
            </label>
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
              <input
                type="file"
                accept=".csv,.xlsx,.xls"
                onChange={handleArchivoChange}
                className="hidden"
                id="archivo-import"
              />
              <label htmlFor="archivo-import" className="cursor-pointer">
                <Upload className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p className="text-lg font-medium text-gray-900 mb-1">
                  Haz clic para seleccionar un archivo
                </p>
                <p className="text-sm text-gray-600">
                  CSV, Excel (.xlsx, .xls) - Máximo 5MB
                </p>
              </label>
              
              {archivo && (
                <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                  <p className="text-sm font-medium text-blue-900">
                    📁 {archivo.name} ({(archivo.size / 1024).toFixed(1)} KB)
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Preview */}
          {preview.headers && (
            <div>
              <h3 className="font-medium text-gray-900 mb-3">👀 Vista Previa</h3>
              <div className="border border-gray-200 rounded-lg overflow-hidden">
                <div className="bg-gray-50 px-4 py-2 border-b border-gray-200">
                  <p className="text-sm text-gray-600">
                    Mostrando 5 de {preview.total} registros encontrados
                  </p>
                </div>
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                      <tr>
                        {preview.headers.map((header, index) => (
                          <th key={index} className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                            {header}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                      {preview.rows.map((row, index) => (
                        <tr key={index}>
                          {preview.headers.map((header, cellIndex) => (
                            <td key={cellIndex} className="px-4 py-2 text-sm text-gray-900">
                              {row[header] || '-'}
                            </td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* Resultado */}
          {resultado && (
            <div className={`border rounded-lg p-4 ${
              resultado.exito ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
            }`}>
              <div className="flex items-start gap-3">
                {resultado.exito ? (
                  <CheckCircle className="w-5 h-5 text-green-600 mt-0.5" />
                ) : (
                  <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
                )}
                <div className="flex-1">
                  <h4 className={`font-medium ${
                    resultado.exito ? 'text-green-900' : 'text-red-900'
                  }`}>
                    {resultado.exito ? '✅ Importación Exitosa' : '❌ Error en Importación'}
                  </h4>
                  
                  {resultado.exito && (
                    <div className="mt-2 text-sm text-green-800">
                      <p>• Productos importados: {resultado.importados}</p>
                      {resultado.duplicados > 0 && (
                        <p>• Productos duplicados omitidos: {resultado.duplicados}</p>
                      )}
                    </div>
                  )}
                  
                  {resultado.errores && resultado.errores.length > 0 && (
                    <div className="mt-2">
                      <p className={`text-sm font-medium ${
                        resultado.exito ? 'text-yellow-800' : 'text-red-800'
                      }`}>
                        Errores encontrados:
                      </p>
                      <ul className={`text-sm mt-1 ${
                        resultado.exito ? 'text-yellow-700' : 'text-red-700'
                      }`}>
                        {resultado.errores.slice(0, 5).map((error, index) => (
                          <li key={index}>• {error}</li>
                        ))}
                        {resultado.errores.length > 5 && (
                          <li>• ... y {resultado.errores.length - 5} errores más</li>
                        )}
                      </ul>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
          <button
            onClick={onClose}
            disabled={procesando}
            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
          >
            {resultado?.exito ? 'Cerrar' : 'Cancelar'}
          </button>
          
          {archivo && !resultado?.exito && (
            <button
              onClick={procesarImportacion}
              disabled={procesando}
              className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50"
            >
              {procesando ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Procesando...
                </>
              ) : (
                <>
                  <Upload className="w-4 h-4" />
                  Importar Productos
                </>
              )}
            </button>
          )}
          
          {resultado?.exito && (
            <button
              onClick={() => {
                onImport();
                onClose();
              }}
              className="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
            >
              <CheckCircle className="w-4 h-4" />
              Finalizar
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default ProductImportModal;
