import React, { useState } from 'react';
import ProductosPage from './components/ProductosPage';
import PuntoDeVentaStockOptimizado from './components/PuntoDeVentaStockOptimizado';
import DashboardVentasCompleto from './components/DashboardVentasCompleto';
import InventarioInteligente from './components/InventarioInteligente';
import UsuariosPage from './components/UsuariosPage';
import LoginPage from './components/LoginPage';
import GestionCajaMejorada from './components/GestionCajaMejorada';
import HistorialTurnosPage from './components/HistorialTurnosPage';
import ConfiguracionPage from './components/ConfiguracionPage';
import ModuloFinancieroCompleto from './components/ModuloFinancieroCompleto';
import ReporteVentasModerno from './components/ReporteVentasModerno';
import IndicadorEstadoCaja from './components/IndicadorEstadoCaja';
import NotificacionesMovimientos from './components/NotificacionesMovimientos';
import { useAuth } from './contexts/AuthContext';
import usePermisos from './hooks/usePermisos';
import { Home, Package, ShoppingCart, Tag, UserCheck, LogOut, Calculator, Brain, Menu, X, Settings, Store, CalendarDays, Clock, BarChart3 } from 'lucide-react';

// Componente de navegación
const NavItem = ({ icon: IconComponent, label, active, onClick }) => (
  <button 
    onClick={onClick} 
    className={`flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150 w-full text-left ${ 
      active ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' 
    }`}
  >
    <IconComponent className={`w-5 h-5 mr-3 ${active ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'}`} /> 
    {label}
  </button>
);

// Sidebar Component Responsive
const Sidebar = ({ setCurrentPage, currentPage, user, onLogout, isOpen, setIsOpen, getFilteredMenuItems }) => {
  // 🔧 ORDEN OPTIMIZADO PARA FLUJO OPERATIVO POS PROFESIONAL
  // Reorganizado según lógica: abrir caja → vender → reportar → administrar
  const allMenuItems = [ 
    { icon: Home, label: 'Dashboard', page: 'Inicio' }, 
    { icon: Calculator, label: 'Control de Caja', page: 'ControlCaja' },
    { icon: Clock, label: 'Historial de Turnos', page: 'HistorialTurnos' },
    { icon: ShoppingCart, label: 'Punto de Ventas', page: 'PuntoDeVenta' },
    { icon: Tag, label: 'Reporte de Ventas', page: 'Ventas' },
    { icon: Brain, label: 'Inventario', page: 'Inventario' },
    { icon: Package, label: 'Productos', page: 'Productos' },
    { icon: BarChart3, label: 'Analisís', page: 'Finanzas' },

    { icon: UserCheck, label: 'Usuarios', page: 'Usuarios' },
    { icon: Settings, label: 'Configuración', page: 'Configuracion' }
  ];
  
  const filteredMenuItems = getFilteredMenuItems ? getFilteredMenuItems(allMenuItems) : [];

  const handleNavClick = (page) => {
    setCurrentPage(page);
    // Cerrar sidebar en móvil después de navegar
    if (window.innerWidth < 1024) {
      setIsOpen(false);
    }
  };

  return (
    <>
      {/* Overlay para móvil */}
      {isOpen && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
          onClick={() => setIsOpen(false)}
        />
      )}
      
      {/* Sidebar */}
      <div className={`
        fixed lg:static lg:translate-x-0 inset-y-0 left-0 z-50
        transform ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        transition-transform duration-300 ease-in-out
        w-64 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 h-screen
      `}>
        <div className="h-16 flex items-center justify-between border-b border-gray-200 px-4">
          <div className="flex items-center"> 
            <Store className="w-6 h-6 mr-2 text-blue-600" /> 
            <span className="text-lg sm:text-xl font-semibold text-gray-800 truncate">Tayrona Almacén</span> 
          </div>
          {/* Botón cerrar para móvil */}
          <button
            onClick={() => setIsOpen(false)}
            className="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-600"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        <nav className="flex-1 overflow-y-auto p-4 space-y-1"> 
          {filteredMenuItems.map((item) => ( 
            <NavItem 
              key={item.label} 
              icon={item.icon} 
              label={item.label} 
              active={currentPage === item.page} 
              onClick={(e) => { 
                e.preventDefault(); 
                handleNavClick(item.page);
              }} 
            /> 
          ))}
          
          {/* Botón de cerrar sesión */}
          <NavItem 
            icon={LogOut}
            label="Cerrar Sesión"
            active={false}
            onClick={(e) => {
              e.preventDefault();
              onLogout();
            }}
          />
        </nav>
      </div>
    </>
  );
};

// TopBar Component Responsive
const TopBar = ({ currentPage, user, onMenuClick }) => {
  const now = new Date();
  
  const formattedDate = now.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  const formattedTime = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
  
  const getTitle = (page) => { 
    switch(page) { 
      case 'Inicio': return 'Dashboard'; 
      case 'PuntoDeVenta': return 'Punto de Ventas'; 
      case 'Productos': return 'Productos';
      case 'Ventas': return 'Reporte de Ventas';
      case 'Inventario': return 'Inventario';
      case 'ControlCaja': return 'Control de Caja';
      case 'HistorialTurnos': return 'Historial de Turnos';
      case 'Finanzas': return 'Analisís';
      case 'Usuarios': return 'Usuarios';
      case 'Configuracion': return 'Configuración';
      default: return 'Tayrona Almacén'; 
    } 
  };

  // Función para obtener iniciales para el avatar
  const getUserInitials = () => {
    if (!user) return '';
    
    const nameParts = user.nombre.split(' ');
    if (nameParts.length === 1) return nameParts[0].charAt(0).toUpperCase();
    return (nameParts[0].charAt(0) + nameParts[1].charAt(0)).toUpperCase();
  };
  
  // Función para obtener color del avatar según el rol
  const getUserAvatarColor = () => {
    if (!user) return 'bg-gray-500';
    
    switch (user.role) {
      case 'admin':
        return 'bg-red-500 ring-red-500';
      case 'vendedor':
        return 'bg-blue-500 ring-blue-500';
      case 'cajero':
        return 'bg-green-500 ring-green-500';
      default:
        return 'bg-gray-500 ring-gray-500';
    }
  };
        
  return (
    <div className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-10">
      <div className="flex items-center">
        {/* Botón menú hamburguesa para móvil */}
        <button
          onClick={onMenuClick}
          className="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 mr-2"
        >
          <Menu className="w-5 h-5" />
        </button>
        
        <div>
          <h1 className="text-lg lg:text-xl font-semibold text-gray-800 truncate">{getTitle(currentPage)}</h1>
        </div>
      </div>
      
      <div className="flex items-center space-x-2 sm:space-x-4">
        <span className="text-xs text-gray-500 hidden sm:inline-flex items-center bg-gray-50 px-2 py-1 rounded"> 
          <CalendarDays size={12} className="mr-1" /> 
          <span className="hidden md:inline">{formattedDate}</span>
          <span className="md:hidden">{formattedDate.split('/').slice(0,2).join('/')}</span>
          <Clock size={12} className="ml-2 mr-1" /> {formattedTime}
        </span>
        
        {/* 🔥 INDICADOR DE ESTADO DE CAJA EN TIEMPO REAL */}
        <IndicadorEstadoCaja />
        
        {user && (
          <div className="flex items-center space-x-2"> 
            <span className="text-sm font-medium text-gray-700 hidden lg:inline truncate max-w-32">{user.nombre}</span> 
            <div className={`w-8 h-8 ${getUserAvatarColor()} rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-offset-1`}>
              {getUserInitials()}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

// Main App Component Responsive
function App() {
  const [currentPage, setCurrentPage] = useState('Inicio');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { currentUser, loading, logout } = useAuth();
  const { hasAccess, getFilteredMenuItems, loading: permisosLoading } = usePermisos(currentUser);

  // Determinar qué página renderizar
  const renderPage = () => {
    // Si no hay usuario autenticado, mostrar página de login
    if (!currentUser) {
      return <LoginPage />;
    }
    
    // Verificar acceso a la página
    if (!hasAccess(currentPage)) {
      return (
        <div className="p-4 sm:p-6 text-center">
          <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            <p>No tienes permisos para acceder a esta página</p>
          </div>
          <button 
            onClick={() => setCurrentPage('Inicio')}
            className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
          >
            Volver al Inicio
          </button>
        </div>
      );
    }

    // Renderizar la página solicitada
    switch (currentPage) {
      case 'Inicio':
        return <DashboardVentasCompleto />;
      case 'Productos':
        return <ProductosPage />;
      case 'PuntoDeVenta':
        return <PuntoDeVentaStockOptimizado />;
      case 'Ventas':
        return <ReporteVentasModerno />;
      case 'Inventario':
        return <InventarioInteligente />;
      case 'Usuarios':
        return <UsuariosPage />;
      case 'ControlCaja':
        return <GestionCajaMejorada />;
      case 'HistorialTurnos':
        return <HistorialTurnosPage />;
      case 'Finanzas':
        return <ModuloFinancieroCompleto />;

      case 'Configuracion':
        return <ConfiguracionPage />;
      default:
        return (
          <div className="p-4 sm:p-6 text-center">
            <h2 className="text-xl font-semibold text-gray-700 mb-4">
              Página {currentPage} en desarrollo
            </h2>
            <p className="text-gray-500">
              Esta sección se encuentra actualmente en desarrollo. Por favor, intente más tarde.
            </p>
          </div>
        );
    }
  };

  return (
    <div className="flex h-screen bg-gray-100 font-sans">
      {currentUser ? (
        <>
          <Sidebar 
            currentPage={currentPage} 
            setCurrentPage={setCurrentPage} 
            user={currentUser}
            onLogout={logout}
            isOpen={sidebarOpen}
            setIsOpen={setSidebarOpen}
            getFilteredMenuItems={getFilteredMenuItems}
          />
          
          <div className="flex-1 flex flex-col overflow-hidden lg:ml-0">
            <TopBar 
              currentPage={currentPage} 
              user={currentUser} 
              onMenuClick={() => setSidebarOpen(true)}
            />
            
            <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
              {renderPage()}
            </main>
            
            {/* 🔔 SISTEMA DE NOTIFICACIONES */}
            <NotificacionesMovimientos />
            
            <div className="bg-white border-t border-gray-200 py-1 px-4 text-xs text-gray-500 flex flex-col sm:flex-row justify-between items-center space-y-1 sm:space-y-0">
              <span>Tayrona Almacén v1.0.0</span>
              <span className="hidden sm:inline">© 2025 - Sistema adaptado para Argentina</span>
            </div>
          </div>
        </>
      ) : (
        <div className="flex-1">
          {loading || permisosLoading ? (
            <div className="flex items-center justify-center h-screen">
              <div className="text-center">
                <div className="w-16 h-16 border-b-2 border-gray-900 rounded-full animate-spin mx-auto mb-4"></div>
                <p className="text-gray-600">
                  {loading ? 'Cargando aplicación...' : 'Cargando permisos...'}
                </p>
              </div>
            </div>
          ) : (
            renderPage()
          )}
        </div>
      )}
    </div>
  );
}

export default App;
