/**
 * src/components/SeguridadAcceso.jsx
 * Módulo de configuración de seguridad de acceso por IP
 * Controla que vendedores solo puedan acceder desde el negocio
 * RELEVANT FILES: api/seguridad_acceso.php, src/components/LoginPage.jsx
 */

import React, { useState, useEffect } from 'react';
import { 
  Shield, Save, AlertTriangle, CheckCircle, Lock,
  MapPin, Wifi, Eye, RefreshCw, Info
} from 'lucide-react';
import CONFIG from '../config/config';

const SeguridadAcceso = () => {
  const [config, setConfig] = useState(null);
  const [loading, setLoading] = useState(true);
  const [guardando, setGuardando] = useState(false);
  const [ipNegocio, setIpNegocio] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [miIpActual, setMiIpActual] = useState('Detectando...');
  const [logs, setLogs] = useState([]);
  const [mostrarLogs, setMostrarLogs] = useState(false);

  useEffect(() => {
    cargarConfiguracion();
    obtenerMiIP();
  }, []);

  const cargarConfiguracion = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/seguridad_acceso.php?accion=obtener_config`);
      const data = await response.json();
      
      if (data.success) {
        setConfig(data.configuracion);
        setIpNegocio(data.configuracion.ip_negocio);
        setDescripcion(data.configuracion.descripcion || '');
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  const obtenerMiIP = async () => {
    try {
      const response = await fetch('https://api.ipify.org?format=json');
      const data = await response.json();
      setMiIpActual(data.ip);
    } catch (error) {
      setMiIpActual('No detectada');
    }
  };

  const guardarConfiguracion = async () => {
    if (!ipNegocio || ipNegocio === '0.0.0.0') {
      alert('⚠️ Debes configurar una IP válida del negocio');
      return;
    }

    setGuardando(true);
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/seguridad_acceso.php?accion=guardar_config`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ip_negocio: ipNegocio,
          descripcion: descripcion
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('✅ Configuración guardada. A partir de ahora los vendedores solo podrán acceder desde esta IP.');
        cargarConfiguracion();
      } else {
        alert('❌ Error: ' + data.error);
      }
    } catch (error) {
      alert('❌ Error al guardar configuración');
    } finally {
      setGuardando(false);
    }
  };

  const cargarLogs = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/seguridad_acceso.php?accion=logs&limite=20`);
      const data = await response.json();
      
      if (data.success) {
        setLogs(data.logs);
      }
    } catch (error) {
      console.error('Error cargando logs:', error);
    }
  };

  const usarMiIPActual = () => {
    if (miIpActual && miIpActual !== 'Detectando...' && miIpActual !== 'No detectada') {
      setIpNegocio(miIpActual);
      setDescripcion('IP detectada automáticamente desde este dispositivo');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 text-blue-600 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-red-500 to-orange-600 text-white rounded-xl p-6">
        <div className="flex items-center">
          <div className="p-3 bg-white bg-opacity-20 rounded-xl mr-4">
            <Shield className="w-8 h-8" />
          </div>
          <div>
            <h2 className="text-2xl font-bold mb-2">🔐 Seguridad de Acceso</h2>
            <p className="text-red-100">
              Controla desde dónde pueden iniciar sesión tus empleados
            </p>
          </div>
        </div>
      </div>

      {/* Configuración de IP */}
      <div className="bg-white rounded-xl shadow-sm border-2 border-red-200 p-6">
        <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <MapPin className="w-5 h-5 mr-2 text-red-600" />
          📍 IP del Negocio
        </h3>

        {/* Info banner */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div className="flex items-start">
            <Info className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
            <div className="text-sm text-blue-800">
              <p className="font-bold mb-2">ℹ️ Cómo funciona:</p>
              <ul className="space-y-1">
                <li>• <strong>Administradores:</strong> Pueden iniciar sesión desde cualquier lugar (casa, celular, etc.)</li>
                <li>• <strong>Vendedores/Cajeros:</strong> Solo pueden iniciar sesión desde la IP configurada aquí</li>
                <li>• Si un vendedor intenta entrar desde casa, el sistema lo rechazará automáticamente</li>
              </ul>
            </div>
          </div>
        </div>

        {/* Tu IP actual */}
        <div className="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-green-700 font-medium mb-1">🌐 Tu IP Actual (este dispositivo)</p>
              <p className="text-2xl font-bold text-green-800">{miIpActual}</p>
              <p className="text-xs text-green-600 mt-1">Esta es la IP desde donde estás accediendo ahora</p>
            </div>
            <button
              onClick={usarMiIPActual}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
            >
              ✅ Usar Esta IP
            </button>
          </div>
        </div>

        {/* Formulario */}
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-bold text-gray-700 mb-2">
              🔒 IP Permitida del Negocio
            </label>
            <input
              type="text"
              value={ipNegocio}
              onChange={(e) => setIpNegocio(e.target.value)}
              className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-lg font-mono"
              placeholder="Ej: 190.123.45.67"
            />
            <p className="text-xs text-gray-500 mt-1">
              Solo desde esta IP podrán iniciar sesión vendedores y cajeros
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              📝 Descripción
            </label>
            <input
              type="text"
              value={descripcion}
              onChange={(e) => setDescripcion(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
              placeholder="Ej: PC Principal del Kiosco"
            />
          </div>

          <div className="flex justify-end">
            <button
              onClick={guardarConfiguracion}
              disabled={guardando}
              className="flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold transition-colors disabled:opacity-50"
            >
              {guardando ? (
                <>
                  <RefreshCw className="w-5 h-5 mr-2 animate-spin" />
                  Guardando...
                </>
              ) : (
                <>
                  <Save className="w-5 h-5 mr-2" />
                  Guardar Configuración
                </>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Ejemplo de uso */}
      <div className="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6">
        <h4 className="font-bold text-yellow-800 mb-3 flex items-center">
          <AlertTriangle className="w-5 h-5 mr-2" />
          ⚠️ Ejemplo de Funcionamiento
        </h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="bg-white rounded-lg p-4 border border-green-200">
            <p className="font-bold text-green-800 mb-2">✅ PERMITIDO:</p>
            <ul className="text-sm text-green-700 space-y-1">
              <li>• Admin desde casa → ✅ Permitido</li>
              <li>• Admin desde el negocio → ✅ Permitido</li>
              <li>• Admin desde celular → ✅ Permitido</li>
              <li>• Vendedor desde el negocio → ✅ Permitido</li>
            </ul>
          </div>
          <div className="bg-white rounded-lg p-4 border border-red-200">
            <p className="font-bold text-red-800 mb-2">❌ BLOQUEADO:</p>
            <ul className="text-sm text-red-700 space-y-1">
              <li>• Vendedor desde casa → ❌ Bloqueado</li>
              <li>• Vendedor desde celular → ❌ Bloqueado</li>
              <li>• Cajero desde WiFi pública → ❌ Bloqueado</li>
              <li>• Cualquier empleado desde otra IP → ❌ Bloqueado</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Logs de acceso */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-bold text-gray-800 flex items-center">
            <Eye className="w-5 h-5 mr-2 text-blue-600" />
            📋 Registro de Intentos de Acceso
          </h3>
          <button
            onClick={() => {
              cargarLogs();
              setMostrarLogs(!mostrarLogs);
            }}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <Eye className="w-4 h-4 mr-2" />
            {mostrarLogs ? 'Ocultar' : 'Ver Logs'}
          </button>
        </div>

        {mostrarLogs && (
          <div className="overflow-x-auto">
            {logs.length === 0 ? (
              <p className="text-center text-gray-500 py-8">No hay registros aún</p>
            ) : (
              <table className="w-full text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-2 text-left">Fecha/Hora</th>
                    <th className="px-4 py-2 text-left">Usuario</th>
                    <th className="px-4 py-2 text-left">IP Origen</th>
                    <th className="px-4 py-2 text-left">Resultado</th>
                    <th className="px-4 py-2 text-left">Motivo</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {logs.map((log, idx) => (
                    <tr key={idx} className={log.exito ? 'bg-green-50' : 'bg-red-50'}>
                      <td className="px-4 py-2">{new Date(log.timestamp).toLocaleString('es-AR')}</td>
                      <td className="px-4 py-2 font-medium">{log.username}</td>
                      <td className="px-4 py-2 font-mono text-xs">{log.ip_origen}</td>
                      <td className="px-4 py-2">
                        {log.exito ? (
                          <span className="px-2 py-1 bg-green-500 text-white rounded-full text-xs font-bold">
                            ✅ Permitido
                          </span>
                        ) : (
                          <span className="px-2 py-1 bg-red-500 text-white rounded-full text-xs font-bold">
                            ❌ Bloqueado
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-2 text-xs">{log.motivo_rechazo || '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default SeguridadAcceso;

