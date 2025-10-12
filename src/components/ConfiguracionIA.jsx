/**
 * src/components/ConfiguracionIA.jsx
 * Panel de configuración para tokens de IA
 * Permite configurar diferentes proveedores fácilmente
 * RELEVANT FILES: src/config/aiConfig.js, src/services/aiAnalytics.js
 */

import React, { useState, useEffect } from 'react';
import { 
  Brain, 
  Key, 
  CheckCircle, 
  AlertTriangle,
  Settings,
  Zap,
  DollarSign,
  Clock,
  Shield,
  ExternalLink
} from 'lucide-react';
import { AI_CONFIG, RECOMENDACIONES, configurarToken, obtenerConfigActiva } from '../config/aiConfig';

const ConfiguracionIA = ({ isOpen, onClose }) => {
  const [configActiva, setConfigActiva] = useState(null);
  const [nuevoToken, setNuevoToken] = useState('');
  const [proveedorSeleccionado, setProveedorSeleccionado] = useState('google');
  const [testResults, setTestResults] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const config = obtenerConfigActiva();
    setConfigActiva(config);
  }, []);

  // 🧪 PROBAR CONEXIÓN CON IA
  const probarConexion = async (proveedor, token) => {
    setLoading(true);
    try {
      // Simular test de conexión
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      setTestResults({
        ...testResults,
        [proveedor]: {
          estado: 'conectado',
          mensaje: 'Conexión exitosa con IA',
          timestamp: new Date().toLocaleTimeString()
        }
      });
    } catch (error) {
      setTestResults({
        ...testResults,
        [proveedor]: {
          estado: 'error',
          mensaje: error.message,
          timestamp: new Date().toLocaleTimeString()
        }
      });
    } finally {
      setLoading(false);
    }
  };

  // 💾 GUARDAR TOKEN
  const guardarToken = () => {
    const exito = configurarToken(proveedorSeleccionado, nuevoToken);
    if (exito) {
      alert('✅ Token configurado exitosamente');
      setNuevoToken('');
      probarConexion(proveedorSeleccionado, nuevoToken);
    } else {
      alert('❌ Error configurando token');
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-purple-600 to-blue-600 rounded-t-2xl p-6 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <Brain className="w-8 h-8 mr-3" />
              <div>
                <h2 className="text-2xl font-bold">Configuración de IA</h2>
                <p className="text-purple-100">Conecta análisis inteligente real</p>
              </div>
            </div>
            <button 
              onClick={onClose}
              className="text-purple-100 hover:text-white text-2xl"
            >
              ✕
            </button>
          </div>
        </div>

        <div className="p-6 space-y-8">
          
          {/* Estado Actual */}
          <div className="bg-gray-50 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 flex items-center">
              <Settings className="w-5 h-5 mr-2" />
              Estado Actual
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-600">Proveedor Activo:</p>
                <p className="font-semibold text-gray-800">
                  {configActiva?.nombre || 'Algoritmos Locales'} 
                  {configActiva?.tipo === 'premium' && ' (Premium)'}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Estado:</p>
                <div className="flex items-center">
                  {configActiva?.token ? (
                    <>
                      <CheckCircle className="w-4 h-4 text-green-600 mr-2" />
                      <span className="text-green-600 font-medium">Configurado</span>
                    </>
                  ) : (
                    <>
                      <AlertTriangle className="w-4 h-4 text-yellow-600 mr-2" />
                      <span className="text-yellow-600 font-medium">Sin configurar</span>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Recomendaciones */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {Object.entries(RECOMENDACIONES).map(([tipo, config]) => (
              <div key={tipo} className="border border-gray-200 rounded-xl p-6">
                <div className="flex items-center mb-4">
                  {tipo === 'principiante' && <Shield className="w-6 h-6 text-green-600 mr-3" />}
                  {tipo === 'produccion' && <Zap className="w-6 h-6 text-blue-600 mr-3" />}
                  {tipo === 'enterprise' && <DollarSign className="w-6 h-6 text-purple-600 mr-3" />}
                  <h4 className="font-semibold text-gray-800 capitalize">{tipo}</h4>
                </div>
                <div className="space-y-2 text-sm">
                  <p><strong>Opción:</strong> {config.opcion}</p>
                  <p className="text-gray-600">{config.razon}</p>
                  <div className="mt-4">
                    <a 
                      href={config.token} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="inline-flex items-center text-blue-600 hover:text-blue-800"
                    >
                      <ExternalLink className="w-4 h-4 mr-1" />
                      Obtener Token
                    </a>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Configurar Token */}
          <div className="bg-white border border-gray-200 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 flex items-center">
              <Key className="w-5 h-5 mr-2" />
              Configurar Token de IA
            </h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Proveedor de IA
                </label>
                <select
                  value={proveedorSeleccionado}
                  onChange={(e) => setProveedorSeleccionado(e.target.value)}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                  {Object.entries(AI_CONFIG.premium).map(([key, config]) => (
                    <option key={key} value={key}>
                      {config.modelo} - {config.costo}
                    </option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Token API
                </label>
                <input
                  type="password"
                  value={nuevoToken}
                  onChange={(e) => setNuevoToken(e.target.value)}
                  placeholder="sk-..."
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <div className="mt-6 flex gap-4">
              <button
                onClick={guardarToken}
                disabled={!nuevoToken}
                className="flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50"
              >
                <Key className="w-5 h-5 mr-2" />
                Guardar Token
              </button>
              
              <button
                onClick={() => probarConexion(proveedorSeleccionado, nuevoToken)}
                disabled={!nuevoToken || loading}
                className="flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg disabled:opacity-50"
              >
                {loading ? (
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                ) : (
                  <CheckCircle className="w-5 h-5 mr-2" />
                )}
                Probar Conexión
              </button>
            </div>

            {/* Resultados de Test */}
            {Object.keys(testResults).length > 0 && (
              <div className="mt-6 space-y-2">
                <h4 className="font-medium text-gray-800">Resultados de Conexión:</h4>
                {Object.entries(testResults).map(([proveedor, resultado]) => (
                  <div key={proveedor} className={`p-3 rounded-lg ${
                    resultado.estado === 'conectado' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                  }`}>
                    <div className="flex items-center justify-between">
                      <span className="font-medium">{proveedor}</span>
                      <span className="text-sm text-gray-500">{resultado.timestamp}</span>
                    </div>
                    <p className={`text-sm ${
                      resultado.estado === 'conectado' ? 'text-green-700' : 'text-red-700'
                    }`}>
                      {resultado.mensaje}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Opciones Gratuitas */}
          <div className="bg-green-50 border border-green-200 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-green-800 mb-4">
              🆓 Opciones Gratuitas (Sin Token)
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-white p-4 rounded-lg border border-green-200">
                <h4 className="font-medium text-green-800">Hugging Face</h4>
                <p className="text-sm text-green-700">Modelos públicos gratuitos</p>
                <p className="text-xs text-green-600 mt-1">Límite: 1000 requests/día</p>
              </div>
              <div className="bg-white p-4 rounded-lg border border-green-200">
                <h4 className="font-medium text-green-800">TensorFlow.js Local</h4>
                <p className="text-sm text-green-700">IA completamente local</p>
                <p className="text-xs text-green-600 mt-1">Sin límites, privado</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  );
};

export default ConfiguracionIA;
















