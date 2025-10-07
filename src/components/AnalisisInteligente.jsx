/**
 * src/components/AnalisisInteligente.jsx
 * Módulo de análisis inteligente con IA para detección de problemas
 * Diagnóstico automático de pérdidas y recomendaciones
 * RELEVANT FILES: src/components/ReporteVentasModerno.jsx, api/reportes_financieros_precisos.php
 */

import React, { useState, useEffect, useCallback } from 'react';
import { 
  Brain, 
  AlertTriangle, 
  TrendingUp, 
  TrendingDown,
  Target,
  Lightbulb,
  Search,
  DollarSign,
  Package,
  Zap,
  CheckCircle,
  XCircle,
  ArrowRight,
  BarChart3,
  PieChart,
  Activity,
  Cpu
} from 'lucide-react';
import DiagnosticoFinanciero from './DiagnosticoFinanciero';
import OpenAIService from '../services/openaiService';

const AnalisisInteligente = ({ datos, resumen, productos }) => {
  const [analisisIA, setAnalisisIA] = useState(null);
  const [diagnostico, setDiagnostico] = useState(null);
  const [loading, setLoading] = useState(false);
  const [usarIAReal, setUsarIAReal] = useState(true);
  const [estadoIA, setEstadoIA] = useState(null);
  
  // 🔍 DEBUG: Log de props recibidas
  console.log('🔍 AnalisisInteligente props:', { 
    datos: !!datos, 
    resumen: !!resumen, 
    productos: !!productos,
    datosCompletos: datos,
    resumenCompleto: resumen 
  });
  
  // Instancia del servicio OpenAI
  const openaiService = new OpenAIService();

  // 🤖 ANÁLISIS CON IA REAL
  const generarAnalisisIA = useCallback(async () => {
    console.log('🚀 generarAnalisisIA EJECUTADO');
    setLoading(true);
    console.log('🧠 Iniciando análisis con OpenAI...', {
      usarIAReal,
      datos: !!datos,
      resumen: !!resumen,
      productos: !!productos
    });
    
    try {
      // 🔧 CAMBIAR VALIDACIÓN: No requiere productos obligatoriamente
      if (usarIAReal && (datos || resumen)) {
        // Preparar datos para IA
        const datosCompletos = {
          resumen,
          productos,
          metodosPago: datos?.metodosPago,
          ventasDetalladas: datos?.ventasDetalladas
        };
        
        // Llamar a OpenAI
        console.log('📤 Enviando datos a OpenAI:', datosCompletos);
        const analisisCompleto = await openaiService.analizarDatosFinancieros(datosCompletos);
        console.log('📥 Respuesta de OpenAI:', analisisCompleto);
        
        // Procesar respuesta del EXPERTO EN KIOSCOS para UI
        const analisisUI = {
          score: analisisCompleto.score_salud || 50,
          estado: mapearEstado(analisisCompleto.diagnostico_principal?.estado),
          
          // 🚨 PRODUCTOS PROBLEMÁTICOS CON DETALLES EXPERTOS
          problemas: Array.isArray(analisisCompleto.productos_problematicos) 
            ? analisisCompleto.productos_problematicos.map(p => ({
                titulo: `${p.nombre || 'Producto problemático'} - ${p.margen_actual || 'N/A'}% vs ${p.margen_industria || 'N/A'}% industria`,
                mensaje: p.accion_inmediata || p.justificacion || 'Requiere ajuste de precios',
                tipo: 'peligro',
                perdida: p.perdida_diaria || p.perdida_por_venta || 0,
                impacto: 'alto',
                detalles: {
                  margen_actual: p.margen_actual,
                  margen_industria: p.margen_industria,
                  problema_tipo: p.problema,
                  accion_concreta: p.accion_inmediata
                }
              }))
            : [],
            
          // ⭐ PRODUCTOS ESTRELLA IDENTIFICADOS 
          productos_estrella: analisisCompleto.productos_estrella || [],
          
          // 🚀 OPORTUNIDADES CON IMPACTO ECONÓMICO
          oportunidades: Array.isArray(analisisCompleto.soluciones_inmediatas) 
            ? analisisCompleto.soluciones_inmediatas
                .filter(s => s && s.urgencia !== 'inmediata')
                .map(s => ({
                  titulo: s.accion || 'Oportunidad detectada',
                  descripcion: s.como_implementar || 'Revisar implementación',
                  impacto_diario: s.impacto_diario,
                  categoria: s.producto_categoria,
                  dificultad: s.dificultad
                }))
            : [],
            
          // 📋 RECOMENDACIONES EXPERTAS
          recomendaciones: Array.isArray(analisisCompleto.soluciones_inmediatas) 
            ? analisisCompleto.soluciones_inmediatas.map(s => ({
                titulo: s.accion || 'Recomendación',
                acciones: [s.como_implementar || s.accion || 'Acción recomendada'],
                prioridad: s.urgencia || 'media',
                categoria: s.producto_categoria || 'Optimización',
                impacto: s.impacto_diario || 'Mejora esperada',
                inversion: s.inversion_requerida || 'A determinar'
              }))
            : [],
            
          // 🎯 OPTIMIZACIÓN DE MIX DE PRODUCTOS
          optimizacion_mix: analisisCompleto.optimizacion_mix || {},
          
          // 📊 PREDICCIONES REALES
          predicciones: analisisCompleto.predicciones_reales || analisisCompleto.predicciones || {},
          
          // 📋 DIAGNÓSTICO PRINCIPAL EXTENDIDO
          diagnostico_principal: {
            ...analisisCompleto.diagnostico_principal,
            comparacion_industria: analisisCompleto.diagnostico_principal?.comparacion_industria
          },
          
          fuente: 'Experto en Kioscos Argentinos (OpenAI)',
          confianza: 0.95, // Mayor confianza con prompt experto
          timestamp: analisisCompleto.timestamp || new Date().toISOString(),
          version_prompt: 'Experto v2.0'
        };
        
        setAnalisisIA(analisisUI);
        setEstadoIA({ estado: 'conectado', fuente: analisisCompleto.fuente });
        
      } else {
        // Fallback a análisis local
        console.log('🔄 Usando análisis local como fallback');
        const analisisLocal = analizarProblemas() || generarAnalisisBasico();
        setAnalisisIA(analisisLocal);
        setEstadoIA({ estado: 'local', fuente: 'Algoritmos locales' });
      }
      
    } catch (error) {
      console.error('❌ Error con IA:', error);
      // Fallback automático más robusto
      try {
        const analisisLocal = analizarProblemas() || generarAnalisisBasico();
        setAnalisisIA(analisisLocal);
        setEstadoIA({ estado: 'error', fuente: 'Fallback local', error: error.message });
      } catch (fallbackError) {
        console.error('❌ Error en fallback:', fallbackError);
        // Último recurso: análisis básico garantizado
        setAnalisisIA({
          score: 50,
          estado: 'Regular',
          problemas: [{ titulo: 'Error de análisis', mensaje: 'No se pudo completar el análisis', tipo: 'advertencia' }],
          oportunidades: ['Revisar configuración del sistema'],
          recomendaciones: ['Contactar soporte técnico'],
          fuente: 'Error recovery',
          confianza: 0.1
        });
        setEstadoIA({ estado: 'error', fuente: 'Error recovery', error: 'Múltiples errores detectados' });
      }
    } finally {
      setLoading(false);
    }
  }, [datos, resumen, productos, usarIAReal]);

  // 🗺️ MAPEAR ESTADO DE IA A UI
  const mapearEstado = (estadoIA) => {
    switch (estadoIA) {
      case 'GANANDO': return 'Excelente';
      case 'EQUILIBRIO': return 'Regular';
      case 'PERDIENDO': return 'Crítico';
      default: return 'Regular';
    }
  };

  // 🛠️ GENERAR ANÁLISIS BÁSICO (CUANDO NO HAY DATOS SUFICIENTES)
  const generarAnalisisBasico = () => {
    console.log('📋 Generando análisis básico con datos limitados');
    
    const utilidadNeta = parseFloat(resumen?.utilidad_neta || datos?.resumenGeneral?.utilidad_neta || 0);
    const score = utilidadNeta >= 0 ? 70 : 30;
    
    return {
      score,
      estado: utilidadNeta >= 0 ? 'Excelente' : 'Crítico',
      problemas: utilidadNeta < 0 ? [{
        titulo: 'Utilidad Negativa Detectada',
        mensaje: `Se detectó una pérdida de $${Math.abs(utilidadNeta).toLocaleString('es-AR')}`,
        tipo: 'peligro'
      }] : [],
      oportunidades: [
        'Revisar estructura de costos',
        'Analizar precios de productos',
        'Optimizar métodos de pago'
      ],
      recomendaciones: [
        'Realizar análisis detallado de productos',
        'Implementar control de inventario',
        'Revisar márgenes de ganancia'
      ],
      fuente: 'Análisis básico',
      confianza: 0.6
    };
  };

  // 🤖 MOTOR DE IA: Análisis automático de problemas (FALLBACK)
  const analizarProblemas = () => {
    if (!resumen || !productos) return null;

    const problemas = [];
    const oportunidades = [];
    const alertas = [];

    const utilidadNeta = parseFloat(resumen.utilidad_neta || 0);
    const totalVentas = parseFloat(resumen.total_ventas || 0);
    const totalIngresos = parseFloat(resumen.total_ingresos_netos || 0);

    // 🚨 DIAGNÓSTICO 1: Análisis de pérdidas
    if (utilidadNeta < 0) {
      problemas.push({
        tipo: 'crítico',
        titulo: 'Negocio en Pérdidas',
        descripcion: `Pérdida de $${Math.abs(utilidadNeta).toLocaleString('es-AR')}`,
        impacto: 'alto',
        causas: analizarCausasPerdidas(productos, resumen),
        solucion: 'Revisar precios y costos de productos con margen negativo'
      });
    }

    // 🔍 DIAGNÓSTICO 2: Productos problemáticos
    const productosNegativos = productos?.filter(p => {
      const margen = parseFloat(p.margen_porcentaje || 0);
      return margen < 0;
    }) || [];

    if (productosNegativos.length > 0) {
      problemas.push({
        tipo: 'urgente',
        titulo: `${productosNegativos.length} Productos Perdiendo Dinero`,
        descripcion: `Productos vendidos por debajo del costo`,
        impacto: 'alto',
        productos: productosNegativos.slice(0, 5),
        solucion: 'Corregir precios o revisar costos inmediatamente'
      });
    }

    // 📊 DIAGNÓSTICO 3: Análisis de márgenes
    const margenPromedio = productos?.reduce((acc, p) => {
      return acc + parseFloat(p.margen_porcentaje || 0);
    }, 0) / (productos?.length || 1);

    if (margenPromedio < 20) {
      alertas.push({
        tipo: 'advertencia',
        titulo: 'Márgenes Bajos',
        descripcion: `Margen promedio: ${margenPromedio.toFixed(1)}%`,
        recomendacion: 'Aumentar precios o negociar mejores costos'
      });
    }

    // 🎯 OPORTUNIDAD 1: Productos de alto rendimiento
    const mejoresProductos = productos?.filter(p => {
      const margen = parseFloat(p.margen_porcentaje || 0);
      return margen > 30;
    }).sort((a, b) => parseFloat(b.total_utilidad) - parseFloat(a.total_utilidad)).slice(0, 3) || [];

    if (mejoresProductos.length > 0) {
      oportunidades.push({
        tipo: 'crecimiento',
        titulo: 'Productos Estrella Identificados',
        descripcion: `${mejoresProductos.length} productos con excelente margen`,
        productos: mejoresProductos,
        accion: 'Potenciar ventas de estos productos'
      });
    }

    // 💰 DIAGNÓSTICO 4: Análisis de ticket promedio
    const ticketPromedio = totalVentas > 0 ? totalIngresos / totalVentas : 0;
    if (ticketPromedio < 1000) {
      oportunidades.push({
        tipo: 'optimización',
        titulo: 'Ticket Promedio Bajo',
        descripcion: `$${ticketPromedio.toLocaleString('es-AR')} por venta`,
        accion: 'Implementar venta cruzada y upselling'
      });
    }

    return {
      problemas,
      oportunidades,
      alertas,
      score: calcularScoreNegocio(resumen),
      recomendaciones: generarRecomendacionesIA(problemas, oportunidades)
    };
  };

  // 🔬 ANÁLISIS PROFUNDO: Causas específicas de pérdidas
  const analizarCausasPerdidas = (productos, resumen) => {
    const causas = [];

    // Analizar productos con margen negativo
    const productosNegativos = productos?.filter(p => parseFloat(p.margen_porcentaje || 0) < 0) || [];
    if (productosNegativos.length > 0) {
      const perdidaTotal = productosNegativos.reduce((acc, p) => acc + Math.abs(parseFloat(p.total_utilidad || 0)), 0);
      causas.push(`${productosNegativos.length} productos con pérdida ($${perdidaTotal.toLocaleString('es-AR')})`);
    }

    // Analizar descuentos excesivos
    const totalDescuentos = parseFloat(resumen.total_descuentos || 0);
    if (totalDescuentos > 0) {
      causas.push(`Descuentos aplicados: $${totalDescuentos.toLocaleString('es-AR')}`);
    }

    // Analizar relación costo/precio
    const costoTotal = parseFloat(resumen.total_costos || 0);
    const ingresoTotal = parseFloat(resumen.total_ingresos_netos || 0);
    if (costoTotal > ingresoTotal) {
      causas.push(`Costos (${costoTotal.toLocaleString('es-AR')}) > Ingresos (${ingresoTotal.toLocaleString('es-AR')})`);
    }

    return causas;
  };

  // 📊 SCORE DE NEGOCIO: Calificación automática
  const calcularScoreNegocio = (resumen) => {
    let score = 50; // Base neutral

    const utilidadNeta = parseFloat(resumen.utilidad_neta || 0);
    const margenNeto = parseFloat(resumen.margen_neto_porcentaje || 0);

    // Factor utilidad
    if (utilidadNeta > 0) score += 30;
    else if (utilidadNeta < 0) score -= 40;

    // Factor margen
    if (margenNeto > 20) score += 20;
    else if (margenNeto < 0) score -= 30;

    // Factor ROI
    const roi = parseFloat(resumen.roi_neto_porcentaje || 0);
    if (roi > 15) score += 15;
    else if (roi < 0) score -= 20;

    return Math.max(0, Math.min(100, score));
  };

  // 🤖 RECOMENDACIONES IA: Sugerencias automáticas
  const generarRecomendacionesIA = (problemas, oportunidades) => {
    const recomendaciones = [];

    // Recomendaciones basadas en problemas
    if (problemas.some(p => p.tipo === 'crítico')) {
      recomendaciones.push({
        prioridad: 'urgente',
        categoria: 'Recuperación',
        titulo: 'Plan de Emergencia Financiera',
        acciones: [
          'Revisar inmediatamente precios de productos con margen negativo',
          'Suspender temporalmente productos no rentables',
          'Negociar mejores precios con proveedores',
          'Implementar control de costos estricto'
        ],
        impacto: 'Puede revertir pérdidas en 1-2 semanas'
      });
    }

    // Recomendaciones basadas en oportunidades
    if (oportunidades.length > 0) {
      recomendaciones.push({
        prioridad: 'alta',
        categoria: 'Crecimiento',
        titulo: 'Estrategia de Optimización',
        acciones: [
          'Promocionar productos de alto margen',
          'Implementar ofertas inteligentes',
          'Capacitar personal en ventas sugestivas',
          'Analizar comportamiento de compra'
        ],
        impacto: 'Puede aumentar rentabilidad 15-25%'
      });
    }

    // Recomendaciones generales de IA
    recomendaciones.push({
      prioridad: 'media',
      categoria: 'Inteligencia de Negocio',
      titulo: 'Automatización y BI',
      acciones: [
        'Implementar alertas automáticas de productos no rentables',
        'Dashboard predictivo de tendencias',
        'Análisis de estacionalidad por IA',
        'Optimización automática de precios'
      ],
      impacto: 'Mejora continua y preventiva'
    });

    return recomendaciones;
  };

  useEffect(() => {
    if (datos && resumen) {
      generarAnalisisIA();
    }
  }, [datos, resumen, productos, generarAnalisisIA]);

  // 🔍 DEBUG: Estados del componente
  console.log('🔍 Estados AnalisisInteligente:', { 
    loading, 
    analisisIA: !!analisisIA, 
    usarIAReal, 
    estadoIA 
  });

  // 📊 VALIDACIÓN INICIAL DE DATOS
  if (!datos && !resumen && !productos) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        {/* Hero Header Moderno para Análisis */}
        <div className="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
          <div className="absolute inset-0 bg-black opacity-10"></div>
          <div className="relative max-w-7xl mx-auto px-4 py-12">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                  <Brain className="w-8 h-8 text-white" />
                </div>
                <div>
                  <h1 className="text-3xl font-bold text-white mb-2">
                    🧠 Centro de Análisis Inteligente
                  </h1>
                  <p className="text-indigo-100 text-lg">
                    IA experta analizando tu negocio en tiempo real
                  </p>
                </div>
              </div>
              
              <div className="hidden lg:flex space-x-6">
                <div className="text-center">
                  <div className="text-2xl font-bold text-white">🤖</div>
                  <div className="text-sm text-indigo-100">IA Lista</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-white">📊</div>
                  <div className="text-sm text-indigo-100">Esperando</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 -mt-6 relative z-10">
          <div className="bg-white rounded-2xl shadow-2xl border-0 overflow-hidden">
            <div className="p-12 text-center">
              <div className="relative mb-8">
                <div className="w-24 h-24 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-2xl mx-auto flex items-center justify-center">
                  <Brain className="w-12 h-12 text-indigo-600" />
                </div>
                <div className="absolute -top-2 -right-2">
                  <div className="w-6 h-6 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center">
                    <CheckCircle className="w-4 h-4 text-white" />
                  </div>
                </div>
              </div>
              
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                🚀 Inteligencia Artificial Lista
              </h3>
              <p className="text-gray-600 mb-8 max-w-md mx-auto">
                No se han recibido datos financieros para analizar. 
                Carga algunos datos para comenzar el análisis inteligente.
              </p>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-lg mx-auto mb-8">
                <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                  <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-2" />
                  <p className="text-sm font-medium text-green-800">OpenAI</p>
                  <p className="text-xs text-green-600">Configurado ✅</p>
                </div>
                <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                  <Activity className="w-6 h-6 text-yellow-600 mx-auto mb-2" />
                  <p className="text-sm font-medium text-yellow-800">Datos</p>
                  <p className="text-xs text-yellow-600">Esperando... ⏳</p>
                </div>
                <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                  <Cpu className="w-6 h-6 text-blue-600 mx-auto mb-2" />
                  <p className="text-sm font-medium text-blue-800">IA</p>
                  <p className="text-xs text-blue-600">Lista 🧠</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        {/* Hero Header Análisis en Proceso */}
        <div className="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
          <div className="absolute inset-0 bg-black opacity-10"></div>
          <div className="relative max-w-7xl mx-auto px-4 py-12">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                  <Brain className="w-8 h-8 text-white animate-pulse" />
                </div>
                <div>
                  <h1 className="text-3xl font-bold text-white mb-2">
                    🧠 IA Analizando tu Negocio
                  </h1>
                  <p className="text-indigo-100 text-lg">
                    Procesando datos financieros con inteligencia artificial
                  </p>
                </div>
              </div>
              
              <div className="hidden lg:flex space-x-6">
                <div className="text-center">
                  <div className="text-2xl font-bold text-white animate-bounce">🤖</div>
                  <div className="text-sm text-indigo-100">Trabajando</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-white">📊</div>
                  <div className="text-sm text-indigo-100">Analizando</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 -mt-6 relative z-10">
          <div className="bg-white rounded-2xl shadow-2xl border-0 overflow-hidden">
            <div className="p-12 text-center">
              {/* Loading Animation Moderno */}
              <div className="relative mb-8">
                <div className="w-32 h-32 mx-auto">
                  {/* Spinner externo */}
                  <div className="w-32 h-32 border-8 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                  {/* Spinner interno */}
                  <div className="absolute top-2 left-2 w-28 h-28 border-6 border-purple-200 border-b-purple-600 rounded-full animate-spin" style={{animationDirection: 'reverse', animationDuration: '1.5s'}}></div>
                  {/* Icono central */}
                  <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div className="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center">
                      <Cpu className="w-8 h-8 text-white animate-pulse" />
                    </div>
                  </div>
                </div>
              </div>
              
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                {usarIAReal ? '🤖 OpenAI GPT-4 Analizando' : '⚙️ IA Local Procesando'}
              </h3>
              <p className="text-gray-600 mb-8 max-w-md mx-auto">
                {usarIAReal 
                  ? 'Conectando con OpenAI para análisis profesional de tu negocio...' 
                  : 'Procesando con algoritmos locales avanzados...'
                }
              </p>
              
              {/* Progress Indicators */}
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4 max-w-2xl mx-auto">
                <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                  <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-2" />
                  <p className="text-sm font-medium text-green-800">Datos</p>
                  <p className="text-xs text-green-600">Recibidos ✅</p>
                </div>
                <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                  <div className="w-6 h-6 mx-auto mb-2">
                    <div className="w-4 h-4 bg-blue-600 rounded-full animate-ping mx-auto"></div>
                  </div>
                  <p className="text-sm font-medium text-blue-800">Procesando</p>
                  <p className="text-xs text-blue-600">En curso... ⚡</p>
                </div>
                <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                  <Brain className="w-6 h-6 text-purple-600 mx-auto mb-2 animate-pulse" />
                  <p className="text-sm font-medium text-purple-800">Analizando</p>
                  <p className="text-xs text-purple-600">IA trabajando 🧠</p>
                </div>
                <div className="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                  <Target className="w-6 h-6 text-gray-400 mx-auto mb-2" />
                  <p className="text-sm font-medium text-gray-600">Reporte</p>
                  <p className="text-xs text-gray-500">Esperando... ⏳</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!analisisIA) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        {/* Hero Header Listo para Análisis */}
        <div className="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
          <div className="absolute inset-0 bg-black opacity-10"></div>
          <div className="relative max-w-7xl mx-auto px-4 py-12">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                  <Brain className="w-8 h-8 text-white" />
                </div>
                <div>
                  <h1 className="text-3xl font-bold text-white mb-2">
                    🚀 Análisis IA Listo
                  </h1>
                  <p className="text-indigo-100 text-lg">
                    Datos recibidos, IA preparada para analizar
                  </p>
                </div>
              </div>
              
              <div className="hidden lg:flex space-x-6">
                <div className="text-center">
                  <div className="text-2xl font-bold text-white">✅</div>
                  <div className="text-sm text-indigo-100">Datos OK</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-white">🧠</div>
                  <div className="text-sm text-indigo-100">IA Lista</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-4 -mt-6 relative z-10">
          <div className="bg-white rounded-2xl shadow-2xl border-0 overflow-hidden">
            <div className="p-12 text-center">
              <div className="relative mb-8">
                <div className="w-24 h-24 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-2xl mx-auto flex items-center justify-center">
                  <Brain className="w-12 h-12 text-indigo-600" />
                </div>
                <div className="absolute -top-2 -right-2">
                  <div className="w-8 h-8 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center animate-pulse">
                    <CheckCircle className="w-5 h-5 text-white" />
                  </div>
                </div>
              </div>
              
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                🎯 Todo Listo para el Análisis
              </h3>
              <p className="text-gray-600 mb-8 max-w-md mx-auto">
                Datos financieros recibidos correctamente. 
                Haz clic para iniciar el análisis inteligente de tu negocio.
              </p>
              
              {/* Botones de Acción Modernos */}
              <div className="space-y-4 mb-8">
                <button
                  onClick={() => {
                    console.log('🔥 BOTÓN CLICKEADO - Iniciando análisis...', { usarIAReal, datos: !!datos, resumen: !!resumen });
                    generarAnalisisIA();
                  }}
                  disabled={loading}
                  className="group relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold py-4 px-8 rounded-2xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105 text-lg"
                >
                  <div className="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                  <div className="relative flex items-center justify-center space-x-3">
                    <Brain className="w-6 h-6" />
                    <span>{usarIAReal ? '🤖 Iniciar Análisis con OpenAI GPT-4' : '⚙️ Iniciar Análisis Local Avanzado'}</span>
                  </div>
                </button>
                
                <button
                  onClick={() => {
                    console.log('🧪 FORZANDO ANÁLISIS BÁSICO...');
                    const analisisBasico = generarAnalisisBasico();
                    setAnalisisIA(analisisBasico);
                    setEstadoIA({ estado: 'local', fuente: 'Análisis forzado' });
                  }}
                  className="group relative overflow-hidden bg-gradient-to-r from-gray-500 to-gray-600 text-white font-medium py-3 px-6 rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm"
                >
                  <div className="relative flex items-center justify-center space-x-2">
                    <Zap className="w-4 h-4" />
                    <span>🧪 Forzar Análisis Básico (Debug)</span>
                  </div>
                </button>
              </div>
              
              {/* Estado de Datos */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-lg mx-auto">
                <div className={`rounded-xl p-4 border-2 ${datos ? 'bg-gradient-to-br from-green-50 to-green-100 border-green-200' : 'bg-gradient-to-br from-red-50 to-red-100 border-red-200'}`}>
                  {datos ? <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-2" /> : <XCircle className="w-6 h-6 text-red-600 mx-auto mb-2" />}
                  <p className={`text-sm font-medium ${datos ? 'text-green-800' : 'text-red-800'}`}>Datos</p>
                  <p className={`text-xs ${datos ? 'text-green-600' : 'text-red-600'}`}>{datos ? 'Recibidos ✅' : 'Faltantes ❌'}</p>
                </div>
                <div className={`rounded-xl p-4 border-2 ${resumen ? 'bg-gradient-to-br from-green-50 to-green-100 border-green-200' : 'bg-gradient-to-br from-red-50 to-red-100 border-red-200'}`}>
                  {resumen ? <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-2" /> : <XCircle className="w-6 h-6 text-red-600 mx-auto mb-2" />}
                  <p className={`text-sm font-medium ${resumen ? 'text-green-800' : 'text-red-800'}`}>Resumen</p>
                  <p className={`text-xs ${resumen ? 'text-green-600' : 'text-red-600'}`}>{resumen ? 'Disponible ✅' : 'Sin datos ❌'}</p>
                </div>
                <div className={`rounded-xl p-4 border-2 ${productos ? 'bg-gradient-to-br from-green-50 to-green-100 border-green-200' : 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200'}`}>
                  {productos ? <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-2" /> : <AlertTriangle className="w-6 h-6 text-yellow-600 mx-auto mb-2" />}
                  <p className={`text-sm font-medium ${productos ? 'text-green-800' : 'text-yellow-800'}`}>Productos</p>
                  <p className={`text-xs ${productos ? 'text-green-600' : 'text-yellow-600'}`}>{productos ? 'Cargados ✅' : 'Opcionales ⚠️'}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const scoreColor = analisisIA.score >= 70 ? 'text-green-600' : 
                    analisisIA.score >= 40 ? 'text-yellow-600' : 'text-red-600';
  const scoreBg = analisisIA.score >= 70 ? 'bg-green-100' : 
                  analisisIA.score >= 40 ? 'bg-yellow-100' : 'bg-red-100';

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      {/* Hero Header con Resultados */}
      <div className="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="relative max-w-7xl mx-auto px-4 py-12">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                <Brain className="w-8 h-8 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-white mb-2">
                  🧠 Análisis IA Completado
                </h1>
                <p className="text-indigo-100 text-lg">
                  Diagnóstico inteligente de tu negocio
                </p>
                
                {/* Estado de IA en Header */}
                {estadoIA && (
                  <div className="mt-2 flex items-center text-sm">
                    {estadoIA.estado === 'conectado' && (
                      <>
                        <CheckCircle className="w-4 h-4 text-green-300 mr-2" />
                        <span className="text-indigo-100">🤖 {estadoIA.fuente}</span>
                      </>
                    )}
                    {estadoIA.estado === 'local' && (
                      <>
                        <Cpu className="w-4 h-4 text-blue-300 mr-2" />
                        <span className="text-indigo-100">⚙️ {estadoIA.fuente}</span>
                      </>
                    )}
                    {estadoIA.estado === 'error' && (
                      <>
                        <XCircle className="w-4 h-4 text-red-300 mr-2" />
                        <span className="text-indigo-100">❌ {estadoIA.fuente}</span>
                      </>
                    )}
                  </div>
                )}
              </div>
            </div>
            
            {/* Score en Header */}
            <div className="hidden lg:block">
              <div className="text-center bg-white bg-opacity-20 backdrop-blur-sm rounded-2xl p-6">
                <div className={`text-4xl font-bold text-white mb-2`}>
                  {analisisIA.score}
                </div>
                <div className="text-sm text-indigo-100">Score de Salud</div>
                <div className={`text-xs text-white font-medium px-3 py-1 rounded-full mt-2 ${
                  analisisIA.score >= 70 ? 'bg-green-500' : 
                  analisisIA.score >= 40 ? 'bg-yellow-500' : 'bg-red-500'
                }`}>
                  {analisisIA.score >= 70 ? 'EXCELENTE' : 
                   analisisIA.score >= 40 ? 'REGULAR' : 'CRÍTICO'}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 -mt-6 relative z-10">
        
        {/* Dashboard de Métricas Principales */}
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
          {/* Score Card */}
          <div className={`rounded-2xl p-6 text-white shadow-2xl transform hover:scale-105 transition-all duration-300 ${
            analisisIA.score >= 70 ? 'bg-gradient-to-br from-green-500 to-green-600' : 
            analisisIA.score >= 40 ? 'bg-gradient-to-br from-yellow-500 to-yellow-600' : 'bg-gradient-to-br from-red-500 to-red-600'
          }`}>
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <Target className="w-6 h-6" />
              </div>
              <span className="text-2xl">
                {analisisIA.score >= 70 ? '🎯' : analisisIA.score >= 40 ? '⚠️' : '🚨'}
              </span>
            </div>
            <div className="text-3xl font-bold mb-1">{analisisIA.score}</div>
            <div className="text-sm opacity-90">Score de Salud</div>
            <div className="text-xs opacity-75 mt-1">
              {analisisIA.score >= 70 ? 'Negocio Saludable' : 
               analisisIA.score >= 40 ? 'Requiere Atención' : 'Situación Crítica'}
            </div>
          </div>

          {/* Estado del Negocio */}
          <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-2xl hover:shadow-3xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <TrendingUp className="w-6 h-6" />
              </div>
              <span className="text-2xl">📊</span>
            </div>
            <div className="text-xl font-bold mb-1">{analisisIA.estado || 'Regular'}</div>
            <div className="text-sm opacity-90">Estado General</div>
            <div className="text-xs opacity-75 mt-1">Diagnóstico IA</div>
          </div>

          {/* Problemas Detectados */}
          <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-2xl hover:shadow-3xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <AlertTriangle className="w-6 h-6" />
              </div>
              <span className="text-2xl">🚨</span>
            </div>
            <div className="text-2xl font-bold mb-1">{analisisIA.problemas?.length || 0}</div>
            <div className="text-sm opacity-90">Problemas</div>
            <div className="text-xs opacity-75 mt-1">Requieren atención</div>
          </div>

          {/* Oportunidades */}
          <div className="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-2xl hover:shadow-3xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <Lightbulb className="w-6 h-6" />
              </div>
              <span className="text-2xl">💡</span>
            </div>
            <div className="text-2xl font-bold mb-1">{analisisIA.oportunidades?.length || 0}</div>
            <div className="text-sm opacity-90">Oportunidades</div>
            <div className="text-xs opacity-75 mt-1">Para optimizar</div>
          </div>
        </div>

        <div className="space-y-8">

          {/* Problemas Críticos Modernos */}
          {analisisIA.problemas.length > 0 && (
            <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
              <div className="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-gray-100">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-red-100 rounded-lg">
                    <AlertTriangle className="w-5 h-5 text-red-600" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900">🚨 Problemas Críticos Detectados</h3>
                    <p className="text-sm text-gray-600">Requieren atención inmediata para optimizar el negocio</p>
                  </div>
                </div>
              </div>
              <div className="p-6">
                <div className="space-y-6">
                  {(analisisIA.problemas || []).map((problema, index) => (
                    <div key={index} className="bg-gradient-to-r from-red-50 to-red-100 rounded-2xl p-6 border border-red-200 hover:shadow-lg transition-all duration-300">
                      <div className="flex items-start justify-between mb-4">
                        <div className="flex-1">
                          <div className="flex items-center space-x-3 mb-3">
                            <div className="p-2 bg-red-200 rounded-lg">
                              <AlertTriangle className="w-5 h-5 text-red-700" />
                            </div>
                            <h4 className="font-bold text-red-900 text-lg">{problema.titulo || 'Problema detectado'}</h4>
                          </div>
                          <p className="text-red-800 mb-4 text-sm leading-relaxed">{problema.descripcion || problema.mensaje || 'Requiere atención'}</p>
                          
                          {problema.causas && (
                            <div className="mb-4">
                              <p className="text-sm font-semibold text-red-900 mb-2">🔍 Causas identificadas:</p>
                              <ul className="text-sm text-red-800 space-y-1">
                                {(problema.causas || []).map((causa, i) => (
                                  <li key={i} className="flex items-start">
                                    <span className="text-red-600 mr-2">•</span>
                                    <span>{causa}</span>
                                  </li>
                                ))}
                              </ul>
                            </div>
                          )}
                          
                          <div className="bg-gradient-to-r from-red-100 to-red-200 p-4 rounded-xl border border-red-300">
                            <p className="text-sm font-semibold text-red-900 mb-2 flex items-center">
                              <Lightbulb className="w-4 h-4 mr-2" />
                              💡 Solución Recomendada:
                            </p>
                            <p className="text-sm text-red-800">{problema.solucion}</p>
                          </div>
                        </div>
                        
                        <div className="ml-6 text-center">
                          <div className={`px-4 py-2 rounded-xl text-xs font-bold shadow-md ${
                            problema.impacto === 'alto' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white'
                          }`}>
                            {(problema.impacto || 'MEDIO').toUpperCase()}
                          </div>
                          <p className="text-xs text-gray-600 mt-2">Prioridad</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Oportunidades Modernas */}
          {analisisIA.oportunidades.length > 0 && (
            <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
              <div className="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-100">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-green-100 rounded-lg">
                    <TrendingUp className="w-5 h-5 text-green-600" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900">🚀 Oportunidades de Crecimiento</h3>
                    <p className="text-sm text-gray-600">Potencial de optimización identificado por IA</p>
                  </div>
                </div>
              </div>
              <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {(analisisIA.oportunidades || []).map((oportunidad, index) => (
                    <div key={index} className="bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl p-6 border border-green-200 hover:shadow-lg hover:scale-105 transition-all duration-300">
                      <div className="flex items-start space-x-4">
                        <div className="p-3 bg-gradient-to-br from-green-400 to-green-500 rounded-xl shadow-md">
                          <Lightbulb className="w-6 h-6 text-white" />
                        </div>
                        <div className="flex-1">
                          <h4 className="font-bold text-green-900 text-lg mb-2">
                            {typeof oportunidad === 'string' ? oportunidad : (oportunidad.titulo || 'Oportunidad identificada')}
                          </h4>
                          <p className="text-green-800 text-sm mb-4 leading-relaxed">
                            {typeof oportunidad === 'string' ? 'Acción recomendada para optimizar el negocio' : (oportunidad.descripcion || 'Revisar implementación')}
                          </p>
                          
                          <div className="bg-gradient-to-r from-green-100 to-green-200 p-4 rounded-xl border border-green-300">
                            <p className="text-sm font-semibold text-green-900 mb-2 flex items-center">
                              <Target className="w-4 h-4 mr-2" />
                              🎯 Acción Recomendada:
                            </p>
                            <p className="text-sm text-green-800">
                              {typeof oportunidad === 'string' ? oportunidad : (oportunidad.accion || 'Implementar mejora')}
                            </p>
                            
                            {oportunidad.impacto_diario && (
                              <div className="mt-3 p-2 bg-green-200 rounded-lg">
                                <p className="text-xs font-medium text-green-900">💰 Impacto diario estimado: {oportunidad.impacto_diario}</p>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Recomendaciones IA Modernas */}
          <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
            <div className="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-blue-100 rounded-lg">
                  <Brain className="w-5 h-5 text-blue-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">🤖 Recomendaciones Inteligentes</h3>
                  <p className="text-sm text-gray-600">Plan de acción generado por IA experta</p>
                </div>
              </div>
            </div>
            <div className="p-6">
              <div className="space-y-6">
                {(analisisIA.recomendaciones || []).map((rec, index) => (
                  <div key={index} className="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl p-6 border border-blue-200 hover:shadow-lg transition-all duration-300">
                    <div className="flex items-start justify-between mb-4">
                      <div className="flex items-center space-x-3">
                        <div className="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                          <Brain className="w-6 h-6 text-white" />
                        </div>
                        <h4 className="font-bold text-blue-900 text-lg">{rec.titulo}</h4>
                      </div>
                      
                      <div className="flex items-center space-x-2">
                        <span className={`px-3 py-1 rounded-xl text-xs font-bold shadow-md ${
                          rec.prioridad === 'urgente' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' :
                          rec.prioridad === 'alta' ? 'bg-gradient-to-r from-orange-400 to-orange-500 text-white' :
                          'bg-gradient-to-r from-blue-400 to-blue-500 text-white'
                        }`}>
                          {(rec.prioridad || 'MEDIA').toUpperCase()}
                        </span>
                        <span className="px-3 py-1 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 rounded-xl text-xs font-medium">
                          {rec.categoria}
                        </span>
                      </div>
                    </div>
                    
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      <div>
                        <p className="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                          <ArrowRight className="w-4 h-4 mr-2" />
                          📋 Plan de Acción:
                        </p>
                        <div className="space-y-2">
                          {(rec.acciones || []).map((accion, i) => (
                            <div key={i} className="flex items-start bg-white bg-opacity-60 rounded-lg p-3 border border-blue-200">
                              <div className="w-6 h-6 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3 mt-0.5">
                                {i + 1}
                              </div>
                              <span className="text-sm text-blue-800 leading-relaxed">{accion}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                      
                      <div className="space-y-4">
                        <div className="bg-gradient-to-r from-blue-100 to-blue-200 p-4 rounded-xl border border-blue-300">
                          <p className="text-sm font-semibold text-blue-900 mb-2 flex items-center">
                            <Target className="w-4 h-4 mr-2" />
                            💡 Impacto Esperado:
                          </p>
                          <p className="text-sm text-blue-800">{rec.impacto}</p>
                        </div>
                        
                        {rec.inversion && (
                          <div className="bg-gradient-to-r from-green-100 to-green-200 p-4 rounded-xl border border-green-300">
                            <p className="text-sm font-semibold text-green-900 mb-2 flex items-center">
                              <DollarSign className="w-4 h-4 mr-2" />
                              💰 Inversión:
                            </p>
                            <p className="text-sm text-green-800">{rec.inversion}</p>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Controles de IA Modernos */}
          <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
            <div className="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-4 border-b border-gray-100">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-gray-100 rounded-lg">
                  <Brain className="w-5 h-5 text-gray-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">⚙️ Panel de Control IA</h3>
                  <p className="text-sm text-gray-600">Configuración y gestión del análisis inteligente</p>
                </div>
              </div>
            </div>
            <div className="p-6">
              <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between space-y-4 lg:space-y-0">
                {/* Selector de Tipo de IA */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
                  <div className="flex items-center space-x-4">
                    <div className="flex items-center">
                      <input
                        type="radio"
                        id="ia-real"
                        name="tipo-ia"
                        checked={usarIAReal}
                        onChange={() => setUsarIAReal(true)}
                        className="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500"
                      />
                      <label htmlFor="ia-real" className="ml-3 flex items-center bg-gradient-to-r from-blue-50 to-blue-100 px-3 py-2 rounded-xl border border-blue-200">
                        <span className="text-sm font-medium text-blue-800">🤖 OpenAI GPT-4</span>
                      </label>
                    </div>
                    
                    <div className="flex items-center">
                      <input
                        type="radio"
                        id="ia-local"
                        name="tipo-ia"
                        checked={!usarIAReal}
                        onChange={() => setUsarIAReal(false)}
                        className="w-5 h-5 text-purple-600 border-gray-300 focus:ring-purple-500"
                      />
                      <label htmlFor="ia-local" className="ml-3 flex items-center bg-gradient-to-r from-purple-50 to-purple-100 px-3 py-2 rounded-xl border border-purple-200">
                        <span className="text-sm font-medium text-purple-800">⚙️ IA Local</span>
                      </label>
                    </div>
                  </div>
                  
                  {analisisIA?.confianza && (
                    <div className="bg-gradient-to-r from-green-50 to-green-100 px-4 py-2 rounded-xl border border-green-200">
                      <p className="text-sm font-medium text-green-800">
                        📊 Confianza: {(analisisIA.confianza * 100).toFixed(0)}%
                      </p>
                    </div>
                  )}
                </div>
                
                {/* Botón de Regenerar */}
                <button
                  onClick={generarAnalisisIA}
                  disabled={loading}
                  className="group relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 disabled:opacity-50"
                >
                  <div className="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                  <div className="relative flex items-center space-x-2">
                    <Brain className="w-5 h-5" />
                    <span>{loading ? 'Analizando...' : '🔄 Regenerar Análisis'}</span>
                  </div>
                </button>
              </div>
              
              {/* Estado de IA */}
              {estadoIA && (
                <div className="mt-6 p-4 rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      {estadoIA.estado === 'conectado' && (
                        <>
                          <div className="p-2 bg-green-100 rounded-lg">
                            <CheckCircle className="w-4 h-4 text-green-600" />
                          </div>
                          <div>
                            <span className="text-green-700 font-semibold">✅ Conectado exitosamente</span>
                            <p className="text-sm text-green-600">{estadoIA.fuente}</p>
                          </div>
                        </>
                      )}
                      {estadoIA.estado === 'local' && (
                        <>
                          <div className="p-2 bg-blue-100 rounded-lg">
                            <Cpu className="w-4 h-4 text-blue-600" />
                          </div>
                          <div>
                            <span className="text-blue-700 font-semibold">⚙️ Análisis local activo</span>
                            <p className="text-sm text-blue-600">{estadoIA.fuente}</p>
                          </div>
                        </>
                      )}
                      {estadoIA.estado === 'error' && (
                        <>
                          <div className="p-2 bg-red-100 rounded-lg">
                            <XCircle className="w-4 h-4 text-red-600" />
                          </div>
                          <div>
                            <span className="text-red-700 font-semibold">❌ Error detectado</span>
                            <p className="text-sm text-red-600">{estadoIA.error || 'Problema de conectividad'}</p>
                          </div>
                        </>
                      )}
                    </div>
                    
                    {analisisIA?.timestamp && (
                      <div className="text-right">
                        <p className="text-xs text-gray-500">Último análisis</p>
                        <p className="text-sm text-gray-700 font-medium">
                          {new Date(analisisIA.timestamp).toLocaleTimeString()}
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>

      {/* Productos Estrella (Nuevo) */}
      {analisisIA.productos_estrella && analisisIA.productos_estrella.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-green-200 p-6">
          <h3 className="text-xl font-bold text-green-600 mb-6 flex items-center">
            <Package className="w-6 h-6 mr-3" />
            ⭐ Productos Estrella Identificados
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {analisisIA.productos_estrella.map((producto, index) => (
              <div key={index} className="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 className="font-semibold text-green-800 mb-2">{producto.nombre}</h4>
                <p className="text-sm text-green-700 mb-2">{producto.porque_es_bueno}</p>
                <div className="bg-green-100 p-2 rounded text-xs text-green-800">
                  <strong>💡 Potenciarlo:</strong> {producto.como_potenciarlo}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Optimización de Mix (Nuevo) */}
      {analisisIA.optimizacion_mix && Object.keys(analisisIA.optimizacion_mix).length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-blue-200 p-6">
          <h3 className="text-xl font-bold text-blue-600 mb-6 flex items-center">
            <BarChart3 className="w-6 h-6 mr-3" />
            🎯 Optimización de Mix de Productos
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {analisisIA.optimizacion_mix.productos_a_discontinuar && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 className="font-semibold text-red-800 mb-2">❌ Discontinuar</h4>
                <ul className="text-sm text-red-700 space-y-1">
                  {analisisIA.optimizacion_mix.productos_a_discontinuar.map((prod, i) => (
                    <li key={i}>• {prod}</li>
                  ))}
                </ul>
              </div>
            )}
            {analisisIA.optimizacion_mix.productos_a_agregar && (
              <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 className="font-semibold text-green-800 mb-2">➕ Agregar</h4>
                <ul className="text-sm text-green-700 space-y-1">
                  {analisisIA.optimizacion_mix.productos_a_agregar.map((prod, i) => (
                    <li key={i}>• {prod}</li>
                  ))}
                </ul>
              </div>
            )}
            {analisisIA.optimizacion_mix.productos_a_promocionar && (
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 className="font-semibold text-yellow-800 mb-2">🚀 Promocionar</h4>
                <ul className="text-sm text-yellow-700 space-y-1">
                  {analisisIA.optimizacion_mix.productos_a_promocionar.map((prod, i) => (
                    <li key={i}>• {prod}</li>
                  ))}
                </ul>
              </div>
            )}
            {analisisIA.optimizacion_mix.estrategia_precios && (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 className="font-semibold text-blue-800 mb-2">💰 Estrategia Precios</h4>
                <p className="text-sm text-blue-700">{analisisIA.optimizacion_mix.estrategia_precios}</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Predicciones Reales */}
      {analisisIA.predicciones && Object.keys(analisisIA.predicciones).length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-purple-200 p-6">
          <h3 className="text-xl font-bold text-purple-600 mb-6 flex items-center">
            <TrendingUp className="w-6 h-6 mr-3" />
            📊 Predicciones y Proyecciones
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <h4 className="font-semibold text-red-800 mb-2">❌ Si no actúas:</h4>
              <p className="text-sm text-red-700">{analisisIA.predicciones.si_no_actuas}</p>
            </div>
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
              <h4 className="font-semibold text-green-800 mb-2">✅ Si implementas:</h4>
              <p className="text-sm text-green-700">{analisisIA.predicciones.si_implementas_todo || analisisIA.predicciones.si_implementas_plan}</p>
            </div>
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h4 className="font-semibold text-blue-800 mb-2">⏱️ Recuperación:</h4>
              <p className="text-sm text-blue-700">{analisisIA.predicciones.tiempo_recuperacion}</p>
            </div>
            <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
              <h4 className="font-semibold text-purple-800 mb-2">📈 ROI Estimado:</h4>
              <p className="text-sm text-purple-700">{analisisIA.predicciones.roi_estimado}</p>
              {analisisIA.predicciones.punto_equilibrio && (
                <p className="text-xs text-purple-600 mt-1">Punto equilibrio: {analisisIA.predicciones.punto_equilibrio}</p>
              )}
            </div>
          </div>
        </div>
      )}

          {/* Diagnóstico Financiero Específico */}
          <DiagnosticoFinanciero 
            resumen={resumen} 
            productos={productos} 
            ventasDetalladas={datos?.ventasDetalladas} 
          />

        </div>
      </div>
    </div>
  );
};

export default AnalisisInteligente;
