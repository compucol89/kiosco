import React, { createContext, useContext, useState, useEffect } from 'react';

// Definición de permisos por rol
const rolePermissions = {
  admin: {
    usuarios: {
      view: true,
      create: true,
      edit: true,
      delete: true
    },
    productos: {
      view: true,
      create: true,
      edit: true,
      delete: true,
      adjustStock: true
    },
    ventas: {
      view: true,
      create: true,
      edit: true,
      delete: true,
      anular: true
    },
    inventario: {
      view: true,
      edit: true,
      export: true
    },
    reportes: {
      view: true,
      export: true
    },
    configuracion: {
      view: true,
      edit: true
    },
    caja: {
      view: true,
      open: true,
      close: true,
      movements: true,
      edit: true
    }
  },
  vendedor: {
    usuarios: {
      view: false,
      create: false,
      edit: false,
      delete: false
    },
    productos: {
      view: true,
      create: false,
      edit: false,
      delete: false,
      adjustStock: false
    },
    ventas: {
      view: true,
      create: true,
      edit: false,
      delete: false,
      anular: false
    },
    inventario: {
      view: true,
      edit: false,
      export: false
    },
    reportes: {
      view: false,
      export: false
    },
    configuracion: {
      view: false,
      edit: false
    },
    caja: {
      view: true,
      open: false,
      close: false,
      movements: false,
      edit: false
    }
  },
  cajero: {
    usuarios: {
      view: false,
      create: false,
      edit: false,
      delete: false
    },
    productos: {
      view: true,
      create: false,
      edit: false,
      delete: false,
      adjustStock: false
    },
    ventas: {
      view: true,
      create: true,
      edit: false,
      delete: false,
      anular: false
    },
    inventario: {
      view: true,
      edit: false,
      export: false
    },
    reportes: {
      view: false,
      export: false
    },
    configuracion: {
      view: false,
      edit: false
    },
    caja: {
      view: true,
      open: true,
      close: true,
      movements: true,
      edit: false
    }
  }
};

// Crear el contexto
const AuthContext = createContext();

// Proveedor del contexto
export const AuthProvider = ({ children }) => {
  const [currentUser, setCurrentUser] = useState(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [loading, setLoading] = useState(true);

  // Comprobar si hay un usuario almacenado en el localStorage
  useEffect(() => {
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
      try {
        const user = JSON.parse(storedUser);
        
        // Asegurar que la propiedad isAdmin esté definida
        if (user.role === 'admin' && !user.hasOwnProperty('isAdmin')) {
          user.isAdmin = true;
          localStorage.setItem('currentUser', JSON.stringify(user));
        }
        
        setCurrentUser(user);
        setIsAuthenticated(true);
      } catch (error) {
        console.error('Error parsing stored user:', error);
        localStorage.removeItem('currentUser');
      }
    }
    setLoading(false);
  }, []);

  // Iniciar sesión
  const login = (userData) => {
    // Asegurar que la propiedad isAdmin esté definida
    if (userData.role === 'admin' && !userData.hasOwnProperty('isAdmin')) {
      userData.isAdmin = true;
    }
    
    localStorage.setItem('currentUser', JSON.stringify(userData));
    setCurrentUser(userData);
    setIsAuthenticated(true);
  };

  // Cerrar sesión
  const logout = () => {
    localStorage.removeItem('currentUser');
    localStorage.removeItem('authToken');
    setCurrentUser(null);
    setIsAuthenticated(false);
  };

  // Verificar si el usuario tiene un permiso específico
  const hasPermission = (module, action) => {
    if (!currentUser) return false;
    
    const role = currentUser.role;
    
    // Si es admin, tiene todos los permisos
    if (role === 'admin') return true;
    
    // Verificar permiso específico
    return rolePermissions[role]?.[module]?.[action] || false;
  };

  // Valor del contexto
  const value = {
    currentUser,
    isAuthenticated,
    loading,
    login,
    logout,
    hasPermission
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

// Hook personalizado para usar el contexto
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe usarse dentro de un AuthProvider');
  }
  return context;
};

export default AuthContext; 