/**
 * src/hooks/useCajaLogic.js
 * Hook personalizado para toda la lógica de negocio de la caja
 * Separa cálculos y validaciones del componente UI
 * RELEVANT FILES: src/components/GestionCajaMejorada.jsx, src/hooks/useCajaApi.js
 */

import { useMemo } from 'react';

export const useCajaLogic = (datosControl) => {
  
  // 🔒 CÁLCULO OPTIMIZADO: Efectivo esperado
  const efectivoEsperado = useMemo(() => {
    if (!datosControl) return 0;

    const montoInicial = parseFloat(datosControl.monto_inicial || 0);
    const ventasEfectivo = parseFloat(datosControl.ventas_efectivo_reales || 0);
    const totalEntradas = parseFloat(datosControl.total_entradas_efectivo || 0);
    const salidasEfectivo = parseFloat(datosControl.salidas_efectivo_reales || 0);

    return montoInicial + totalEntradas - salidasEfectivo;
  }, [datosControl]);

  // 📊 CÁLCULO OPTIMIZADO: Resumen de ventas
  const resumenVentas = useMemo(() => {
    if (!datosControl) return null;

    const efectivo = parseFloat(datosControl.ventas_efectivo_reales || 0);
    const transferencia = parseFloat(datosControl.ventas_transferencia_reales || 0);
    const tarjeta = parseFloat(datosControl.ventas_tarjeta_reales || 0);
    const qr = parseFloat(datosControl.ventas_qr_reales || 0);

    const total = efectivo + transferencia + tarjeta + qr;

    return {
      efectivo,
      transferencia,
      tarjeta,
      qr,
      total,
      porcentajes: {
        efectivo: total > 0 ? (efectivo / total * 100) : 0,
        transferencia: total > 0 ? (transferencia / total * 100) : 0,
        tarjeta: total > 0 ? (tarjeta / total * 100) : 0,
        qr: total > 0 ? (qr / total * 100) : 0
      }
    };
  }, [datosControl]);

  // 💰 CÁLCULO OPTIMIZADO: Flujo de efectivo
  const flujoEfectivo = useMemo(() => {
    if (!datosControl) return null;

    const inicial = parseFloat(datosControl.monto_inicial || 0);
    const entradas = parseFloat(datosControl.total_entradas_efectivo || 0);
    const salidas = parseFloat(datosControl.salidas_efectivo_reales || 0);
    const actual = inicial + entradas - salidas;

    return {
      inicial,
      entradas,
      salidas,
      actual,
      variacion: actual - inicial
    };
  }, [datosControl]);

  // ⏰ CÁLCULO OPTIMIZADO: Tiempo de turno
  const tiempoTurno = useMemo(() => {
    if (!datosControl?.fecha_apertura) return null;

    const apertura = new Date(datosControl.fecha_apertura);
    const ahora = new Date();
    const diferencia = Math.floor((ahora - apertura) / (1000 * 60)); // minutos

    return {
      minutos: diferencia,
      horas: Math.floor(diferencia / 60),
      minutosRestantes: diferencia % 60,
      formateado: `${Math.floor(diferencia / 60)}h ${diferencia % 60}m`
    };
  }, [datosControl?.fecha_apertura]);

  // 📈 CÁLCULO OPTIMIZADO: Métricas de rendimiento
  const metricas = useMemo(() => {
    if (!datosControl || !tiempoTurno) return null;

    const totalVentas = resumenVentas?.total || 0;
    const ventasPorHora = tiempoTurno.minutos > 0 ? (totalVentas / tiempoTurno.minutos * 60) : 0;

    return {
      ventasPorHora: Math.round(ventasPorHora * 100) / 100,
      tiempoTranscurrido: tiempoTurno.formateado,
      efectivoEsperado,
      diferenciaPotencial: 0 // Se calculará en el cierre
    };
  }, [datosControl, tiempoTurno, resumenVentas, efectivoEsperado]);

  // ✅ VALIDACIÓN: Datos para cierre
  const validarDatosCierre = (efectivoContado) => {
    const efectivoNum = parseFloat(efectivoContado);
    
    if (isNaN(efectivoNum) || efectivoNum < 0) {
      return { valido: false, error: 'Debe ingresar un monto válido mayor o igual a 0' };
    }

    const diferencia = efectivoNum - efectivoEsperado;
    const tolerancia = 10; // $10 de tolerancia

    return {
      valido: true,
      efectivoContado: efectivoNum,
      efectivoEsperado,
      diferencia,
      esDiferenciaSignificativa: Math.abs(diferencia) > tolerancia,
      tipo: diferencia > 0 ? 'sobrante' : diferencia < 0 ? 'faltante' : 'exacto'
    };
  };

  // 🎯 FORMATEO: Moneda argentina
  const formatearMoneda = (valor) => {
    return parseFloat(valor || 0).toLocaleString('es-AR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  // 📋 PREPARAR: Datos para el resumen
  const prepararResumen = () => {
    return {
      turno: {
        id: datosControl?.id,
        fechaApertura: datosControl?.fecha_apertura,
        cajero: datosControl?.cajero_nombre,
        tiempoTranscurrido: tiempoTurno?.formateado
      },
      financiero: {
        montoInicial: datosControl?.monto_inicial || 0,
        efectivoEsperado,
        flujoEfectivo,
        resumenVentas
      },
      metricas
    };
  };

  return {
    efectivoEsperado,
    resumenVentas,
    flujoEfectivo,
    tiempoTurno,
    metricas,
    validarDatosCierre,
    formatearMoneda,
    prepararResumen
  };
};














