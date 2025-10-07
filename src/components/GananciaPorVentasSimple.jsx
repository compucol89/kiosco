import React from 'react';
import { DollarSign, TrendingUp, Package, Calculator } from 'lucide-react';

// Componente CORREGIDO que muestra la ganancia REAL por ventas (Utilidad Bruta)
const GananciaPorVentasSimple = ({ ventasDetalladas }) => {
  if (!ventasDetalladas || ventasDetalladas.length === 0) return null;

  // Calcular ganancia CORRECTA por venta usando fórmulas SpaceX Grade
  const ventasConGanancia = ventasDetalladas.map(venta => {
    // ACCESO CORRECTO A LOS DATOS según la estructura real
    const producto = venta?.productos?.[0] || {};
    const precioVenta = producto?.precio_venta_unitario || venta?.precio_unitario || 0;
    const costoProducto = producto?.costo_unitario || venta?.costo_unitario || 0;
    const descuentoAplicado = venta?.descuento_aplicado || 0;
    
    // FÓRMULAS CORRECTAS AFIP Compatible
    const ingresoNeto = precioVenta - descuentoAplicado; // Precio - Descuentos
    const utilidadBruta = ingresoNeto - costoProducto;   // Ingreso Neto - Costo del Producto
    const margenPorcentual = ingresoNeto > 0 ? (utilidadBruta / ingresoNeto) * 100 : 0;
    
    return {
      ...venta,
      ingreso_neto: ingresoNeto,
      utilidad_bruta: utilidadBruta,
      margen_porcentual: margenPorcentual,
      precio_venta: precioVenta,
      costo_producto: costoProducto,
      descuento: descuentoAplicado
    };
  });

  const totalUtilidadBruta = ventasConGanancia.reduce((sum, v) => sum + v.utilidad_bruta, 0);
  const totalIngresosNetos = ventasConGanancia.reduce((sum, v) => sum + v.ingreso_neto, 0);
  const utilidadPromedioPorVenta = ventasConGanancia.length > 0 ? totalUtilidadBruta / ventasConGanancia.length : 0;
  const margenPromedioGeneral = totalIngresosNetos > 0 ? (totalUtilidadBruta / totalIngresosNetos) * 100 : 0;

  return (
    <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-8 mb-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h3 className="text-2xl font-bold text-gray-900 flex items-center">
            <Calculator className="w-7 h-7 mr-3 text-green-600" />
            💰 UTILIDAD BRUTA POR VENTAS
          </h3>
          <p className="text-gray-600 mt-2">
            Ganancia real de productos vendidos (sin incluir gastos fijos)
          </p>
        </div>
        <div className="flex items-center space-x-3">
          <div className="px-4 py-2 bg-green-100 text-green-800 rounded-lg text-sm font-medium">
            ✅ {ventasConGanancia.length} ventas procesadas
          </div>
          <div className="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium">
            🔐 AFIP Compatible
          </div>
        </div>
      </div>

      {/* KPIs Principales */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {/* Total Utilidad Bruta */}
        <div className="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-6 border-2 border-emerald-200 text-center">
          <div className="text-4xl font-bold text-emerald-800 mb-2">
            ${totalUtilidadBruta.toLocaleString()}
          </div>
          <div className="text-sm font-semibold text-emerald-700 mb-1">
            Utilidad Bruta Total
          </div>
          <div className="text-xs text-emerald-600">
            Ingresos Netos - Costos de Productos
          </div>
        </div>

        {/* Utilidad Promedio por Venta */}
        <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border-2 border-blue-200 text-center">
          <div className="text-4xl font-bold text-blue-800 mb-2">
            ${utilidadPromedioPorVenta.toLocaleString()}
          </div>
          <div className="text-sm font-semibold text-blue-700 mb-1">
            Utilidad por Venta
          </div>
          <div className="text-xs text-blue-600">
            Promedio por transacción
          </div>
        </div>

        {/* Margen Promedio */}
        <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border-2 border-purple-200 text-center">
          <div className="text-4xl font-bold text-purple-800 mb-2">
            {margenPromedioGeneral.toFixed(1)}%
          </div>
          <div className="text-sm font-semibold text-purple-700 mb-1">
            Margen Promedio
          </div>
          <div className="text-xs text-purple-600">
            (Utilidad ÷ Ingreso Neto) × 100
          </div>
        </div>

        {/* Ingresos Netos */}
        <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border-2 border-green-200 text-center">
          <div className="text-4xl font-bold text-green-800 mb-2">
            ${totalIngresosNetos.toLocaleString()}
          </div>
          <div className="text-sm font-semibold text-green-700 mb-1">
            Ingresos Netos
          </div>
          <div className="text-xs text-green-600">
            Ventas - Descuentos Aplicados
          </div>
        </div>
      </div>

      {/* Tabla Detalle por Venta */}
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-50 border-b-2 border-gray-200">
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Venta ID</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Método Pago</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Precio Venta</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Descuento</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ingreso Neto</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Costo Producto</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Utilidad Bruta</th>
              <th className="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Margen %</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {ventasConGanancia.map((venta, index) => (
              <tr key={index} className="hover:bg-gray-50 transition-colors">
                <td className="px-4 py-3 text-sm font-medium text-gray-900">
                  #{venta?.venta_id || (index + 1)}
                </td>
                <td className="px-4 py-3 text-sm">
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                    venta?.metodo_pago === 'efectivo' ? 'bg-green-100 text-green-800' :
                    venta?.metodo_pago === 'tarjeta' ? 'bg-blue-100 text-blue-800' :
                    venta?.metodo_pago === 'transferencia' ? 'bg-purple-100 text-purple-800' :
                    'bg-orange-100 text-orange-800'
                  }`}>
                    {venta?.metodo_pago || 'N/A'}
                  </span>
                </td>
                <td className="px-4 py-3 text-sm font-medium text-blue-600">
                  ${venta.precio_venta.toLocaleString()}
                </td>
                <td className="px-4 py-3 text-sm font-medium text-orange-600">
                  ${venta.descuento.toLocaleString()}
                </td>
                <td className="px-4 py-3 text-sm font-bold text-green-600">
                  ${venta.ingreso_neto.toLocaleString()}
                </td>
                <td className="px-4 py-3 text-sm font-medium text-red-600">
                  ${venta.costo_producto.toLocaleString()}
                </td>
                <td className={`px-4 py-3 text-sm font-bold ${
                  venta.utilidad_bruta >= 0 ? 'text-emerald-600' : 'text-red-600'
                }`}>
                  ${venta.utilidad_bruta.toLocaleString()}
                </td>
                <td className={`px-4 py-3 text-sm font-bold ${
                  venta.margen_porcentual >= 0 ? 'text-emerald-600' : 'text-red-600'
                }`}>
                  {venta.margen_porcentual.toFixed(1)}%
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Fórmulas Aplicadas */}
      <div className="mt-8 bg-blue-50 rounded-lg p-6 border-2 border-blue-200">
        <h4 className="text-lg font-bold text-blue-900 mb-4 flex items-center">
          <Calculator className="w-5 h-5 mr-2" />
          📐 Fórmulas Matemáticas Aplicadas
        </h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div className="bg-white p-4 rounded border">
            <strong className="text-blue-800">Ingreso Neto por Venta:</strong><br />
            <code className="text-blue-600">IngresoNeto = PrecioVenta - DescuentoAplicado</code>
          </div>
          <div className="bg-white p-4 rounded border">
            <strong className="text-blue-800">Utilidad Bruta por Venta:</strong><br />
            <code className="text-blue-600">UtilidadBruta = IngresoNeto - CostoProducto</code>
          </div>
          <div className="bg-white p-4 rounded border">
            <strong className="text-blue-800">Margen Porcentual:</strong><br />
            <code className="text-blue-600">Margen = (UtilidadBruta ÷ IngresoNeto) × 100</code>
          </div>
          <div className="bg-white p-4 rounded border">
            <strong className="text-blue-800">ROI Individual:</strong><br />
            <code className="text-blue-600">ROI = (UtilidadBruta ÷ CostoProducto) × 100</code>
          </div>
        </div>
      </div>

      {/* Aclaración Importante */}
      <div className="mt-6 p-6 bg-yellow-50 border-2 border-yellow-200 rounded-lg">
        <div className="flex items-start">
          <Package className="w-6 h-6 text-yellow-600 mr-3 mt-1" />
          <div>
            <h5 className="font-bold text-yellow-800 mb-2">💡 Importante: Diferencia entre Utilidad Bruta y Ganancia Neta</h5>
            <div className="text-sm text-yellow-700 space-y-2">
              <p><strong>UTILIDAD BRUTA:</strong> Es lo que se muestra aquí. Son las ganancias por la venta de productos (Precio - Costo del producto).</p>
              <p><strong>GANANCIA NETA:</strong> Es la utilidad bruta MENOS los gastos fijos del negocio (alquiler, sueldos, servicios, etc.).</p>
              <p><strong>Fórmula Final:</strong> Ganancia Neta = Utilidad Bruta - Gastos Fijos Diarios</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GananciaPorVentasSimple;