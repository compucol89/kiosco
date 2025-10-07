import CONFIG from '../config/config';

// Servicio para manejar las configuraciones del sistema
const configService = {
    // Obtener la configuración actual
    getConfiguracion: async () => {
        try {
            // Usar el endpoint básico de configuración (sin autenticación empresarial)
            const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.CONFIGURACION}`);
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al obtener configuración:', error);
            throw error;
        }
    },
    
    // Actualizar una configuración específica
    actualizarConfiguracion: async (clave, valor) => {
        try {
            // Usar el endpoint básico de configuración (sin autenticación empresarial)
            const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.CONFIGURACION}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    clave,
                    valor
                })
            });
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al actualizar configuración:', error);
            throw error;
        }
    },
    
    // Reiniciar el sistema completo
    reiniciarSistema: async (confirmar = false, opciones = null) => {
        if (!confirmar) {
            throw new Error('Se requiere confirmación para reiniciar el sistema');
        }
        
        // Si no se especifican opciones, usar valores predeterminados
        const opcionesEliminacion = {
            eliminarVentas: true,
            eliminarCaja: true,
            eliminarProductos: false,
            eliminarClientes: false,
            ...opciones
        };
        
        try {
            console.log('🔄 Iniciando reinicio del sistema...');
            console.log('📋 Opciones de eliminación:', opcionesEliminacion);
            
            const usuario = JSON.parse(localStorage.getItem('currentUser'));
            if (!usuario || !usuario.id) {
                throw new Error('Usuario no autenticado o ID no disponible');
            }

            // Usar el endpoint básico de reset (funciona sin autenticación empresarial)
            const url = `${CONFIG.API_URL}/api/reset_sistema.php`;
            console.log('📡 URL de la API:', url);

            const requestData = {
                clave_confirmacion: 'REINICIAR_SISTEMA_CONFIRMAR',
                usuario_id: usuario.id,
                opciones: opcionesEliminacion
            };

            console.log('📤 Datos a enviar:', requestData);
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            console.log('📨 Respuesta recibida:', {
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries())
            });

            // Obtener el texto de la respuesta primero
            const responseText = await response.text();
            console.log('📄 Contenido de la respuesta:', responseText);

            // Verificar si la respuesta es HTML (error de PHP)
            if (responseText.includes('<html>') || responseText.includes('<!DOCTYPE') || responseText.includes('<br />')) {
                console.error('❌ Respuesta HTML detectada (posible error de PHP):', responseText);
                throw new Error('El servidor devolvió una página de error HTML en lugar de JSON. Revise los logs del servidor para más detalles.');
            }

            // Verificar si la respuesta está vacía
            if (!responseText.trim()) {
                console.error('❌ Respuesta vacía del servidor');
                throw new Error('El servidor devolvió una respuesta vacía. Verifique la configuración del servidor.');
            }

            // Intentar parsear como JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('❌ Error al parsear JSON:', jsonError);
                console.error('📄 Contenido que causó el error:', responseText.substring(0, 500) + '...');
                throw new Error(`El servidor devolvió una respuesta inválida. Error JSON: ${jsonError.message}`);
            }

            console.log('📋 Datos parseados:', data);

            // Verificar errores HTTP
            if (!response.ok) {
                const errorMessage = data.mensaje || `Error HTTP ${response.status}: ${response.statusText}`;
                console.error('❌ Error HTTP:', errorMessage);
                throw new Error(errorMessage);
            }

            // Verificar el éxito de la operación
            if (!data.success) {
                const errorMessage = data.mensaje || 'Error desconocido al reiniciar el sistema';
                console.error('❌ Error de operación:', errorMessage);
                throw new Error(errorMessage);
            }

            console.log('✅ Reinicio completado exitosamente:', data);
            return data;
            
        } catch (error) {
            console.error('💥 Error completo al reiniciar sistema:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            // Mejorar el mensaje de error para el usuario
            let userMessage = error.message;
            
            if (error.message.includes('fetch')) {
                userMessage = 'Error de conexión con el servidor. Verifique que el servidor web esté funcionando.';
            } else if (error.message.includes('NetworkError')) {
                userMessage = 'Error de red. Verifique su conexión a internet y que el servidor esté accesible.';
            } else if (error.message.includes('HTML')) {
                userMessage = 'Error del servidor. Revise los logs de PHP para más detalles.';
            }
            
            throw new Error(userMessage);
        }
    }
};

export default configService; 