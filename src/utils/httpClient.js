// File: src/utils/httpClient.js
// Centralized HTTP client with API key injection
// Exists to add X-Api-Key header automatically to all API requests
// Related files: src/config/config.js, api/api_key_middleware.php, api/cors_middleware.php

import axios from 'axios';
import CONFIG from '../config/config';

/**
 * 🔐 API KEY PARA PROTECCIÓN DE BACKEND
 * 
 * Esta key NO es para autenticación de usuario (eso es el token de login)
 * Es una capa adicional para prevenir:
 * - Scraping de la API
 * - Acceso desde scripts no autorizados
 * - Requests desde orígenes desconocidos
 * 
 * La API key debe coincidir con el backend (api/api_key_middleware.php)
 */

// API Key según ambiente
const getApiKey = () => {
  // Prioridad 1: Variable de entorno (producción/staging)
  if (process.env.REACT_APP_API_KEY) {
    return process.env.REACT_APP_API_KEY;
  }
  
  // Prioridad 2: Fallback para desarrollo local
  // IMPORTANTE: En producción DEBE estar en .env
  if (process.env.NODE_ENV === 'development') {
    return 'kiosco-api-2025-cambiar-en-produccion';
  }
  
  // Prioridad 3: Error si no hay key en producción
  console.error('⚠️ FALTA API_KEY: Definir REACT_APP_API_KEY en .env');
  return ''; // Fallará en backend, lo cual es correcto
};

/**
 * 🌐 CLIENTE HTTP AXIOS CON CONFIGURACIÓN CENTRALIZADA
 * 
 * Incluye:
 * - Base URL configurada
 * - Headers comunes (X-Api-Key, Content-Type)
 * - Interceptores para auth token
 * - Manejo de errores centralizado
 */
const httpClient = axios.create({
  baseURL: CONFIG.API_URL,
  timeout: 30000, // 30 segundos
  headers: {
    'Content-Type': 'application/json'
  }
});

/**
 * 📤 INTERCEPTOR DE REQUEST
 * Agrega headers necesarios antes de cada request
 */
httpClient.interceptors.request.use(
  (config) => {
    // 1️⃣ Agregar API Key a TODAS las requests
    const apiKey = getApiKey();
    if (apiKey) {
      config.headers['X-Api-Key'] = apiKey;
    }
    
    // 2️⃣ Agregar Auth Token si existe (para usuario autenticado)
    const authToken = localStorage.getItem('authToken');
    if (authToken) {
      config.headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    // 3️⃣ Agregar timestamp para debugging
    config.headers['X-Request-Time'] = new Date().toISOString();
    
    // Log en desarrollo
    if (process.env.NODE_ENV === 'development') {
      console.log('🚀 HTTP Request:', {
        method: config.method?.toUpperCase(),
        url: config.url,
        hasApiKey: !!apiKey,
        hasAuthToken: !!authToken
      });
    }
    
    return config;
  },
  (error) => {
    console.error('❌ Request Interceptor Error:', error);
    return Promise.reject(error);
  }
);

/**
 * 📥 INTERCEPTOR DE RESPONSE
 * Maneja respuestas y errores de forma centralizada
 */
httpClient.interceptors.response.use(
  (response) => {
    // Log en desarrollo
    if (process.env.NODE_ENV === 'development') {
      console.log('✅ HTTP Response:', {
        status: response.status,
        url: response.config.url,
        data: response.data
      });
    }
    
    return response;
  },
  (error) => {
    // Manejar errores comunes
    if (error.response) {
      const { status, data } = error.response;
      
      // 401 Unauthorized - Token o API Key inválido
      if (status === 401) {
        console.error('🔐 Error 401: No autorizado');
        
        // Si el error es por API Key, es crítico
        if (data?.error === 'API key required' || data?.error === 'Invalid API key') {
          console.error('❌ API KEY INVÁLIDA - Verificar configuración');
        }
        
        // Si el error es por Auth Token, logout
        if (data?.error === 'Token inválido' || data?.error === 'Sesión inválida') {
          console.warn('⚠️ Token expirado - Redirigiendo a login...');
          // Limpiar localStorage
          localStorage.removeItem('authToken');
          localStorage.removeItem('currentUser');
          // Redirigir a login (el componente se encargará)
          window.location.href = '/#/login';
        }
      }
      
      // 403 Forbidden - Sin permisos
      else if (status === 403) {
        console.error('🚫 Error 403: Acceso denegado - Sin permisos');
      }
      
      // 429 Too Many Requests - Rate limiting
      else if (status === 429) {
        console.warn('⏱️ Error 429: Demasiadas peticiones - Rate limiting activo');
        const retryAfter = data?.retry_after || 15;
        console.warn(`Reintentar en ${retryAfter} minutos`);
      }
      
      // 500 Internal Server Error
      else if (status === 500) {
        console.error('💥 Error 500: Error interno del servidor');
      }
    } else if (error.request) {
      // Request enviado pero sin respuesta
      console.error('📡 Error de Red: No se recibió respuesta del servidor');
    } else {
      // Error al configurar el request
      console.error('⚙️ Error de Configuración:', error.message);
    }
    
    return Promise.reject(error);
  }
);

/**
 * 🛠️ HELPERS PARA REQUESTS COMUNES
 */

/**
 * GET request helper
 */
export const get = (url, config = {}) => {
  return httpClient.get(url, config);
};

/**
 * POST request helper
 */
export const post = (url, data = {}, config = {}) => {
  return httpClient.post(url, data, config);
};

/**
 * PUT request helper
 */
export const put = (url, data = {}, config = {}) => {
  return httpClient.put(url, data, config);
};

/**
 * DELETE request helper
 */
export const del = (url, config = {}) => {
  return httpClient.delete(url, config);
};

/**
 * 🔍 Helper para requests sin API Key (solo para endpoints públicos)
 * Ejemplo: health check, status endpoints
 */
export const getPublic = (url, config = {}) => {
  return axios.get(`${CONFIG.API_URL}${url}`, {
    ...config,
    headers: {
      ...config.headers,
      // No enviar X-Api-Key
    }
  });
};

/**
 * 📊 Verificar configuración de API Key
 */
export const checkApiKeyConfig = () => {
  const apiKey = getApiKey();
  const hasEnvKey = !!process.env.REACT_APP_API_KEY;
  const isProduction = process.env.NODE_ENV === 'production';
  
  if (isProduction && !hasEnvKey) {
    console.error('❌ CONFIGURACIÓN INCORRECTA');
    console.error('En producción DEBE existir REACT_APP_API_KEY en .env');
    return false;
  }
  
  console.log('✅ Configuración de API Key:', {
    hasKey: !!apiKey,
    keyLength: apiKey?.length || 0,
    environment: process.env.NODE_ENV,
    usingEnvVar: hasEnvKey
  });
  
  return true;
};

/**
 * 🔐 CONFIGURACIÓN EN .env
 * 
 * Para usar este cliente, agregar en .env:
 * 
 * DESARROLLO (.env.development):
 * REACT_APP_API_KEY=kiosco-api-2025-cambiar-en-produccion
 * 
 * PRODUCCIÓN (.env.production):
 * REACT_APP_API_KEY=tu-key-generada-en-backend
 * 
 * Generar nueva key:
 * php -r "echo bin2hex(random_bytes(32));"
 */

// Export default
export default httpClient;

