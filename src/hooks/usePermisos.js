import { useState, useEffect } from 'react';
import permisosService from '../services/permisosService';

// Hook personalizado para gestionar permisos dinámicos
const usePermisos = (user) => {
  const [permisos, setPermisos] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const cargarPermisos = async () => {
      if (!user) {
        setPermisos({});
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);
        
        const resultado = await permisosService.obtenerPermisos();
        
        if (resultado.success) {
          setPermisos(resultado.permisos);
        } else {
          console.error('Error cargando permisos:', resultado.message);
          setError(resultado.message);
          // Usar permisos por defecto en caso de error
          setPermisos(permisosService.getPermisosDefecto());
        }
      } catch (err) {
        console.error('Error cargando permisos:', err);
        setError(err.message);
        // Usar permisos por defecto en caso de error
        setPermisos(permisosService.getPermisosDefecto());
      } finally {
        setLoading(false);
      }
    };

    cargarPermisos();
  }, [user]);

  // Función para verificar si el usuario tiene acceso a una página
  const hasAccess = (page) => {
    if (!user || !permisos) return false;
    
    // Admin siempre tiene acceso a todo
    if (user.role === 'admin') return true;
    
    // Verificar permisos específicos del rol
    const permisosRol = permisos[user.role];
    if (!permisosRol) return false;
    
    return permisosRol[page] === true;
  };

  // Función para obtener los elementos del menú filtrados según permisos
  const getFilteredMenuItems = (allMenuItems) => {
    if (!user || !permisos) return [];
    
    // Admin tiene acceso a todo
    if (user.role === 'admin') return allMenuItems;
    
    // Filtrar según permisos del rol
    return allMenuItems.filter(item => hasAccess(item.page));
  };

  // Función para recargar permisos
  const reloadPermisos = async () => {
    if (!user) return;
    
    try {
      setLoading(true);
      setError(null);
      
      const resultado = await permisosService.obtenerPermisos();
      
      if (resultado.success) {
        setPermisos(resultado.permisos);
      } else {
        setError(resultado.message);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return {
    permisos,
    loading,
    error,
    hasAccess,
    getFilteredMenuItems,
    reloadPermisos
  };
};

export default usePermisos; 