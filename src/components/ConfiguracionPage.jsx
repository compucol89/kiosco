import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import configService from '../services/configService';
import permisosService from '../services/permisosService';
import seguridadInventarioService from '../services/seguridadInventarioService';
import descuentosService from '../services/descuentosService';
import ConfiguracionFacturacion from './ConfiguracionFacturacion';
import GestionPermisos from './GestionPermisos';
import SeguridadAcceso from './SeguridadAcceso';
import GestionDispositivos from './GestionDispositivos';
// import CONFIG from '../config/config'; // Comentado temporalmente - no utilizado
import { 
  Settings, 
  AlertTriangle, 
  RefreshCw, 
  Building, 
  Phone, 
  MapPin, 
  Receipt, 
  RotateCcw,
  CheckCircle,
  XCircle,
  Shield,
  X,
  Lock,
  Save,
  ShieldCheck,
  Percent,
  Info
} from 'lucide-react';
import { showSuccess, showError, showWarning, showInfo } from '../utils/toastNotifications';

// Componente de tarjeta de sección
const SectionCard = ({ title, description, icon: Icon, children, variant = 'default' }) => {
  const getBgColor = () => {
    switch (variant) {
      case 'danger': return 'bg-red-50 border-red-200';
      case 'warning': return 'bg-yellow-50 border-yellow-200';
      case 'success': return 'bg-green-50 border-green-200';
      default: return 'bg-white border-gray-200';
    }
  };

  const getIconColor = () => {
    switch (variant) {
      case 'danger': return 'text-red-500';
      case 'warning': return 'text-yellow-500';
      case 'success': return 'text-green-500';
      default: return 'text-blue-500';
    }
  };

  return (
    <div className={`rounded-lg border-2 ${getBgColor()} shadow-sm hover:shadow-md transition-shadow duration-200`}>
      <div className="p-6">
        <div className="flex items-center mb-4">
          {Icon && <Icon className={`w-6 h-6 mr-3 ${getIconColor()}`} />}
          <div>
            <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
            {description && <p className="text-sm text-gray-600 mt-1">{description}</p>}
          </div>
        </div>
        {children}
      </div>
    </div>
  );
};

// Componente de campo de entrada
const InputField = ({ 
  label, 
  value, 
  onChange, 
  type = 'text', 
  placeholder, 
  description,
  icon: Icon,
  disabled = false,
  required = false
}) => {
  return (
    <div className="mb-4">
      <label className="block text-sm font-medium text-gray-700 mb-2">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      <div className="relative">
        {Icon && (
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon className="h-5 w-5 text-gray-400" />
          </div>
        )}
        <input
          type={type}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          disabled={disabled}
          className={`
            block w-full rounded-md border-gray-300 shadow-sm
            focus:border-blue-500 focus:ring-blue-500
            ${Icon ? 'pl-10' : 'pl-3'} pr-3 py-2
            ${disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}
            transition-colors duration-200
          `}
        />
      </div>
      {description && (
        <p className="mt-2 text-sm text-gray-500">{description}</p>
      )}
    </div>
  );
};

// Componente de textarea
const TextareaField = ({ 
  label, 
  value, 
  onChange, 
  placeholder, 
  description,
  rows = 3,
  disabled = false
}) => {
  return (
    <div className="mb-4">
      <label className="block text-sm font-medium text-gray-700 mb-2">
        {label}
      </label>
      <textarea
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        disabled={disabled}
        rows={rows}
        className={`
          block w-full rounded-md border-gray-300 shadow-sm
          focus:border-blue-500 focus:ring-blue-500
          px-3 py-2
          ${disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}
          transition-colors duration-200
        `}
      />
      {description && (
        <p className="mt-2 text-sm text-gray-500">{description}</p>
      )}
    </div>
  );
};

// Componente de checkbox
const CheckboxField = ({ 
  label, 
  checked, 
  onChange, 
  description,
  disabled = false
}) => {
  return (
    <div className="flex items-start">
      <div className="flex items-center h-5">
        <input
          type="checkbox"
          checked={checked}
          onChange={onChange}
          disabled={disabled}
          className="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
        />
      </div>
      <div className="ml-3 text-sm">
        <label className="font-medium text-gray-700">{label}</label>
        {description && (
          <p className="text-gray-500">{description}</p>
        )}
      </div>
    </div>
  );
};

// Componente de botón
const Button = ({ 
  children, 
  onClick, 
  variant = 'primary', 
  size = 'md', 
  disabled = false,
  loading = false,
  icon: Icon
}) => {
  const getVariantClasses = () => {
    switch (variant) {
      case 'danger':
        return 'bg-red-600 hover:bg-red-700 text-white border-transparent';
      case 'warning':
        return 'bg-yellow-500 hover:bg-yellow-600 text-white border-transparent';
      case 'success':
        return 'bg-green-600 hover:bg-green-700 text-white border-transparent';
      case 'secondary':
        return 'bg-gray-600 hover:bg-gray-700 text-white border-transparent';
      case 'outline':
        return 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300';
      default:
        return 'bg-blue-600 hover:bg-blue-700 text-white border-transparent';
    }
  };

  const getSizeClasses = () => {
    switch (size) {
      case 'sm':
        return 'px-3 py-1.5 text-sm';
      case 'lg':
        return 'px-6 py-3 text-lg';
      default:
        return 'px-4 py-2 text-sm';
    }
  };

  return (
    <button
      onClick={onClick}
      disabled={disabled || loading}
      className={`
        inline-flex items-center border font-medium rounded-md
        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
        transition-colors duration-200
        ${getVariantClasses()}
        ${getSizeClasses()}
        ${disabled || loading ? 'opacity-50 cursor-not-allowed' : ''}
      `}
    >
      {loading ? (
        <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
      ) : Icon ? (
        <Icon className="w-4 h-4 mr-2" />
      ) : null}
      {children}
    </button>
  );
};

// Modal de confirmación para reinicio
const ReinicioModal = ({ 
  isOpen, 
  onClose, 
  onConfirm, 
  opciones, 
  setOpciones, 
  confirmText, 
  setConfirmText, 
  loading 
}) => {
  if (!isOpen) return null;

  const tieneOpcionesSeleccionadas = Object.values(opciones).some(Boolean);
  const confirmacionCorrecta = confirmText === 'REINICIAR';

  return (
    <div className="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-all">
        <div className="bg-gradient-to-r from-red-500 via-red-600 to-red-700 text-white px-6 py-6 rounded-t-2xl">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-white bg-opacity-20 rounded-xl">
                <AlertTriangle className="w-6 h-6" />
              </div>
              <div>
                <h3 className="text-xl font-bold">🔄 Reset Completo del Sistema</h3>
                <p className="text-red-100 text-sm">Eliminación selectiva de datos</p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        <div className="p-6">
          <div className="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div className="flex">
              <AlertTriangle className="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
              <div className="ml-3">
                <h4 className="text-sm font-medium text-red-800">
                  ⚠️ Sistema de Reinicio Mejorado - Versión Actualizada
                </h4>
                <p className="mt-2 text-sm text-red-700">
                  Esta versión mejorada incluye TODAS las nuevas funcionalidades: 
                  <strong>egresos, gastos fijos, reportes financieros, auditoría de inventario</strong> y más.
                  <br /><br />
                  <strong>¡No se puede deshacer!</strong> Se realizará un reinicio completo y profesional.
                </p>
              </div>
            </div>
          </div>

          {/* Nueva sección informativa sobre las mejoras */}
          <div className="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
            <h4 className="text-sm font-medium text-blue-800 mb-2">
              🎯 Características del Sistema de Reinicio Mejorado:
            </h4>
            <ul className="text-sm text-blue-700 space-y-1">
              <li>✅ Incluye todas las tablas nuevas (egresos, gastos fijos, auditoría)</li>
              <li>✅ Limpia automáticamente tablas de backup innecesarias</li>
              <li>✅ Protege datos críticos (usuarios, configuración, logs de seguridad)</li>
              <li>✅ Categoriza tablas por función (ventas, inventario, financiero, caja)</li>
              <li>✅ Manejo de errores avanzado con rollback automático</li>
            </ul>
          </div>

          <div className="space-y-3 mb-6">
            <p className="font-medium text-gray-900">📋 Seleccione qué categorías de datos eliminar:</p>
            
            <CheckboxField
              label="💰 Ventas y Transacciones"
              checked={opciones.eliminarVentas}
              onChange={() => setOpciones(prev => ({ ...prev, eliminarVentas: !prev.eliminarVentas }))}
              description="Eliminará ventas, detalles de ventas, y todo el historial comercial"
            />
            
            <CheckboxField
              label="💳 Registros de Caja y Finanzas"
              checked={opciones.eliminarCaja}
              onChange={() => setOpciones(prev => ({ ...prev, eliminarCaja: !prev.eliminarCaja }))}
              description="Eliminará movimientos de caja, turnos, egresos, ingresos extra y reportes financieros"
            />
            
            <CheckboxField
              label="📦 Movimientos de Inventario"
              checked={opciones.eliminarProductos}
              onChange={() => setOpciones(prev => ({ ...prev, eliminarProductos: !prev.eliminarProductos }))}
              description="Eliminará movimientos y auditorías de inventario. ✅ Los productos se conservan siempre"
            />
            
            <CheckboxField
              label="👥 Clientes y Proveedores"
              checked={opciones.eliminarClientes}
              onChange={() => setOpciones(prev => ({ ...prev, eliminarClientes: !prev.eliminarClientes }))}
              description="Eliminará información de clientes. ✅ Proveedores se conservan siempre"
            />
          </div>

          {/* Información adicional sobre tablas protegidas */}
          <div className="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <h4 className="text-sm font-medium text-green-800 mb-2">
              🔒 Datos que SIEMPRE se protegen (GARANTIZADO):
            </h4>
            <ul className="text-sm text-green-700 space-y-1">
              <li>• ✅ Usuarios y roles del sistema</li>
              <li>• ✅ Productos del inventario</li>
              <li>• ✅ Proveedores configurados</li>
              <li>• ✅ Configuraciones generales</li>
              <li>• ✅ Permisos y roles</li>
              <li>• ✅ Gastos fijos mensuales</li>
              <li>• ✅ Dispositivos confiables</li>
              <li>• ✅ Seguridad de acceso</li>
            </ul>
          </div>

          {!tieneOpcionesSeleccionadas && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4">
              <p className="text-sm text-yellow-800">
                ⚠️ Debe seleccionar al menos una categoría para continuar
              </p>
            </div>
          )}

          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Para confirmar este reinicio completo, escriba <strong>REINICIAR</strong>:
            </label>
            <input
              type="text"
              value={confirmText}
              onChange={(e) => setConfirmText(e.target.value)}
              placeholder="Escriba REINICIAR para confirmar"
              className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
            />
          </div>

          <div className="flex justify-end space-x-3">
            <Button
              variant="outline"
              onClick={onClose}
              disabled={loading}
            >
              Cancelar
            </Button>
            <Button
              variant="danger"
              onClick={onConfirm}
              disabled={!tieneOpcionesSeleccionadas || !confirmacionCorrecta || loading}
              loading={loading}
              icon={RotateCcw}
            >
              {loading ? 'Reiniciando Sistema...' : 'Ejecutar Reinicio Completo'}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

// Componente principal
const ConfiguracionPage = () => {
  const { currentUser } = useAuth();
  const [configuraciones, setConfiguraciones] = useState({});
  const [loading, setLoading] = useState(true);
  const [, setSaving] = useState(false); // eslint-disable-line no-unused-vars
  const [error, setError] = useState(null);
  const [mostrarModalReinicio, setMostrarModalReinicio] = useState(false);
  const [confirmarReinicio, setConfirmarReinicio] = useState('');
  const [reiniciando, setReiniciando] = useState(false);
  const [saveNotification, setSaveNotification] = useState(null);
  
  // Estados para seguridad del inventario
  const [seguridadInventario, setSeguridadInventario] = useState({
    requiereAutenticacion: false,
    requiereConfirmacionPassword: false,
    nivelSeguridadModificaciones: 'admin',
    auditoriaCambios: true,
    backupAntesCambios: true,
    limitarModificacionesPorDia: false,
    maxModificacionesDia: 10,
    horariosPermitidos: {
      habilitado: false,
      horaInicio: '08:00',
      horaFin: '18:00'
    }
  });
  
  // Estados para descuentos
  const [descuentos, setDescuentos] = useState({
    efectivo: 10,
    transferencia: 10,
    tarjeta: 0,
    mercadopago: 0,
    qr: 0,
    otros: 0
  });
  const [loadingDescuentos, setLoadingDescuentos] = useState(false);
  const [guardandoDescuentos, setGuardandoDescuentos] = useState(false);
  

  
  // Opciones de eliminación
  const [opcionesReinicio, setOpcionesReinicio] = useState({
    eliminarVentas: true,
    eliminarCaja: true,
    eliminarProductos: false,
    eliminarClientes: false
  });

  useEffect(() => {
    cargarConfiguraciones();
  }, []);

  const cargarConfiguraciones = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('🔄 Cargando configuraciones...');
      const data = await configService.getConfiguracion();
      console.log('✅ Configuraciones cargadas:', data);
      
      setConfiguraciones(data);
      showSuccess('✅ Configuraciones cargadas correctamente');
      
    } catch (error) {
      console.error('❌ Error al cargar configuraciones:', error);
      const errorMessage = `Error al cargar la configuración: ${error.message}`;
      setError(errorMessage);
      showError(`❌ ${errorMessage}`);
    } finally {
      setLoading(false);
    }
  };

  const mostrarNotificacion = (mensaje, tipo = 'success') => {
    // Usar el sistema de toast en lugar de las notificaciones internas
    switch(tipo) {
      case 'success':
        showSuccess(mensaje);
        break;
      case 'error':
        showError(mensaje);
        break;
      case 'warning':
        showWarning(mensaje);
        break;
      default:
        showInfo(mensaje);
    }
    
    // Mantener el sistema interno para compatibilidad
    setSaveNotification({ mensaje, tipo });
    setTimeout(() => setSaveNotification(null), 3000);
  };

  const handleActualizarConfig = async (clave, valor) => {
    try {
      setSaving(true);
      await configService.actualizarConfiguracion(clave, valor);
      mostrarNotificacion('Configuración actualizada correctamente');
      cargarConfiguraciones();
    } catch (error) {
      mostrarNotificacion('Error al actualizar la configuración', 'error');
      console.error('Error al actualizar:', error);
    } finally {
      setSaving(false);
    }
  };

  // Cargar configuraciones al inicializar
  useEffect(() => {
    cargarConfiguracionSeguridad();
    cargarDescuentos();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Funciones para seguridad del inventario
  const cargarConfiguracionSeguridad = async () => {
    try {
      const resultado = await seguridadInventarioService.obtenerConfiguracion();
      if (resultado.success) {
        const config = resultado.configuracion;
        setSeguridadInventario({
          requiereAutenticacion: config.requiere_autenticacion === 'true',
          requiereConfirmacionPassword: config.requiere_confirmacion_password === 'true',
          nivelSeguridadModificaciones: config.nivel_seguridad_modificaciones || 'admin',
          auditoriaCambios: config.auditoria_cambios === 'true',
          backupAntesCambios: config.backup_antes_cambios === 'true',
          limitarModificacionesPorDia: config.limitar_modificaciones_por_dia === 'true',
          maxModificacionesDia: parseInt(config.max_modificaciones_dia || '10'),
          horariosPermitidos: {
            habilitado: config.horarios_permitidos_habilitado === 'true',
            horaInicio: config.horarios_permitidos_inicio || '08:00',
            horaFin: config.horarios_permitidos_fin || '18:00'
          }
        });
      }
    } catch (error) {
      console.error('Error cargando configuración de seguridad:', error);
    }
  };

  const guardarConfiguracionSeguridad = async () => {
    try {
      const configParaEnviar = {
        requiere_autenticacion: seguridadInventario.requiereAutenticacion ? 'true' : 'false',
        requiere_confirmacion_password: seguridadInventario.requiereConfirmacionPassword ? 'true' : 'false',
        nivel_seguridad_modificaciones: seguridadInventario.nivelSeguridadModificaciones,
        auditoria_cambios: seguridadInventario.auditoriaCambios ? 'true' : 'false',
        backup_antes_cambios: seguridadInventario.backupAntesCambios ? 'true' : 'false',
        limitar_modificaciones_por_dia: seguridadInventario.limitarModificacionesPorDia ? 'true' : 'false',
        max_modificaciones_dia: seguridadInventario.maxModificacionesDia.toString(),
        horarios_permitidos_habilitado: seguridadInventario.horariosPermitidos.habilitado ? 'true' : 'false',
        horarios_permitidos_inicio: seguridadInventario.horariosPermitidos.horaInicio,
        horarios_permitidos_fin: seguridadInventario.horariosPermitidos.horaFin
      };

      const resultado = await seguridadInventarioService.actualizarConfiguracion(configParaEnviar);
      if (resultado.success) {
        mostrarNotificacion('Configuración de seguridad guardada correctamente', 'success');
      } else {
        mostrarNotificacion('Error al guardar configuración: ' + resultado.message, 'error');
      }
    } catch (error) {
      mostrarNotificacion('Error al guardar configuración de seguridad', 'error');
    }
  };

  // Funciones para gestión de descuentos
  const cargarDescuentos = async () => {
    setLoadingDescuentos(true);
    try {
      const resultado = await descuentosService.obtenerDescuentos();
      if (resultado.success) {
        setDescuentos(resultado.descuentos);
      } else {
        mostrarNotificacion('Error al cargar descuentos: ' + resultado.message, 'error');
      }
    } catch (error) {
      mostrarNotificacion('Error al cargar descuentos', 'error');
    } finally {
      setLoadingDescuentos(false);
    }
  };

  const guardarDescuentos = async () => {
    setGuardandoDescuentos(true);
    try {
      const resultado = await descuentosService.actualizarMultiplesDescuentos(descuentos);
      if (resultado.success) {
        mostrarNotificacion('Descuentos actualizados correctamente', 'success');
      } else {
        mostrarNotificacion('Error al guardar descuentos: ' + resultado.message, 'error');
      }
    } catch (error) {
      mostrarNotificacion('Error al guardar descuentos', 'error');
    } finally {
      setGuardandoDescuentos(false);
    }
  };

  const cambiarDescuento = (metodoPago, valor) => {
    const porcentaje = parseFloat(valor);
    if (isNaN(porcentaje) || porcentaje < 0 || porcentaje > 100) {
      mostrarNotificacion('El descuento debe ser un número entre 0 y 100', 'error');
      return;
    }
    
    setDescuentos(prev => ({
      ...prev,
      [metodoPago]: porcentaje
    }));
  };

  const resetearDescuentosDefecto = () => {
    if (window.confirm('¿Está seguro de resetear todos los descuentos a los valores por defecto?')) {
      setDescuentos({
        efectivo: 10,
        transferencia: 10,
        tarjeta: 0,
        mercadopago: 0,
        qr: 0,
        otros: 0
      });
      mostrarNotificacion('Descuentos reseteados a valores por defecto', 'success');
    }
  };



  const confirmarReinicioSistema = async () => {
    try {
      setReiniciando(true);
      
      // Mostrar notificación de inicio
      showInfo('🔄 Iniciando reinicio del sistema...');
      
      const resultado = await configService.reiniciarSistema(true, opcionesReinicio);
      
      if (resultado.estadisticas && resultado.estadisticas.tablas_limpiadas > 0) {
        showSuccess(
          `🎉 Sistema reiniciado exitosamente! Se procesaron ${resultado.estadisticas.tablas_procesadas || 0} tablas y se limpiaron ${resultado.estadisticas.tablas_limpiadas || 0} tablas.`
        );
        
        // Mostrar detalles adicionales
        if (resultado.estadisticas.turnos_cerrados_automaticamente > 0) {
          showInfo(`💰 Se cerraron automáticamente ${resultado.estadisticas.turnos_cerrados_automaticamente} turnos abiertos`);
        }
        
        if (resultado.estadisticas.tablas_backup_eliminadas > 0) {
          showInfo(`🧹 Se eliminaron ${resultado.estadisticas.tablas_backup_eliminadas} tablas de backup`);
        }
      } else {
        showWarning('⚠️ Reinicio completado. Las tablas seleccionadas ya estaban vacías.');
      }
      
      setMostrarModalReinicio(false);
      setConfirmarReinicio('');
      
      // Recargar configuraciones después de un breve delay
      setTimeout(() => {
        cargarConfiguraciones();
      }, 1000);
      
    } catch (error) {
      showError(`❌ Error al reiniciar el sistema: ${error.message}`);
      console.error('Error completo:', error);
    } finally {
      setReiniciando(false);
    }
  };

  // Verificar permisos
  if (!currentUser || currentUser.role !== 'admin') {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full">
          <SectionCard
            title="Acceso Denegado"
            description="No tiene permisos para acceder a esta página"
            icon={Shield}
            variant="danger"
          >
            <div className="text-center">
              <p className="text-gray-600 mb-4">
                Esta página es solo para administradores del sistema.
              </p>
              <Button
                variant="primary"
                onClick={() => window.history.back()}
              >
                Volver
              </Button>
            </div>
          </SectionCard>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <RefreshCw className="w-8 h-8 animate-spin text-blue-500 mx-auto mb-4" />
          <p className="text-gray-600">Cargando configuraciones...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full">
          <SectionCard
            title="Error"
            description="Ha ocurrido un problema al cargar la configuración"
            icon={XCircle}
            variant="danger"
          >
            <p className="text-gray-600 mb-4">{error}</p>
            <Button
              variant="primary"
              onClick={cargarConfiguraciones}
              icon={RefreshCw}
            >
              Reintentar
            </Button>
          </SectionCard>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      {/* Hero Header Moderno */}
      <div className="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="relative max-w-7xl mx-auto px-4 py-12">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                <Settings className="w-8 h-8 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-white mb-2">
                  ⚙️ Centro de Configuración
                </h1>
                <p className="text-blue-100 text-lg">
                  Personaliza y administra tu sistema de kiosco
                </p>
              </div>
            </div>
            
            {/* Quick Stats */}
            <div className="hidden lg:flex space-x-6">
              <div className="text-center">
                <div className="text-2xl font-bold text-white">✅</div>
                <div className="text-sm text-blue-100">Activo</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-white">{Object.keys(configuraciones).length}</div>
                <div className="text-sm text-blue-100">Configuraciones</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 -mt-6 relative z-10">

        {/* Notificación de guardado */}
        {saveNotification && (
          <div className={`mb-6 p-4 rounded-md flex items-center ${
            saveNotification.tipo === 'success' 
              ? 'bg-green-50 text-green-700 border border-green-200' 
              : 'bg-red-50 text-red-700 border border-red-200'
          }`}>
            {saveNotification.tipo === 'success' ? (
              <CheckCircle className="w-5 h-5 mr-2" />
            ) : (
              <XCircle className="w-5 h-5 mr-2" />
            )}
            {saveNotification.mensaje}
          </div>
        )}

        {/* Dashboard de Configuraciones */}
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
          {/* Quick Action Cards */}
          <div className="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105"
               onClick={() => setMostrarModalReinicio(true)}>
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <RotateCcw className="w-6 h-6" />
              </div>
              <span className="text-2xl">🔄</span>
            </div>
            <h3 className="text-lg font-semibold mb-1">Reset Sistema</h3>
            <p className="text-red-100 text-sm">Reinicio completo</p>
          </div>

          <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white hover:shadow-xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <CheckCircle className="w-6 h-6" />
              </div>
              <span className="text-2xl">💾</span>
            </div>
            <h3 className="text-lg font-semibold mb-1">Todo Guardado</h3>
            <p className="text-green-100 text-sm">Sistema sincronizado</p>
          </div>

          <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white hover:shadow-xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <Shield className="w-6 h-6" />
              </div>
              <span className="text-2xl">🔒</span>
            </div>
            <h3 className="text-lg font-semibold mb-1">Seguridad</h3>
            <p className="text-blue-100 text-sm">Protección activa</p>
          </div>

          <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white hover:shadow-xl transition-all duration-300">
            <div className="flex items-center justify-between mb-4">
              <div className="p-2 bg-white bg-opacity-20 rounded-lg">
                <Building className="w-6 h-6" />
              </div>
              <span className="text-2xl">🏪</span>
            </div>
            <h3 className="text-lg font-semibold mb-1">Mi Negocio</h3>
            <p className="text-purple-100 text-sm">{configuraciones.nombre_negocio || 'Sin configurar'}</p>
          </div>
        </div>

        <div className="space-y-8">

          {/* Configuración General Moderna */}
          <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
            <div className="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 border-b border-gray-100">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-blue-100 rounded-lg">
                  <Settings className="w-5 h-5 text-blue-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">⚡ Configuración General</h3>
                  <p className="text-sm text-gray-600">Opciones generales del sistema</p>
                </div>
              </div>
            </div>
            <div className="p-6">
              <div className="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-200">
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-yellow-100 rounded-lg">
                    <AlertTriangle className="w-5 h-5 text-yellow-600" />
                  </div>
                  <div>
                    <h4 className="font-medium text-gray-900">🔧 Modo Mantenimiento</h4>
                    <p className="text-sm text-gray-600">Solo administradores pueden acceder cuando está activo</p>
                  </div>
                </div>
                <label className="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    checked={configuraciones.modo_mantenimiento === "1"}
                    onChange={(e) => handleActualizarConfig('modo_mantenimiento', e.target.checked ? "1" : "0")}
                    className="sr-only peer"
                  />
                  <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
              </div>
            </div>
          </div>

          {/* Información del Negocio Moderna */}
          <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
            <div className="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-100">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-purple-100 rounded-lg">
                  <Building className="w-5 h-5 text-purple-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">🏪 Información del Negocio</h3>
                  <p className="text-sm text-gray-600">Datos que aparecerán en tickets y reportes</p>
                </div>
              </div>
            </div>
            <div className="p-6">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="lg:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    🏪 Nombre del Negocio
                  </label>
                  <div className="relative">
                    <Building className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                      type="text"
                      value={configuraciones.nombre_negocio || ''}
                      onChange={(e) => handleActualizarConfig('nombre_negocio', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                      placeholder="Ej: Kiosco El Rincón"
                    />
                  </div>
                </div>

                <div className="lg:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📍 Dirección del Negocio
                  </label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                      type="text"
                      value={configuraciones.direccion_negocio || ''}
                      onChange={(e) => handleActualizarConfig('direccion_negocio', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                      placeholder="Ej: Av. Corrientes 1234, CABA"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    📞 Teléfono de Contacto
                  </label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                      type="tel"
                      value={configuraciones.telefono_negocio || ''}
                      onChange={(e) => handleActualizarConfig('telefono_negocio', e.target.value)}
                      className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                      placeholder="+54 11 1234-5678"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    🎫 Mensaje en Tickets
                  </label>
                  <div className="relative">
                    <Receipt className="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                    <textarea
                      value={configuraciones.mensaje_pie_ticket || ''}
                      onChange={(e) => handleActualizarConfig('mensaje_pie_ticket', e.target.value)}
                      rows="3"
                      className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                      placeholder="¡Gracias por tu compra!"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Gestión de Permisos de Usuario - MÓDULO VISUAL MODERNO */}
          <GestionPermisos />

          {/* Seguridad de Acceso por IP */}
          <SeguridadAcceso />

          {/* Gestión de Dispositivos Confiables */}
          <GestionDispositivos />

          {/* Seguridad del Inventario */}
          <SectionCard
            title="Seguridad del Inventario"
            description="Medidas de protección para modificaciones del inventario"
            icon={Shield}
            variant="warning"
          >
            <div className="space-y-6">
              <div className="bg-amber-50 border border-amber-200 rounded-md p-4">
                <div className="flex">
                  <Lock className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
                  <div className="ml-3">
                    <h4 className="text-sm font-medium text-amber-800">
                      Protección crítica del inventario
                    </h4>
                    <p className="mt-1 text-sm text-amber-700">
                      Configure medidas de seguridad adicionales para proteger las modificaciones del inventario.
                      Solo personal autorizado podrá realizar cambios según estas configuraciones.
                    </p>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium text-gray-900">Controles de Acceso</h4>
                  
                  <CheckboxField
                    label="Requerir autenticación adicional"
                    checked={seguridadInventario.requiereAutenticacion}
                    onChange={(e) => setSeguridadInventario(prev => ({
                      ...prev, requiereAutenticacion: e.target.checked
                    }))}
                    description="Solicitar confirmación de identidad antes de modificar inventario"
                  />

                  <CheckboxField
                    label="Confirmación de contraseña"
                    checked={seguridadInventario.requiereConfirmacionPassword}
                    onChange={(e) => setSeguridadInventario(prev => ({
                      ...prev, requiereConfirmacionPassword: e.target.checked
                    }))}
                    description="Requerir que el usuario ingrese su contraseña para confirmar cambios"
                  />

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Nivel de seguridad para modificaciones
                    </label>
                    <select
                      value={seguridadInventario.nivelSeguridadModificaciones}
                      onChange={(e) => setSeguridadInventario(prev => ({
                        ...prev, nivelSeguridadModificaciones: e.target.value
                      }))}
                      className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="admin">Solo Administradores</option>
                      <option value="admin_vendedor">Administradores y Vendedores de confianza</option>
                      <option value="todos">Todos los usuarios autorizados</option>
                    </select>
                    <p className="mt-1 text-sm text-gray-500">
                      Define quién puede realizar modificaciones en el inventario
                    </p>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium text-gray-900">Auditoría y Respaldos</h4>

                  <CheckboxField
                    label="Registro de auditoría"
                    checked={seguridadInventario.auditoriaCambios}
                    onChange={(e) => setSeguridadInventario(prev => ({
                      ...prev, auditoriaCambios: e.target.checked
                    }))}
                    description="Registrar todos los cambios de inventario con usuario, fecha y hora"
                  />

                  <CheckboxField
                    label="Backup automático antes de cambios"
                    checked={seguridadInventario.backupAntesCambios}
                    onChange={(e) => setSeguridadInventario(prev => ({
                      ...prev, backupAntesCambios: e.target.checked
                    }))}
                    description="Crear respaldo automático del inventario antes de modificaciones importantes"
                  />

                  <CheckboxField
                    label="Limitar modificaciones diarias"
                    checked={seguridadInventario.limitarModificacionesPorDia}
                    onChange={(e) => setSeguridadInventario(prev => ({
                      ...prev, limitarModificacionesPorDia: e.target.checked
                    }))}
                    description="Establecer límite de modificaciones por usuario por día"
                  />

                  {seguridadInventario.limitarModificacionesPorDia && (
                    <InputField
                      label="Máximo de modificaciones por día"
                      type="number"
                      value={seguridadInventario.maxModificacionesDia}
                      onChange={(e) => setSeguridadInventario(prev => ({
                        ...prev, maxModificacionesDia: parseInt(e.target.value) || 10
                      }))}
                      placeholder="10"
                    />
                  )}
                </div>
              </div>

              <div className="border-t border-gray-200 pt-4">
                <h4 className="font-medium text-gray-900 mb-4">Horarios Permitidos</h4>
                
                <CheckboxField
                  label="Restringir horarios de modificación"
                  checked={seguridadInventario.horariosPermitidos.habilitado}
                  onChange={(e) => setSeguridadInventario(prev => ({
                    ...prev,
                    horariosPermitidos: {
                      ...prev.horariosPermitidos,
                      habilitado: e.target.checked
                    }
                  }))}
                  description="Solo permitir modificaciones de inventario en horarios específicos"
                />

                {seguridadInventario.horariosPermitidos.habilitado && (
                  <div className="grid grid-cols-2 gap-4 mt-4">
                    <InputField
                      label="Hora de inicio"
                      type="time"
                      value={seguridadInventario.horariosPermitidos.horaInicio}
                      onChange={(e) => setSeguridadInventario(prev => ({
                        ...prev,
                        horariosPermitidos: {
                          ...prev.horariosPermitidos,
                          horaInicio: e.target.value
                        }
                      }))}
                    />
                    <InputField
                      label="Hora de fin"
                      type="time"
                      value={seguridadInventario.horariosPermitidos.horaFin}
                      onChange={(e) => setSeguridadInventario(prev => ({
                        ...prev,
                        horariosPermitidos: {
                          ...prev.horariosPermitidos,
                          horaFin: e.target.value
                        }
                      }))}
                    />
                  </div>
                )}
              </div>

              <div className="flex justify-end pt-4 border-t border-gray-200">
                <Button
                  variant="success"
                  onClick={guardarConfiguracionSeguridad}
                  icon={Save}
                >
                  Guardar Configuración de Seguridad
                </Button>
              </div>
            </div>
          </SectionCard>

          {/* Configuración de Tickets */}
          <SectionCard
            title="Configuración de Tickets"
            description="Personalice la apariencia y comportamiento de los comprobantes de venta"
            icon={Receipt}
          >
            <div className="space-y-4">
              <TextareaField
                label="Mensaje al pie del ticket"
                value={configuraciones.mensaje_pie_ticket || ''}
                onChange={(e) => handleActualizarConfig('mensaje_pie_ticket', e.target.value)}
                placeholder="Gracias por su compra!"
                description="Este mensaje aparecerá al final de todos los tickets de venta"
                rows={3}
              />

              <CheckboxField
                label="Impresión automática"
                checked={configuraciones.impresion_automatica === "1"}
                onChange={(e) => handleActualizarConfig('impresion_automatica', e.target.checked ? "1" : "0")}
                description="Los tickets se imprimirán automáticamente al finalizar cada venta"
              />
            </div>
          </SectionCard>

          {/* Descuentos por Método de Pago Moderno */}
          <div className="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
            <div className="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-100">
              <div className="flex items-center space-x-3">
                <div className="p-2 bg-green-100 rounded-lg">
                  <Percent className="w-5 h-5 text-green-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">💰 Descuentos por Método de Pago</h3>
                  <p className="text-sm text-gray-600">Configura descuentos automáticos para cada forma de pago</p>
                </div>
              </div>
            </div>
            <div className="p-6">
              {loadingDescuentos ? (
                <div className="flex justify-center py-12">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                </div>
              ) : (
                <div className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Efectivo */}
                    <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200">
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center space-x-3">
                          <span className="text-2xl">💵</span>
                          <div>
                            <h4 className="font-semibold text-gray-900">Efectivo</h4>
                            <p className="text-sm text-gray-600">Pago en efectivo</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <input
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            value={descuentos.efectivo}
                            onChange={(e) => cambiarDescuento('efectivo', e.target.value)}
                            className="w-16 text-center rounded-lg border-green-300 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                          />
                          <span className="text-green-600 font-medium">%</span>
                        </div>
                      </div>
                      <div className="text-sm text-green-700">
                        {descuentos.efectivo > 0 ? `✅ Descuento del ${descuentos.efectivo}%` : '❌ Sin descuento'}
                      </div>
                    </div>

                    {/* Transferencia */}
                    <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200">
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center space-x-3">
                          <span className="text-2xl">📱</span>
                          <div>
                            <h4 className="font-semibold text-gray-900">Transferencia</h4>
                            <p className="text-sm text-gray-600">Transferencia bancaria</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <input
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            value={descuentos.transferencia}
                            onChange={(e) => cambiarDescuento('transferencia', e.target.value)}
                            className="w-16 text-center rounded-lg border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          />
                          <span className="text-blue-600 font-medium">%</span>
                        </div>
                      </div>
                      <div className="text-sm text-blue-700">
                        {descuentos.transferencia > 0 ? `✅ Descuento del ${descuentos.transferencia}%` : '❌ Sin descuento'}
                      </div>
                    </div>

                    {/* Tarjeta */}
                    <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200">
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center space-x-3">
                          <span className="text-2xl">💳</span>
                          <div>
                            <h4 className="font-semibold text-gray-900">Tarjeta</h4>
                            <p className="text-sm text-gray-600">Débito/Crédito</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <input
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            value={descuentos.tarjeta}
                            onChange={(e) => cambiarDescuento('tarjeta', e.target.value)}
                            className="w-16 text-center rounded-lg border-purple-300 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                          />
                          <span className="text-purple-600 font-medium">%</span>
                        </div>
                      </div>
                      <div className="text-sm text-purple-700">
                        {descuentos.tarjeta > 0 ? `✅ Descuento del ${descuentos.tarjeta}%` : '❌ Sin descuento'}
                      </div>
                    </div>

                    {/* MercadoPago */}
                    <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-5 border border-yellow-200">
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center space-x-3">
                          <span className="text-2xl">📱</span>
                          <div>
                            <h4 className="font-semibold text-gray-900">QR MercadoPago</h4>
                            <p className="text-sm text-gray-600">Código QR</p>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <input
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            value={descuentos.mercadopago}
                            onChange={(e) => cambiarDescuento('mercadopago', e.target.value)}
                            className="w-16 text-center rounded-lg border-yellow-300 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                          />
                          <span className="text-yellow-600 font-medium">%</span>
                        </div>
                      </div>
                      <div className="text-sm text-yellow-700">
                        {descuentos.mercadopago > 0 ? `✅ Descuento del ${descuentos.mercadopago}%` : '❌ Sin descuento'}
                      </div>
                    </div>
                  </div>

                  {/* Info Box */}
                  <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                    <div className="flex items-start space-x-3">
                      <div className="p-2 bg-blue-100 rounded-lg">
                        <Info className="h-5 w-5 text-blue-600" />
                      </div>
                      <div>
                        <h4 className="font-medium text-blue-900 mb-2">💡 Información Importante</h4>
                        <ul className="text-sm text-blue-700 space-y-1">
                          <li>• Los descuentos se aplican automáticamente al elegir el método de pago</li>
                          <li>• Se calculan sobre el subtotal antes de impuestos</li>
                          <li>• Valores válidos: 0% a 100%</li>
                          <li>• Los cambios se guardan automáticamente</li>
                        </ul>
                      </div>
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div className="flex flex-col sm:flex-row gap-3 pt-4">
                    <button
                      onClick={resetearDescuentosDefecto}
                      className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-xl transition-colors flex items-center justify-center space-x-2"
                    >
                      <RotateCcw className="w-4 h-4" />
                      <span>Restaurar Valores por Defecto</span>
                    </button>
                    
                    <button
                      onClick={guardarDescuentos}
                      disabled={guardandoDescuentos}
                      className="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-3 px-6 rounded-xl transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
                    >
                      {guardandoDescuentos ? (
                        <>
                          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                          <span>Guardando...</span>
                        </>
                      ) : (
                        <>
                          <Save className="w-4 h-4" />
                          <span>Guardar Descuentos</span>
                        </>
                      )}
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* SECCIÓN: CONFIGURACIÓN DE FACTURACIÓN AFIP */}
        <div className="mb-8">
          <ConfiguracionFacturacion />
        </div>

        {/* Modal de reinicio */}
        <ReinicioModal
          isOpen={mostrarModalReinicio}
          onClose={() => {
            setMostrarModalReinicio(false);
            setConfirmarReinicio('');
          }}
          onConfirm={confirmarReinicioSistema}
          opciones={opcionesReinicio}
          setOpciones={setOpcionesReinicio}
          confirmText={confirmarReinicio}
          setConfirmText={setConfirmarReinicio}
          loading={reiniciando}
        />
      </div>
    </div>
  );
};

export default ConfiguracionPage; 