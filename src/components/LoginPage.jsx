import React, { useState, useEffect } from 'react';
import { User, Lock, LogIn, AlertCircle, Store, Shield, Copy, Check } from 'lucide-react';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';
import { generateDeviceFingerprint, getDeviceInfo } from '../utils/deviceFingerprint';

const LoginPage = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const { login } = useAuth();
  
  // Estados para dispositivos
  const [deviceFingerprint, setDeviceFingerprint] = useState('');
  const [dispositivoBloqueado, setDispositivoBloqueado] = useState(false);
  const [codigoActivacion, setCodigoActivacion] = useState('');
  const [estadoDispositivo, setEstadoDispositivo] = useState(''); // 'pendiente', 'rechazado'
  const [copiado, setCopiado] = useState(false);
  
  // Generar fingerprint al cargar
  useEffect(() => {
    const initFingerprint = async () => {
      const fp = await generateDeviceFingerprint();
      setDeviceFingerprint(fp);
    };
    initFingerprint();
  }, []);
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!username || !password) {
      setError('Por favor, ingrese usuario y contraseña.');
      return;
    }
    
    setLoading(true);
    setError(null);
    
    try {
      // 🔐 BYPASS: Admin nunca requiere validación de dispositivo
      const isAdmin = username.toLowerCase() === 'admin';
      
      if (!isAdmin) {
        // 🔐 PASO 1: Verificar dispositivo confiable (solo para NO admin)
        const dispositivoResponse = await axios.get(
          `${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=verificar_dispositivo&fingerprint=${encodeURIComponent(deviceFingerprint)}&username=${encodeURIComponent(username)}`
        );
        
        if (dispositivoResponse.data && !dispositivoResponse.data.acceso_concedido) {
          // Dispositivo no autorizado
          if (dispositivoResponse.data.requiere_aprobacion) {
            // Solicitar código de activación
            const solicitudResponse = await axios.post(
              `${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=solicitar_acceso`,
              {
                device_fingerprint: deviceFingerprint,
                username: username
              }
            );
            
            if (solicitudResponse.data.success) {
              setCodigoActivacion(solicitudResponse.data.codigo_activacion);
              setDispositivoBloqueado(true);
              setEstadoDispositivo('pendiente');
              setLoading(false);
              return;
            }
          } else if (dispositivoResponse.data.estado === 'pendiente') {
            // Ya tiene solicitud pendiente
            setCodigoActivacion(dispositivoResponse.data.codigo_activacion);
            setDispositivoBloqueado(true);
            setEstadoDispositivo('pendiente');
            setLoading(false);
            return;
          } else {
            setError(dispositivoResponse.data.motivo || 'Dispositivo no autorizado');
            setLoading(false);
            return;
          }
        }
      }
      
      // 🔐 PASO 2: Proceder con autenticación (admin bypasea validación de dispositivo)
      const response = await axios.post(`${CONFIG.API_URL}/api/auth.php`, {
        username: username,
        password: password
      });
      
      if (response.data && response.data.success) {
        // El usuario se autenticó correctamente
        const userData = response.data.user;
        
        // Guardar token en localStorage
        localStorage.setItem('authToken', response.data.token);
        
        // Llamar a la función de login del contexto
        login(userData);
      } else {
        setError(response.data?.message || 'Credenciales inválidas. Intente nuevamente.');
      }
    } catch (err) {
      console.error('Error de autenticación:', err);
      setError('Error de autenticación. Verifique la conexión con el servidor.');
    } finally {
      setLoading(false);
    }
  };
  
  const copiarCodigo = () => {
    navigator.clipboard.writeText(codigoActivacion);
    setCopiado(true);
    setTimeout(() => setCopiado(false), 2000);
  };

  const intentarNuevamente = () => {
    setDispositivoBloqueado(false);
    setCodigoActivacion('');
    setEstadoDispositivo('');
    setError(null);
  };
  
  // Pantalla de código de activación
  if (dispositivoBloqueado && codigoActivacion) {
    const deviceInfo = getDeviceInfo();
    
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="p-8 bg-white rounded-lg shadow-2xl w-full max-w-lg">
          <div className="text-center mb-8">
            <div className="flex justify-center mb-4">
              <div className="p-4 bg-yellow-100 rounded-full">
                <Shield size={48} className="text-yellow-600" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">🔐 Dispositivo No Autorizado</h1>
            <p className="text-gray-600">
              Este dispositivo necesita ser aprobado por un administrador
            </p>
          </div>
          
          {/* Código de activación */}
          <div className="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl p-6 mb-6">
            <p className="text-sm font-medium text-yellow-800 mb-3 text-center">
              📋 Comparte este código con tu administrador:
            </p>
            <div className="bg-white rounded-lg p-4 mb-4">
              <p className="text-3xl font-bold text-center text-gray-800 font-mono tracking-wider">
                {codigoActivacion}
              </p>
            </div>
            <button
              onClick={copiarCodigo}
              className={`w-full flex items-center justify-center px-4 py-3 rounded-lg font-medium transition-colors ${
                copiado 
                  ? 'bg-green-500 text-white' 
                  : 'bg-yellow-600 hover:bg-yellow-700 text-white'
              }`}
            >
              {copiado ? (
                <>
                  <Check className="w-5 h-5 mr-2" />
                  ¡Código Copiado!
                </>
              ) : (
                <>
                  <Copy className="w-5 h-5 mr-2" />
                  Copiar Código
                </>
              )}
            </button>
          </div>

          {/* Información del dispositivo */}
          <div className="bg-gray-50 rounded-lg p-4 mb-6">
            <p className="text-sm font-medium text-gray-700 mb-2">📱 Información del Dispositivo:</p>
            <div className="text-sm text-gray-600 space-y-1">
              <p><strong>Usuario:</strong> {username}</p>
              <p><strong>Sistema:</strong> {deviceInfo.os}</p>
              <p><strong>Navegador:</strong> {deviceInfo.browser}</p>
            </div>
          </div>

          {/* Instrucciones */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p className="text-sm font-medium text-blue-800 mb-2">ℹ️ Qué hacer ahora:</p>
            <ol className="text-sm text-blue-700 space-y-1 list-decimal list-inside">
              <li>Copia el código con el botón de arriba</li>
              <li>Compártelo con el administrador (WhatsApp, teléfono, etc.)</li>
              <li>Espera a que lo apruebe desde Configuración → Dispositivos</li>
              <li>Una vez aprobado, intenta iniciar sesión nuevamente</li>
            </ol>
          </div>

          <button
            onClick={intentarNuevamente}
            className="w-full px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
          >
            ← Volver al Login
          </button>
        </div>
      </div>
    );
  }
  
  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100">
      <div className="p-6 bg-white rounded-lg shadow-lg w-full max-w-md">
        <div className="text-center mb-8">
          <div className="flex justify-center mb-2">
            <Store size={48} className="text-blue-600" />
          </div>
          <h1 className="text-2xl font-bold text-gray-800">Tayrona Almacén</h1>
          <p className="text-gray-600">Sistema de Gestión de Inventario</p>
        </div>
        
        {error && (
          <div className="mb-4 p-3 bg-red-100 text-red-700 rounded-md flex items-center">
            <AlertCircle size={18} className="mr-2" />
            <span>{error}</span>
          </div>
        )}
        
        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-1">
              Usuario
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <User size={18} className="text-gray-400" />
              </div>
              <input
                id="username"
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="pl-10 w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ingrese su nombre de usuario"
              />
            </div>
          </div>
          
          <div className="mb-6">
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
              Contraseña
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Lock size={18} className="text-gray-400" />
              </div>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="pl-10 w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ingrese su contraseña"
              />
            </div>
          </div>
          
          <button
            type="submit"
            className="w-full flex items-center justify-center bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            disabled={loading}
          >
            {loading ? (
              <span className="flex items-center">
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Procesando...
              </span>
            ) : (
              <span className="flex items-center">
                <LogIn size={18} className="mr-2" />
                Iniciar Sesión
              </span>
            )}
          </button>
          
          <div className="mt-6 text-center text-sm text-gray-500">
            <div className="bg-green-50 border border-green-200 rounded-md p-3 mb-3">
              <p className="text-green-800 font-medium">🚀 Modo Producción</p>
              <p className="text-green-700">Sistema conectado al backend</p>
            </div>
            <p className="font-medium text-gray-700">Use sus credenciales reales</p>
          </div>
        </form>
      </div>
    </div>
  );
};

export default LoginPage; 