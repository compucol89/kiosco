import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { 
  User, Edit, Trash2, RefreshCw, X, UserPlus, Eye, EyeOff, Search
} from 'lucide-react';
import CONFIG from '../config/config';
import PermissionGuard from './PermissionGuard';

const UsuariosPage = () => {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [modalAbierto, setModalAbierto] = useState(false);
  const [usuarioEditando, setUsuarioEditando] = useState(null);
  const [formData, setFormData] = useState({
    username: '',
    nombre: '',
    role: 'vendedor',
    password: '',
    confirmPassword: ''
  });
  const [showPassword, setShowPassword] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filteredUsuarios, setFilteredUsuarios] = useState([]);

  // Cargar usuarios al montar el componente
  useEffect(() => {
    fetchUsuarios();
  }, []);

  // Filtrar usuarios cuando cambia el término de búsqueda
  useEffect(() => {
    if (searchTerm.trim() === '') {
      setFilteredUsuarios(usuarios);
    } else {
      const filtered = usuarios.filter(usuario => 
        usuario.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
        usuario.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
        usuario.role.toLowerCase().includes(searchTerm.toLowerCase())
      );
      setFilteredUsuarios(filtered);
    }
  }, [searchTerm, usuarios]);

  const fetchUsuarios = async () => {
    setLoading(true);
    try {
      console.log("Iniciando carga de usuarios desde:", `${CONFIG.API_URL}/api/usuarios.php`);
      
      const response = await axios.get(`${CONFIG.API_URL}/api/usuarios.php`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      
      console.log("Respuesta completa recibida:", response);
      
      // Revisar si la respuesta tiene datos
      if (response.data) {
        let usuariosData = null;
        
        // Intentar diferentes estructuras de respuesta
        if (Array.isArray(response.data)) {
          console.log("Los datos son un array directamente:", response.data);
          usuariosData = response.data;
        } 
        else if (response.data.items && Array.isArray(response.data.items)) {
          console.log("Los datos están en la propiedad 'items':", response.data.items);
          usuariosData = response.data.items;
        }
        else if (response.data.usuarios && Array.isArray(response.data.usuarios)) {
          console.log("Los datos están en la propiedad 'usuarios':", response.data.usuarios);
          usuariosData = response.data.usuarios;
        }
        else if (response.data.success && response.data.data && Array.isArray(response.data.data)) {
          console.log("Los datos están en un formato success/data:", response.data.data);
          usuariosData = response.data.data;
        }
        else {
          // Si no podemos encontrar un array en los datos, intentar convertir los datos en array
          console.log("Formato no reconocido, intentando análisis detallado:", response.data);
          
          if (typeof response.data === 'object' && response.data !== null) {
            // Crear array a partir del objeto
            usuariosData = Object.values(response.data).filter(item => 
              typeof item === 'object' && item !== null && item.id && item.username
            );
            
            if (usuariosData.length > 0) {
              console.log("Convertidos datos de objeto a array:", usuariosData);
            } else {
              // Crear mensaje de error detallado
              let errorMsg = "Error: No se encontraron usuarios en la respuesta. ";
              
              // Añadir detalles de la estructura de la respuesta
              if (typeof response.data === 'object') {
                errorMsg += "La respuesta contiene las siguientes propiedades: " + 
                            Object.keys(response.data).join(", ");
              } else {
                errorMsg += "La respuesta es de tipo: " + typeof response.data;
              }
              
              throw new Error(errorMsg);
            }
          } else {
            throw new Error("Formato de respuesta no reconocido. Tipo: " + typeof response.data);
          }
        }
        
        // Si llegamos aquí, tenemos un array de usuarios
        if (usuariosData.length === 0) {
          setError("No hay usuarios registrados en el sistema. Por favor, cree un nuevo usuario.");
        } else {
          setError(null); // Limpiar cualquier error previo
        }
        
        setUsuarios(usuariosData);
        setFilteredUsuarios(usuariosData);
        console.log("Usuarios cargados correctamente:", usuariosData.length);
      } else {
        console.error("La respuesta no contiene datos:", response);
        setError('La respuesta de la API no contiene datos. Verifique la conexión con el servidor.');
      }
    } catch (err) {
      console.error('Error detallado al cargar usuarios:', err);
      
      // Mensaje de error detallado con información para diagnóstico
      let errorMessage = `Error al cargar usuarios: ${err.message}`;
      
      // Añadir información adicional si está disponible
      if (err.response) {
        errorMessage += `\nEstado: ${err.response.status}`;
        if (err.response.data) {
          if (typeof err.response.data === 'object') {
            errorMessage += `\nMensaje del servidor: ${JSON.stringify(err.response.data)}`;
          } else {
            errorMessage += `\nMensaje del servidor: ${err.response.data}`;
          }
        }
      } else if (err.request) {
        errorMessage += "\nNo se recibió respuesta del servidor. Verifique que la API esté funcionando y accesible.";
      }
      
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const abrirModal = (usuario = null) => {
    if (usuario) {
      // Modo edición
      setUsuarioEditando(usuario);
      setFormData({
        username: usuario.username,
        nombre: usuario.nombre,
        role: usuario.role,
        password: '',
        confirmPassword: ''
      });
    } else {
      // Modo creación
      setUsuarioEditando(null);
      setFormData({
        username: '',
        nombre: '',
        role: 'vendedor',
        password: '',
        confirmPassword: ''
      });
    }
    setModalAbierto(true);
  };

  const cerrarModal = () => {
    setModalAbierto(false);
    setUsuarioEditando(null);
    setFormData({
      username: '',
      nombre: '',
      role: 'vendedor',
      password: '',
      confirmPassword: ''
    });
  };

  const handleInputChange = useCallback((e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    console.log('Enviando formulario con datos:', formData);
    
    // Validaciones básicas
    if (!formData.username || !formData.nombre || !formData.role) {
      setError('Todos los campos son obligatorios');
      return;
    }
    
    // Validar contraseña en modo creación
    if (!usuarioEditando && (!formData.password || formData.password !== formData.confirmPassword)) {
      setError('Las contraseñas no coinciden o están vacías');
      return;
    }
    
    // Validar contraseña en modo edición (solo si se proporcionó una)
    if (usuarioEditando && formData.password && formData.password !== formData.confirmPassword) {
      setError('Las contraseñas no coinciden');
      return;
    }
    
    setLoading(true);
    try {
      const datos = {
        ...formData
      };
      
      // No enviar confirmPassword
      delete datos.confirmPassword;
      
      // Si no se proporcionó contraseña en modo edición, no enviarla
      if (usuarioEditando && !formData.password) {
        delete datos.password;
      }
      
      console.log('Datos a enviar:', datos);
      
      let response;
      let url;
      if (usuarioEditando) {
        // Actualizar usuario existente
        url = `${CONFIG.API_URL}/api/usuarios.php/${usuarioEditando.id}`;
        console.log('Realizando PUT a:', url);
        response = await axios.put(url, datos, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('authToken')}`
          }
        });
      } else {
        // Crear nuevo usuario
        url = `${CONFIG.API_URL}/api/usuarios.php`;
        console.log('Realizando POST a:', url);
        response = await axios.post(url, datos, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('authToken')}`
          }
        });
      }
      
      console.log('Respuesta del servidor:', response.data);
      
      if (response.data && response.data.success) {
        console.log('Operación exitosa, actualizando lista de usuarios');
        // Actualizar lista de usuarios
        fetchUsuarios();
        cerrarModal();
      } else {
        console.error('Error en la respuesta:', response.data);
        setError(response.data?.message || 'Error al guardar usuario');
      }
    } catch (err) {
      console.error('Error al guardar usuario:', err);
      if (err.response) {
        console.error('Detalles de la respuesta de error:', {
          status: err.response.status,
          data: err.response.data
        });
      }
      setError(`Error al guardar usuario: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const eliminarUsuario = async (id) => {
    if (!window.confirm('¿Está seguro que desea eliminar este usuario?')) {
      return;
    }
    
    setLoading(true);
    try {
      const response = await axios.delete(`${CONFIG.API_URL}/api/usuarios.php/${id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      
      if (response.data && response.data.success) {
        // Actualizar lista de usuarios
        fetchUsuarios();
      } else {
        setError(response.data?.message || 'Error al eliminar usuario');
      }
    } catch (err) {
      console.error('Error al eliminar usuario:', err);
      setError(`Error al eliminar usuario: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const getRoleBadgeColor = (role) => {
    switch (role) {
      case 'admin':
        return 'bg-red-100 text-red-800';
      case 'vendedor':
        return 'bg-blue-100 text-blue-800';
      case 'cajero':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="p-4">
      <h1 className="text-2xl font-bold mb-4">Gestión de Usuarios</h1>
      
      {error && (
        <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 flex justify-between items-center">
          <p>{error}</p>
          <button className="text-red-700" onClick={() => setError(null)}>
            <X size={16} />
          </button>
        </div>
      )}
      
      <div className="bg-white p-4 rounded-lg shadow mb-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
          <div className="relative mb-4 md:mb-0 md:w-1/3">
            <input
              type="text"
              placeholder="Buscar usuario..."
              className="w-full pl-10 pr-4 py-2 border rounded"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <div className="absolute left-3 top-2 text-gray-400">
              <Search size={18} />
            </div>
          </div>
          
          <PermissionGuard 
            module="usuarios" 
            action="create" 
            hideOnNoPermission={true}
          >
            <button
              onClick={() => abrirModal()}
              className="flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              <UserPlus size={16} className="mr-2" />
              Nuevo Usuario
            </button>
          </PermissionGuard>
        </div>
      </div>
      
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {loading && !modalAbierto ? (
          <div className="text-center p-8">
            <RefreshCw size={32} className="animate-spin mx-auto mb-2 text-blue-500" />
            <p>Cargando usuarios...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Usuario
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nombre Completo
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Rol
                  </th>
                  <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredUsuarios.length > 0 ? (
                  filteredUsuarios.map((usuario) => (
                    <tr key={usuario.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <User className="h-5 w-5 text-blue-600" />
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-gray-900">{usuario.username}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{usuario.nombre}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getRoleBadgeColor(usuario.role)}`}>
                          {usuario.role.charAt(0).toUpperCase() + usuario.role.slice(1)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <PermissionGuard module="usuarios" action="edit" hideOnNoPermission={true}>
                          <button
                            onClick={() => abrirModal(usuario)}
                            className="text-blue-600 hover:text-blue-900 mx-1"
                            title="Editar usuario"
                          >
                            <Edit size={18} />
                          </button>
                        </PermissionGuard>
                        
                        <PermissionGuard 
                          module="usuarios" 
                          action="delete" 
                          hideOnNoPermission={true}
                        >
                          <button
                            onClick={() => eliminarUsuario(usuario.id)}
                            className="text-red-600 hover:text-red-900 mx-1"
                            title="Eliminar usuario"
                            disabled={usuario.role === 'admin' && filteredUsuarios.filter(u => u.role === 'admin').length <= 1}
                          >
                            <Trash2 size={18} />
                          </button>
                        </PermissionGuard>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="4" className="px-6 py-4 text-center text-gray-500">
                      No se encontraron usuarios
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
      
      {/* Modal para crear/editar usuario */}
      {modalAbierto && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">
                {usuarioEditando ? 'Editar Usuario' : 'Nuevo Usuario'}
              </h3>
              <button onClick={cerrarModal} className="text-gray-500 hover:text-gray-700">
                <X size={20} />
              </button>
            </div>
            
            <form onSubmit={handleSubmit}>
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Nombre de usuario
                </label>
                <input
                  type="text"
                  name="username"
                  value={formData.username}
                  onChange={handleInputChange}
                  className="border rounded w-full px-3 py-2"
                  disabled={usuarioEditando}
                />
              </div>
              
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Nombre completo
                </label>
                <input
                  type="text"
                  name="nombre"
                  value={formData.nombre}
                  onChange={handleInputChange}
                  className="border rounded w-full px-3 py-2"
                />
              </div>
              
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Rol
                </label>
                <select
                  name="role"
                  value={formData.role}
                  onChange={handleInputChange}
                  className="border rounded w-full px-3 py-2"
                >
                  <option value="admin">Administrador</option>
                  <option value="vendedor">Vendedor</option>
                  <option value="cajero">Cajero</option>
                </select>
              </div>
              
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Contraseña {usuarioEditando && '(dejar en blanco para mantener la actual)'}
                </label>
                <div className="relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    value={formData.password}
                    onChange={handleInputChange}
                    className="border rounded w-full px-3 py-2 pr-10"
                  />
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </div>
              
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Confirmar contraseña
                </label>
                <input
                  type={showPassword ? 'text' : 'password'}
                  name="confirmPassword"
                  value={formData.confirmPassword}
                  onChange={handleInputChange}
                  className="border rounded w-full px-3 py-2"
                />
              </div>
              
              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={cerrarModal}
                  className="px-4 py-2 border rounded text-gray-700 hover:bg-gray-100"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                  disabled={loading}
                >
                  {loading ? 'Guardando...' : 'Guardar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default UsuariosPage; 