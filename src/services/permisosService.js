import CONFIG from '../config/config';

class PermisosService {
  
  // Obtener permisos configurados
  async obtenerPermisos() {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PERMISOS_USUARIO), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al obtener permisos');
      }

      return {
        success: true,
        permisos: data.permisos,
        modulos: data.modulos,
        roles: data.roles
      };

    } catch (error) {
      console.error('Error obteniendo permisos:', error);
      return {
        success: false,
        message: error.message || 'Error al obtener permisos'
      };
    }
  }

  // Actualizar permisos
  async actualizarPermisos(permisos) {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.PERMISOS_USUARIO), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          permisos: permisos
        }),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al actualizar permisos');
      }

      return {
        success: true,
        message: data.message
      };

    } catch (error) {
      console.error('Error actualizando permisos:', error);
      return {
        success: false,
        message: error.message || 'Error al actualizar permisos'
      };
    }
  }

  // Verificar si un usuario tiene acceso a un módulo específico
  verificarAcceso(permisos, userRole, modulo) {
    if (!permisos || !userRole || !modulo) {
      return false;
    }

    // Admin siempre tiene acceso
    if (userRole === 'admin') {
      return true;
    }

    // Verificar permiso específico
    return permisos[userRole] && permisos[userRole][modulo] === true;
  }

  // Obtener módulos accesibles para un rol
  getModulosAccesibles(permisos, userRole) {
    if (!permisos || !userRole) {
      return [];
    }

    // Admin tiene acceso a todo
    if (userRole === 'admin') {
      return Object.keys(permisos.admin || {});
    }

    // Filtrar módulos con acceso true
    const modulosRol = permisos[userRole] || {};
    return Object.keys(modulosRol).filter(modulo => modulosRol[modulo] === true);
  }

  // Validar estructura de permisos
  validarPermisos(permisos) {
    const roles = ['admin', 'vendedor', 'cajero'];
    const modulosRequeridos = [
      'Inicio', 'PuntoDeVenta', 'Ventas', 'ControlCaja', 'Inventario', 
      'Productos', 'Reportes', 'GastosFijos', 'Usuarios', 'Configuracion'
    ];

    const errores = [];

    // Verificar que existan todos los roles
    for (const rol of roles) {
      if (!permisos[rol]) {
        errores.push(`Faltan permisos para el rol: ${rol}`);
        continue;
      }

      // Verificar que cada rol tenga todos los módulos
      for (const modulo of modulosRequeridos) {
        if (permisos[rol][modulo] === undefined) {
          errores.push(`Falta permiso para ${rol} en módulo: ${modulo}`);
        }
      }
    }

    // Verificar que admin tenga 'Inicio' siempre habilitado
    if (permisos.admin && permisos.admin.Inicio !== true) {
      errores.push('El administrador debe tener acceso al módulo Inicio');
    }

    return {
      valido: errores.length === 0,
      errores: errores
    };
  }

  // Resetear permisos a valores por defecto
  getPermisosDefecto() {
    return {
      admin: {
        Inicio: true,
        PuntoDeVenta: true,
        Ventas: true,
        ControlCaja: true,
        Inventario: true,
        Productos: true,
        Reportes: true,
        GastosFijos: true,
        Usuarios: true,
        Configuracion: true
      },
      vendedor: {
        Inicio: true,
        PuntoDeVenta: true,
        Ventas: true,
        ControlCaja: false,
        Inventario: true,
        Productos: true,
        Reportes: false,
        GastosFijos: false,
        Usuarios: false,
        Configuracion: false
      },
      cajero: {
        Inicio: true,
        PuntoDeVenta: true,
        Ventas: true,
        ControlCaja: true,
        Inventario: true,
        Productos: false,
        Reportes: false,
        GastosFijos: false,
        Usuarios: false,
        Configuracion: false
      }
    };
  }

}

const permisosService = new PermisosService();
export default permisosService; 