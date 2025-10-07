import CONFIG from '../config/config';

// Servicios para productos
export const productosService = {
  // Obtener todos los productos
  getAll: async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.PRODUCTOS}`);
      if (!response.ok) {
        throw new Error(`Error: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error('Error al obtener productos:', error);
      throw error;
    }
  },

  // Obtener un producto por su ID
  getById: async (id) => {
    try {
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.PRODUCTOS}/${id}`);
      if (!response.ok) {
        throw new Error(`Error: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error(`Error al obtener producto con ID ${id}:`, error);
      throw error;
    }
  },

  // Crear un nuevo producto
  create: async (producto) => {
    try {
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.PRODUCTOS}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(producto),
      });
      if (!response.ok) {
        throw new Error(`Error: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error('Error al crear producto:', error);
      throw error;
    }
  },

  // Actualizar un producto existente
  update: async (id, producto) => {
    try {
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.PRODUCTOS}/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(producto),
      });
      if (!response.ok) {
        throw new Error(`Error: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error(`Error al actualizar producto con ID ${id}:`, error);
      throw error;
    }
  },

  // Eliminar un producto
  delete: async (id) => {
    try {
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.PRODUCTOS}/${id}`, {
        method: 'DELETE',
      });
      if (!response.ok) {
        throw new Error(`Error: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error(`Error al eliminar producto con ID ${id}:`, error);
      throw error;
    }
  }
};

// NOTA: Los servicios de caja han sido unificados en cajaService.js
// para evitar duplicación y usar la API optimizada

export default {
  productos: productosService
  // caja: Usar cajaService.js en su lugar
}; 