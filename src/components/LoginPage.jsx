import React, { useState } from 'react';
import { User, Lock, LogIn, AlertCircle, Store } from 'lucide-react';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';
import CONFIG from '../config/config';

const LoginPage = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const { login } = useAuth();
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!username || !password) {
      setError('Por favor, ingrese usuario y contraseña.');
      return;
    }
    
    setLoading(true);
    setError(null);
    
    try {
      // Realizar solicitud a la API real
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