import React, { useState, useEffect } from 'react';
import { 
  DollarSign, TrendingUp, TrendingDown, Package, AlertTriangle, 
  CheckCircle, CreditCard, Banknote, QrCode, Smartphone, 
  ShoppingCart, RefreshCw, BarChart3, ArrowUp, ArrowDown,
  Store, Wallet, TrendingUpDown, Activity, AlertCircle, Info
} from 'lucide-react';
import CONFIG from '../config/config';

// Componente de tarjeta estadística mejorada
const StatCard = ({ 
  icon: IconComponent, 
  title, 
  value, 
  subValue, 
  subLabel, 
  trend,
  trendValue,
  bgColor = 'bg-blue-500', 
  textColor = 'text-white',
  loading = false
}) => {
  const TrendIcon = trend === 'up' ? TrendingUp : trend === 'down' ? TrendingDown : TrendingUpDown;
  const trendColor = trend === 'up' ? 'text-green-400' : trend === 'down' ? 'text-red-400' : 'text-gray-400';

  return (
    <div className={`rounded-lg p-3 sm:p-4 shadow-md ${bgColor} ${textColor} flex flex-col justify-between min-h-[110px] sm:min-h-[130px] relative`}>
      {loading && (
        <div className="absolute inset-0 bg-black bg-opacity-20 rounded-lg flex items-center justify-center">
          <RefreshCw className="w-6 h-6 animate-spin text-white opacity-70" />
        </div>
      )}
      
      <div className="flex justify-between items-start">
        <div className="flex-1">
          <p className="text-xs sm:text-sm font-medium opacity-90">{title}</p>
          <p className="text-xl sm:text-2xl font-bold mt-1">{value}</p>
          {trend && trendValue !== undefined && (
            <div className={`flex items-center mt-1 ${trendColor}`}>
              <TrendIcon className="w-4 h-4 mr-1" />
              <span className="text-sm font-medium">{Math.abs(trendValue)}%</span>
            </div>
          )}
        </div>
        {IconComponent && <IconComponent className="w-6 h-6 sm:w-8 sm:h-8 opacity-70" />}
      </div>
      
      {subLabel && subValue !== undefined && (
        <p className="text-xs mt-2 opacity-80">{subValue} {subLabel}</p>
      )}
    </div>
  );
};

// Componente para métodos de pago
const PaymentMethodCard = ({ method, amount, count, percentage, icon: IconComponent, isLoading }) => (
  <div className="bg-white rounded-lg p-4 shadow-sm border">
    <div className="flex items-center justify-between mb-2">
      <div className="flex items-center">
        <div className="p-2 bg-blue-50 rounded-lg mr-3">
          <IconComponent className="w-5 h-5 text-blue-600" />
        </div>
        <div>
          <p className="font-medium text-gray-800">{method}</p>
          <p className="text-sm text-gray-500">{count} ventas</p>
        </div>
      </div>
      <div className="text-right">
        <p className="font-bold text-lg text-gray-800">{CONFIG.formatCurrency(amount)}</p>
        <p className="text-sm text-gray-500">{percentage}%</p>
      </div>
    </div>
  </div>
);

// Componente para productos más vendidos
const TopProductCard = ({ product, index, isLoading }) => (
  <div className="flex items-center p-3 bg-white rounded-lg shadow-sm border">
    <div className="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mr-3">
      <span className="text-sm font-bold text-blue-600">#{index + 1}</span>
    </div>
    <div className="flex-1">
      <h4 className="font-medium text-gray-800 text-sm">{product.producto_nombre}</h4>
      <p className="text-xs text-gray-500">{product.categoria}</p>
    </div>
    <div className="text-right">
      <p className="font-semibold text-gray-800">{product.cantidad_vendida}</p>
      <p className="text-xs text-gray-500">{CONFIG.formatCurrency(product.total_vendido)}</p>
    </div>
  </div>
);

// Componente para productos con stock bajo
const LowStockAlert = ({ product }) => (
  <div className="flex items-center p-2 bg-yellow-50 rounded-lg border border-yellow-200">
    <AlertTriangle className="w-4 h-4 text-yellow-600 mr-2" />
    <div className="flex-1">
      <p className="text-sm font-medium text-gray-800">{product.nombre}</p>
      <p className="text-xs text-gray-500">{product.codigo}</p>
    </div>
    <div className="text-right">
      <span className="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">
        {product.stock} unidades
      </span>
    </div>
  </div>
);

// Hook personalizado para obtener estadísticas del dashboard
const useDashboardStats = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lastUpdate, setLastUpdate] = useState(null);

  const fetchStats = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`${CONFIG.API_URL}${CONFIG.API_ENDPOINTS.DASHBOARD_STATS}`);
      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      if (data.success) {
        setStats(data);
        setLastUpdate(new Date());
      } else {
        throw new Error(data.message || 'Error al obtener estadísticas');
      }
    } catch (err) {
      console.error('Error al cargar estadísticas:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStats();
    
    // Actualizar cada 2 minutos
    const interval = setInterval(fetchStats, 2 * 60 * 1000);
    
    return () => clearInterval(interval);
  }, []);

  return { stats, loading, error, refetch: fetchStats, lastUpdate };
};

// Componente principal del dashboard optimizado
const DashboardOptimizado = () => {
  const { stats, loading, error, refetch, lastUpdate } = useDashboardStats();
  
  // Función para obtener el ícono del método de pago
  const getPaymentIcon = (metodo) => {
    switch (metodo.toLowerCase()) {
      case 'efectivo': return Banknote;
      case 'tarjeta': return CreditCard;
      case 'mercadopago': case 'mp': return QrCode;
      case 'transferencia': return Smartphone;
      default: return DollarSign;
    }
  };

  if (error) {
    return (
      <div className="p-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
          <AlertCircle className="w-5 h-5 text-red-600 mr-3" />
          <div className="flex-1">
            <h3 className="font-medium text-red-800">Error al cargar el dashboard</h3>
            <p className="text-sm text-red-600 mt-1">{error}</p>
          </div>
          <button
            onClick={refetch}
            className="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 flex items-center"
          >
            <RefreshCw className="w-4 h-4 mr-2" />
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  const ventasHoy = stats?.ventas_hoy || {};
  const estadoCaja = stats?.estado_caja || {};
  const metodosPago = stats?.metodos_pago || [];
  const productosVendidos = stats?.productos_mas_vendidos || [];
  const stockBajo = stats?.productos_stock_bajo || [];

  return (
    <div className="p-4 sm:p-6 space-y-4 sm:space-y-6">
      {/* Header con fecha y última actualización */}
      <div className="flex flex-col space-y-4 sm:flex-row sm:justify-between sm:items-start sm:space-y-0">
        <div className="flex-1">
          <h1 className="text-xl sm:text-2xl font-bold text-gray-800">Dashboard del Día</h1>
          <p className="text-sm sm:text-base text-gray-600">{new Date().toLocaleDateString('es-AR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
          })}</p>
          {/* Información explicativa */}
          <div className="mt-2 flex items-start text-xs sm:text-sm text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">
            <Info className="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" />
            <span className="leading-tight">Datos únicamente del día actual • Los reportes por defecto coinciden con esta información</span>
          </div>
        </div>
        <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
          {lastUpdate && (
            <p className="text-xs sm:text-sm text-gray-500">
              Actualizado: {lastUpdate.toLocaleTimeString('es-AR')}
            </p>
          )}
          <button
            onClick={refetch}
            disabled={loading}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 flex items-center justify-center disabled:opacity-50"
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
            Actualizar
          </button>
        </div>
      </div>

      {/* Tarjetas de estadísticas principales */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <StatCard
          icon={ShoppingCart}
          title="Ventas del Día"
          value={ventasHoy.cantidad?.toString() || '0'}
          subValue={CONFIG.formatCurrency(ventasHoy.total || 0)}
          subLabel=""
          trend={ventasHoy.cambio_cantidad_pct > 0 ? 'up' : ventasHoy.cambio_cantidad_pct < 0 ? 'down' : 'neutral'}
          trendValue={ventasHoy.cambio_cantidad_pct}
          bgColor="bg-blue-500"
          loading={loading}
        />
        
        <StatCard
          icon={DollarSign}
          title="Total Recaudado"
          value={CONFIG.formatCurrency(ventasHoy.total || 0)}
          subValue={CONFIG.formatCurrency(ventasHoy.promedio || 0)}
          subLabel="promedio por venta"
          trend={ventasHoy.cambio_total_pct > 0 ? 'up' : ventasHoy.cambio_total_pct < 0 ? 'down' : 'neutral'}
          trendValue={ventasHoy.cambio_total_pct}
          bgColor="bg-green-500"
          loading={loading}
        />
        
        <StatCard
          icon={estadoCaja.esta_abierta ? Wallet : Store}
          title="Estado de Caja"
          value={estadoCaja.esta_abierta ? "Abierta" : "Cerrada"}
          subValue={CONFIG.formatCurrency(estadoCaja.efectivo_actual || 0)}
          subLabel="efectivo actual"
          bgColor={estadoCaja.esta_abierta ? "bg-emerald-500" : "bg-gray-500"}
          loading={loading}
        />
        
        <StatCard
          icon={AlertTriangle}
          title="Stock Bajo"
          value={stockBajo.length?.toString() || '0'}
          subValue="productos"
          subLabel="requieren atención"
          bgColor={stockBajo.length > 0 ? "bg-orange-500" : "bg-gray-400"}
          loading={loading}
        />
      </div>

      {/* Grid de información detallada */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6">
        {/* Métodos de pago */}
        <div className="bg-white rounded-lg shadow-sm border p-4 sm:p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800 flex items-center">
              <CreditCard className="w-5 h-5 mr-2" />
              Métodos de Pago Hoy
            </h3>
            <span className="text-sm text-gray-500">{metodosPago.length} métodos</span>
          </div>
          
          <div className="space-y-3">
            {metodosPago.map((metodo, index) => {
              const total = metodosPago.reduce((sum, m) => sum + parseFloat(m.monto_total), 0);
              const percentage = total > 0 ? ((parseFloat(metodo.monto_total) / total) * 100).toFixed(1) : 0;
              
              return (
                <PaymentMethodCard
                  key={index}
                  method={metodo.metodo_pago}
                  amount={parseFloat(metodo.monto_total)}
                  count={metodo.cantidad}
                  percentage={percentage}
                  icon={getPaymentIcon(metodo.metodo_pago)}
                  isLoading={loading}
                />
              );
            })}
            
            {metodosPago.length === 0 && !loading && (
              <div className="text-center py-4 text-gray-500">
                <Activity className="w-8 h-8 mx-auto mb-2 opacity-50" />
                <p>No hay ventas registradas hoy</p>
              </div>
            )}
          </div>
        </div>

        {/* Productos más vendidos */}
        <div className="bg-white rounded-lg shadow-sm border p-4 sm:p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800 flex items-center">
              <BarChart3 className="w-5 h-5 mr-2" />
              Productos Más Vendidos
            </h3>
            <span className="text-sm text-gray-500">Top {productosVendidos.length}</span>
          </div>
          
          <div className="space-y-3 max-h-64 overflow-y-auto">
            {productosVendidos.map((producto, index) => (
              <TopProductCard
                key={index}
                product={producto}
                index={index}
                isLoading={loading}
              />
            ))}
            
            {productosVendidos.length === 0 && !loading && (
              <div className="text-center py-4 text-gray-500">
                <Package className="w-8 h-8 mx-auto mb-2 opacity-50" />
                <p>No hay productos vendidos hoy</p>
              </div>
            )}
          </div>
        </div>

        {/* Estado de caja detallado */}
        <div className="bg-white rounded-lg shadow-sm border p-4 sm:p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800 flex items-center">
              <Wallet className="w-5 h-5 mr-2" />
              Movimientos de Caja
            </h3>
            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
              estadoCaja.esta_abierta 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
            }`}>
              {estadoCaja.esta_abierta ? 'Abierta' : 'Cerrada'}
            </span>
          </div>
          
          <div className="space-y-4">
            <div className="flex justify-between items-center py-2 border-b">
              <span className="text-gray-600">Apertura</span>
              <span className="font-medium">{CONFIG.formatCurrency(estadoCaja.monto_apertura || 0)}</span>
            </div>
            
            <div className="flex justify-between items-center py-2 border-b">
              <span className="text-gray-600 flex items-center">
                <ArrowUp className="w-4 h-4 mr-1 text-green-500" />
                Ingresos
              </span>
              <span className="font-medium text-green-600">
                +{CONFIG.formatCurrency(estadoCaja.total_ingresos || 0)}
              </span>
            </div>
            
            <div className="flex justify-between items-center py-2 border-b">
              <span className="text-gray-600 flex items-center">
                <ArrowDown className="w-4 h-4 mr-1 text-red-500" />
                Egresos
              </span>
              <span className="font-medium text-red-600">
                -{CONFIG.formatCurrency(estadoCaja.total_egresos || 0)}
              </span>
            </div>
            
            <div className="flex justify-between items-center py-2 bg-blue-50 px-3 rounded-lg">
              <span className="font-semibold text-blue-800">Efectivo Actual</span>
              <span className="font-bold text-blue-800">
                {CONFIG.formatCurrency(estadoCaja.efectivo_actual || 0)}
              </span>
            </div>
          </div>
        </div>

        {/* Alertas de stock bajo */}
        <div className="bg-white rounded-lg shadow-sm border p-4 sm:p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800 flex items-center">
              <AlertTriangle className="w-5 h-5 mr-2" />
              Stock Bajo
            </h3>
            <span className="text-sm text-gray-500">{stockBajo.length} productos</span>
          </div>
          
          <div className="space-y-2 max-h-64 overflow-y-auto">
            {stockBajo.map((producto, index) => (
              <LowStockAlert key={index} product={producto} />
            ))}
            
            {stockBajo.length === 0 && !loading && (
              <div className="text-center py-4 text-gray-500">
                <CheckCircle className="w-8 h-8 mx-auto mb-2 opacity-50" />
                <p>Todos los productos tienen stock suficiente</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardOptimizado; 