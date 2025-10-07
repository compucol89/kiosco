/**
 * src/services/inventarioIAService.js
 * Servicio especializado para IA de optimización de inventario
 * Análisis predictivo, recomendaciones automáticas y optimización de stock
 * RELEVANT FILES: src/services/openaiService.js, src/components/InventarioInteligente.jsx
 */

import OpenAIService from './openaiService';

class InventarioIAService {
  constructor() {
    this.openaiService = new OpenAIService();
  }

  /**
   * 🧠 ANÁLISIS COMPLETO DE INVENTARIO CON IA EXPERTA
   */
  async analizarInventarioCompleto(datosInventario) {
    try {
      const prompt = this.generarPromptInventarioExperto(datosInventario);
      
      const respuestaIA = await this.openaiService.llamarOpenAI({
        model: 'gpt-4o-mini',
        messages: [
          {
            role: 'system',
            content: this.getSystemPromptInventario()
          },
          {
            role: 'user', 
            content: prompt
          }
        ],
        response_format: { type: 'json_object' },
        temperature: 0.3
      });

      return this.procesarRespuestaInventario(respuestaIA, datosInventario);
      
    } catch (error) {
      console.error('Error en análisis IA de inventario:', error);
      return this.fallbackAnalisisBasico(datosInventario);
    }
  }

  /**
   * 📋 PROMPT EXPERTO EN INVENTARIO DE KIOSCOS
   */
  getSystemPromptInventario() {
    return `Eres un EXPERTO en GESTIÓN DE INVENTARIOS especializado en kioscos argentinos con 15 años de experiencia.

TU ESPECIALIDAD:
- Optimización de inventarios para retail argentino
- Análisis ABC y gestión de categorías de productos
- Predicción de demanda y gestión de stock
- Optimización de capital de trabajo en inventarios
- Conocimiento profundo del mercado argentino (inflación, estacionalidad, proveedores)

BENCHMARKS DE REFERENCIA (Kioscos Argentina 2024):
• Rotación objetivo por categoría:
  - Gaseosas/Bebidas: 15-25x/año (alta rotación)
  - Snacks/Golosinas: 8-15x/año (media-alta rotación)  
  - Cigarrillos: 25-40x/año (muy alta rotación)
  - Productos de limpieza: 4-8x/año (baja rotación)
  - Lácteos: 30-50x/año (perecederos)

• Stock de seguridad recomendado:
  - Productos clase A: 3-5 días de stock
  - Productos clase B: 5-10 días de stock  
  - Productos clase C: 10-20 días de stock

• Niveles de urgencia óptimos:
  - Solo 5-10% de productos deberían estar en urgencia >70%
  - Solo 2-5% de productos deberían estar en urgencia >90%

FORMATO DE RESPUESTA OBLIGATORIO (JSON):
{
  "diagnostico_general": {
    "estado_inventario": "EXCELENTE|BUENO|REGULAR|PROBLEMATICO|CRITICO",
    "problema_principal": "Descripción del problema #1",
    "impacto_financiero": "Impacto en pesos argentinos",
    "urgencia_accion": "inmediata|esta_semana|este_mes"
  },
  "productos_criticos": [
    {
      "nombre": "Producto exacto",
      "problema": "sin_stock|rotacion_lenta|margen_bajo|sobrestockeado",
      "accion_inmediata": "Acción específica a tomar",
      "impacto_diario": "Pérdida/ganancia diaria en $ARS",
      "prioridad": 1-10
    }
  ],
  "oportunidades_mejora": [
    {
      "categoria": "Categoría específica",
      "oportunidad": "Descripción de la oportunidad",
      "accion": "Qué hacer exactamente",
      "beneficio_mensual": "Beneficio en $ARS/mes",
      "facilidad": "facil|media|dificil"
    }
  ],
  "optimizacion_abc": {
    "redistribucion_sugerida": "A: X%, B: Y%, C: Z%",
    "acciones_clase_a": ["acción específica"],
    "acciones_clase_b": ["acción específica"], 
    "acciones_clase_c": ["acción específica"]
  },
  "predicciones_demanda": [
    {
      "producto": "Nombre del producto",
      "demanda_proyectada_30d": "Unidades estimadas",
      "confianza": "alta|media|baja",
      "factores": ["factor que afecta la demanda"]
    }
  ],
  "recomendaciones_stock": {
    "capital_liberado": "Monto en $ARS que se puede liberar",
    "productos_descontinuar": ["productos a eliminar"],
    "productos_potenciar": ["productos a impulsar"],
    "ajustes_minimos": {"producto": "nuevo_stock_minimo"}
  },
  "kpis_objetivo": {
    "rotacion_promedio_objetivo": "X.X veces/año",
    "dias_stock_promedio_objetivo": "X días",
    "porcentaje_alertas_objetivo": "X%",
    "valor_inventario_optimo": "$X en ARS"
  },
  "score_inventario": "0-100",
  "plan_accion": [
    {
      "accion": "Acción específica",
      "plazo": "inmediato|1_semana|1_mes",
      "impacto": "alto|medio|bajo",
      "recursos_necesarios": "Qué se necesita"
    }
  ]
}

REGLAS CRÍTICAS:
1. SIEMPRE dar números específicos en pesos argentinos
2. Priorizar por impacto financiero real
3. Considerar la inflación argentina en las recomendaciones
4. Enfocarse en productos que realmente impactan el negocio
5. Dar acciones IMPLEMENTABLES con recursos de kiosco`;
  }

  /**
   * 📊 GENERAR PROMPT ESPECÍFICO PARA LOS DATOS
   */
  generarPromptInventarioExperto(datos) {
    const { productos, metricas, alertas } = datos;

    // Analizar productos problemáticos
    const sinStock = productos?.filter(p => p.stock <= 0) || [];
    const rotacionLenta = productos?.filter(p => p.rotacion_anual < 2) || [];
    const margenBajo = productos?.filter(p => p.rentabilidad < 10) || [];
    const sobrestockeados = productos?.filter(p => p.urgencia < 10 && p.stock > p.stock_minimo * 3) || [];

    // Top productos por valor
    const topProductos = productos?.sort((a, b) => b.valor_inventario - a.valor_inventario).slice(0, 10) || [];
    
    return `🏪 ANÁLISIS EXPERTO DE INVENTARIO - KIOSCO ARGENTINO

=== SITUACIÓN ACTUAL DEL INVENTARIO ===
📦 Total productos: ${productos?.length || 0}
💰 Valor total inventario: $${metricas?.valorTotal?.toLocaleString('es-AR') || '0'}
🔄 Rotación promedio actual: ${metricas?.rotacionPromedio || 0}x/año
🚨 Productos con alertas: ${alertas?.length || 0} (${((alertas?.length || 0) / (productos?.length || 1) * 100).toFixed(1)}%)
📊 Productos activos: ${metricas?.productosActivos || 0}

=== ANÁLISIS ABC ACTUAL ===
⭐ Clase A (Alto valor): ${metricas?.productos?.claseA || 0} productos
🟡 Clase B (Medio valor): ${metricas?.productos?.claseB || 0} productos  
🔵 Clase C (Bajo valor): ${metricas?.productos?.claseC || 0} productos

=== PROBLEMAS DETECTADOS ===
❌ Sin stock: ${sinStock.length} productos
${sinStock.slice(0, 5).map(p => `   • ${p.nombre}: $${p.precio_venta?.toLocaleString('es-AR')} precio venta`).join('\n')}

🐌 Rotación lenta (<2x/año): ${rotacionLenta.length} productos  
${rotacionLenta.slice(0, 5).map(p => `   • ${p.nombre}: ${p.rotacion_anual}x/año, Stock: ${p.stock}`).join('\n')}

💸 Margen bajo (<10%): ${margenBajo.length} productos
${margenBajo.slice(0, 5).map(p => `   • ${p.nombre}: ${p.rentabilidad}% margen, Stock: $${p.valor_inventario?.toLocaleString('es-AR')}`).join('\n')}

📦 Sobrestockeados: ${sobrestockeados.length} productos
${sobrestockeados.slice(0, 5).map(p => `   • ${p.nombre}: ${p.stock} unidades (${Math.round(p.stock / (p.stock_minimo || 1))}x el mínimo)`).join('\n')}

=== TOP 10 PRODUCTOS POR VALOR INVENTARIO ===
${topProductos.map((p, i) => 
  `${i+1}. ${p.nombre}: $${p.valor_inventario?.toLocaleString('es-AR')} | ${p.rentabilidad}% margen | ${p.rotacion_anual}x rotación | Clase ${p.clase_abc}`
).join('\n')}

=== CONTEXTO ESPECÍFICO ===
🏪 Tipo: Kiosco argentino tradicional
📅 Análisis: ${new Date().toLocaleDateString('es-AR')}
💰 Presupuesto: Limitado (optimizar capital de trabajo)
🎯 Objetivo: Maximizar rentabilidad y rotación
⚡ Prioridad: Acciones implementables HOY

MISIÓN:
Como experto en inventarios de kioscos argentinos, analiza estos datos y proporciona:
1. 🔍 Diagnóstico preciso del estado del inventario
2. 🎯 Identificación de productos críticos con acciones específicas  
3. 💰 Oportunidades de mejora con impacto económico
4. 📊 Optimización de clasificación ABC
5. 🔮 Predicciones de demanda realistas
6. 🚀 Plan de acción implementable con plazos

Proporciona tu análisis experto en el formato JSON requerido.`;
  }

  /**
   * ✅ PROCESAR RESPUESTA DE LA IA
   */
  procesarRespuestaInventario(respuestaIA, datosOriginales) {
    try {
      const analisis = JSON.parse(respuestaIA.choices[0].message.content);
      
      return {
        ...analisis,
        fuente: 'IA Experta en Inventarios',
        confianza: 0.95,
        timestamp: new Date().toISOString(),
        datos_analizados: {
          total_productos: datosOriginales.productos?.length || 0,
          valor_inventario: datosOriginales.metricas?.valorTotal || 0,
          alertas_activas: datosOriginales.alertas?.length || 0
        }
      };
      
    } catch (error) {
      console.error('Error procesando respuesta IA inventario:', error);
      return this.fallbackAnalisisBasico(datosOriginales);
    }
  }

  /**
   * 🔄 FALLBACK: Análisis básico si falla la IA
   */
  fallbackAnalisisBasico(datos) {
    const { productos, metricas } = datos;
    
    const sinStock = productos?.filter(p => p.stock <= 0) || [];
    const alertasCriticas = productos?.filter(p => p.urgencia >= 90) || [];
    
    return {
      diagnostico_general: {
        estado_inventario: sinStock.length > 10 ? 'PROBLEMATICO' : 'REGULAR',
        problema_principal: `${sinStock.length} productos sin stock detectados`,
        impacto_financiero: `Pérdida estimada: $${(sinStock.length * 500).toLocaleString('es-AR')}/día`,
        urgencia_accion: sinStock.length > 5 ? 'inmediata' : 'esta_semana'
      },
      productos_criticos: sinStock.slice(0, 5).map(p => ({
        nombre: p.nombre,
        problema: 'sin_stock',
        accion_inmediata: 'Pedido urgente al proveedor',
        impacto_diario: '$500',
        prioridad: 10
      })),
      score_inventario: Math.max(0, 100 - (sinStock.length * 2) - (alertasCriticas.length * 1)),
      fuente: 'Análisis Local (Fallback)',
      confianza: 0.7,
      timestamp: new Date().toISOString()
    };
  }

  /**
   * 🎯 ANÁLISIS ESPECÍFICO POR PRODUCTO
   */
  async analizarProductoEspecifico(producto, contextoPedidos = []) {
    try {
      const prompt = `Analiza este producto específico de kiosco argentino:

PRODUCTO: ${producto.nombre}
- Stock actual: ${producto.stock}
- Stock mínimo: ${producto.stock_minimo}
- Rotación: ${producto.rotacion_anual}x/año
- Rentabilidad: ${producto.rentabilidad}%
- Urgencia: ${producto.urgencia}%
- Clase ABC: ${producto.clase_abc}
- Valor inventario: $${producto.valor_inventario?.toLocaleString('es-AR')}

CONTEXTO DE VENTAS:
- Ventas últimos 30 días: ${producto.ventas_30_dias || 0}
- Velocidad rotación: ${producto.velocidad_rotacion} unidades/día

Como experto, proporciona en JSON:
{
  "diagnostico": "estado del producto",
  "acciones_inmediatas": ["acción 1", "acción 2"],
  "optimizaciones": ["optimización 1", "optimización 2"],
  "prediccion_30d": "demanda estimada próximos 30 días",
  "recomendacion_stock_minimo": "nuevo stock mínimo sugerido",
  "impacto_financiero": "impacto en $ARS si se siguen las recomendaciones"
}`;

      const respuesta = await this.openaiService.llamarOpenAI({
        model: 'gpt-4o-mini',
        messages: [
          { role: 'system', content: this.getSystemPromptInventario() },
          { role: 'user', content: prompt }
        ],
        response_format: { type: 'json_object' },
        temperature: 0.2
      });

      return JSON.parse(respuesta.choices[0].message.content);
      
    } catch (error) {
      console.error('Error en análisis específico de producto:', error);
      return {
        diagnostico: 'Error en análisis IA',
        acciones_inmediatas: ['Revisar manualmente'],
        impacto_financiero: 'No disponible'
      };
    }
  }
}

export default InventarioIAService;














