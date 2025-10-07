import CONFIG from '../config/config';

class SeguridadInventarioService {
  
  // Obtener configuración de seguridad
  async obtenerConfiguracion() {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO), {
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
        throw new Error(data.message || 'Error al obtener configuración');
      }

      return {
        success: true,
        configuracion: data.configuracion
      };

    } catch (error) {
      console.error('Error obteniendo configuración de seguridad:', error);
      return {
        success: false,
        message: error.message || 'Error al obtener configuración'
      };
    }
  }

  // Actualizar configuración de seguridad
  async actualizarConfiguracion(configuracion) {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          configuracion: configuracion
        }),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al actualizar configuración');
      }

      return {
        success: true,
        message: data.message
      };

    } catch (error) {
      console.error('Error actualizando configuración:', error);
      return {
        success: false,
        message: error.message || 'Error al actualizar configuración'
      };
    }
  }

  // Registrar cambio en auditoría
  async registrarCambio(cambio) {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO) + '?action=log', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(cambio),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al registrar cambio');
      }

      return {
        success: true,
        audit_id: data.audit_id
      };

    } catch (error) {
      console.error('Error registrando cambio:', error);
      return {
        success: false,
        message: error.message || 'Error al registrar cambio'
      };
    }
  }

  // Obtener logs de auditoría
  async obtenerLogs(filtros = {}) {
    try {
      const params = new URLSearchParams();
      params.append('action', 'logs');
      
      if (filtros.limite) params.append('limite', filtros.limite);
      if (filtros.offset) params.append('offset', filtros.offset);
      if (filtros.usuario_id) params.append('usuario_id', filtros.usuario_id);
      if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
      if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

      const response = await fetch(`${CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO)}?${params}`, {
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
        throw new Error(data.message || 'Error al obtener logs');
      }

      return {
        success: true,
        logs: data.logs,
        total: data.total,
        limite: data.limite,
        offset: data.offset
      };

    } catch (error) {
      console.error('Error obteniendo logs:', error);
      return {
        success: false,
        message: error.message || 'Error al obtener logs'
      };
    }
  }

  // Crear backup del inventario
  async crearBackup(motivo = 'Backup manual', usuario = 'Sistema') {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO) + '?action=backup', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          motivo: motivo,
          usuario: usuario
        }),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al crear backup');
      }

      return {
        success: true,
        backup_id: data.backup_id,
        nombre: data.nombre,
        productos_respaldados: data.productos_respaldados
      };

    } catch (error) {
      console.error('Error creando backup:', error);
      return {
        success: false,
        message: error.message || 'Error al crear backup'
      };
    }
  }

  // Validar acceso para modificar inventario
  async validarAcceso(usuario_id, accion) {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.AUDITORIA_INVENTARIO) + '?action=validar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          usuario_id: usuario_id,
          accion: accion
        }),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al validar acceso');
      }

      return {
        success: true,
        acceso_valido: data.acceso_valido,
        validaciones: data.validaciones,
        configuracion: data.configuracion
      };

    } catch (error) {
      console.error('Error validando acceso:', error);
      return {
        success: false,
        message: error.message || 'Error al validar acceso'
      };
    }
  }

  // Verificar si es necesario crear backup antes de cambio
  async verificarBackupNecesario(configuracion, accion) {
    const accionesQueRequierenBackup = [
      'eliminar_producto',
      'modificacion_masiva',
      'ajuste_inventario',
      'importacion_datos'
    ];

    const requiereBackup = configuracion?.backup_antes_cambios === 'true' && 
                          accionesQueRequierenBackup.includes(accion);

    return requiereBackup;
  }

  // Verificar horario permitido
  verificarHorarioPermitido(configuracion) {
    if (configuracion?.horarios_permitidos_habilitado !== 'true') {
      return { permitido: true, mensaje: 'Sin restricciones de horario' };
    }

    const horaActual = new Date().toLocaleTimeString('en-US', { 
      hour12: false, 
      hour: '2-digit', 
      minute: '2-digit' 
    });

    const horaInicio = configuracion.horarios_permitidos_inicio || '08:00';
    const horaFin = configuracion.horarios_permitidos_fin || '18:00';

    const permitido = horaActual >= horaInicio && horaActual <= horaFin;

    return {
      permitido,
      mensaje: permitido 
        ? 'Horario válido para modificaciones'
        : `Modificaciones permitidas solo entre ${horaInicio} y ${horaFin}`
    };
  }

  // Formatear datos para auditoría
  prepararDatosAuditoria(usuario, accion, tabla, registroId = null, datosAnteriores = null, datosNuevos = null) {
    return {
      usuario_id: usuario.id,
      usuario_nombre: usuario.nombre,
      accion: accion,
      tabla_afectada: tabla,
      registro_id: registroId,
      datos_anteriores: datosAnteriores,
      datos_nuevos: datosNuevos
    };
  }

  // Helper para confirmar operación crítica con password
  async confirmarOperacionCritica(titulo, mensaje, onConfirm) {
    return new Promise((resolve) => {
      const modal = document.createElement('div');
      modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
      modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold text-gray-900 mb-4">${titulo}</h3>
          <p class="text-gray-600 mb-4">${mensaje}</p>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Confirme su contraseña:
            </label>
            <input 
              type="password" 
              id="password-confirm" 
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Ingrese su contraseña"
            />
          </div>
          <div class="flex justify-end space-x-3">
            <button 
              id="cancel-btn" 
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              id="confirm-btn" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            >
              Confirmar
            </button>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      const passwordInput = modal.querySelector('#password-confirm');
      const cancelBtn = modal.querySelector('#cancel-btn');
      const confirmBtn = modal.querySelector('#confirm-btn');

      passwordInput.focus();

      const cleanup = () => {
        document.body.removeChild(modal);
      };

      cancelBtn.onclick = () => {
        cleanup();
        resolve(false);
      };

      confirmBtn.onclick = () => {
        const password = passwordInput.value;
        if (password) {
          cleanup();
          onConfirm && onConfirm(password);
          resolve(true);
        }
      };

      passwordInput.onkeypress = (e) => {
        if (e.key === 'Enter') {
          confirmBtn.click();
        }
      };
    });
  }

}

export default new SeguridadInventarioService(); 