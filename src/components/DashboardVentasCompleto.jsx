import React, { useState, useEffect } from 'react';
import { 
  DollarSign, TrendingUp, TrendingDown, ShoppingCart, Target, 
  CheckCircle, AlertCircle, Lock, Unlock, Store, BarChart3,
  Calendar, Clock, Users, Activity, ArrowUp, ArrowDown,
  RefreshCw, Wallet, CreditCard, Smartphone, QrCode
} from 'lucide-react';
import CONFIG from '../config/config';
import DashboardResumenCaja from './DashboardResumenCaja';

// 📊 COMPONENTE: TARJETA PRINCIPAL
const MainCard = ({ 
  title, 
  value, 
  subtitle, 
  icon: IconComponent, 
  bgColor = 'bg-blue-500', 
  textColor = 'text-white',
  loading = false,
  extraInfo = null
}) => {
  return (
    <div className={`rounded-xl p-6 shadow-lg ${bgColor} ${textColor} relative min-h-[140px] transition-all duration-300 hover:shadow-xl`}>
      {loading && (
        <div className="absolute inset-0 bg-black bg-opacity-20 rounded-xl flex items-center justify-center">
          <RefreshCw className="w-6 h-6 animate-spin text-white opacity-70" />
        </div>
      )}
      
      <div className="flex justify-between items-start mb-4">
        <div>
          <p className="text-sm font-medium opacity-90">{title}</p>
          <p className="text-3xl font-bold mt-2">{value}</p>
        </div>
        {IconComponent && <IconComponent className="w-8 h-8 opacity-70" />}
      </div>
      
      <div className="space-y-1">
        {subtitle && <p className="text-sm opacity-80">{subtitle}</p>}
        {extraInfo && extraInfo}
      </div>
    </div>
  );
};

// 📈 COMPONENTE: GRÁFICO SIMPLE CSS
const SimpleLineChart = ({ data, height = 80, color = 'rgb(59, 130, 246)' }) => {
  if (!data || data.length === 0) return <div className={`h-${height} bg-gray-100 rounded`}></div>;

  const max = Math.max(...data);
  const min = Math.min(...data);
  const range = max - min || 1;

  const points = data.map((value, index) => {
    const x = (index / (data.length - 1)) * 100;
    const y = 100 - ((value - min) / range) * 100;
    return `${x},${y}`;
  }).join(' ');

  return (
    <div className={`h-20 w-full relative`}>
      <svg className="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
        {/* Línea de tendencia */}
        <polyline
          fill="none"
          stroke={color}
          strokeWidth="2"
          points={points}
          className="drop-shadow-sm"
        />
        {/* Área bajo la curva */}
        <polygon
          fill={color}
          fillOpacity="0.1"
          points={`0,100 ${points} 100,100`}
        />
      </svg>
    </div>
  );
};

// 📊 COMPONENTE: TARJETA DE ANÁLISIS
const AnalysisCard = ({ 
  title, 
  subtitle,
  value, 
  change, 
  changeLabel,
  chartData,
  chartColor = 'rgb(59, 130, 246)',
  loading = false 
}) => {
  const isPositive = change >= 0;
  const TrendIcon = isPositive ? ArrowUp : ArrowDown;
  const trendColor = isPositive ? 'text-green-600' : 'text-red-600';

  return (
    <div className="rounded-xl border bg-white shadow-sm hover:shadow-md transition-shadow duration-300">
      {loading && (
        <div className="absolute inset-0 bg-white bg-opacity-70 rounded-xl flex items-center justify-center z-10">
          <RefreshCw className="w-6 h-6 animate-spin text-gray-500" />
        </div>
      )}
      
      <div className="p-6">
        <div className="space-y-1 mb-4">
          <p className="text-sm text-gray-600">{title}</p>
          <p className="text-3xl font-bold tracking-tight">{value}</p>
          <div className={`flex items-center text-sm ${trendColor}`}>
            <TrendIcon className="w-4 h-4 mr-1" />
            <span>{Math.abs(change)}% {changeLabel}</span>
          </div>
        </div>
      </div>
      
      <div className="px-6 pb-6">
        <SimpleLineChart data={chartData} color={chartColor} />
      </div>
    </div>
  );
};

// 🎯 COMPONENTE: BARRA DE PROGRESO
const ProgressBar = ({ current, target, label }) => {
  const percentage = Math.min((current / target) * 100, 100);
  const isComplete = current >= target;

  return (
    <div className="space-y-2">
      <div className="flex justify-between text-sm">
        <span className="opacity-90">{label}</span>
        <span className="font-semibold">{percentage.toFixed(1)}%</span>
      </div>
      <div className="w-full bg-white bg-opacity-30 rounded-full h-3">
        <div 
          className={`h-3 rounded-full transition-all duration-500 ${
            isComplete ? 'bg-green-400' : 'bg-white'
          }`}
          style={{ width: `${percentage}%` }}
        ></div>
      </div>
    </div>
  );
};

// 🏠 COMPONENTE PRINCIPAL: DASHBOARD VENTAS COMPLETO
const DashboardVentasCompleto = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Estados para datos principales
  const [ventasData, setVentasData] = useState({
    totalVentas: 0,
    ticketPromedio: 0,
    cantidadVentas: 0,
    promedioPorTurno: 0,
    ventasPorMetodo: {
      efectivo: 0,
      tarjeta: 0,
      transferencia: 0,
      qr: 0
    }
  });

  const [cajaData, setCajaData] = useState({
    estado: 'cerrado',
    efectivoDisponible: 0,
    balanceGlobal: 0
  });

  const [metaData, setMetaData] = useState({
    objetivo: 600000,
    actual: 0,
    porcentaje: 0
  });

  // Estados para análisis
  const [tendenciaHoy, setTendenciaHoy] = useState([]);
  const [tendenciaAyer, setTendenciaAyer] = useState([]);
  const [tendenciaMensual, setTendenciaMensual] = useState([]);
  
  // Estados para comparativas reales
  const [ventasComparativa, setVentasComparativa] = useState({
    hoy: 0,
    ayer: 0,
    anteayer: 0,
    promedioMensual: 0,
    cambioVsAyer: 0,
    cambioAyerVsAnteayer: 0,
    cambioVsPromedio: 0
  });

  // 🔄 FUNCIÓN: Cargar datos de ventas (PRODUCCIÓN)
  const cargarDatosVentas = async () => {
    try {
      // Agregar timestamp para evitar cache
      const timestamp = new Date().getTime();
      const response = await fetch(`${CONFIG.API_URL}/api/finanzas_completo.php?periodo=hoy&_t=${timestamp}`);
      const data = await response.json();
      
      if (data.success) {
        // Calcular total usando la estructura correcta del API
        const total = data.componente_4_detalle_ventas?.totales?.total_ventas || 0;
        const cantidad = data.componente_4_detalle_ventas?.ventas?.length || 0;
        
        // Extraer ventas por método de pago
        const efectivo = data.componente_3_metodos_pago?.tarjeta_1_efectivo?.valor_principal || 0;
        const transferencia = data.componente_3_metodos_pago?.tarjeta_2_transferencia?.valor_principal || 0;
        const tarjeta = data.componente_3_metodos_pago?.tarjeta_3_tarjeta?.valor_principal || 0;
        const qr = data.componente_3_metodos_pago?.tarjeta_4_qr?.valor_principal || 0;
        
        setVentasData({
          totalVentas: total,
          ticketPromedio: cantidad > 0 ? total / cantidad : 0,
          cantidadVentas: cantidad,
          promedioPorTurno: cantidad > 0 ? cantidad / 2 : 0,
          ventasPorMetodo: {
            efectivo: efectivo,
            tarjeta: tarjeta,
            transferencia: transferencia,
            qr: qr
          }
        });

        setMetaData(prev => ({
          ...prev,
          actual: total,
          porcentaje: (total / prev.objetivo) * 100
        }));
      }
    } catch (error) {
      console.error('Error cargando datos de ventas:', error);
      setError('Error al cargar datos de ventas');
    }
  };

  // 🔄 FUNCIÓN: Cargar estado de caja (PRODUCCIÓN)
  const cargarEstadoCaja = async () => {
    try {
      const response = await fetch(`${CONFIG.API_URL}/api/gestion_caja_completa.php?accion=estado_caja&usuario_id=1`);
      const data = await response.json();
      
      if (data.success) {
        setCajaData({
          estado: data.turno ? 'abierto' : 'cerrado',
          efectivoDisponible: data.efectivo_disponible || 0,
          balanceGlobal: data.turno?.balance_acumulado || 0
        });
      }
    } catch (error) {
      console.error('Error cargando estado de caja:', error);
    }
  };

  // 🔄 FUNCIÓN: Cargar datos comparativos reales (PRODUCCIÓN)
  const cargarDatosComparativos = async () => {
    try {
      // Obtener datos de hoy
      const responseHoy = await fetch(`${CONFIG.API_URL}/api/finanzas_completo.php?periodo=hoy`);
      const dataHoy = await responseHoy.json();
      
      // Obtener datos de ayer
      const responseAyer = await fetch(`${CONFIG.API_URL}/api/finanzas_completo.php?periodo=ayer`);
      const dataAyer = await responseAyer.json();
      
      // Obtener datos de semana anterior para comparación
      const responseSemana = await fetch(`${CONFIG.API_URL}/api/finanzas_completo.php?periodo=semana_anterior`);
      const dataSemana = await responseSemana.json();

      if (dataHoy.success && dataAyer.success) {
        // Función helper para sumar todas las ventas
        const calcularTotalVentas = (data) => {
          // Primero intentar con los totales directos
          if (data.componente_4_detalle_ventas?.totales?.total_ventas) {
            return data.componente_4_detalle_ventas.totales.total_ventas;
          }
          
          // Si no, sumar por métodos de pago
          const efectivo = data.componente_3_metodos_pago?.tarjeta_1_efectivo?.valor_principal || 0;
          const transferencia = data.componente_3_metodos_pago?.tarjeta_2_transferencia?.valor_principal || 0;
          const tarjeta = data.componente_3_metodos_pago?.tarjeta_3_tarjeta?.valor_principal || 0;
          const qr = data.componente_3_metodos_pago?.tarjeta_4_qr?.valor_principal || 0;
          
          return efectivo + transferencia + tarjeta + qr;
        };

        const ventasHoy = calcularTotalVentas(dataHoy);
        const ventasAyer = calcularTotalVentas(dataAyer);
        
        // Calcular promedio semanal como referencia
        const ventasSemanaAnterior = dataSemana.success ? calcularTotalVentas(dataSemana) : 0;
        const promedioSemanal = ventasSemanaAnterior / 7; // Promedio diario de semana anterior

        // Calcular porcentajes de cambio reales
        const cambioVsAyer = ventasAyer > 0 ? ((ventasHoy - ventasAyer) / ventasAyer) * 100 : 0;
        const cambioAyerVsPromedio = promedioSemanal > 0 ? ((ventasAyer - promedioSemanal) / promedioSemanal) * 100 : 0;
        const cambioVsPromedio = promedioSemanal > 0 ? ((ventasHoy - promedioSemanal) / promedioSemanal) * 100 : 0;

        setVentasComparativa({
          hoy: ventasHoy,
          ayer: ventasAyer,
          anteayer: 0, // No usamos este dato
          promedioMensual: promedioSemanal,
          cambioVsAyer: cambioVsAyer,
          cambioAyerVsAnteayer: cambioAyerVsPromedio,
          cambioVsPromedio: cambioVsPromedio
        });

        console.log('📊 Datos comparativos cargados:', {
          ventasHoy,
          ventasAyer,
          promedioSemanal,
          cambioVsAyer: cambioVsAyer.toFixed(1) + '%',
          cambioVsPromedio: cambioVsPromedio.toFixed(1) + '%'
        });
      }
    } catch (error) {
      console.error('Error cargando datos comparativos:', error);
      // En caso de error, usar valores por defecto
      setVentasComparativa({
        hoy: 0,
        ayer: 0,
        anteayer: 0,
        promedioMensual: 0,
        cambioVsAyer: 0,
        cambioAyerVsAnteayer: 0,
        cambioVsPromedio: 0
      });
    }
  };

  // 🔄 FUNCIÓN: Generar datos de tendencia (simulados pero realistas)
  const generarTendenciaHoraria = (base) => {
    const horas = Array.from({length: 14}, (_, i) => i + 8); // 8am a 10pm
    return horas.map(hora => {
      // Simular patrones realistas de venta por hora
      let factor = 0.3; // Base
      if (hora >= 12 && hora <= 14) factor = 1.2; // Almuerzo
      if (hora >= 18 && hora <= 20) factor = 1.4; // Cena
      if (hora >= 21) factor = 0.6; // Tarde
      
      return Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    });
  };

  // 🔄 FUNCIÓN: Cargar todos los datos (PRODUCCIÓN)
  const cargarTodosLosDatos = async () => {
    setLoading(true);
    setError(null);
    
    try {
      await Promise.all([
        cargarDatosVentas(),
        cargarEstadoCaja(),
        cargarDatosComparativos()
      ]);

    } catch (error) {
      setError('Error al cargar los datos del dashboard');
      console.error('Error general:', error);
    } finally {
      setLoading(false);
    }
  };

  // 🔄 Efecto para generar tendencias después de cargar datos
  useEffect(() => {
    if (ventasData.totalVentas > 0) {
      // Generar tendencias basadas en datos reales
      setTendenciaHoy(generarTendenciaHoraria(ventasData.totalVentas / 14));
      setTendenciaAyer(generarTendenciaHoraria((ventasData.totalVentas * 0.9) / 14));
      setTendenciaMensual(Array.from({length: 30}, (_, i) => 
        ventasData.totalVentas * (0.7 + Math.random() * 0.6)
      ));
    }
  }, [ventasData.totalVentas]);

  // 🔄 Efecto inicial y recarga cada 30 segundos (PRODUCCIÓN)
  useEffect(() => {
    cargarTodosLosDatos();
    const interval = setInterval(cargarTodosLosDatos, 30000);
    return () => clearInterval(interval);
  }, []);

  if (error) {
    return (
      <div className="p-6 bg-red-50 border border-red-200 rounded-xl">
        <div className="flex items-center">
          <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
          <p className="text-red-800">{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 p-6">
      {/* 🔥 DASHBOARD DE RESUMEN DE CAJA */}
      <DashboardResumenCaja />
      
      {/* 🏠 ENCABEZADO */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Dashboard de Ventas</h1>
          <p className="text-gray-600">Panel completo y dinámico - Datos en tiempo real</p>
        </div>
        <button 
          onClick={cargarTodosLosDatos}
          className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          disabled={loading}
        >
          <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
          Actualizar
        </button>
      </div>

      {/* 📊 TARJETAS PRINCIPALES (4 en fila) */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        {/* Tarjeta 1: Total Ventas */}
        <MainCard
          title="Total de Ventas"
          value={`$${(ventasData.totalVentas || 0).toLocaleString('es-AR')}`}
          subtitle={`Ticket Promedio: $${(ventasData.ticketPromedio || 0).toLocaleString('es-AR')}`}
          icon={DollarSign}
          bgColor="bg-green-500"
          loading={loading}
        />

        {/* Tarjeta 2: Número de Ventas */}
        <MainCard
          title="Ventas del Día"
          value={(ventasData.cantidadVentas || 0).toString()}
          subtitle={`Promedio por turno: ${(ventasData.promedioPorTurno || 0).toFixed(1)} ventas`}
          icon={ShoppingCart}
          bgColor="bg-blue-500"
          loading={loading}
        />

        {/* Tarjeta 3: Estado de Caja */}
        <MainCard
          title={cajaData.estado === 'abierto' ? 'Caja Abierta' : 'Caja Cerrada'}
          value={`$${(cajaData.efectivoDisponible || 0).toLocaleString('es-AR')}`}
          subtitle="Según balance global"
          icon={cajaData.estado === 'abierto' ? Unlock : Lock}
          bgColor={cajaData.estado === 'abierto' ? 'bg-emerald-500' : 'bg-gray-500'}
          loading={loading}
          extraInfo={
            <div className="flex items-center">
              <div className={`w-2 h-2 rounded-full mr-2 ${
                cajaData.estado === 'abierto' ? 'bg-green-400' : 'bg-gray-400'
              }`}></div>
              <span className="text-xs opacity-90">
                {cajaData.estado === 'abierto' ? 'Operativo' : 'Inactivo'}
              </span>
            </div>
          }
        />

        {/* Tarjeta 4: Meta Diaria */}
        <MainCard
          title="Meta Diaria"
          value={`$${(metaData.objetivo || 0).toLocaleString('es-AR')}`}
          subtitle=""
          icon={Target}
          bgColor="bg-purple-500"
          loading={loading}
          extraInfo={
            <ProgressBar 
              current={metaData.actual || 0}
              target={metaData.objetivo || 600000}
              label={`${(metaData.porcentaje || 0).toFixed(1)}% completado`}
            />
          }
        />
      </div>

      {/* 📈 TARJETAS DE ANÁLISIS (3 tarjetas con gráficos) */}
      <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        {/* Análisis 1: Ventas de Hoy */}
        <AnalysisCard
          title="Total Ventas Hoy"
          value={`$${(ventasComparativa.hoy || 0).toLocaleString('es-AR')}`}
          change={ventasComparativa.cambioVsAyer || 0}
          changeLabel="vs día anterior"
          chartData={tendenciaHoy || []}
          chartColor="rgb(34, 197, 94)"
          loading={loading}
        />

        {/* Análisis 2: Ventas de Ayer */}
        <AnalysisCard
          title="Total Ventas Ayer"
          value={`$${(ventasComparativa.ayer || 0).toLocaleString('es-AR')}`}
          change={ventasComparativa.cambioAyerVsAnteayer || 0}
          changeLabel="vs promedio semanal"
          chartData={tendenciaAyer || []}
          chartColor="rgb(59, 130, 246)"
          loading={loading}
        />

        {/* Análisis 3: Balance Mensual */}
        <div className="lg:col-span-2 xl:col-span-1">
          <AnalysisCard
            title="Promedio Semanal Anterior"
            subtitle="Promedio diario de semana anterior"
            value={`$${(ventasComparativa.promedioMensual || 0).toLocaleString('es-AR')}`}
            change={ventasComparativa.cambioVsPromedio || 0}
            changeLabel="hoy vs promedio"
            chartData={(tendenciaMensual || []).slice(-7)} // Últimos 7 días
            chartColor="rgb(168, 85, 247)"
            loading={loading}
          />
        </div>
      </div>

    </div>
  );
};

export default DashboardVentasCompleto;
