/**
 * src/components/GestionPermisos.jsx
 * Módulo visual para gestión de permisos por rol
 * Permite configurar qué módulos puede acceder cada tipo de usuario
 * RELEVANT FILES: src/services/permisosService.js, api/permisos_usuario.php
 */

import React, { useState, useEffect } from 'react';
import { 
  Shield, Save, RefreshCw, CheckCircle, XCircle, 
  Users, Eye, EyeOff, AlertTriangle, Check, X,
  Home, Calculator, ShoppingCart, Package, BarChart3,
  Settings, Clock, DollarSign, Store
} from 'lucide-react';
import permisosService from '../services/permisosService';

const GestionPermisos = () => {
  const [permisos, setPermisos] = useState({});
  const [permisosCopia, setPermisosCopia] = useState({});
  const [loading, setLoading] = useState(true);
  const [guardando, setGuardando] = useState(false);
  const [mensaje, setMensaje] = useState(null);
  const [modulos, setModulos] = useState({});
  const [hayCambios, setHayCambios] = useState(false);

  // Íconos por módulo para UI moderna
  const iconosPorModulo = {
    'Inicio': Home,
    'PuntoDeVenta': ShoppingCart,
    'Ventas': BarChart3,
    'ControlCaja': Calculator,
    'Inventario': Package,
    'Productos': Store,
    'Reportes': BarChart3,
    'GastosFijos': DollarSign,
    'Usuarios': Users,
    'Configuracion': Settings,
    'HistorialTurnos': Clock
  };

  // Descripciones por rol
  const descripcionesRoles = {
    admin: {
      nombre: 'Administrador',
      descripcion: 'Acceso total al sistema. Gestiona configuración, usuarios y reportes financieros.',
      color: 'blue',
      icono: '👑'
    },
    vendedor: {
      nombre: 'Vendedor',
      descripcion: 'Usuario básico para ventas. Acceso a POS, inventario y consulta de productos.',
      color: 'green',
      icono: '🛒'
    },
    cajero: {
      nombre: 'Cajero',
      descripcion: 'Usuario con acceso a caja y ventas. Puede abrir/cerrar turnos y vender.',
      color: 'purple',
      icono: '💰'
    }
  };

  // Cargar permisos al montar
  useEffect(() => {
    cargarPermisos();
  }, []);

  // Detectar cambios
  useEffect(() => {
    const cambiosDetectados = JSON.stringify(permisos) !== JSON.stringify(permisosCopia);
    setHayCambios(cambiosDetectados);
  }, [permisos, permisosCopia]);

  const cargarPermisos = async () => {
    setLoading(true);
    try {
      const resultado = await permisosService.obtenerPermisos();
      
      if (resultado.success) {
        setPermisos(resultado.permisos);
        setPermisosCopia(JSON.parse(JSON.stringify(resultado.permisos))); // Deep copy
        setModulos(resultado.modulos || {});
        setMensaje({ tipo: 'success', texto: 'Permisos cargados correctamente' });
        setTimeout(() => setMensaje(null), 3000);
      } else {
        // Usar permisos por defecto si no hay en BD
        const permisosDefecto = permisosService.getPermisosDefecto();
        setPermisos(permisosDefecto);
        setPermisosCopia(JSON.parse(JSON.stringify(permisosDefecto)));
        setMensaje({ tipo: 'info', texto: 'Usando configuración por defecto' });
      }
    } catch (error) {
      console.error('Error cargando permisos:', error);
      setMensaje({ tipo: 'error', texto: 'Error al cargar permisos' });
    } finally {
      setLoading(false);
    }
  };

  const guardarPermisos = async () => {
    setGuardando(true);
    try {
      const resultado = await permisosService.actualizarPermisos(permisos);
      
      if (resultado.success) {
        setPermisosCopia(JSON.parse(JSON.stringify(permisos))); // Actualizar copia
        setMensaje({ tipo: 'success', texto: '✅ Permisos guardados correctamente' });
        setTimeout(() => setMensaje(null), 5000);
      } else {
        setMensaje({ tipo: 'error', texto: 'Error al guardar permisos: ' + resultado.message });
      }
    } catch (error) {
      console.error('Error guardando permisos:', error);
      setMensaje({ tipo: 'error', texto: 'Error al guardar permisos' });
    } finally {
      setGuardando(false);
    }
  };

  const togglePermiso = (rol, modulo) => {
    // No permitir desactivar "Inicio" para ningún rol
    if (modulo === 'Inicio') {
      setMensaje({ tipo: 'warning', texto: '⚠️ Todos los usuarios deben tener acceso al Dashboard' });
      setTimeout(() => setMensaje(null), 3000);
      return;
    }

    setPermisos(prev => ({
      ...prev,
      [rol]: {
        ...prev[rol],
        [modulo]: !prev[rol]?.[modulo]
      }
    }));
  };

  const toggleTodosLosModulos = (rol, activar) => {
    const nuevosPermisos = {};
    const modulosDisponibles = Object.keys(modulos);
    
    modulosDisponibles.forEach(modulo => {
      nuevosPermisos[modulo] = activar;
    });

    setPermisos(prev => ({
      ...prev,
      [rol]: nuevosPermisos
    }));
  };

  const descartarCambios = () => {
    setPermisos(JSON.parse(JSON.stringify(permisosCopia)));
    setMensaje({ tipo: 'info', texto: 'Cambios descartados' });
    setTimeout(() => setMensaje(null), 3000);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 text-blue-600 animate-spin" />
        <span className="ml-3 text-gray-600">Cargando permisos...</span>
      </div>
    );
  }

  const roles = Object.keys(permisos);
  const modulosDisponibles = Object.keys(modulos);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-6 text-white">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold mb-2 flex items-center">
              <Shield className="w-7 h-7 mr-3" />
              🔐 Gestión de Permisos por Rol
            </h2>
            <p className="text-blue-100">
              Configura qué módulos puede ver cada tipo de usuario
            </p>
          </div>
          <div className="flex items-center gap-3">
            {hayCambios && (
              <div className="bg-yellow-500 text-white px-4 py-2 rounded-lg flex items-center">
                <AlertTriangle className="w-4 h-4 mr-2" />
                Cambios sin guardar
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Mensaje de feedback */}
      {mensaje && (
        <div className={`rounded-lg p-4 flex items-center ${
          mensaje.tipo === 'success' ? 'bg-green-50 border border-green-200 text-green-800' :
          mensaje.tipo === 'error' ? 'bg-red-50 border border-red-200 text-red-800' :
          mensaje.tipo === 'warning' ? 'bg-yellow-50 border border-yellow-200 text-yellow-800' :
          'bg-blue-50 border border-blue-200 text-blue-800'
        }`}>
          {mensaje.tipo === 'success' && <CheckCircle className="w-5 h-5 mr-3" />}
          {mensaje.tipo === 'error' && <XCircle className="w-5 h-5 mr-3" />}
          {mensaje.tipo === 'warning' && <AlertTriangle className="w-5 h-5 mr-3" />}
          <span className="font-medium">{mensaje.texto}</span>
        </div>
      )}

      {/* Tabla de permisos por rol */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {roles.map(rol => {
          const infoRol = descripcionesRoles[rol] || {};
          const modulosActivados = Object.values(permisos[rol] || {}).filter(v => v).length;
          const totalModulos = modulosDisponibles.length;
          const porcentaje = (modulosActivados / totalModulos) * 100;

          return (
            <div key={rol} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              {/* Header del rol */}
              <div className={`p-6 bg-gradient-to-r ${
                infoRol.color === 'blue' ? 'from-blue-500 to-blue-600' :
                infoRol.color === 'green' ? 'from-green-500 to-green-600' :
                'from-purple-500 to-purple-600'
              } text-white`}>
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center">
                    <div className="text-3xl mr-3">{infoRol.icono}</div>
                    <div>
                      <h3 className="text-xl font-bold">{infoRol.nombre}</h3>
                      <p className="text-sm opacity-90">{rol}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-2xl font-bold">{modulosActivados}</div>
                    <div className="text-xs opacity-90">módulos</div>
                  </div>
                </div>
                <p className="text-sm opacity-90 mb-3">{infoRol.descripcion}</p>
                
                {/* Barra de progreso */}
                <div className="bg-white bg-opacity-20 rounded-full h-2">
                  <div 
                    className="bg-white rounded-full h-2 transition-all duration-300"
                    style={{ width: `${porcentaje}%` }}
                  />
                </div>
                <p className="text-xs opacity-75 mt-1">{porcentaje.toFixed(0)}% de acceso</p>
              </div>

              {/* Lista de módulos */}
              <div className="p-4">
                {/* Botones rápidos */}
                {rol !== 'admin' && (
                  <div className="flex gap-2 mb-4">
                    <button
                      onClick={() => toggleTodosLosModulos(rol, true)}
                      className="flex-1 px-3 py-2 text-xs bg-green-50 text-green-700 rounded-lg hover:bg-green-100 border border-green-200 transition-colors"
                    >
                      ✅ Activar Todos
                    </button>
                    <button
                      onClick={() => toggleTodosLosModulos(rol, false)}
                      className="flex-1 px-3 py-2 text-xs bg-red-50 text-red-700 rounded-lg hover:bg-red-100 border border-red-200 transition-colors"
                    >
                      ❌ Desactivar Todos
                    </button>
                  </div>
                )}

                <div className="space-y-2 max-h-96 overflow-y-auto">
                  {modulosDisponibles.map(modulo => {
                    const tieneAcceso = permisos[rol]?.[modulo];
                    const Icono = iconosPorModulo[modulo] || Package;
                    const infoModulo = modulos[modulo] || {};
                    const esAdmin = rol === 'admin';
                    const esInicio = modulo === 'Inicio';

                    return (
                      <div 
                        key={modulo}
                        className={`p-3 rounded-lg border transition-all duration-200 ${
                          tieneAcceso 
                            ? 'bg-green-50 border-green-200 hover:bg-green-100' 
                            : 'bg-gray-50 border-gray-200 hover:bg-gray-100'
                        } ${esAdmin || esInicio ? 'opacity-75' : 'cursor-pointer'}`}
                        onClick={() => !esAdmin && !esInicio && togglePermiso(rol, modulo)}
                      >
                        <div className="flex items-center justify-between">
                          <div className="flex items-center flex-1">
                            <div className={`p-2 rounded-lg mr-3 ${
                              tieneAcceso ? 'bg-green-100' : 'bg-gray-100'
                            }`}>
                              <Icono className={`w-4 h-4 ${
                                tieneAcceso ? 'text-green-600' : 'text-gray-400'
                              }`} />
                            </div>
                            <div className="flex-1">
                              <p className={`font-medium text-sm ${
                                tieneAcceso ? 'text-green-900' : 'text-gray-600'
                              }`}>
                                {infoModulo.nombre || modulo}
                              </p>
                              <p className="text-xs text-gray-500">
                                {infoModulo.descripcion || ''}
                              </p>
                            </div>
                          </div>
                          
                          {/* Toggle visual */}
                          <div className={`ml-3 flex items-center justify-center w-12 h-6 rounded-full transition-colors ${
                            tieneAcceso ? 'bg-green-500' : 'bg-gray-300'
                          }`}>
                            <div className={`w-4 h-4 rounded-full bg-white transition-transform ${
                              tieneAcceso ? 'translate-x-3' : '-translate-x-3'
                            }`} />
                          </div>
                        </div>
                        
                        {/* Nota para admin */}
                        {esAdmin && (
                          <p className="text-xs text-blue-600 mt-2 ml-12">
                            ℹ️ Administrador tiene acceso total siempre
                          </p>
                        )}
                        
                        {/* Nota para Inicio */}
                        {esInicio && rol !== 'admin' && (
                          <p className="text-xs text-green-600 mt-2 ml-12">
                            ℹ️ Todos los usuarios deben tener acceso al Dashboard
                          </p>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Resumen de configuración */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <BarChart3 className="w-5 h-5 mr-2 text-blue-600" />
          📊 Resumen de Accesos
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {roles.map(rol => {
            const infoRol = descripcionesRoles[rol] || {};
            const modulosActivos = Object.entries(permisos[rol] || {}).filter(([_, acceso]) => acceso);
            
            return (
              <div key={rol} className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div className="flex items-center mb-3">
                  <span className="text-2xl mr-2">{infoRol.icono}</span>
                  <div>
                    <p className="font-semibold text-gray-800">{infoRol.nombre}</p>
                    <p className="text-xs text-gray-600">{modulosActivos.length}/{modulosDisponibles.length} módulos</p>
                  </div>
                </div>
                <div className="space-y-1">
                  {modulosActivos.map(([modulo]) => (
                    <div key={modulo} className="flex items-center text-xs text-gray-600">
                      <Check className="w-3 h-3 text-green-600 mr-1" />
                      {modulos[modulo]?.nombre || modulo}
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Botones de acción */}
      <div className="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div className="flex items-center gap-4">
          <button
            onClick={cargarPermisos}
            disabled={loading}
            className="flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors disabled:opacity-50"
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
            Recargar
          </button>
          
          {hayCambios && (
            <button
              onClick={descartarCambios}
              className="flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors"
            >
              <X className="w-4 h-4 mr-2" />
              Descartar Cambios
            </button>
          )}
        </div>

        <button
          onClick={guardarPermisos}
          disabled={guardando || !hayCambios}
          className={`flex items-center px-6 py-3 rounded-lg transition-all font-semibold ${
            hayCambios
              ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg hover:shadow-xl transform hover:scale-105'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          }`}
        >
          {guardando ? (
            <>
              <RefreshCw className="w-5 h-5 mr-2 animate-spin" />
              Guardando...
            </>
          ) : (
            <>
              <Save className="w-5 h-5 mr-2" />
              Guardar Cambios
            </>
          )}
        </button>
      </div>

      {/* Información adicional */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div className="flex items-start">
          <AlertTriangle className="w-5 h-5 text-blue-600 mr-3 mt-0.5" />
          <div className="flex-1">
            <p className="text-sm font-semibold text-blue-900 mb-2">ℹ️ Información Importante:</p>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• <strong>Administradores</strong> siempre tienen acceso total a todos los módulos</li>
              <li>• <strong>Todos los usuarios</strong> deben tener acceso al Dashboard (Inicio)</li>
              <li>• Los cambios se aplican <strong>inmediatamente</strong> después de guardar</li>
              <li>• Los usuarios deben <strong>cerrar sesión y volver a entrar</strong> para ver los cambios</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Configuración recomendada */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <Eye className="w-5 h-5 mr-2 text-green-600" />
          ✅ Configuración Recomendada
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p className="font-semibold text-blue-900 mb-2">👑 Administrador</p>
            <p className="text-sm text-blue-800">Acceso total a todos los módulos para gestión completa del negocio.</p>
          </div>
          
          <div className="bg-green-50 rounded-lg p-4 border border-green-200">
            <p className="font-semibold text-green-900 mb-2">🛒 Vendedor</p>
            <p className="text-sm text-green-800">Acceso a: Inicio, POS, Ventas, Inventario y Productos. <strong>Sin acceso</strong> a reportes financieros ni configuración.</p>
          </div>
          
          <div className="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <p className="font-semibold text-purple-900 mb-2">💰 Cajero</p>
            <p className="text-sm text-purple-800">Acceso a: Inicio, POS, Ventas, Control de Caja e Inventario. <strong>Sin acceso</strong> a productos ni reportes.</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GestionPermisos;

