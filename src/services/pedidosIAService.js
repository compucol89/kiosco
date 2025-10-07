/**
 * src/services/pedidosIAService.js
 * Servicio de IA especializado para optimización de pedidos
 * Análisis predictivo de demanda, optimización de compras y gestión de proveedores
 * RELEVANT FILES: src/components/InventarioInteligente.jsx, src/services/inventarioIAService.js
 */

import { obtenerConfigActiva } from '../config/aiConfig';

class PedidosIAService {
  constructor() {
    const config = obtenerConfigActiva();
    this.apiKey = config.token;
    this.baseURL = config.url.replace('/chat/completions', '');
    this.model = config.modelo;
    
    console.log('🛒 Pedidos IA Service configurado:', {
      modelo: this.model,
      tieneToken: !!this.apiKey,
      tipo: config.tipo
    });
  }

  /**
   * 🛒 ANÁLISIS COMPLETO DE PEDIDOS CON IA EXPERTA
   */
  async optimizarPedidos(datosPedidos) {
    try {
      const prompt = this.generarPromptPedidosExperto(datosPedidos);
      
      const respuestaIA = await this.llamarOpenAI({
        model: this.model,
        messages: [
          {
            role: 'system',
            content: this.getSystemPromptPedidos()
          },
          {
            role: 'user', 
            content: prompt
          }
        ],
        response_format: { type: 'json_object' },
        temperature: 0.1
      });

      return this.procesarRespuestaPedidos(respuestaIA, datosPedidos);
      
    } catch (error) {
      console.error('Error en optimización IA de pedidos:', error);
      return this.fallbackPedidosBasico(datosPedidos);
    }
  }

  /**
   * 🧠 PROMPT EXPERTO EN PEDIDOS Y COMPRAS
   */
  getSystemPromptPedidos() {
    return `Eres un EXPERTO en GESTIÓN DE COMPRAS y PEDIDOS especializado en kioscos argentinos con 20 años de experiencia.

TU ESPECIALIDAD:
- Optimización de compras para maximizar rentabilidad
- Análisis de proveedores y negociación de precios
- Predicción de demanda y gestión de stock
- Minimización de capital de trabajo en inventarios
- Conocimiento profundo del mercado argentino (inflación, estacionalidad, proveedores)

BENCHMARKS DE COMPRAS (Kioscos Argentina 2024):
• Frecuencia de pedidos óptima:
  - Productos clase A: Pedidos cada 3-5 días
  - Productos clase B: Pedidos cada 7-10 días  
  - Productos clase C: Pedidos cada 15-30 días
  - Productos perecederos: Pedidos diarios

• Cantidad óptima por pedido:
  - Stock de seguridad: 3-7 días de venta
  - Pedido mínimo: Que cubra 7-15 días
  - Máximo recomendado: 30 días (por inflación)

• Proveedores típicos Argentina:
  - Distribuidores locales: Mejor precio, entrega rápida
  - Mayoristas: Descuentos por volumen
  - Fabricantes: Mejores márgenes, pedidos grandes

ANÁLISIS QUE DEBES HACER:
1. 🔍 Identificar productos que necesitan pedido URGENTE
2. 💰 Optimizar cantidad por pedido vs. costo de almacenamiento
3. 📊 Agrupar pedidos por proveedor para descuentos
4. ⏰ Calcular timing perfecto para cada pedido
5. 🎯 Priorizar por impacto en ventas y rentabilidad
6. 💡 Detectar oportunidades de negociación con proveedores

FORMATO DE RESPUESTA OBLIGATORIO (JSON):
{
  "resumen_pedidos": {
    "total_productos_pedir": "X productos necesitan pedido",
    "inversion_total": "Monto total en $ARS",
    "ahorro_potencial": "Ahorro por optimización en $ARS",
    "urgencia_general": "baja|media|alta|critica"
  },
  "pedidos_urgentes": [
    {
      "producto": "Nombre exacto del producto",
      "stock_actual": "X unidades",
      "stock_minimo": "X unidades", 
      "dias_sin_stock": "X días hasta agotamiento",
      "cantidad_sugerida": "X unidades a pedir",
      "proveedor_recomendado": "Nombre del proveedor",
      "costo_pedido": "$X en ARS",
      "justificacion": "Por qué pedir esta cantidad específica",
      "impacto_no_pedir": "Pérdida diaria si no se pide"
    }
  ],
  "optimizacion_proveedores": [
    {
      "proveedor": "Nombre del proveedor",
      "productos_agrupar": ["producto1", "producto2", "producto3"],
      "costo_total": "$X en ARS",
      "descuento_posible": "X% por volumen",
      "fecha_sugerida": "Cuándo hacer el pedido",
      "beneficios": ["beneficio específico 1", "beneficio 2"]
    }
  ],
  "estrategia_compras": {
    "productos_discontinuar": ["productos que no conviene seguir vendiendo"],
    "productos_aumentar_stock": ["productos para aumentar inventario"],
    "negociaciones_pendientes": ["qué negociar con cada proveedor"],
    "timing_optimal": "Cuándo hacer pedidos para maximizar cash flow"
  },
  "prediccion_demanda": [
    {
      "producto": "Nombre del producto",
      "demanda_estimada_7d": "X unidades próximos 7 días",
      "demanda_estimada_30d": "X unidades próximos 30 días",
      "factores_demanda": ["factor 1", "factor 2"],
      "confianza": "alta|media|baja"
    }
  ],
  "alertas_financieras": [
    {
      "tipo": "capital_inmovilizado|oportunidad_compra|riesgo_obsolescencia",
      "mensaje": "Descripción específica del problema/oportunidad",
      "impacto_ars": "$X impacto financiero",
      "accion_recomendada": "Qué hacer específicamente"
    }
  ],
  "kpis_compras": {
    "rotacion_inventario_objetivo": "X veces/año",
    "dias_stock_promedio_objetivo": "X días",
    "capital_trabajo_optimo": "$X en ARS",
    "frecuencia_pedidos_optima": "cada X días"
  },
  "plan_accion_compras": [
    {
      "accion": "Acción específica de compras",
      "plazo": "inmediato|esta_semana|este_mes",
      "prioridad": "alta|media|baja",
      "recursos_necesarios": "$X en ARS o recurso específico",
      "impacto_esperado": "Beneficio específico en $ARS o %"
    }
  ]
}

REGLAS CRÍTICAS:
1. SIEMPRE dar cantidades y montos específicos en unidades y ARS
2. Priorizar por impacto en ventas y rentabilidad real
3. Considerar la inflación argentina en timing de compras
4. Agrupar pedidos por proveedor para negociar descuentos
5. Dar acciones IMPLEMENTABLES con recursos de kiosco disponibles
6. Optimizar capital de trabajo (no sobrestockear)`;
  }

  /**
   * 📊 GENERAR PROMPT ESPECÍFICO PARA PEDIDOS
   */
  generarPromptPedidosExperto(datos) {
    const { productos, alertas, metricas } = datos;

    // Analizar productos que necesitan pedido
    const necesitanPedido = productos?.filter(p => p.necesita_pedido || p.stock <= p.stock_minimo) || [];
    const sinStock = productos?.filter(p => p.stock <= 0) || [];
    const stockCritico = productos?.filter(p => p.urgencia >= 90) || [];

    // Agrupar por proveedor
    const proveedores = {};
    necesitanPedido.forEach(p => {
      const prov = p.proveedor || 'Sin proveedor';
      if (!proveedores[prov]) {
        proveedores[prov] = {
          productos: [],
          valorTotal: 0
        };
      }
      proveedores[prov].productos.push(p);
      proveedores[prov].valorTotal += (p.cantidad_optima_pedido || 10) * (p.precio_costo || 0);
    });
    
    return `🛒 ANÁLISIS EXPERTO DE PEDIDOS - KIOSCO ARGENTINO

=== SITUACIÓN ACTUAL DE STOCK ===
📦 Total productos: ${productos?.length || 0}
🚨 Necesitan pedido: ${necesitanPedido.length} productos
❌ Sin stock: ${sinStock.length} productos
⚠️ Stock crítico: ${stockCritico.length} productos
💰 Valor inventario actual: $${metricas?.valorTotal?.toLocaleString('es-AR') || '0'}

=== PRODUCTOS SIN STOCK (CRÍTICO) ===
${sinStock.slice(0, 10).map(p => 
  `❌ ${p.nombre}: $${p.precio_venta?.toLocaleString('es-AR')} precio venta | Proveedor: ${p.proveedor || 'Sin definir'}`
).join('\n')}

=== PRODUCTOS CON STOCK CRÍTICO ===
${stockCritico.slice(0, 10).map(p => 
  `⚠️ ${p.nombre}: ${p.stock} unidades | Mínimo: ${p.stock_minimo} | Días restantes: ${p.dias_stock || 'N/A'}`
).join('\n')}

=== ANÁLISIS POR PROVEEDOR ===
${Object.entries(proveedores).map(([proveedor, data]) => 
  `🏪 ${proveedor}: ${data.productos.length} productos | Valor total: $${data.valorTotal.toLocaleString('es-AR')}`
).join('\n')}

=== TOP PRODUCTOS POR URGENCIA DE PEDIDO ===
${necesitanPedido.slice(0, 15).map((p, i) => 
  `${i+1}. ${p.nombre}: Urgencia ${p.urgencia}% | Stock: ${p.stock}/${p.stock_minimo} | Pedido sugerido: ${p.cantidad_optima_pedido || 'N/A'} unidades`
).join('\n')}

=== CONTEXTO ESPECÍFICO ===
🏪 Tipo: Kiosco argentino tradicional
📅 Análisis: ${new Date().toLocaleDateString('es-AR')}
💰 Presupuesto: Limitado - optimizar capital de trabajo
🎯 Objetivo: Minimizar quiebres de stock y maximizar rotación
⚡ Prioridad: Pedidos que impacten ventas inmediatas

MISIÓN ESPECÍFICA:
Como experto en compras para kioscos argentinos, analiza estos datos y proporciona:

1. 🚨 Identificación de pedidos URGENTES con cantidades exactas
2. 💰 Optimización de compras por proveedor para descuentos
3. 📊 Predicción de demanda realista para próximos 7 y 30 días
4. 🎯 Estrategia de compras que minimice capital inmovilizado
5. ⏰ Timing óptimo para cada pedido considerando inflación
6. 💡 Oportunidades de negociación con proveedores
7. 🚀 Plan de acción implementable con presupuesto limitado

CONTEXTO CRÍTICO:
- Capital limitado: priorizar productos que más rotan
- Inflación alta: no sobrestockear productos de baja rotación  
- Competencia: asegurar disponibilidad de productos clave
- Cash flow: optimizar timing de pagos vs. descuentos

Proporciona tu análisis experto en el formato JSON requerido.`;
  }

  /**
   * 🌐 LLAMAR A OPENAI API
   */
  async llamarOpenAI(parametros) {
    const response = await fetch(`${this.baseURL}/v1/chat/completions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.apiKey}`
      },
      body: JSON.stringify(parametros)
    });

    if (!response.ok) {
      throw new Error(`Error OpenAI: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  }

  /**
   * ✅ PROCESAR RESPUESTA DE PEDIDOS IA
   */
  procesarRespuestaPedidos(respuestaIA, datosOriginales) {
    try {
      const analisis = JSON.parse(respuestaIA.choices[0].message.content);
      
      return {
        ...analisis,
        fuente: 'IA Experta en Pedidos y Compras',
        confianza: 0.95,
        timestamp: new Date().toISOString(),
        datos_analizados: {
          total_productos: datosOriginales.productos?.length || 0,
          productos_necesitan_pedido: datosOriginales.productos?.filter(p => p.necesita_pedido)?.length || 0,
          sin_stock: datosOriginales.productos?.filter(p => p.stock <= 0)?.length || 0
        }
      };
      
    } catch (error) {
      console.error('Error procesando respuesta IA pedidos:', error);
      return this.fallbackPedidosBasico(datosOriginales);
    }
  }

  /**
   * 🔄 FALLBACK: Análisis básico si falla la IA
   */
  fallbackPedidosBasico(datos) {
    const { productos } = datos;
    
    const sinStock = productos?.filter(p => p.stock <= 0) || [];
    const necesitanPedido = productos?.filter(p => p.necesita_pedido) || [];
    
    return {
      resumen_pedidos: {
        total_productos_pedir: `${necesitanPedido.length} productos necesitan pedido`,
        inversion_total: `$${(necesitanPedido.length * 5000).toLocaleString('es-AR')} estimado`,
        urgencia_general: sinStock.length > 10 ? 'critica' : 'media'
      },
      pedidos_urgentes: sinStock.slice(0, 5).map(p => ({
        producto: p.nombre,
        stock_actual: p.stock,
        cantidad_sugerida: p.cantidad_optima_pedido || 20,
        impacto_no_pedir: '$500/día pérdida estimada'
      })),
      fuente: 'Análisis Local (Fallback)',
      confianza: 0.7,
      timestamp: new Date().toISOString()
    };
  }

  /**
   * 🎯 ANÁLISIS ESPECÍFICO DE PROVEEDOR
   */
  async analizarProveedor(proveedor, productos) {
    try {
      const prompt = `Analiza este proveedor específico de kiosco argentino:

PROVEEDOR: ${proveedor}
PRODUCTOS: ${productos.length}

DETALLE DE PRODUCTOS:
${productos.map(p => `- ${p.nombre}: Stock ${p.stock}/${p.stock_minimo}, Costo $${p.precio_costo}, Urgencia ${p.urgencia}%`).join('\n')}

Como experto en compras, proporciona en JSON:
{
  "estrategia_proveedor": "Plan específico para este proveedor",
  "pedido_optimizado": {
    "productos_incluir": ["producto1", "producto2"],
    "cantidad_total": "X unidades",
    "monto_total": "$X ARS",
    "descuento_negociable": "X%"
  },
  "timing_optimo": "Cuándo hacer el pedido",
  "negociacion": {
    "puntos_fuertes": ["qué negociar"],
    "descuentos_posibles": "X% por volumen, Y% por pago contado",
    "condiciones_proponer": "términos específicos"
  },
  "alternativas": "Otros proveedores o estrategias"
}`;

      const respuesta = await this.llamarOpenAI({
        model: this.model,
        messages: [
          { role: 'system', content: this.getSystemPromptPedidos() },
          { role: 'user', content: prompt }
        ],
        response_format: { type: 'json_object' },
        temperature: 0.1
      });

      return JSON.parse(respuesta.choices[0].message.content);
      
    } catch (error) {
      console.error('Error en análisis de proveedor:', error);
      return {
        estrategia_proveedor: 'Error en análisis IA',
        pedido_optimizado: { monto_total: 'No disponible' }
      };
    }
  }
}

export default PedidosIAService;














