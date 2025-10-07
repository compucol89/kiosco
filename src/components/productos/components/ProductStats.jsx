// src/components/productos/components/ProductStats.jsx
// Componente para mostrar estadísticas de productos
// Diseño modular y reutilizable con iconos y métricas claras
// RELEVANT FILES: ProductosPage.jsx, useProductStats.js

import React from 'react';
import { 
  Package, DollarSign, AlertCircle, AlertTriangle, BarChart3, TrendingUp,
  ShieldCheck, Zap, Target, Trophy, Gauge, Calendar
} from 'lucide-react';

const ProductStats = ({ estadisticas, loading = false }) => {
  if (loading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6 mb-6">
        {Array.from({ length: 6 }).map((_, index) => (
          <div key={index} className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div className="animate-pulse">
              <div className="h-4 bg-gray-200 rounded mb-2"></div>
              <div className="h-8 bg-gray-200 rounded"></div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  // 🔥 DETERMINAR ESTADO DE SALUD DEL INVENTARIO
  const getSaludColor = (salud) => {
    if (salud >= 80) return { bg: 'bg-green-100', text: 'text-green-600', emoji: '🟢' };
    if (salud >= 60) return { bg: 'bg-yellow-100', text: 'text-yellow-600', emoji: '🟡' };
    return { bg: 'bg-red-100', text: 'text-red-600', emoji: '🔴' };
  };

  const saludColor = getSaludColor(estadisticas.saludInventario || 0);

  const stats = [
    {
      label: 'Total Productos',
      value: estadisticas.totalProductos,
      subtitle: `${estadisticas.porcentajeActivosStock}% con stock`,
      icon: Package,
      color: 'blue',
      bgColor: 'bg-blue-100',
      textColor: 'text-blue-600'
    },
    {
      label: 'Inversión Total',
      value: `$${estadisticas.valorTotal.toLocaleString()}`,
      subtitle: `Promedio: $${estadisticas.valorPromedioPorProducto?.toLocaleString() || 0}`,
      icon: DollarSign,
      color: 'green',
      bgColor: 'bg-green-100',
      textColor: 'text-green-600'
    },
    {
      label: 'Salud Inventario',
      value: `${estadisticas.saludInventario || 0}%`,
      subtitle: `${estadisticas.productosActivosStock || 0} productos activos`,
      icon: Gauge,
      color: 'salud',
      bgColor: saludColor.bg,
      textColor: saludColor.text,
      emoji: saludColor.emoji
    },
    {
      label: 'Stock Crítico',
      value: estadisticas.stockCritico || 0,
      subtitle: `⚠️ Requieren atención`,
      icon: AlertTriangle,
      color: 'orange',
      bgColor: 'bg-orange-100',
      textColor: 'text-orange-600'
    },
    {
      label: 'Sin Stock',
      value: estadisticas.sinStock,
      subtitle: `❌ Sin disponibilidad`,
      icon: AlertCircle,
      color: 'red',
      bgColor: 'bg-red-100',
      textColor: 'text-red-600'
    },
    {
      label: 'Productos Rentables',
      value: `${estadisticas.porcentajeRentables || 0}%`,
      subtitle: `${estadisticas.productosRentables || 0} con +20% margen`,
      icon: Trophy,
      color: 'emerald',
      bgColor: 'bg-emerald-100',
      textColor: 'text-emerald-600'
    },
    {
      label: 'Top Categoría',
      value: estadisticas.topCategoria || 'N/A',
      subtitle: `${estadisticas.categorias} categorías total`,
      icon: Target,
      color: 'purple',
      bgColor: 'bg-purple-100',
      textColor: 'text-purple-600'
    },
    {
      label: 'Margen Promedio',
      value: `${estadisticas.margenPromedio}%`,
      subtitle: `Rentabilidad general`,
      icon: TrendingUp,
      color: 'indigo',
      bgColor: 'bg-indigo-100',
      textColor: 'text-indigo-600'
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-6">
      {stats.map((stat, index) => {
        const IconComponent = stat.icon;
        return (
          <div key={index} className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <p className="text-sm font-medium text-gray-600">{stat.label}</p>
                  {stat.emoji && <span>{stat.emoji}</span>}
                </div>
                <p className={`text-2xl font-bold ${stat.textColor} mb-1`}>{stat.value}</p>
                {stat.subtitle && (
                  <p className="text-xs text-gray-500">{stat.subtitle}</p>
                )}
              </div>
              <div className={`p-3 ${stat.bgColor} rounded-lg flex-shrink-0`}>
                <IconComponent className={`w-6 h-6 ${stat.textColor}`} />
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default React.memo(ProductStats);
