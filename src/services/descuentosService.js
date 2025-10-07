import CONFIG from '../config/config';

class DescuentosService {
  
  // Obtener configuración de descuentos
  async obtenerDescuentos() {
    try {
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.CONFIGURACION));
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const configuraciones = await response.json();
      
      // Retornar descuentos con valores por defecto si no existen
      return {
        success: true,
        descuentos: {
          efectivo: parseFloat(configuraciones.descuento_efectivo || '10'),
          transferencia: parseFloat(configuraciones.descuento_transferencia || '10'),
          tarjeta: parseFloat(configuraciones.descuento_tarjeta || '0'),
          mercadopago: parseFloat(configuraciones.descuento_mercadopago || '0'),
          qr: parseFloat(configuraciones.descuento_qr || '0'),
          otros: parseFloat(configuraciones.descuento_otros || '0')
        }
      };

    } catch (error) {
      console.error('Error obteniendo descuentos:', error);
      // Retornar valores por defecto en caso de error
      return {
        success: false,
        message: error.message,
        descuentos: {
          efectivo: 10,
          transferencia: 10,
          tarjeta: 0,
          mercadopago: 0,
          qr: 0,
          otros: 0
        }
      };
    }
  }

  // Actualizar un descuento específico
  async actualizarDescuento(metodoPago, porcentaje) {
    try {
      const clave = `descuento_${metodoPago}`;
      const valor = parseFloat(porcentaje).toString();
      
      const response = await fetch(CONFIG.getApiUrl(CONFIG.API_ENDPOINTS.CONFIGURACION), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          clave: clave,
          valor: valor,
          descripcion: `Descuento para ${metodoPago} en porcentaje`
        }),
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Error al actualizar descuento');
      }

      return {
        success: true,
        message: `Descuento de ${metodoPago} actualizado correctamente`
      };

    } catch (error) {
      console.error('Error actualizando descuento:', error);
      return {
        success: false,
        message: error.message || 'Error al actualizar descuento'
      };
    }
  }

  // Actualizar múltiples descuentos a la vez
  async actualizarMultiplesDescuentos(descuentos) {
    try {
      const resultados = [];
      
      for (const [metodoPago, porcentaje] of Object.entries(descuentos)) {
        const resultado = await this.actualizarDescuento(metodoPago, porcentaje);
        resultados.push({ metodoPago, ...resultado });
        
        // Si algún descuento falla, detener el proceso
        if (!resultado.success) {
          return {
            success: false,
            message: `Error al actualizar descuento de ${metodoPago}: ${resultado.message}`,
            resultados
          };
        }
      }

      return {
        success: true,
        message: 'Todos los descuentos actualizados correctamente',
        resultados
      };

    } catch (error) {
      console.error('Error actualizando múltiples descuentos:', error);
      return {
        success: false,
        message: error.message || 'Error al actualizar descuentos'
      };
    }
  }

  // Obtener el descuento para un método de pago específico
  async obtenerDescuentoMetodo(metodoPago) {
    try {
      const resultado = await this.obtenerDescuentos();
      return resultado.descuentos[metodoPago] || 0;
    } catch (error) {
      console.error(`Error obteniendo descuento para ${metodoPago}:`, error);
      return 0;
    }
  }

  // Validar que un porcentaje sea válido
  validarPorcentaje(porcentaje) {
    const num = parseFloat(porcentaje);
    return !isNaN(num) && num >= 0 && num <= 100;
  }

  // Formatear descuentos para mostrar en interfaz
  formatearDescuentos(descuentos) {
    const formateados = {};
    
    for (const [metodo, porcentaje] of Object.entries(descuentos)) {
      formateados[metodo] = {
        porcentaje: parseFloat(porcentaje),
        texto: `${parseFloat(porcentaje)}%`,
        aplicar: parseFloat(porcentaje) > 0
      };
    }
    
    return formateados;
  }
}

// Crear una instancia singleton
const descuentosService = new DescuentosService();

export default descuentosService; 