import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { 
  Search, Edit, RefreshCw, AlertTriangle, TrendingUp,
  Package, DollarSign, Clock, BarChart3, PieChart, ShoppingCart,
  Bell, Brain, Lightbulb, ArrowUp, ArrowDown, Eye, Download,
  AlertCircle, CheckCircle, XCircle, Store
} from 'lucide-react';
import CONFIG from '../config/config';
import { useAuth } from '../contexts/AuthContext';
import InventarioIAService from '../services/inventarioIAService';
import PedidosIAService from '../services/pedidosIAService';
import GestionProveedores from './GestionProveedores';
import PedidosInteligentes from './PedidosInteligentes';

const InventarioInteligente = () => {
  const { currentUser } = useAuth();
  
  // Estados principales
  const [productos, setProductos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Estados de filtros y búsqueda
  const [searchTerm, setSearchTerm] = useState('');
  const [categoriaFilter, setCategoriaFilter] = useState('todas');
  const [stockFilter, setStockFilter] = useState('todos');
  const [urgenciaFilter, setUrgenciaFilter] = useState('todos');
  
  // Estados de vista
  const [vistaActual, setVistaActual] = useState('dashboard'); // dashboard, productos, alertas, proveedores, pedidos, ia
  const [sortField, setSortField] = useState('urgencia');
  const [sortDirection, setSortDirection] = useState('desc');
  
  // Estados de paginación
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(25);
  
  // Estados de análisis
  const [alertasInteligentes, setAlertasInteligentes] = useState([]);
  
  // Estados de IA
  const [analisisIA, setAnalisisIA] = useState(null);
  const [loadingIA, setLoadingIA] = useState(false);
  const [inventarioIAService] = useState(new InventarioIAService());
  
  // Estados de IA para Pedidos
  const [analisisPedidosIA, setAnalisisPedidosIA] = useState(null);
  const [loadingPedidosIA, setLoadingPedidosIA] = useState(false);
  const [pedidosIAService] = useState(new PedidosIAService());
  
  // Estados de métricas
  const [metricas, setMetricas] = useState({
    valorTotal: 0,
    productosActivos: 0,
    stockBajo: 0,
    sinStock: 0,
    rotacionPromedio: 0,
    costoAlmacenamiento: 0,
    productos: {
      claseA: 0,
      claseB: 0,
      claseC: 0
    }
  });

  // Estados para modales
  const [modalDetalle, setModalDetalle] = useState(false);
  const [modalAjuste, setModalAjuste] = useState(false);
  const [modalPedido, setModalPedido] = useState(false);
  const [modalEditarImagen, setModalEditarImagen] = useState(false);
  const [productoSeleccionado, setProductoSeleccionado] = useState(null);
  const [cantidadAjuste, setCantidadAjuste] = useState(0);
  const [tipoAjuste, setTipoAjuste] = useState('entrada');
  const [motivoAjuste, setMotivoAjuste] = useState('');
  const [nuevaImagenUrl, setNuevaImagenUrl] = useState('');

  const cargarDatos = useCallback(async () => {
    setLoading(true);
    try {
      // ✅ OPTIMIZADO: Solo cargar productos (contiene toda la lógica necesaria)
      await cargarProductos();
      
      // ✅ OPTIMIZADO: Cargar análisis opcionales en paralelo sin bloquear
      Promise.all([
        cargarAnalisis().catch(e => console.warn('Análisis ABC opcional no disponible')),
        cargarPredicciones().catch(e => console.warn('Predicciones opcionales no disponibles')),
        cargarAlertas().catch(e => console.warn('Alertas opcionales no disponibles'))
      ]);
    } catch (error) {
      setError('Error al cargar datos del inventario');
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  const cargarProductos = async () => {
    try {
      // ✅ OPTIMIZADO: Mostrar progreso de carga
      setError(null);
      const timeStart = performance.now();
      
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=productos`, {
        timeout: 10000, // 10 segundos timeout
        headers: {
          'Cache-Control': 'no-cache',
          'Pragma': 'no-cache'
        }
      });
      
      const timeEnd = performance.now();
      console.log(`⚡ Inventario cargado en ${Math.round(timeEnd - timeStart)}ms`);
      
      if (response.data.success) {
        setProductos(response.data.productos);
        calcularMetricas(response.data.productos);
      } else {
        throw new Error('Response not successful');
      }
    } catch (error) {
      console.warn('⚠️ Fallback a API legacy:', error.message);
      try {
        // Fallback a API original si la nueva no existe aún
        const response = await axios.get(`${CONFIG.API_URL}/api/productos.php?admin=true`);
        const productosEnriquecidos = enriquecerProductos(response.data);
        setProductos(productosEnriquecidos);
        calcularMetricas(productosEnriquecidos);
      } catch (fallbackError) {
        setError('Error al cargar productos del inventario');
        throw fallbackError;
      }
    }
  };

  const cargarAnalisis = async () => {
    try {
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=analisis-abc`);
      if (response.data.success) {
        console.log('✅ Análisis ABC cargado:', response.data.analisis);
      }
    } catch (error) {
      console.warn('⚠️ Análisis ABC no disponible, usando cálculo local');
      // Fallback silencioso - no es crítico
    }
  };

  const cargarPredicciones = async () => {
    try {
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=predicciones`);
      if (response.data.success) {
        console.log('✅ Predicciones cargadas:', response.data.predicciones);
      }
    } catch (error) {
      console.warn('⚠️ Predicciones no disponibles, usando análisis básico');
      // Fallback silencioso - no es crítico
    }
  };

  const cargarAlertas = async () => {
    try {
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=alertas`);
      if (response.data.success) {
        setAlertasInteligentes(response.data.alertas);
      }
    } catch (error) {
      // Alertas inteligentes no disponibles
      generarAlertasBasicas();
    }
  };

  // 🧠 FUNCIÓN PARA ANÁLISIS IA
  const ejecutarAnalisisIA = async () => {
    setLoadingIA(true);
    try {
      // Obtener datos estructurados para IA
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=analisis-ia`);
      
      if (response.data.success) {
        const datosIA = response.data.datos_ia;
        
        // Ejecutar análisis con IA
        const analisis = await inventarioIAService.analizarInventarioCompleto(datosIA);
        
        setAnalisisIA(analisis);
        console.log('🧠 Análisis IA completado:', analisis);
      }
    } catch (error) {
      console.error('Error en análisis IA:', error);
      setAnalisisIA({
        diagnostico_general: {
          estado_inventario: 'ERROR',
          problema_principal: 'Error al conectar con IA',
          urgencia_accion: 'revisar_configuracion'
        },
        score_inventario: 0,
        fuente: 'Error'
      });
    } finally {
      setLoadingIA(false);
    }
  };

  // 🛒 FUNCIÓN PARA ANÁLISIS IA DE PEDIDOS
  const ejecutarAnalisisPedidosIA = async () => {
    setLoadingPedidosIA(true);
    try {
      // Obtener datos estructurados para IA de pedidos
      const response = await axios.get(`${CONFIG.API_URL}/api/inventario-inteligente.php?action=analisis-ia`);
      
      if (response.data.success) {
        const datosPedidos = response.data.datos_ia;
        
        // Ejecutar análisis de pedidos con IA
        const analisisPedidos = await pedidosIAService.optimizarPedidos(datosPedidos);
        
        setAnalisisPedidosIA(analisisPedidos);
        console.log('🛒 Análisis IA de Pedidos completado:', analisisPedidos);
      }
    } catch (error) {
      console.error('Error en análisis IA de pedidos:', error);
      setAnalisisPedidosIA({
        resumen_pedidos: {
          total_productos_pedir: 'Error al conectar con IA',
          urgencia_general: 'error'
        },
        fuente: 'Error'
      });
    } finally {
      setLoadingPedidosIA(false);
    }
  };

  // ✅ OPTIMIZADO: Ya no es necesario enriquecer en el frontend (se hace en backend)
  const enriquecerProductos = useMemo(() => {
    return (productosBase) => {
      // El backend ya envía productos enriquecidos, solo validar datos
      return productosBase.map(producto => ({
        ...producto,
        // Asegurar que los campos existen (compatibilidad con API legacy)
        urgencia: producto.urgencia ?? calcularUrgenciaFallback(producto),
        diasStock: producto.dias_stock ?? calcularDiasStockFallback(producto),
        claseABC: producto.clase_abc ?? 'C',
        rotacion: producto.rotacion_anual ?? 0,
        rentabilidad: producto.rentabilidad ?? 0,
        valorInventario: producto.valor_inventario ?? ((producto.stock || 0) * (producto.precio_costo || 0))
      }));
    };
  }, []);

  // ✅ FUNCIONES DE FALLBACK para compatibilidad con API legacy
  const calcularUrgenciaFallback = (producto) => {
    const stockActual = producto.stock || 0;
    const stockMinimo = producto.stock_minimo || 10;
    if (stockActual <= 0) return 100;
    const ratio = stockActual / stockMinimo;
    if (ratio <= 0.5) return 90;
    if (ratio <= 1.0) return 70;
    if (ratio <= 2.0) return 30;
    return 10;
  };

  const calcularDiasStockFallback = (producto) => {
    const ventas = producto.ventas_30_dias || producto.ventas_7_dias || 1;
    const ventasDiarias = ventas / (producto.ventas_30_dias ? 30 : 7);
    return ventasDiarias > 0 ? Math.ceil((producto.stock || 0) / ventasDiarias) : 999;
  };

  const calcularUrgencia = (producto) => {
    const stockActual = producto.stock || 0;
    const stockMinimo = producto.stock_minimo || 10;
    const ratio = stockActual / stockMinimo;
    
    if (stockActual === 0) return 100; // Crítico
    if (ratio <= 0.5) return 90; // Muy urgente
    if (ratio <= 1) return 70; // Urgente
    if (ratio <= 1.5) return 40; // Moderado
    return 10; // Bajo
  };

  const calcularDiasStock = (producto) => {
    // Simulación: asumiendo venta promedio de 1-5 unidades por día
    const ventaPromedioDiaria = Math.max(1, (producto.precio_venta || 100) / 200);
    return Math.floor((producto.stock || 0) / ventaPromedioDiaria);
  };

  // IMPLEMENTACIÓN CORREGIDA: Análisis ABC basado en principio de Pareto 80/20
  const calcularClaseABC = (producto, todosLosProductos = []) => {
    // Si no hay datos suficientes, usar clasificación por valor como fallback
    if (!todosLosProductos.length) {
      const valor = (producto.stock || 0) * (producto.precio_costo || 0);
      if (valor > 10000) return 'A';
      if (valor > 2000) return 'B';
      return 'C';
    }
    
    // Calcular valor total de inventario para este producto
    const valorProducto = (producto.stock || 0) * (producto.precio_costo || 0);
    
    // Ordenar productos por valor de inventario descendente
    const productosOrdenados = [...todosLosProductos]
      .map(p => ({
        ...p,
        valorInventario: (p.stock || 0) * (p.precio_costo || 0)
      }))
      .sort((a, b) => b.valorInventario - a.valorInventario);
    
    const valorTotal = productosOrdenados.reduce((sum, p) => sum + p.valorInventario, 0);
    
    // Aplicar regla de Pareto 80/20
    let valorAcumulado = 0;
    let posicion = 0;
    
    for (let i = 0; i < productosOrdenados.length; i++) {
      valorAcumulado += productosOrdenados[i].valorInventario;
      if (productosOrdenados[i].id === producto.id) {
        posicion = i;
        break;
      }
    }
    
    const porcentajeValor = valorTotal > 0 ? (valorAcumulado / valorTotal) : 0;
    const porcentajeProductos = productosOrdenados.length > 0 ? ((posicion + 1) / productosOrdenados.length) : 0;
    
    // Clasificación ABC basada en principio de Pareto
    if (porcentajeValor <= 0.8 && porcentajeProductos <= 0.2) return 'A'; // 80% del valor, 20% de productos
    if (porcentajeValor <= 0.95 && porcentajeProductos <= 0.5) return 'B'; // Siguiente 15% del valor, 30% de productos
    return 'C'; // Resto: 5% del valor, 50% de productos
  };

  const calcularRotacion = (producto) => {
    // Mejorado: rotación basada en ventas históricas si están disponibles
    if (producto.ventas_30_dias && producto.stock > 0) {
      // Rotación anualizada basada en ventas de últimos 30 días
      const rotacionMensual = producto.ventas_30_dias / (producto.stock || 1);
      return Math.max(0.1, rotacionMensual * 12);
    }
    
    // Fallback: simulación basada en precio y categoría
    const baseRotacion = (producto.precio_venta || 100) < 500 ? 12 : 6;
    return Math.max(1, baseRotacion - (producto.stock || 0) / 10);
  };

  const calcularRentabilidad = (producto) => {
    const precioCosto = producto.precio_costo || 0;
    const precioVenta = producto.precio_venta || 0;
    
    if (precioCosto <= 0) return 0;
    
    const margenBruto = precioVenta - precioCosto;
    const porcentajeRentabilidad = (margenBruto / precioCosto) * 100;
    
    return Math.round(porcentajeRentabilidad * 100) / 100; // Redondear a 2 decimales
  };

  // FUNCIÓN MEJORADA: Cálculo de métricas con validación matemática
  // ✅ OPTIMIZADO: Usar useMemo para evitar recálculos innecesarios
  const calcularMetricas = useMemo(() => {
    return (productosData) => {
      if (!Array.isArray(productosData) || productosData.length === 0) {
        setMetricas({
          valorTotal: 0,
          productosActivos: 0,
          stockBajo: 0,
          sinStock: 0,
          rotacionPromedio: 0,
          costoAlmacenamiento: 0,
          productos: { claseA: 0, claseB: 0, claseC: 0 },
          validacion: { status: 'warning', mensaje: 'Sin datos de productos' }
        });
        return;
      }

      const total = productosData.length;
      
      // ✅ OPTIMIZADO: Una sola pasada para calcular múltiples métricas
      let valorTotal = 0;
      let sinStock = 0;
      let stockBajo = 0;
      let claseA = 0;
      let claseB = 0;
      let claseC = 0;
      let outliers = 0;
      let alertasCriticas = 0;
      let sumaRotaciones = 0;
      
      for (const p of productosData) {
        // Valor total
        const valor = (p.valor_inventario ?? (p.stock || 0) * (p.precio_costo || 0));
        if (isFinite(valor)) valorTotal += valor;
        
        // Contadores de stock
        const stock = p.stock || 0;
        if (stock === 0) sinStock++;
        else if (stock <= (p.stock_minimo || 10)) stockBajo++;
        
        // Clasificación ABC
        const clase = p.clase_abc;
        if (clase === 'A') claseA++;
        else if (clase === 'B') claseB++;
        else claseC++;
        
        // Outliers y alertas
        if ((p.es_outlier || []).length > 0) outliers++;
        if ((p.alertas_validacion || []).some(alert => alert.severidad === 'critica')) {
          alertasCriticas++;
        }
        
        // Rotación
        const rotacion = p.rotacion_anual || p.rotacion || 0;
        if (isFinite(rotacion)) sumaRotaciones += rotacion;
      }
      
      const productosActivos = total - sinStock;
      const rotacionPromedio = sumaRotaciones / Math.max(total, 1);
      
      // Validación simplificada
      const sumaClasificacion = claseA + claseB + claseC;
      const discrepanciaClasificacion = Math.abs(sumaClasificacion - total);
      
      const validacion = {
        status: discrepanciaClasificacion === 0 ? 'success' : 'error',
        mensaje: discrepanciaClasificacion === 0 
          ? `✅ Datos consistentes: ${total} productos`
          : `⚠️ Discrepancia: ${discrepanciaClasificacion} productos`,
        detalles: {
          total_productos: total,
          suma_clasificacion: sumaClasificacion,
          discrepancia: discrepanciaClasificacion,
          productos_activos: productosActivos
        }
      };

      setMetricas({
        valorTotal: Math.round(valorTotal * 100) / 100,
        productosActivos,
        stockBajo,
        sinStock,
        rotacionPromedio: Math.round(rotacionPromedio * 100) / 100,
        costoAlmacenamiento: Math.round(valorTotal * 0.02 * 100) / 100,
        outliers,
        alertasCriticas,
        productos: { claseA, claseB, claseC },
        validacion,
        distribucion: {
          porcentajeA: total > 0 ? Math.round((claseA / total) * 100) : 0,
          porcentajeB: total > 0 ? Math.round((claseB / total) * 100) : 0,
          porcentajeC: total > 0 ? Math.round((claseC / total) * 100) : 0,
          cumplePareto: (claseA / total) <= 0.25 && (claseC / total) >= 0.45
        }
      });
    };
  }, []);

  // Validar principio de Pareto 80/20
  const validarPrincipiPareto = (productos) => {
    if (!productos.length) return { valido: false, mensaje: 'Sin datos' };
    
    const productosConValor = productos
      .map(p => ({ ...p, valorInventario: (p.stock || 0) * (p.precio_costo || 0) }))
      .sort((a, b) => b.valorInventario - a.valorInventario);
    
    const valorTotal = productosConValor.reduce((sum, p) => sum + p.valorInventario, 0);
    const claseACount = productosConValor.filter(p => p.claseABC === 'A').length;
    const claseBCount = productosConValor.filter(p => p.claseABC === 'B').length;
    const claseCCount = productosConValor.filter(p => p.claseABC === 'C').length;
    
    const porcentajeA = (claseACount / productos.length) * 100;
    const porcentajeB = (claseBCount / productos.length) * 100;
    const porcentajeC = (claseCCount / productos.length) * 100;
    
    const esperadoA = 20; // 20% de productos
    const esperadoB = 30; // 30% de productos  
    const esperadoC = 50; // 50% de productos
    
    const desvioA = Math.abs(porcentajeA - esperadoA);
    const desvioB = Math.abs(porcentajeB - esperadoB);
    const desvioC = Math.abs(porcentajeC - esperadoC);
    
    const cumplePareto = desvioA <= 10 && desvioB <= 15 && desvioC <= 15; // Tolerancia del 10-15%
    
    return {
      valido: cumplePareto,
      porcentajes: { A: porcentajeA, B: porcentajeB, C: porcentajeC },
      desvios: { A: desvioA, B: desvioB, C: desvioC },
      mensaje: cumplePareto 
        ? '✅ Principio de Pareto respetado'
        : `⚠️ Desviación del principio de Pareto: A=${porcentajeA.toFixed(1)}% B=${porcentajeB.toFixed(1)}% C=${porcentajeC.toFixed(1)}%`
    };
  };

  const generarAlertasBasicas = () => {
    const alertas = productos
      .filter(p => p.urgencia >= 70)
      .map(p => ({
        id: p.id,
        tipo: p.stock === 0 ? 'sin_stock' : 'stock_bajo',
        producto: p.nombre,
        mensaje: p.stock === 0 ? 'Producto agotado' : `Stock bajo: ${p.stock} unidades`,
        urgencia: p.urgencia,
        accion: 'Reabastecer'
      }))
      .slice(0, 10);
    
    setAlertasInteligentes(alertas);
  };

  // Filtrar productos
  const productosFiltrados = useMemo(() => {
    let filtrados = [...productos];

    // Filtro de búsqueda
    if (searchTerm) {
      filtrados = filtrados.filter(p => 
        p.nombre?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.codigo?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.barcode?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Filtro de categoría
    if (categoriaFilter !== 'todas') {
      filtrados = filtrados.filter(p => p.categoria === categoriaFilter);
    }

    // Filtro de stock
    switch (stockFilter) {
      case 'sin_stock':
        filtrados = filtrados.filter(p => (p.stock || 0) === 0);
        break;
      case 'stock_bajo':
        filtrados = filtrados.filter(p => (p.stock || 0) > 0 && (p.stock || 0) <= (p.stock_minimo || 10));
        break;
      case 'stock_normal':
        filtrados = filtrados.filter(p => (p.stock || 0) > (p.stock_minimo || 10));
        break;
      default:
        // Mostrar todos los productos
        break;
    }

    // Filtro de urgencia
    switch (urgenciaFilter) {
      case 'critico':
        filtrados = filtrados.filter(p => p.urgencia >= 90);
        break;
      case 'alto':
        filtrados = filtrados.filter(p => p.urgencia >= 70 && p.urgencia < 90);
        break;
      case 'medio':
        filtrados = filtrados.filter(p => p.urgencia >= 40 && p.urgencia < 70);
        break;
      case 'bajo':
        filtrados = filtrados.filter(p => p.urgencia < 40);
        break;
      default:
        // Mostrar todos los niveles de urgencia
        break;
    }

    // Ordenamiento
    filtrados.sort((a, b) => {
      let valueA = a[sortField] || 0;
      let valueB = b[sortField] || 0;

      if (typeof valueA === 'string') {
        valueA = valueA.toLowerCase();
        valueB = valueB.toLowerCase();
      }

      if (sortDirection === 'desc') {
        return valueA > valueB ? -1 : 1;
      }
      return valueA < valueB ? -1 : 1;
    });

    return filtrados;
  }, [productos, searchTerm, categoriaFilter, stockFilter, urgenciaFilter, sortField, sortDirection]);

  // Paginación
  const totalPages = Math.ceil(productosFiltrados.length / itemsPerPage);
  const productosActuales = productosFiltrados.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  // Obtener categorías únicas
  const categorias = useMemo(() => {
    const cats = [...new Set(productos.map(p => p.categoria).filter(Boolean))];
    return ['todas', ...cats];
  }, [productos]);

  // Funciones de utilidad
  const formatCurrency = (value) => CONFIG.formatCurrency(value);
  
  const getUrgenciaColor = (urgencia) => {
    if (urgencia >= 90) return 'text-red-600 bg-red-50';
    if (urgencia >= 70) return 'text-orange-600 bg-orange-50';
    if (urgencia >= 40) return 'text-yellow-600 bg-yellow-50';
    return 'text-green-600 bg-green-50';
  };

  const getUrgenciaIcon = (urgencia) => {
    if (urgencia >= 90) return <AlertCircle size={16} />;
    if (urgencia >= 70) return <AlertTriangle size={16} />;
    if (urgencia >= 40) return <Clock size={16} />;
    return <CheckCircle size={16} />;
  };

  // Componente para imagen de producto mejorado
  const ProductImage = ({ producto, size = 'w-12 h-12' }) => {
    const [imageState, setImageState] = useState('checking'); // checking, loading, loaded, error
    const [imageUrl, setImageUrl] = useState(null);

    // Verificar imágenes disponibles al montar
    useEffect(() => {
      const checkImageAvailability = async () => {
        if (!producto.barcode && !producto.codigo) {
          setImageState('error');
          return;
        }

        const imageId = producto.barcode || producto.codigo;
        const imagesToCheck = [
          `/img/productos/${imageId}.svg`,
          `/img/productos/${imageId}.jpg`,
          `/img/productos/${imageId}.png`
        ];

        // Intentar cargar cada imagen en orden
        for (const url of imagesToCheck) {
          try {
            await new Promise((resolve, reject) => {
              const img = new Image();
              img.onload = resolve;
              img.onerror = reject;
              img.src = url;
            });
            
            // Si llegamos aquí, la imagen se cargó correctamente
            setImageUrl(url);
            setImageState('loading');
            return;
          } catch (error) {
            // Continuar con la siguiente imagen
            continue;
          }
        }

        // Si ninguna imagen funciona, mostrar error
        setImageState('error');
      };

      checkImageAvailability();
    }, [producto.barcode, producto.codigo]);

    const handleImageLoad = () => {
      setImageState('loaded');
    };

    const handleImageError = () => {
      setImageState('error');
    };

    // Mostrar ícono por defecto
    const renderDefaultIcon = () => (
      <div className={`relative ${size} flex items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg overflow-hidden border border-blue-200`}>
        <div className="text-center">
          <Package size={size.includes('w-24') ? 32 : size.includes('w-32') ? 40 : 20} className="text-blue-500 mx-auto mb-1" />
          <div className="text-xs text-blue-600 font-medium">PRODUCTO</div>
        </div>
      </div>
    );

    // Mostrar spinner de carga
    const renderLoading = () => (
      <div className={`relative ${size} flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden border border-gray-200`}>
        <div className="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );

    // Estados de renderizado
    if (imageState === 'checking' || imageState === 'error') {
      return renderDefaultIcon();
    }

    if (imageState === 'loading') {
      return (
        <div className={`relative ${size} flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden border border-gray-200`}>
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          </div>
          <img
            src={imageUrl}
            alt={producto.nombre || 'Producto'}
            onLoad={handleImageLoad}
            onError={handleImageError}
            className="w-full h-full object-cover opacity-0"
          />
        </div>
      );
    }

    // Estado loaded - mostrar imagen
    return (
      <div className={`relative ${size} flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden border border-gray-200`}>
        <img
          src={imageUrl}
          alt={producto.nombre || 'Producto'}
          onError={handleImageError}
          className="w-full h-full object-cover"
        />
      </div>
    );
  };

  // Componente de tarjetas de métricas
  const MetricCard = ({ title, value, icon: Icon, color, change, subtitle }) => (
    <div className={`bg-white p-6 rounded-lg shadow-md border-l-4 ${color}`}>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-2xl font-bold text-gray-900">{value}</p>
          {subtitle && <p className="text-xs text-gray-500 mt-1">{subtitle}</p>}
          {change && (
            <p className={`text-xs mt-1 flex items-center ${change > 0 ? 'text-green-600' : 'text-red-600'}`}>
              {change > 0 ? <ArrowUp size={12} /> : <ArrowDown size={12} />}
              <span className="ml-1">{Math.abs(change)}%</span>
            </p>
          )}
        </div>
        <div className={`p-3 rounded-full ${color.replace('border-l-4', 'bg-opacity-10')}`}>
          <Icon size={24} className={color.replace('border-l-4 border-', 'text-')} />
        </div>
      </div>
    </div>
  );

  // Dashboard principal
  const renderDashboard = () => (
    <div className="space-y-6">
      {/* Métricas principales */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <MetricCard
          title="Valor Total Inventario"
          value={formatCurrency(metricas.valorTotal)}
          icon={DollarSign}
          color="border-l-4 border-blue-500"
          subtitle="Costo de reposición"
        />
        <MetricCard
          title="Productos Activos"
          value={metricas.productosActivos}
          icon={Package}
          color="border-l-4 border-green-500"
          subtitle={`${metricas.sinStock} sin stock`}
        />
        <MetricCard
          title="Alertas Críticas"
          value={metricas.stockBajo + metricas.sinStock}
          icon={AlertTriangle}
          color="border-l-4 border-red-500"
          subtitle="Requieren atención"
        />
        <MetricCard
          title="Rotación Promedio"
          value={`${metricas.rotacionPromedio.toFixed(1)}x`}
          icon={TrendingUp}
          color="border-l-4 border-purple-500"
          subtitle="Veces por año"
        />
      </div>

      {/* Análisis ABC */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-md">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">Análisis ABC</h3>
            <PieChart size={20} className="text-gray-500" />
          </div>
          <div className="space-y-3">
            <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
              <div>
                <span className="font-medium text-green-800">Clase A (Alto valor)</span>
                <p className="text-sm text-green-600">80% del valor, 20% productos</p>
              </div>
              <span className="text-2xl font-bold text-green-800">{metricas.productos.claseA}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
              <div>
                <span className="font-medium text-yellow-800">Clase B (Medio valor)</span>
                <p className="text-sm text-yellow-600">15% del valor, 30% productos</p>
              </div>
              <span className="text-2xl font-bold text-yellow-800">{metricas.productos.claseB}</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
              <div>
                <span className="font-medium text-blue-800">Clase C (Bajo valor)</span>
                <p className="text-sm text-blue-600">5% del valor, 50% productos</p>
              </div>
              <span className="text-2xl font-bold text-blue-800">{metricas.productos.claseC}</span>
            </div>
          </div>
        </div>

        {/* Alertas inteligentes */}
        <div className="bg-white p-6 rounded-lg shadow-md">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">Alertas Inteligentes</h3>
            <Bell size={20} className="text-gray-500" />
          </div>
          <div className="space-y-2 max-h-64 overflow-y-auto">
            {alertasInteligentes.slice(0, 8).map((alerta, index) => (
              <div key={index} className="flex items-center p-3 bg-gray-50 rounded-lg">
                <div className={`p-2 rounded-full mr-3 ${getUrgenciaColor(alerta.urgencia)}`}>
                  {getUrgenciaIcon(alerta.urgencia)}
                </div>
                <div className="flex-1">
                  <p className="text-sm font-medium text-gray-900">{alerta.producto}</p>
                  <p className="text-xs text-gray-600">{alerta.mensaje}</p>
                </div>
                <button className="text-blue-600 text-xs font-medium hover:text-blue-800">
                  {alerta.accion}
                </button>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );

  // Vista de productos
  const renderProductos = () => (
    <div className="space-y-6">
      {/* Controles y filtros */}
      <div className="bg-white p-4 rounded-lg shadow-md">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="relative">
            <input
              type="text"
              placeholder="Buscar productos..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <Search size={18} className="absolute left-3 top-2.5 text-gray-400" />
          </div>

          <select
            value={categoriaFilter}
            onChange={(e) => setCategoriaFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            {categorias.map(cat => (
              <option key={cat} value={cat}>
                {cat === 'todas' ? 'Todas las categorías' : cat}
              </option>
            ))}
          </select>

          <select
            value={stockFilter}
            onChange={(e) => setStockFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="todos">Todos los stocks</option>
            <option value="sin_stock">Sin stock</option>
            <option value="stock_bajo">Stock bajo</option>
            <option value="stock_normal">Stock normal</option>
          </select>

          <select
            value={urgenciaFilter}
            onChange={(e) => setUrgenciaFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="todos">Todas las urgencias</option>
            <option value="critico">Crítico</option>
            <option value="alto">Alto</option>
            <option value="medio">Medio</option>
            <option value="bajo">Bajo</option>
          </select>
        </div>
      </div>

      {/* Tabla de productos */}
      <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Imagen
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('nombre')}>
                  Producto
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('categoria')}>
                  Categoría
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('stock')}>
                  Stock
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('diasStock')}>
                  Días Stock
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('urgencia')}>
                  Urgencia
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('claseABC')}>
                  Clase ABC
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                    onClick={() => setSortField('rotacion')}>
                  Rotación
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {productosActuales.map((producto) => (
                <tr key={producto.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <ProductImage producto={producto} size="w-16 h-16" />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{producto.nombre}</div>
                      <div className="text-sm text-gray-500">{producto.codigo}</div>
                      {producto.barcode && producto.barcode !== producto.codigo && (
                        <div className="text-xs text-gray-400">BC: {producto.barcode}</div>
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {producto.categoria || 'Sin categoría'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                      ${producto.stock === 0 ? 'bg-red-100 text-red-800' :
                        producto.stock <= (producto.stock_minimo || 10) ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800'
                      }`}>
                      {producto.stock || 0}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {producto.diasStock || 0} días
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className={`flex items-center px-2 py-1 rounded-full text-xs font-medium ${getUrgenciaColor(producto.urgencia)}`}>
                      {getUrgenciaIcon(producto.urgencia)}
                      <span className="ml-1">{Math.round(producto.urgencia || 0)}%</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                      ${producto.claseABC === 'A' ? 'bg-green-100 text-green-800' :
                        producto.claseABC === 'B' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-blue-100 text-blue-800'
                      }`}>
                      {producto.claseABC || 'C'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {(producto.rotacion || 0).toFixed(1)}x
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div className="flex space-x-2">
                      <button 
                        onClick={() => verDetalles(producto)}
                        className="text-blue-600 hover:text-blue-900" 
                        title="Ver detalles"
                      >
                        <Eye size={16} />
                      </button>
                      <button 
                        onClick={() => abrirModalAjuste(producto)}
                        className="text-green-600 hover:text-green-900" 
                        title="Ajustar stock"
                      >
                        <Edit size={16} />
                      </button>
                      <button 
                        onClick={() => abrirModalPedido(producto)}
                        className="text-purple-600 hover:text-purple-900" 
                        title="Pedir ahora"
                      >
                        <ShoppingCart size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Paginación */}
        {totalPages > 1 && (
          <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
            <div className="flex-1 flex justify-between sm:hidden">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Anterior
              </button>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Siguiente
              </button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p className="text-sm text-gray-700">
                  Mostrando{' '}
                  <span className="font-medium">{(currentPage - 1) * itemsPerPage + 1}</span>
                  {' '}a{' '}
                  <span className="font-medium">
                    {Math.min(currentPage * itemsPerPage, productosFiltrados.length)}
                  </span>
                  {' '}de{' '}
                  <span className="font-medium">{productosFiltrados.length}</span>
                  {' '}resultados
                </p>
              </div>
              <div>
                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  {[...Array(totalPages)].map((_, i) => (
                    <button
                      key={i}
                      onClick={() => setCurrentPage(i + 1)}
                      className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium
                        ${currentPage === i + 1
                          ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        }
                        ${i === 0 ? 'rounded-l-md' : ''}
                        ${i === totalPages - 1 ? 'rounded-r-md' : ''}
                      `}
                    >
                      {i + 1}
                    </button>
                  ))}
                </nav>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );

  // Funciones para las acciones
  const verDetalles = (producto) => {
    setProductoSeleccionado(producto);
    setModalDetalle(true);
  };

  const abrirModalAjuste = (producto) => {
    setProductoSeleccionado(producto);
    setCantidadAjuste(0);
    setTipoAjuste('entrada');
    setMotivoAjuste('');
    setModalAjuste(true);
  };

  const abrirModalPedido = (producto) => {
    setProductoSeleccionado(producto);
    setModalPedido(true);
  };

  const procesarAjuste = async () => {
    if (!productoSeleccionado || cantidadAjuste <= 0 || !motivoAjuste.trim()) {
      alert('Por favor complete todos los campos');
      return;
    }

    try {
      const nuevoStock = tipoAjuste === 'entrada' 
        ? productoSeleccionado.stock + cantidadAjuste 
        : productoSeleccionado.stock - cantidadAjuste;

      if (nuevoStock < 0) {
        alert('El ajuste no puede resultar en un stock negativo');
        return;
      }

      const response = await axios.put(
        `${CONFIG.API_URL}/api/productos.php/${productoSeleccionado.id}`, 
        { id: productoSeleccionado.id, stock: nuevoStock }
      );

      if (response.data) {
        // Actualizar productos en el estado
        const nuevosProductos = productos.map(p => 
          p.id === productoSeleccionado.id ? { ...p, stock: nuevoStock } : p
        );
        setProductos(nuevosProductos);
        calcularMetricas(nuevosProductos);
        
        setModalAjuste(false);
        alert(`Stock actualizado correctamente. Nuevo stock: ${nuevoStock}`);
      }
    } catch (error) {
      console.error('Error al actualizar stock:', error);
      alert('Error al actualizar el stock');
    }
  };

  const generarPedido = () => {
    if (!productoSeleccionado) return;
    
    const cantidadSugerida = Math.max(
      (productoSeleccionado.stock_minimo || 10) * 2,
      productoSeleccionado.cantidad_optima_pedido || 50
    );
    
    alert(`Pedido sugerido para ${productoSeleccionado.nombre}:\n\nCantidad: ${cantidadSugerida} unidades\nProveedor: ${productoSeleccionado.proveedor || 'Por definir'}\nCosto estimado: ${formatCurrency(cantidadSugerida * (productoSeleccionado.precio_costo || 0))}\n\n¡Funcionalidad de pedidos automáticos próximamente!`);
    setModalPedido(false);
  };

  const abrirModalEditarImagen = (producto) => {
    setProductoSeleccionado(producto);
    setNuevaImagenUrl('');
    setModalEditarImagen(true);
  };

  const guardarNuevaImagen = async () => {
    if (!productoSeleccionado || !nuevaImagenUrl.trim()) {
      alert('Por favor ingresa una URL válida para la imagen');
      return;
    }

    try {
      // Aquí puedes implementar la lógica para guardar la imagen
      // Por ahora solo mostramos un mensaje de éxito
      alert(`Imagen actualizada para ${productoSeleccionado.nombre}\n\nNota: Para implementar completamente esta funcionalidad, se necesita:\n1. Servidor para manejar subida de archivos\n2. API para actualizar la imagen en la base de datos\n3. Sistema de validación de imágenes\n\n¡Funcionalidad básica implementada!`);
      
      setModalEditarImagen(false);
      setNuevaImagenUrl('');
    } catch (error) {
      console.error('Error al actualizar imagen:', error);
      alert('Error al actualizar la imagen');
    }
  };

  // Cargar datos iniciales
  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-md p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <XCircle className="h-5 w-5 text-red-400" />
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800">Error</h3>
            <div className="mt-2 text-sm text-red-700">
              <p>{error}</p>
            </div>
            <div className="mt-4">
              <button
                onClick={cargarDatos}
                className="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200"
              >
                Reintentar
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 flex items-center">
            <Brain className="mr-3 text-blue-600" size={32} />
            Inventario
          </h1>
          <p className="text-gray-600 mt-1">
            Gestión optimizada con análisis predictivo y automatización
          </p>
        </div>
        <div className="flex space-x-3">
          <button
            onClick={cargarDatos}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <RefreshCw size={16} className="mr-2" />
            Actualizar
          </button>
          <button className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <Download size={16} className="mr-2" />
            Exportar
          </button>
        </div>
      </div>

      {/* Navegación */}
      <div className="flex space-x-1 mb-6 bg-gray-100 p-1 rounded-lg">
        {[
          { key: 'dashboard', label: 'Dashboard', icon: BarChart3 },
          { key: 'productos', label: 'Stock', icon: Package },
          { key: 'proveedores', label: 'Proveedores', icon: Store },
          { key: 'pedidos', label: 'Pedidos IA', icon: ShoppingCart },
          { key: 'alertas', label: 'Alertas', icon: Bell },
          { key: 'ia', label: '🧠 Análisis IA', icon: Brain }
        ].map(({ key, label, icon: Icon }) => (
          <button
            key={key}
            onClick={() => setVistaActual(key)}
            className={`flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors
              ${vistaActual === key
                ? 'bg-white text-blue-600 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
              }`}
          >
            <Icon size={16} className="mr-2" />
            {label}
          </button>
        ))}
      </div>

      {/* Contenido principal */}
      {vistaActual === 'dashboard' && renderDashboard()}
      {vistaActual === 'productos' && renderProductos()}
      
      {/* NUEVA: Vista de Proveedores */}
      {vistaActual === 'proveedores' && <GestionProveedores />}
      
      {/* NUEVA: Vista de Pedidos Inteligentes */}
      {vistaActual === 'pedidos' && <PedidosInteligentes />}
      
      {vistaActual === 'alertas' && (
        <div className="text-center py-12">
          <Bell size={48} className="mx-auto text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Vista de Alertas</h3>
          <p className="text-gray-600">Próximamente: Sistema avanzado de alertas inteligentes</p>
        </div>
      )}
      
      {/* Vista de Pedidos ANTIGUA (remover después) */}
      {vistaActual === 'pedidos_old' && (
        <div className="space-y-6">
          {/* Header del análisis IA de Pedidos */}
          <div className="bg-white p-6 rounded-lg shadow-md">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-xl font-bold text-gray-900 flex items-center">
                  <ShoppingCart className="w-6 h-6 mr-3 text-purple-600" />
                  🛒 Optimización de Pedidos con IA
                </h3>
                <p className="text-gray-600 mt-1">
                  Análisis inteligente de compras, proveedores y gestión de stock
                </p>
              </div>
              <button
                onClick={ejecutarAnalisisPedidosIA}
                disabled={loadingPedidosIA}
                className={`flex items-center px-6 py-3 rounded-lg font-medium transition-colors ${
                  loadingPedidosIA 
                    ? 'bg-gray-400 text-white cursor-not-allowed'
                    : 'bg-purple-600 text-white hover:bg-purple-700'
                }`}
              >
                {loadingPedidosIA ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Optimizando...
                  </>
                ) : (
                  <>
                    <ShoppingCart size={20} className="mr-2" />
                    🛒 🚀 Optimizar Pedidos IA
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Resultados del análisis de Pedidos IA */}
          {analisisPedidosIA && (
            <div className="space-y-6">
              {/* Resumen de pedidos */}
              <div className="bg-white p-6 rounded-lg shadow-md">
                <h4 className="text-lg font-semibold text-gray-900 mb-4">📊 Resumen de Pedidos</h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h6 className="font-medium text-blue-800">Total a Pedir</h6>
                    <p className="text-lg font-bold text-blue-900">{analisisPedidosIA.resumen_pedidos?.total_productos_pedir}</p>
                  </div>
                  <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h6 className="font-medium text-green-800">Inversión Total</h6>
                    <p className="text-lg font-bold text-green-900">{analisisPedidosIA.resumen_pedidos?.inversion_total}</p>
                  </div>
                  <div className={`p-4 rounded-lg border ${
                    analisisPedidosIA.resumen_pedidos?.urgencia_general === 'critica' ? 'bg-red-50 border-red-200' :
                    analisisPedidosIA.resumen_pedidos?.urgencia_general === 'alta' ? 'bg-orange-50 border-orange-200' :
                    'bg-yellow-50 border-yellow-200'
                  }`}>
                    <h6 className={`font-medium ${
                      analisisPedidosIA.resumen_pedidos?.urgencia_general === 'critica' ? 'text-red-800' :
                      analisisPedidosIA.resumen_pedidos?.urgencia_general === 'alta' ? 'text-orange-800' :
                      'text-yellow-800'
                    }`}>Urgencia</h6>
                    <p className={`text-lg font-bold ${
                      analisisPedidosIA.resumen_pedidos?.urgencia_general === 'critica' ? 'text-red-900' :
                      analisisPedidosIA.resumen_pedidos?.urgencia_general === 'alta' ? 'text-orange-900' :
                      'text-yellow-900'
                    }`}>{analisisPedidosIA.resumen_pedidos?.urgencia_general?.toUpperCase()}</p>
                  </div>
                </div>
                {analisisPedidosIA.resumen_pedidos?.ahorro_potencial && (
                  <div className="mt-4 p-3 bg-green-100 rounded-lg">
                    <p className="text-green-800 font-medium">
                      💰 Ahorro potencial: {analisisPedidosIA.resumen_pedidos.ahorro_potencial}
                    </p>
                  </div>
                )}
              </div>

              {/* Pedidos urgentes */}
              {analisisPedidosIA.pedidos_urgentes && analisisPedidosIA.pedidos_urgentes.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">🚨 Pedidos Urgentes</h4>
                  <div className="space-y-3">
                    {analisisPedidosIA.pedidos_urgentes.slice(0, 8).map((pedido, index) => (
                      <div key={index} className="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                        <div className="flex-1">
                          <h6 className="font-medium text-red-800">{pedido.producto}</h6>
                          <div className="text-sm text-red-600 grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                            <span>Stock: {pedido.stock_actual}/{pedido.stock_minimo}</span>
                            <span>Pedir: {pedido.cantidad_sugerida} unidades</span>
                            <span>Costo: {pedido.costo_pedido}</span>
                            <span>Días sin stock: {pedido.dias_sin_stock}</span>
                          </div>
                          <p className="text-xs text-red-500 mt-1">{pedido.justificacion}</p>
                        </div>
                        <div className="text-right ml-4">
                          <button className="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                            Pedir Ahora
                          </button>
                          <p className="text-xs text-red-600 mt-1">{pedido.impacto_no_pedir}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Optimización por proveedores */}
              {analisisPedidosIA.optimizacion_proveedores && analisisPedidosIA.optimizacion_proveedores.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">🏪 Optimización por Proveedor</h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {analisisPedidosIA.optimizacion_proveedores.map((prov, index) => (
                      <div key={index} className="p-4 bg-purple-50 rounded-lg border border-purple-200">
                        <h6 className="font-medium text-purple-800">{prov.proveedor}</h6>
                        <div className="text-sm text-purple-700 mt-2">
                          <p>Productos: {prov.productos_agrupar?.length || 0}</p>
                          <p>Costo total: {prov.costo_total}</p>
                          <p>Descuento posible: {prov.descuento_posible}</p>
                          <p>Fecha sugerida: {prov.fecha_sugerida}</p>
                        </div>
                        <div className="mt-2">
                          {prov.beneficios?.slice(0, 2).map((beneficio, i) => (
                            <p key={i} className="text-xs text-purple-600">• {beneficio}</p>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Plan de acción */}
              {analisisPedidosIA.plan_accion_compras && analisisPedidosIA.plan_accion_compras.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">🎯 Plan de Acción</h4>
                  <div className="space-y-3">
                    {analisisPedidosIA.plan_accion_compras.map((accion, index) => (
                      <div key={index} className="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div className="flex-1">
                          <h6 className="font-medium text-blue-800">{accion.accion}</h6>
                          <p className="text-sm text-blue-600">
                            ⏱️ {accion.plazo} | 💰 {accion.recursos_necesarios}
                          </p>
                          <p className="text-xs text-blue-500 mt-1">Impacto: {accion.impacto_esperado}</p>
                        </div>
                        <div className="text-right">
                          <span className={`px-2 py-1 rounded text-xs font-medium ${
                            accion.prioridad === 'alta' ? 'bg-red-100 text-red-800' :
                            accion.prioridad === 'media' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-green-100 text-green-800'
                          }`}>
                            {accion.prioridad?.toUpperCase()}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Estado inicial - Solo mensaje sin botón duplicado */}
          {!analisisPedidosIA && !loadingPedidosIA && (
            <div className="text-center py-12 bg-white rounded-lg shadow-md">
              <ShoppingCart size={64} className="mx-auto text-purple-400 mb-4" />
              <h3 className="text-xl font-medium text-gray-900 mb-2">Optimización de Pedidos no ejecutada</h3>
              <p className="text-gray-600">
                Usa el botón "🛒 🚀 Optimizar Pedidos IA" de arriba para ejecutar la optimización inteligente
              </p>
            </div>
          )}
        </div>
      )}

      {/* Vista de Análisis IA */}
      {vistaActual === 'ia' && (
        <div className="space-y-6">
          {/* Header del análisis IA */}
          <div className="bg-white p-6 rounded-lg shadow-md">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-xl font-bold text-gray-900 flex items-center">
                  <Brain className="w-6 h-6 mr-3 text-blue-600" />
                  🧠 Análisis Experto con IA
                </h3>
                <p className="text-gray-600 mt-1">
                  Optimización inteligente basada en algoritmos de Machine Learning
                </p>
              </div>
              <button
                onClick={ejecutarAnalisisIA}
                disabled={loadingIA}
                className={`flex items-center px-6 py-3 rounded-lg font-medium transition-colors ${
                  loadingIA 
                    ? 'bg-gray-400 text-white cursor-not-allowed'
                    : 'bg-blue-600 text-white hover:bg-blue-700'
                }`}
              >
                {loadingIA ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Analizando...
                  </>
                ) : (
                  <>
                    <Brain size={20} className="mr-2" />
                    🧠 ✨ Iniciar Análisis IA
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Resultados del análisis IA */}
          {analisisIA && (
            <div className="space-y-6">
              {/* Score general */}
              <div className="bg-white p-6 rounded-lg shadow-md">
                <div className="flex items-center justify-between mb-4">
                  <h4 className="text-lg font-semibold text-gray-900">📊 Score de Inventario</h4>
                  <span className="text-xs text-gray-500">{analisisIA.fuente}</span>
                </div>
                <div className="flex items-center">
                  <div className="relative w-32 h-32">
                    <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                      <circle
                        cx="60" cy="60" r="50"
                        stroke="#e5e7eb" strokeWidth="8" fill="none"
                      />
                      <circle
                        cx="60" cy="60" r="50"
                        stroke={analisisIA.score_inventario >= 80 ? '#10b981' : 
                               analisisIA.score_inventario >= 60 ? '#f59e0b' : '#ef4444'}
                        strokeWidth="8" fill="none"
                        strokeLinecap="round"
                        strokeDasharray={`${(analisisIA.score_inventario / 100) * 314} 314`}
                      />
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center">
                      <span className="text-2xl font-bold text-gray-900">
                        {analisisIA.score_inventario}
                      </span>
                    </div>
                  </div>
                  <div className="ml-6">
                    <h5 className="text-xl font-bold text-gray-900">
                      {analisisIA.diagnostico_general?.estado_inventario || 'REGULAR'}
                    </h5>
                    <p className="text-gray-600 mt-1">
                      {analisisIA.diagnostico_general?.problema_principal || 'Análisis en progreso'}
                    </p>
                    <p className="text-sm text-blue-600 mt-2">
                      💰 {analisisIA.diagnostico_general?.impacto_financiero || 'Calculando impacto...'}
                    </p>
                  </div>
                </div>
              </div>

              {/* Productos críticos */}
              {analisisIA.productos_criticos && analisisIA.productos_criticos.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">🚨 Productos Críticos</h4>
                  <div className="space-y-3">
                    {analisisIA.productos_criticos.slice(0, 5).map((producto, index) => (
                      <div key={index} className="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                        <div className="flex-1">
                          <h6 className="font-medium text-red-800">{producto.nombre}</h6>
                          <p className="text-sm text-red-600">{producto.problema}</p>
                          <p className="text-xs text-red-500 mt-1">{producto.accion_inmediata}</p>
                        </div>
                        <div className="text-right">
                          <span className="text-sm font-bold text-red-800">
                            Prioridad {producto.prioridad}/10
                          </span>
                          <p className="text-xs text-red-600">{producto.impacto_diario}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Oportunidades de mejora */}
              {analisisIA.oportunidades_mejora && analisisIA.oportunidades_mejora.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">💡 Oportunidades de Mejora</h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {analisisIA.oportunidades_mejora.slice(0, 4).map((oportunidad, index) => (
                      <div key={index} className="p-4 bg-green-50 rounded-lg border border-green-200">
                        <h6 className="font-medium text-green-800">{oportunidad.categoria}</h6>
                        <p className="text-sm text-green-700 mt-1">{oportunidad.oportunidad}</p>
                        <p className="text-xs text-green-600 mt-2">
                          💰 {oportunidad.beneficio_mensual} | {oportunidad.facilidad}
                        </p>
                        <p className="text-xs text-green-800 mt-1 font-medium">
                          ➜ {oportunidad.accion}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Plan de acción */}
              {analisisIA.plan_accion && analisisIA.plan_accion.length > 0 && (
                <div className="bg-white p-6 rounded-lg shadow-md">
                  <h4 className="text-lg font-semibold text-gray-900 mb-4">🎯 Plan de Acción Recomendado</h4>
                  <div className="space-y-3">
                    {analisisIA.plan_accion.map((accion, index) => (
                      <div key={index} className="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div className="flex-1">
                          <h6 className="font-medium text-blue-800">{accion.accion}</h6>
                          <p className="text-sm text-blue-600">
                            ⏱️ {accion.plazo} | 📈 Impacto: {accion.impacto}
                          </p>
                          <p className="text-xs text-blue-500 mt-1">{accion.recursos_necesarios}</p>
                        </div>
                        <div className="text-right">
                          <span className={`px-2 py-1 rounded text-xs font-medium ${
                            accion.impacto === 'alto' ? 'bg-red-100 text-red-800' :
                            accion.impacto === 'medio' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-green-100 text-green-800'
                          }`}>
                            {accion.impacto.toUpperCase()}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Estado inicial - Solo mensaje sin botón duplicado */}
          {!analisisIA && !loadingIA && (
            <div className="text-center py-12 bg-white rounded-lg shadow-md">
              <Brain size={64} className="mx-auto text-blue-400 mb-4" />
              <h3 className="text-xl font-medium text-gray-900 mb-2">Análisis IA no ejecutado</h3>
              <p className="text-gray-600">
                Usa el botón "🧠 ✨ Iniciar Análisis IA" de arriba para ejecutar el análisis inteligente
              </p>
            </div>
          )}
        </div>
      )}

      {/* Modal de Detalles */}
      {modalDetalle && productoSeleccionado && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-semibold mb-6 text-center">Detalles del Producto</h3>
            
            {/* Header con imagen y título */}
            <div className="flex items-center mb-6 p-4 bg-gray-50 rounded-lg">
              <div className="relative">
                <ProductImage producto={productoSeleccionado} size="w-24 h-24" />
                <button
                  onClick={() => abrirModalEditarImagen(productoSeleccionado)}
                  className="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full p-1 hover:bg-blue-600 shadow-lg"
                  title="Cambiar imagen"
                >
                  <Edit size={12} />
                </button>
              </div>
              <div className="ml-6 flex-1">
                <h4 className="text-lg font-bold text-gray-800">{productoSeleccionado.nombre}</h4>
                <p className="text-sm text-gray-600">{productoSeleccionado.categoria}</p>
                <div className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-2 ${getUrgenciaColor(productoSeleccionado.urgencia)}`}>
                  {getUrgenciaIcon(productoSeleccionado.urgencia)}
                  <span className="ml-1">Urgencia: {Math.round(productoSeleccionado.urgencia)}%</span>
                </div>
                <p className="text-xs text-gray-500 mt-1">Haz clic en el botón 📷 para cambiar la imagen</p>
              </div>
            </div>
            
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Información básica */}
              <div className="bg-blue-50 p-4 rounded-lg">
                <h5 className="font-semibold text-blue-800 mb-3">📋 Información Básica</h5>
                <div className="space-y-2 text-sm">
                  <p><strong>Código:</strong> {productoSeleccionado.codigo || 'N/A'}</p>
                  <p><strong>Barcode:</strong> {productoSeleccionado.barcode || 'N/A'}</p>
                  <p><strong>Categoría:</strong> {productoSeleccionado.categoria || 'General'}</p>
                  <p><strong>Proveedor:</strong> {productoSeleccionado.proveedor || 'Por definir'}</p>
                </div>
              </div>
              
              {/* Stock e inventario */}
              <div className="bg-green-50 p-4 rounded-lg">
                <h5 className="font-semibold text-green-800 mb-3">📦 Stock e Inventario</h5>
                <div className="space-y-2 text-sm">
                  <p><strong>Stock Actual:</strong> 
                    <span className={`ml-2 px-2 py-1 rounded text-xs font-medium ${
                      productoSeleccionado.stock === 0 ? 'bg-red-100 text-red-800' :
                      productoSeleccionado.stock <= (productoSeleccionado.stock_minimo || 10) ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    }`}>
                      {productoSeleccionado.stock || 0} unidades
                    </span>
                  </p>
                  <p><strong>Stock Mínimo:</strong> {productoSeleccionado.stock_minimo || 10} unidades</p>
                  <p><strong>Días de Stock:</strong> {productoSeleccionado.diasStock || 0} días</p>
                  <p><strong>Clase ABC:</strong> 
                    <span className={`ml-2 px-2 py-1 rounded text-xs font-medium ${
                      productoSeleccionado.claseABC === 'A' ? 'bg-green-100 text-green-800' :
                      productoSeleccionado.claseABC === 'B' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-blue-100 text-blue-800'
                    }`}>
                      {productoSeleccionado.claseABC || 'C'}
                    </span>
                  </p>
                </div>
              </div>
              
              {/* Precios y rentabilidad */}
              <div className="bg-purple-50 p-4 rounded-lg">
                <h5 className="font-semibold text-purple-800 mb-3">💰 Precios y Rentabilidad</h5>
                <div className="space-y-2 text-sm">
                  <p><strong>Precio Costo:</strong> {formatCurrency(productoSeleccionado.precio_costo || 0)}</p>
                  <p><strong>Precio Venta:</strong> {formatCurrency(productoSeleccionado.precio_venta || 0)}</p>
                  <p><strong>Margen:</strong> {formatCurrency((productoSeleccionado.precio_venta || 0) - (productoSeleccionado.precio_costo || 0))}</p>
                  <p><strong>Rentabilidad:</strong> {(productoSeleccionado.rentabilidad || 0).toFixed(1)}%</p>
                  <p><strong>Rotación:</strong> {(productoSeleccionado.rotacion || 0).toFixed(1)}x/año</p>
                </div>
              </div>
            </div>
            
            {/* Código de barras */}
            <div className="mt-6 bg-gray-50 p-4 rounded-lg text-center">
              <h5 className="font-semibold text-gray-800 mb-3">🔖 Código de Barras</h5>
              {productoSeleccionado.barcode ? (
                <div className="space-y-3">
                  <img 
                    src={`https://barcode.tec-it.com/barcode.ashx?data=${productoSeleccionado.barcode}&code=Code128&translate-esc=true&unit=Fit&imagetype=png&color=000000&bgcolor=FFFFFF&qunit=Mm&quiet=0`}
                    alt={`Código de barras: ${productoSeleccionado.barcode}`}
                    className="mx-auto max-w-xs border border-gray-300 rounded p-2 bg-white"
                    onError={(e) => {
                      e.target.style.display = 'none';
                      e.target.nextElementSibling.style.display = 'block';
                    }}
                  />
                  <div style={{display: 'none'}} className="text-gray-500 text-sm">
                    Error al cargar código de barras
                  </div>
                  <p className="text-lg font-mono font-bold text-gray-700">{productoSeleccionado.barcode}</p>
                  <p className="text-xs text-gray-500">Código de barras generado automáticamente</p>
                </div>
              ) : (
                <div className="text-gray-500">
                  <Package size={48} className="mx-auto mb-2 text-gray-400" />
                  <p>No hay código de barras disponible</p>
                  <p className="text-xs">Se puede agregar desde la edición del producto</p>
                </div>
              )}
            </div>
            
            {/* Botones de acción */}
            <div className="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
              <div className="flex space-x-2">
                <button
                  onClick={() => {
                    setModalDetalle(false);
                    abrirModalAjuste(productoSeleccionado);
                  }}
                  className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center"
                >
                  <Edit size={16} className="mr-2" />
                  Ajustar Stock
                </button>
                <button
                  onClick={() => {
                    setModalDetalle(false);
                    abrirModalPedido(productoSeleccionado);
                  }}
                  className="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 flex items-center"
                >
                  <ShoppingCart size={16} className="mr-2" />
                  Hacer Pedido
                </button>
              </div>
              <button
                onClick={() => setModalDetalle(false)}
                className="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Ajuste de Stock */}
      {modalAjuste && productoSeleccionado && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Ajustar Stock</h3>
            <p className="mb-4"><strong>{productoSeleccionado.nombre}</strong></p>
            <p className="mb-4">Stock actual: <strong>{productoSeleccionado.stock}</strong></p>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Tipo de Ajuste</label>
                <select
                  value={tipoAjuste}
                  onChange={(e) => setTipoAjuste(e.target.value)}
                  className="w-full p-2 border border-gray-300 rounded"
                >
                  <option value="entrada">Entrada (+)</option>
                  <option value="salida">Salida (-)</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium mb-1">Cantidad</label>
                <input
                  type="number"
                  value={cantidadAjuste}
                  onChange={(e) => setCantidadAjuste(parseInt(e.target.value) || 0)}
                  className="w-full p-2 border border-gray-300 rounded"
                  min="1"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium mb-1">Motivo</label>
                <textarea
                  value={motivoAjuste}
                  onChange={(e) => setMotivoAjuste(e.target.value)}
                  className="w-full p-2 border border-gray-300 rounded"
                  rows="3"
                  placeholder="Describe el motivo del ajuste..."
                />
              </div>
              
              <div className="text-sm text-gray-600">
                Stock resultante: <strong>
                  {tipoAjuste === 'entrada' 
                    ? productoSeleccionado.stock + cantidadAjuste 
                    : productoSeleccionado.stock - cantidadAjuste}
                </strong>
              </div>
            </div>
            
            <div className="flex justify-end space-x-2 mt-6">
              <button
                onClick={() => setModalAjuste(false)}
                className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Cancelar
              </button>
              <button
                onClick={procesarAjuste}
                className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
              >
                Confirmar Ajuste
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Pedido */}
      {modalPedido && productoSeleccionado && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Generar Pedido</h3>
            <p className="mb-4"><strong>{productoSeleccionado.nombre}</strong></p>
            
            <div className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span>Stock actual:</span>
                <span className="font-medium">{productoSeleccionado.stock}</span>
              </div>
              <div className="flex justify-between">
                <span>Stock mínimo:</span>
                <span className="font-medium">{productoSeleccionado.stock_minimo || 10}</span>
              </div>
              <div className="flex justify-between">
                <span>Urgencia:</span>
                <span className={`font-medium ${getUrgenciaColor(productoSeleccionado.urgencia)}`}>
                  {Math.round(productoSeleccionado.urgencia)}%
                </span>
              </div>
              <div className="flex justify-between">
                <span>Cantidad sugerida:</span>
                <span className="font-medium">
                  {Math.max(
                    (productoSeleccionado.stock_minimo || 10) * 2,
                    productoSeleccionado.cantidad_optima_pedido || 50
                  )} unidades
                </span>
              </div>
              <div className="flex justify-between">
                <span>Proveedor:</span>
                <span className="font-medium">{productoSeleccionado.proveedor || 'Por definir'}</span>
              </div>
            </div>
            
            <div className="flex justify-end space-x-2 mt-6">
              <button
                onClick={() => setModalPedido(false)}
                className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Cancelar
              </button>
              <button
                onClick={generarPedido}
                className="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
              >
                Generar Pedido
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Editar Imagen */}
      {modalEditarImagen && productoSeleccionado && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Cambiar Imagen del Producto</h3>
            <p className="mb-4"><strong>{productoSeleccionado.nombre}</strong></p>
            
            {/* Vista previa actual */}
            <div className="mb-4 text-center">
              <p className="text-sm text-gray-600 mb-2">Imagen actual:</p>
              <ProductImage producto={productoSeleccionado} size="w-32 h-32 mx-auto" />
            </div>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">URL de la nueva imagen</label>
                <input
                  type="url"
                  value={nuevaImagenUrl}
                  onChange={(e) => setNuevaImagenUrl(e.target.value)}
                  className="w-full p-2 border border-gray-300 rounded"
                  placeholder="https://ejemplo.com/imagen.jpg"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Ingresa la URL de una imagen o sube el archivo al servidor
                </p>
              </div>
              
              {/* Vista previa de nueva imagen */}
              {nuevaImagenUrl && (
                <div className="text-center">
                  <p className="text-sm text-gray-600 mb-2">Vista previa:</p>
                  <img
                    src={nuevaImagenUrl}
                    alt="Vista previa"
                    className="w-32 h-32 mx-auto object-cover border border-gray-300 rounded"
                    onError={(e) => {
                      e.target.style.display = 'none';
                      e.target.nextElementSibling.style.display = 'block';
                    }}
                  />
                  <div style={{display: 'none'}} className="w-32 h-32 mx-auto bg-red-100 border border-red-300 rounded flex items-center justify-center">
                    <span className="text-red-500 text-xs">Error al cargar imagen</span>
                  </div>
                </div>
              )}
              
              {/* Opciones adicionales */}
              <div className="bg-blue-50 p-3 rounded text-sm">
                <p className="font-medium text-blue-800 mb-2">💡 Opciones para agregar imágenes:</p>
                <ul className="text-blue-700 space-y-1">
                  <li>• Usa una URL directa de imagen</li>
                  <li>• Sube archivos a <code>/public/img/productos/</code></li>
                  <li>• Nombra el archivo como: <code>{productoSeleccionado.barcode || productoSeleccionado.codigo || 'codigo'}.jpg</code></li>
                  <li>• Formatos recomendados: JPG, PNG, SVG</li>
                </ul>
              </div>
            </div>
            
            <div className="flex justify-end space-x-2 mt-6">
              <button
                onClick={() => setModalEditarImagen(false)}
                className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Cancelar
              </button>
              <button
                onClick={guardarNuevaImagen}
                className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                disabled={!nuevaImagenUrl.trim()}
              >
                Guardar Imagen
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InventarioInteligente; 