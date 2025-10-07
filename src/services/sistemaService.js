import { API_URL } from "../config/config";

const sistemaService = {
  /**
   * Reinicia el sistema, vaciando todas las tablas excepto usuarios y configuración
   * @param {number} usuario_id - ID del usuario administrador que autoriza el reinicio
   * @returns {Promise} - Promesa con la respuesta del servidor
   */
  reiniciarSistema: async (usuario_id) => {
    try {
      const response = await fetch(`${API_URL}/reset_sistema.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          usuario_id,
          clave_confirmacion: "REINICIAR_SISTEMA_CONFIRMAR",
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.mensaje || "Error al reiniciar el sistema");
      }

      return data;
    } catch (error) {
      console.error("Error en reiniciarSistema:", error);
      throw error;
    }
  },

  /**
   * Obtiene información del estado actual del sistema
   * @returns {Promise} - Promesa con información del sistema
   */
  getEstadoSistema: async () => {
    try {
      const response = await fetch(`${API_URL}/sistema.php?action=estado`, {
        method: "GET",
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.mensaje || "Error al obtener estado del sistema");
      }

      return data;
    } catch (error) {
      console.error("Error en getEstadoSistema:", error);
      throw error;
    }
  }
};

export default sistemaService; 