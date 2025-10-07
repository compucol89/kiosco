import React from 'react';
import { useAuth } from '../contexts/AuthContext';

/**
 * Componente que verifica si el usuario tiene permiso para realizar una acción específica
 * @param {string} module - El módulo al que se intenta acceder (ej: 'usuarios', 'productos')
 * @param {string} action - La acción que se intenta realizar (ej: 'create', 'edit', 'delete')
 * @param {string} requiredRole - Rol requerido para la acción (ej: 'admin', 'vendedor')
 * @param {boolean} hideOnNoPermission - Si es true, el componente no se renderiza si no tiene permisos
 * @param {ReactNode} fallback - Componente a mostrar si no tiene permisos (solo si hideOnNoPermission es false)
 * @param {ReactNode} children - Contenido a mostrar si tiene permisos
 */
const PermissionGuard = ({ 
  module, 
  action, 
  requiredRole = '',
  hideOnNoPermission = false, 
  fallback = null, 
  children 
}) => {
  const { hasPermission, currentUser } = useAuth();
  
  // Verificar si el usuario tiene permiso
  const hasActionPermission = hasPermission(module, action);
  
  // Verificar si el usuario tiene el rol requerido
  const hasRequiredRole = requiredRole 
    ? (currentUser?.role || '').toLowerCase() === requiredRole.toLowerCase()
    : true;
  
  // El usuario debe tener tanto el permiso como el rol (si se requiere)
  const hasAccess = hasActionPermission && hasRequiredRole;
  
  // Si no tiene permiso y se debe ocultar, no renderizar nada
  if (!hasAccess && hideOnNoPermission) {
    return null;
  }
  
  // Si no tiene permiso, mostrar el fallback (si se proporciona)
  if (!hasAccess) {
    return fallback || null;
  }
  
  // Si tiene permiso, mostrar el contenido
  return <>{children}</>;
};

export default PermissionGuard; 