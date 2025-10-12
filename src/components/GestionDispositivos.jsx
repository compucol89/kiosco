/**
 * src/components/GestionDispositivos.jsx
 * Gestión de dispositivos confiables para vendedores/cajeros
 * Admin aprueba/rechaza/revoca dispositivos
 * RELEVANT FILES: api/dispositivos_confiables.php, src/components/LoginPage.jsx
 */

import React, { useState, useEffect } from 'react';
import { 
  Monitor, CheckCircle, XCircle, Clock, Trash2, 
  AlertTriangle, RefreshCw, Shield, User, Calendar
} from 'lucide-react';
import CONFIG from '../config/config';

const GestionDispositivos = () => {
  const [dispositivos, setDispositivos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [estadisticas, setEstadisticas] = useState(null);

  useEffect(() => {
    cargarDispositivos();
  }, []);

  const cargarDispositivos = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=listar_dispositivos`);
      const data = await response.json();
      
      if (data.success) {
        setDispositivos(data.dispositivos);
        setEstadisticas(data.por_estado);
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  const aprobarDispositivo = async (codigo, nombre) => {
    const nombreDispositivo = prompt('Nombre descriptivo del dispositivo:', nombre || 'PC Vendedor');
    if (!nombreDispositivo) return;

    try {
      const response = await fetch(`${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=aprobar_dispositivo`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          codigo_activacion: codigo,
          nombre_dispositivo: nombreDispositivo,
          admin_username: 'admin'
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('✅ Dispositivo aprobado exitosamente');
        cargarDispositivos();
      }
    } catch (error) {
      alert('❌ Error al aprobar dispositivo');
    }
  };

  const revocarDispositivo = async (id, nombre) => {
    if (!window.confirm(`¿Revocar acceso al dispositivo "${nombre}"?\n\nEl usuario ya no podrá iniciar sesión desde este dispositivo.`)) {
      return;
    }

    try {
      const response = await fetch(`${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=revocar_dispositivo&id=${id}`);
      const data = await response.json();
      
      if (data.success) {
        alert('✅ Acceso revocado');
        cargarDispositivos();
      }
    } catch (error) {
      alert('❌ Error al revocar dispositivo');
    }
  };

  const rechazarDispositivo = async (codigo) => {
    if (!window.confirm('¿Rechazar esta solicitud de acceso?')) {
      return;
    }

    try {
      const response = await fetch(`${CONFIG.API_URL}/api/dispositivos_confiables.php?accion=rechazar_dispositivo&codigo=${codigo}`);
      const data = await response.json();
      
      if (data.success) {
        alert('✅ Solicitud rechazada');
        cargarDispositivos();
      }
    } catch (error) {
      alert('❌ Error al rechazar');
    }
  };

  const pendientes = dispositivos.filter(d => d.estado === 'pendiente');
  const aprobados = dispositivos.filter(d => d.estado === 'aprobado');
  const otros = dispositivos.filter(d => d.estado === 'rechazado' || d.estado === 'revocado');

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl p-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <div className="p-3 bg-white bg-opacity-20 rounded-xl mr-4">
              <Monitor className="w-8 h-8" />
            </div>
            <div>
              <h2 className="text-2xl font-bold mb-2">🖥️ Dispositivos Confiables</h2>
              <p className="text-indigo-100">
                Gestiona qué computadoras pueden usar tus empleados
              </p>
            </div>
          </div>
          
          <button
            onClick={cargarDispositivos}
            className="flex items-center px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition-colors"
          >
            <RefreshCw className={`w-5 h-5 mr-2 ${loading ? 'animate-spin' : ''}`} />
            Actualizar
          </button>
        </div>
      </div>

      {/* Estadísticas */}
      {estadisticas && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Aprobados</p>
            <p className="text-3xl font-bold text-green-600">{estadisticas.aprobados}</p>
          </div>
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Pendientes</p>
            <p className="text-3xl font-bold text-yellow-600">{estadisticas.pendientes}</p>
          </div>
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Rechazados</p>
            <p className="text-3xl font-bold text-red-600">{estadisticas.rechazados}</p>
          </div>
          <div className="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p className="text-sm text-gray-600 mb-1">Revocados</p>
            <p className="text-3xl font-bold text-gray-600">{estadisticas.revocados}</p>
          </div>
        </div>
      )}

      {/* Solicitudes pendientes */}
      {pendientes.length > 0 && (
        <div className="bg-yellow-50 border-2 border-yellow-300 rounded-xl p-6">
          <h3 className="text-lg font-bold text-yellow-800 mb-4 flex items-center">
            <Clock className="w-5 h-5 mr-2" />
            ⏳ Solicitudes Pendientes ({pendientes.length})
          </h3>
          
          <div className="space-y-3">
            {pendientes.map(disp => (
              <div key={disp.id} className="bg-white rounded-lg border border-yellow-200 p-4">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center mb-2">
                      <User className="w-4 h-4 text-gray-600 mr-2" />
                      <span className="font-bold text-gray-900">{disp.usuario_solicito}</span>
                      <span className="ml-3 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                        Esperando aprobación
                      </span>
                    </div>
                    <div className="grid grid-cols-2 gap-2 text-sm text-gray-600">
                      <div>
                        <strong>Código:</strong> {disp.codigo_activacion}
                      </div>
                      <div>
                        <strong>IP:</strong> {disp.ip_primer_uso}
                      </div>
                      <div className="col-span-2">
                        <strong>Solicitado:</strong> {new Date(disp.fecha_solicitud).toLocaleString('es-AR')}
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex gap-2 ml-4">
                    <button
                      onClick={() => aprobarDispositivo(disp.codigo_activacion, disp.usuario_solicito)}
                      className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                    >
                      ✅ Aprobar
                    </button>
                    <button
                      onClick={() => rechazarDispositivo(disp.codigo_activacion)}
                      className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium"
                    >
                      ❌ Rechazar
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Dispositivos aprobados */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <CheckCircle className="w-5 h-5 mr-2 text-green-600" />
          ✅ Dispositivos Autorizados ({aprobados.length})
        </h3>
        
        {aprobados.length === 0 ? (
          <div className="text-center py-8 text-gray-500">
            <Monitor className="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>No hay dispositivos autorizados aún</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {aprobados.map(disp => (
              <div key={disp.id} className="bg-green-50 border border-green-200 rounded-lg p-4">
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center">
                    <div className="p-2 bg-green-100 rounded-lg mr-3">
                      <Monitor className="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                      <h4 className="font-bold text-gray-900">{disp.nombre_dispositivo}</h4>
                      <p className="text-xs text-gray-600">{disp.usuario_solicito}</p>
                    </div>
                  </div>
                  <button
                    onClick={() => revocarDispositivo(disp.id, disp.nombre_dispositivo)}
                    className="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition-colors"
                    title="Revocar acceso"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
                
                <div className="space-y-1 text-xs text-gray-600">
                  <div>
                    <strong>IP:</strong> {disp.ip_primer_uso}
                  </div>
                  <div>
                    <strong>Aprobado:</strong> {new Date(disp.fecha_aprobacion).toLocaleDateString('es-AR')}
                  </div>
                  {disp.ultima_actividad && (
                    <div>
                      <strong>Última vez:</strong> {new Date(disp.ultima_actividad).toLocaleString('es-AR')}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Info */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div className="flex items-start">
          <Shield className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
          <div className="text-sm text-blue-800">
            <p className="font-bold mb-2">ℹ️ Cómo funciona:</p>
            <ul className="space-y-1">
              <li>• Cuando un vendedor intenta entrar desde un PC nuevo, el sistema genera un código</li>
              <li>• El vendedor te comparte el código</li>
              <li>• Tú apruebas el dispositivo aquí</li>
              <li>• Ese PC queda autorizado permanentemente (aunque la IP cambie)</li>
              <li>• Puedes revocar acceso en cualquier momento</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GestionDispositivos;

