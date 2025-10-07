// src/components/productos/components/ProductAlerts.jsx
// Panel de alertas inteligentes para gestión proactiva de productos
// Identifica problemas y oportunidades automáticamente
// RELEVANT FILES: ProductStats.jsx, useProductStats.js

import React from 'react';
import { AlertTriangle, AlertCircle, TrendingDown, Zap, CheckCircle, Info } from 'lucide-react';
import { showSuccess, showError, showWarning, showInfo } from '../../../utils/toastNotifications';

const ProductAlerts = ({ estadisticas, productos }) => {
  const generateAlerts = () => {
    const alerts = [];

    // 🚨 ALERTAS CRÍTICAS
    if (estadisticas.sinStock > 0) {
      alerts.push({
        type: 'error',
        icon: AlertCircle,
        title: `${estadisticas.sinStock} productos sin stock`,
        message: 'Productos que no se pueden vender. Revisar reposición urgente.',
        action: 'Ver productos',
        priority: 'high'
      });
    }

    if (estadisticas.stockCritico > 0) {
      alerts.push({
        type: 'warning',
        icon: AlertTriangle,
        title: `${estadisticas.stockCritico} productos con stock crítico`,
        message: 'Stock muy bajo (≤3 unidades). Planificar reposición.',
        action: 'Revisar stock',
        priority: 'medium'
      });
    }

    // 💰 OPORTUNIDADES DE MEJORA
    const productosNoRentables = productos?.filter(p => {
      const margen = p.precio_costo > 0 ? ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100 : 0;
      return margen < 10 && margen > 0;
    }).length || 0;

    if (productosNoRentables > 0) {
      alerts.push({
        type: 'info',
        icon: TrendingDown,
        title: `${productosNoRentables} productos con bajo margen`,
        message: 'Productos con menos del 10% de ganancia. Revisar precios.',
        action: 'Optimizar precios',
        priority: 'low'
      });
    }

    // ✅ MÉTRICAS POSITIVAS
    if (estadisticas.saludInventario >= 80) {
      alerts.push({
        type: 'success',
        icon: CheckCircle,
        title: 'Inventario en excelente estado',
        message: `${estadisticas.saludInventario}% de salud. Gestión óptima del stock.`,
        action: null,
        priority: 'info'
      });
    }

    if (estadisticas.porcentajeRentables >= 70) {
      alerts.push({
        type: 'success',
        icon: Zap,
        title: 'Alta rentabilidad del catálogo',
        message: `${estadisticas.porcentajeRentables}% de productos rentables (+20% margen).`,
        action: null,
        priority: 'info'
      });
    }

    // 📊 INFORMACIÓN ÚTIL
    if (estadisticas.totalProductos > 500) {
      alerts.push({
        type: 'info',
        icon: Info,
        title: 'Catálogo extenso detectado',
        message: `${estadisticas.totalProductos} productos. Considerar análisis ABC para optimización.`,
        action: 'Ver análisis',
        priority: 'low'
      });
    }

    return alerts.sort((a, b) => {
      const priority = { high: 3, medium: 2, low: 1, info: 0 };
      return priority[b.priority] - priority[a.priority];
    });
  };

  const alerts = generateAlerts();

  if (alerts.length === 0) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
        <div className="flex items-center gap-3">
          <CheckCircle className="w-5 h-5 text-green-600" />
          <div>
            <h3 className="font-medium text-green-800">¡Todo está en orden!</h3>
            <p className="text-sm text-green-600">No hay alertas pendientes en tu inventario.</p>
          </div>
        </div>
      </div>
    );
  }

  const getAlertStyles = (type) => {
    const styles = {
      error: {
        container: 'bg-red-50 border-red-200',
        icon: 'text-red-600',
        title: 'text-red-800',
        message: 'text-red-600',
        button: 'bg-red-600 hover:bg-red-700 text-white'
      },
      warning: {
        container: 'bg-yellow-50 border-yellow-200',
        icon: 'text-yellow-600',
        title: 'text-yellow-800',
        message: 'text-yellow-600',
        button: 'bg-yellow-600 hover:bg-yellow-700 text-white'
      },
      info: {
        container: 'bg-blue-50 border-blue-200',
        icon: 'text-blue-600',
        title: 'text-blue-800',
        message: 'text-blue-600',
        button: 'bg-blue-600 hover:bg-blue-700 text-white'
      },
      success: {
        container: 'bg-green-50 border-green-200',
        icon: 'text-green-600',
        title: 'text-green-800',
        message: 'text-green-600',
        button: 'bg-green-600 hover:bg-green-700 text-white'
      }
    };
    return styles[type] || styles.info;
  };

  return (
    <div className="space-y-3 mb-6">
      <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <Zap className="w-5 h-5 text-blue-600" />
        Alertas Inteligentes
      </h3>
      
      <div className="grid gap-3">
        {alerts.slice(0, 4).map((alert, index) => {
          const styles = getAlertStyles(alert.type);
          const IconComponent = alert.icon;
          
          return (
            <div key={index} className={`border rounded-lg p-4 ${styles.container}`}>
              <div className="flex items-start gap-3">
                <IconComponent className={`w-5 h-5 mt-0.5 ${styles.icon}`} />
                <div className="flex-1">
                  <h4 className={`font-medium ${styles.title}`}>{alert.title}</h4>
                  <p className={`text-sm mt-1 ${styles.message}`}>{alert.message}</p>
                </div>
                {alert.action && (
                  <button 
                    onClick={() => {
                      // Mostrar notificación según el tipo de alerta
                      switch(alert.type) {
                        case 'error':
                          showError(`🚨 ${alert.title}: ${alert.message}`);
                          break;
                        case 'warning':
                          showWarning(`⚠️ ${alert.title}: ${alert.message}`);
                          break;
                        case 'success':
                          showSuccess(`✅ ${alert.title}: ${alert.message}`);
                          break;
                        default:
                          showInfo(`ℹ️ ${alert.title}: ${alert.message}`);
                      }
                    }}
                    className={`px-3 py-1.5 text-xs font-medium rounded-md transition-colors ${styles.button}`}
                  >
                    {alert.action}
                  </button>
                )}
              </div>
            </div>
          );
        })}
        
        {alerts.length > 4 && (
          <div className="text-center">
            <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">
              Ver {alerts.length - 4} alertas más
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default React.memo(ProductAlerts);
