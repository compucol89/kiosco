import React, { useState, useEffect } from 'react';
import { FileText, Package } from 'lucide-react';

// Componente de tarjeta estadística
const StatCard = ({ icon: IconComponent, title, value, subValue, subLabel, bgColor = 'bg-blue-500', textColor = 'text-white' }) => (
  <div className={`rounded-lg p-4 shadow-md ${bgColor} ${textColor} flex flex-col justify-between min-h-[120px]`}>
    <div className="flex justify-between items-start">
      <div>
        <p className="text-sm font-medium opacity-90">{title}</p>
        <p className="text-3xl font-bold mt-1">{value}</p>
      </div>
      {IconComponent && <IconComponent className="w-8 h-8 opacity-70" />}
    </div>
    {subLabel && subValue !== undefined && (
      <p className="text-xs mt-2 opacity-80">{subValue} {subLabel}</p>
    )}
  </div>
);

// Componente que carga y muestra las estadísticas
const StatCards = () => {
  const [stats, setStats] = useState({
    totalProductos: 0,
    totalComprobantes: 0,
    nuevosProductos: 0,
    nuevosComprobantes: 0,
    loading: true,
    error: null
  });

  useEffect(() => {
    const fetchStats = async () => {
      try {
        // Cargar datos de productos
        const productosResponse = await fetch('http://localhost/kiosco/api/productos.php');
        if (!productosResponse.ok) {
          throw new Error('Error al cargar productos');
        }
        const productos = await productosResponse.json();
        
        // Simulamos una carga de datos del resto de entidades
        // En un caso real, tendrías endpoints API para cada una
        
        // Actualizamos el estado con los datos obtenidos
        setStats({
          totalProductos: productos.length,
          totalComprobantes: 112, // Simulado
          nuevosProductos: 5, // Simulado
          nuevosComprobantes: 3, // Simulado
          loading: false,
          error: null
        });
        
      } catch (error) {
        console.error('Error cargando estadísticas:', error);
        // En caso de error, mantenemos los datos simulados
        setStats({
          totalProductos: 499,
          totalComprobantes: 112,
          nuevosProductos: 5,
          nuevosComprobantes: 3,
          loading: false,
          error: error.message
        });
      }
    };
    
    fetchStats();
  }, []);

  // Renderizar un spinner mientras se cargan los datos
  if (stats.loading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        {[1, 2].map(i => (
          <div key={i} className="rounded-lg p-4 shadow-md bg-gray-100 animate-pulse min-h-[120px]"></div>
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <StatCard 
        icon={FileText} 
        title="Total Comprobantes" 
        value={stats.totalComprobantes.toString()} 
        bgColor="bg-blue-500" 
        textColor="text-white" 
        subValue={stats.nuevosComprobantes.toString()} 
        subLabel="Comprobantes Hoy" 
      />
      <StatCard 
        icon={Package} 
        title="Total Productos" 
        value={stats.totalProductos.toString()} 
        bgColor="bg-purple-500" 
        textColor="text-white" 
        subValue={stats.nuevosProductos.toString()} 
        subLabel="Productos Nuevos" 
      />
    </div>
  );
};

export { StatCard, StatCards }; 