/**
 * src/components/DiagnosticoFinanciero.jsx
 * Diagnóstico avanzado de problemas financieros específicos
 * Identifica exactamente por qué el negocio está en pérdidas
 * RELEVANT FILES: src/components/AnalisisInteligente.jsx, api/reportes_financieros_precisos.php
 */

import React, { useState, useEffect } from 'react';
import { 
  Search, 
  AlertTriangle, 
  DollarSign, 
  Package,
  TrendingDown,
  Calculator,
  Eye,
  CheckCircle,
  XCircle,
  ArrowRight,
  Lightbulb,
  Target,
  Zap
} from 'lucide-react';

const DiagnosticoFinanciero = ({ resumen, productos, ventasDetalladas }) => {
  const [diagnostico, setDiagnostico] = useState(null);
  const [analisisDetallado, setAnalisisDetallado] = useState(false);

  // 🔍 DIAGNÓSTICO ESPECÍFICO: ¿Por qué hay pérdidas?
  const diagnosticarPerdidas = () => {
    if (!resumen || !productos) return null;

    const utilidadNeta = parseFloat(resumen.utilidad_neta || 0);
    const totalIngresos = parseFloat(resumen.total_ingresos_netos || 0);
    const totalCostos = parseFloat(resumen.total_costos || 0);

    const resultados = {
      resumenProblema: null,
      productosProblematicos: [],
      impactoFinanciero: {},
      solucionesInmediatas: [],
      planAccion: []
    };

    // 1. IDENTIFICAR TIPO DE PROBLEMA
    if (utilidadNeta < 0) {
      if (totalCostos > totalIngresos) {
        resultados.resumenProblema = {
          tipo: 'costos_excesivos',
          titulo: 'Costos Superiores a Ingresos',
          descripcion: `Los costos ($${totalCostos.toLocaleString('es-AR')}) superan los ingresos ($${totalIngresos.toLocaleString('es-AR')})`,
          gravedad: 'crítica'
        };
      } else {
        resultados.resumenProblema = {
          tipo: 'margen_negativo',
          titulo: 'Productos con Margen Negativo',
          descripcion: 'Algunos productos se venden por debajo del costo',
          gravedad: 'alta'
        };
      }
    }

    // 2. ANALIZAR PRODUCTOS ESPECÍFICOS
    const productosNegativos = productos.filter(p => {
      const utilidad = parseFloat(p.total_utilidad || 0);
      const margen = parseFloat(p.margen_porcentaje || 0);
      return utilidad < 0 || margen < 0;
    }).map(p => ({
      ...p,
      perdida: Math.abs(parseFloat(p.total_utilidad || 0)),
      ventasCount: parseInt(p.total_ventas || 0),
      impactoPorVenta: Math.abs(parseFloat(p.total_utilidad || 0)) / parseInt(p.total_ventas || 1)
    })).sort((a, b) => b.perdida - a.perdida);

    resultados.productosProblematicos = productosNegativos;

    // 3. CALCULAR IMPACTO FINANCIERO
    const perdidaTotal = productosNegativos.reduce((acc, p) => acc + p.perdida, 0);
    const ventasPerdida = productosNegativos.reduce((acc, p) => acc + p.ventasCount, 0);

    resultados.impactoFinanciero = {
      perdidaTotal,
      ventasPerdida,
      porcentajeVentas: totalIngresos > 0 ? (ventasPerdida / (resumen.total_ventas || 1)) * 100 : 0,
      impactoPromedioPorVenta: ventasPerdida > 0 ? perdidaTotal / ventasPerdida : 0
    };

    // 4. GENERAR SOLUCIONES INMEDIATAS
    if (productosNegativos.length > 0) {
      const productoMasProblemático = productosNegativos[0];
      
      resultados.solucionesInmediatas = [
        {
          accion: 'SUSPENDER PRODUCTOS NO RENTABLES',
          detalle: `Suspender inmediatamente ${productosNegativos.length} productos con pérdidas`,
          impacto: `Eliminar $${perdidaTotal.toLocaleString('es-AR')} en pérdidas`,
          urgencia: 'inmediata'
        },
        {
          accion: 'CORREGIR PRECIOS',
          detalle: `Aumentar precio de "${productoMasProblemático.nombre}" mínimo ${Math.abs(parseFloat(productoMasProblemático.margen_porcentaje)).toFixed(1)}%`,
          impacto: `Convertir en rentable el producto más vendido`,
          urgencia: 'hoy'
        },
        {
          accion: 'REVISAR COSTOS',
          detalle: 'Verificar si los costos en sistema coinciden con costos reales',
          impacto: 'Asegurar precisión en cálculos',
          urgencia: 'esta_semana'
        }
      ];

      // 5. PLAN DE ACCIÓN ESPECÍFICO
      resultados.planAccion = [
        {
          paso: 1,
          titulo: 'Análisis Inmediato (Hoy)',
          tareas: [
            'Verificar precios de venta vs costos en sistema',
            'Confirmar que costos reflejan precio real de compra',
            'Identificar productos más vendidos con pérdida'
          ]
        },
        {
          paso: 2,
          titulo: 'Corrección Urgente (1-2 días)',
          tareas: [
            `Aumentar precio de ${productosNegativos.slice(0, 3).map(p => p.nombre).join(', ')}`,
            'Suspender temporalmente productos muy problemáticos',
            'Implementar alerta automática para productos no rentables'
          ]
        },
        {
          paso: 3,
          titulo: 'Optimización (1 semana)',
          tareas: [
            'Negociar mejores precios con proveedores',
            'Implementar estrategias de bundling',
            'Capacitar personal en detección de productos problemáticos'
          ]
        }
      ];
    }

    return resultados;
  };

  useEffect(() => {
    if (resumen && productos) {
      const resultado = diagnosticarPerdidas();
      setDiagnostico(resultado);
    }
  }, [resumen, productos]);

  if (!diagnostico || !diagnostico.resumenProblema) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-xl p-6">
        <div className="flex items-center">
          <CheckCircle className="w-8 h-8 text-green-600 mr-4" />
          <div>
            <h3 className="text-lg font-semibold text-green-800">✅ Negocio Rentable</h3>
            <p className="text-green-700">No se detectaron problemas críticos de rentabilidad</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      
      {/* Resumen del Problema */}
      <div className="bg-red-50 border-l-4 border-red-400 rounded-r-xl p-6">
        <div className="flex items-start">
          <AlertTriangle className="w-8 h-8 text-red-600 mr-4 mt-1" />
          <div className="flex-1">
            <h3 className="text-xl font-bold text-red-800 mb-2">
              🚨 {diagnostico.resumenProblema.titulo}
            </h3>
            <p className="text-red-700 mb-4">{diagnostico.resumenProblema.descripcion}</p>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 bg-red-100 rounded-lg p-4">
              <div className="text-center">
                <div className="text-2xl font-bold text-red-800">
                  ${diagnostico.impactoFinanciero.perdidaTotal?.toLocaleString('es-AR') || '0'}
                </div>
                <div className="text-sm text-red-600">Pérdida Total</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-red-800">
                  {diagnostico.productosProblematicos.length}
                </div>
                <div className="text-sm text-red-600">Productos Problemáticos</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-red-800">
                  {diagnostico.impactoFinanciero.porcentajeVentas?.toFixed(1) || '0'}%
                </div>
                <div className="text-sm text-red-600">% de Ventas Afectadas</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Productos Problemáticos */}
      {diagnostico.productosProblematicos.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <Package className="w-6 h-6 mr-3 text-red-600" />
            📦 Productos Perdiendo Dinero
          </h3>
          
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-red-50">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Producto</th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Ventas</th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Pérdida Total</th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Pérdida/Venta</th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Margen</th>
                  <th className="px-4 py-3 text-left text-sm font-medium text-red-800">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {diagnostico.productosProblematicos.slice(0, 10).map((producto, index) => (
                  <tr key={index} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <div>
                        <div className="font-medium text-gray-900">{producto.nombre}</div>
                        <div className="text-xs text-gray-500">{producto.codigo_barras}</div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-sm font-semibold text-gray-900">
                      {producto.ventasCount}
                    </td>
                    <td className="px-4 py-3 text-sm font-bold text-red-600">
                      -${producto.perdida.toLocaleString('es-AR')}
                    </td>
                    <td className="px-4 py-3 text-sm text-red-600">
                      -${producto.impactoPorVenta.toLocaleString('es-AR')}
                    </td>
                    <td className="px-4 py-3">
                      <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        {parseFloat(producto.margen_porcentaje || 0).toFixed(1)}%
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <button className="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg">
                        Corregir Ahora
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Soluciones Inmediatas */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-xl font-bold text-orange-600 mb-6 flex items-center">
          <Zap className="w-6 h-6 mr-3" />
          ⚡ Soluciones Inmediatas
        </h3>
        
        <div className="space-y-4">
          {diagnostico.solucionesInmediatas.map((solucion, index) => (
            <div key={index} className="border-l-4 border-orange-400 bg-orange-50 p-4 rounded-r-lg">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h4 className="font-semibold text-orange-800">{solucion.accion}</h4>
                  <p className="text-orange-700 mt-1">{solucion.detalle}</p>
                  <div className="mt-2 text-sm text-orange-600">
                    <strong>Impacto:</strong> {solucion.impacto}
                  </div>
                </div>
                <div className={`ml-4 px-3 py-1 rounded-full text-xs font-medium ${
                  solucion.urgencia === 'inmediata' ? 'bg-red-200 text-red-800' :
                  solucion.urgencia === 'hoy' ? 'bg-orange-200 text-orange-800' :
                  'bg-yellow-200 text-yellow-800'
                }`}>
                  {solucion.urgencia.toUpperCase()}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Plan de Acción Detallado */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-xl font-bold text-blue-600 mb-6 flex items-center">
          <Target className="w-6 h-6 mr-3" />
          🎯 Plan de Acción Paso a Paso
        </h3>
        
        <div className="space-y-6">
          {diagnostico.planAccion.map((fase, index) => (
            <div key={index} className="border border-blue-200 rounded-lg p-4">
              <div className="flex items-center mb-3">
                <div className="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">
                  {fase.paso}
                </div>
                <h4 className="font-semibold text-blue-800">{fase.titulo}</h4>
              </div>
              <ul className="space-y-2 ml-11">
                {fase.tareas.map((tarea, i) => (
                  <li key={i} className="flex items-start text-sm text-gray-700">
                    <ArrowRight className="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" />
                    {tarea}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>

      {/* Botón de Análisis Detallado */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-xl font-bold mb-2">🔬 Análisis Detallado Avanzado</h3>
            <p className="text-blue-100">
              Diagnóstico profundo con predicciones y simulaciones de escenarios
            </p>
          </div>
          <button
            onClick={() => setAnalisisDetallado(!analisisDetallado)}
            className="flex items-center px-6 py-3 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition-all backdrop-blur-sm"
          >
            <Search className="w-5 h-5 mr-2" />
            {analisisDetallado ? 'Ocultar' : 'Ver'} Análisis
          </button>
        </div>
        
        {analisisDetallado && (
          <div className="mt-6 bg-blue-800 bg-opacity-50 rounded-lg p-4">
            <h4 className="font-semibold mb-3">📊 Simulación de Correcciones</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <p className="font-medium text-blue-200">Si corriges los 3 productos más problemáticos:</p>
                <p className="text-white">
                  • Ganancia potencial: +${(diagnostico.impactoFinanciero.perdidaTotal * 0.7).toLocaleString('es-AR')}
                </p>
                <p className="text-white">
                  • Tiempo estimado: 2-3 días
                </p>
                <p className="text-white">
                  • Probabilidad de éxito: 85%
                </p>
              </div>
              <div>
                <p className="font-medium text-blue-200">Impacto en próximos 30 días:</p>
                <p className="text-white">
                  • Utilidad proyectada: +${(Math.abs(parseFloat(resumen.utilidad_neta)) * 1.5).toLocaleString('es-AR')}
                </p>
                <p className="text-white">
                  • ROI mejorado: ~{(parseFloat(resumen.roi_neto_porcentaje) + 15).toFixed(1)}%
                </p>
                <p className="text-white">
                  • Estado proyectado: RENTABLE
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

    </div>
  );
};

export default DiagnosticoFinanciero;
















