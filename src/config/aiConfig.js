/**
 * src/config/aiConfig.js
 * Configuración para diferentes proveedores de IA
 * Soporte para OpenAI, Anthropic, Google, etc.
 * RELEVANT FILES: src/services/aiAnalytics.js
 */

// 🔑 CONFIGURACIÓN DE TOKENS IA
const AI_CONFIG = {
  
  // 🚀 PROVEEDORES GRATUITOS (NO REQUIEREN TOKEN)
  gratuitos: {
    huggingFace: {
      url: 'https://api-inference.huggingface.co/models/',
      modelo: 'microsoft/DialoGPT-medium',
      token: null, // No requiere
      limite: '1000 requests/día'
    },
    
    openRouterFree: {
      url: 'https://openrouter.ai/api/v1/chat/completions',
      modelo: 'mistralai/mistral-7b-instruct:free',
      token: 'OPCIONAL', // Algunos modelos gratis
      limite: 'Limitado pero funcional'
    },

    localAI: {
      descripcion: 'TensorFlow.js local',
      ventajas: 'Sin límites, sin internet, privado',
      desventajas: 'Menos potente que modelos cloud'
    }
  },

  // 💰 PROVEEDORES PREMIUM (REQUIEREN TOKEN)
  premium: {
    openai: {
      url: 'https://api.openai.com/v1/chat/completions',
      modelo: 'gpt-4o-mini', // Más barato que GPT-4
      tokenVar: 'REACT_APP_OPENAI_TOKEN',
      costo: '$0.15 por 1M tokens',
      calidad: 'Excelente'
    },

    anthropic: {
      url: 'https://api.anthropic.com/v1/messages',
      modelo: 'claude-3-haiku-20240307', // Más barato
      tokenVar: 'REACT_APP_ANTHROPIC_TOKEN',
      costo: '$0.25 por 1M tokens',
      calidad: 'Excelente'
    },

    google: {
      url: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
      modelo: 'gemini-pro',
      tokenVar: 'REACT_APP_GOOGLE_AI_TOKEN',
      costo: 'Gratis hasta 60 requests/min',
      calidad: 'Muy buena'
    },

    groq: {
      url: 'https://api.groq.com/openai/v1/chat/completions',
      modelo: 'llama-3.1-8b-instant', // Muy rápido
      tokenVar: 'REACT_APP_GROQ_TOKEN',
      costo: 'Gratis hasta 6000 tokens/min',
      velocidad: 'Ultra rápida'
    }
  }
};

// 🎯 RECOMENDACIONES POR CASO DE USO
const RECOMENDACIONES = {
  
  // Para comenzar (GRATIS)
  principiante: {
    opcion: 'Google AI (Gemini)',
    razon: 'Gratis, fácil de configurar, buena calidad',
    token: 'https://makersuite.google.com/app/apikey',
    implementacion: 'Inmediata'
  },

  // Para producción (BARATO)
  produccion: {
    opcion: 'Groq (Llama 3.1)',
    razon: 'Muy rápido, límite generoso, excelente precio',
    token: 'https://console.groq.com/keys',
    ventaja: 'Velocidad increíble'
  },

  // Para máxima calidad (PREMIUM)
  enterprise: {
    opcion: 'OpenAI GPT-4o-mini',
    razon: 'Mejor calidad de análisis, más preciso',
    token: 'https://platform.openai.com/api-keys',
    costo: 'Moderado pero excelente ROI'
  }
};

// 🔧 CONFIGURAR TOKEN
const configurarToken = (proveedor, token) => {
  // Guardar en localStorage de forma segura
  const tokenKey = AI_CONFIG.premium[proveedor]?.tokenVar;
  if (tokenKey) {
    localStorage.setItem(tokenKey, token);
    console.log(`✅ Token configurado para ${proveedor}`);
    return true;
  }
  return false;
};

// 🚀 OBTENER CONFIGURACIÓN ACTIVA
const obtenerConfigActiva = () => {
  // CONFIGURACIÓN FIJA CON TU TOKEN OPENAI
  const TOKEN_OPENAI = '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';
  
  return {
    nombre: 'openai',
    url: 'https://api.openai.com/v1/chat/completions',
    modelo: 'gpt-4o-mini',
    token: TOKEN_OPENAI,
    tipo: 'premium'
  };
};

export { 
  AI_CONFIG, 
  RECOMENDACIONES, 
  configurarToken, 
  obtenerConfigActiva 
};
