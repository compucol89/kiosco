/**
 * src/components/ModalAperturaCaja.jsx
 * Modal optimizado para apertura de caja con verificación manual
 * Componente independiente y reutilizable
 * RELEVANT FILES: src/components/GestionCajaMejorada.jsx, src/hooks/useCajaApi.js
 */

import React, { useState } from 'react';
import { 
  Unlock, 
  DollarSign, 
  User, 
  Calendar, 
  Clock, 
  FileText,
  AlertTriangle,
  CheckCircle,
  X
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useCajaApi } from '../hooks/useCajaApi';

const ModalAperturaCaja = ({ isOpen, onClose, onSuccess, ultimoCierre }) => {
  const { user } = useAuth();
  const { abrirCaja, loading } = useCajaApi();
  
  const [datos, setDatos] = useState({
    observaciones: '',
    fecha: new Date().toLocaleDateString('es-AR'),
    hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
    cajero: user?.nombre || 'Usuario'
  });

  const [mostrarVerificacion, setMostrarVerificacion] = useState(false);
  const [datosVerificacion, setDatosVerificacion] = useState(null);
  const [efectivoContado, setEfectivoContado] = useState('');

  if (!isOpen) return null;

  // 🔄 FASE 1: Verificar si requiere validación manual
  const handleIniciarApertura = async () => {
    try {
      const resultado = await abrirCaja({
        notas: datos.observaciones
      });

      if (resultado.requiereVerificacion) {
        setDatosVerificacion(resultado);
        setMostrarVerificacion(true);
      } else {
        onSuccess(resultado);
        handleClose();
      }
    } catch (error) {
      alert('❌ Error al iniciar apertura: ' + error.message);
    }
  };

  // ✅ FASE 2: Completar apertura con verificación
  const handleCompletarApertura = async () => {
    try {
      const efectivoNum = parseFloat(efectivoContado);
      if (isNaN(efectivoNum) || efectivoNum < 0) {
        alert('❌ Por favor ingrese un monto válido');
        return;
      }

      const resultado = await abrirCaja({
        notas: datos.observaciones,
        efectivo_contado: efectivoNum
      });

      onSuccess(resultado);
      handleClose();
      
      // Mostrar resumen de la verificación
      const diferencia = efectivoNum - datosVerificacion.efectivo_esperado;
      alert(
        `✅ CAJA ABIERTA EXITOSAMENTE\n\n` +
        `💰 Efectivo esperado: $${parseFloat(datosVerificacion.efectivo_esperado).toLocaleString('es-AR')}\n` +
        `💰 Efectivo contado: $${efectivoNum.toLocaleString('es-AR')}\n` +
        `⚖️ Diferencia: $${diferencia.toLocaleString('es-AR')} ${diferencia === 0 ? '(Exacto)' : diferencia > 0 ? '(Sobrante)' : '(Faltante)'}`
      );
    } catch (error) {
      alert('❌ Error al completar apertura: ' + error.message);
    }
  };

  const handleClose = () => {
    setMostrarVerificacion(false);
    setDatosVerificacion(null);
    setEfectivoContado('');
    setDatos({
      observaciones: '',
      fecha: new Date().toLocaleDateString('es-AR'),
      hora: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
      cajero: user?.nombre || 'Usuario'
    });
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
        
        {!mostrarVerificacion ? (
          // 🚀 MODAL INICIAL DE APERTURA
          <>
            <div className="bg-gradient-to-r from-green-600 to-green-700 rounded-t-2xl p-6 text-white">
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <Unlock className="w-8 h-8 mr-3" />
                  <div>
                    <h2 className="text-2xl font-bold">Abrir Caja</h2>
                    <p className="text-green-100">Iniciar nuevo turno</p>
                  </div>
                </div>
                <button 
                  onClick={handleClose}
                  className="text-green-100 hover:text-white transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6">
              {/* Información del último cierre */}
              {ultimoCierre && (
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                  <div className="flex items-center mb-3">
                    <CheckCircle className="w-5 h-5 text-blue-600 mr-2" />
                    <h3 className="font-semibold text-blue-800">Último Cierre</h3>
                  </div>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <p className="text-blue-600">Turno:</p>
                      <p className="font-semibold">#{ultimoCierre.id}</p>
                    </div>
                    <div>
                      <p className="text-blue-600">Efectivo:</p>
                      <p className="font-semibold">${parseFloat(ultimoCierre.efectivo_teorico || 0).toLocaleString('es-AR')}</p>
                    </div>
                    <div>
                      <p className="text-blue-600">Fecha:</p>
                      <p className="font-semibold">{ultimoCierre.fecha_formateada}</p>
                    </div>
                    <div>
                      <p className="text-blue-600">Cajero:</p>
                      <p className="font-semibold">{ultimoCierre.cajero_nombre}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Información del turno actual */}
              <div className="space-y-4 mb-6">
                <div className="grid grid-cols-2 gap-4">
                  <div className="flex items-center text-sm text-gray-600">
                    <Calendar className="w-4 h-4 mr-2" />
                    <span>{datos.fecha}</span>
                  </div>
                  <div className="flex items-center text-sm text-gray-600">
                    <Clock className="w-4 h-4 mr-2" />
                    <span>{datos.hora}</span>
                  </div>
                </div>
                <div className="flex items-center text-sm text-gray-600">
                  <User className="w-4 h-4 mr-2" />
                  <span className="font-medium">{datos.cajero}</span>
                </div>
              </div>

              {/* Observaciones */}
              <div className="mb-6">
                <label className="flex items-center text-sm font-medium text-gray-700 mb-2">
                  <FileText className="w-4 h-4 mr-2" />
                  Observaciones (opcional)
                </label>
                <textarea
                  value={datos.observaciones}
                  onChange={(e) => setDatos({...datos, observaciones: e.target.value})}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                  rows="3"
                  placeholder="Notas sobre el inicio del turno..."
                />
              </div>

              {/* Botones */}
              <div className="flex gap-3">
                <button
                  onClick={handleClose}
                  className="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleIniciarApertura}
                  disabled={loading}
                  className="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 flex items-center justify-center"
                >
                  {loading ? (
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  ) : (
                    <>
                      <Unlock className="w-5 h-5 mr-2" />
                      Abrir Caja
                    </>
                  )}
                </button>
              </div>
            </div>
          </>
        ) : (
          // 🔍 MODAL DE VERIFICACIÓN MANUAL
          <>
            <div className="bg-gradient-to-r from-orange-600 to-orange-700 rounded-t-2xl p-6 text-white">
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <AlertTriangle className="w-8 h-8 mr-3" />
                  <div>
                    <h2 className="text-2xl font-bold">Verificación Manual</h2>
                    <p className="text-orange-100">Contar efectivo físico</p>
                  </div>
                </div>
              </div>
            </div>

            <div className="p-6">
              <div className="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
                <div className="flex items-center mb-3">
                  <DollarSign className="w-5 h-5 text-orange-600 mr-2" />
                  <h3 className="font-semibold text-orange-800">Efectivo Esperado</h3>
                </div>
                <p className="text-2xl font-bold text-orange-600">
                  ${parseFloat(datosVerificacion.efectivo_esperado).toLocaleString('es-AR')}
                </p>
                <p className="text-sm text-orange-600 mt-1">
                  Basado en el último cierre (Turno #{datosVerificacion.ultimo_cierre.id})
                </p>
              </div>

              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  💰 Efectivo contado físicamente:
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={efectivoContado}
                  onChange={(e) => setEfectivoContado(e.target.value)}
                  className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-lg font-semibold"
                  placeholder="0.00"
                  autoFocus
                />
                <p className="text-xs text-gray-500 mt-1">
                  Cuenta físicamente todo el efectivo que hay en la caja
                </p>
              </div>

              <div className="flex gap-3">
                <button
                  onClick={handleClose}
                  className="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleCompletarApertura}
                  disabled={loading || !efectivoContado}
                  className="flex-1 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 flex items-center justify-center"
                >
                  {loading ? (
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  ) : (
                    <>
                      <CheckCircle className="w-5 h-5 mr-2" />
                      Confirmar
                    </>
                  )}
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default ModalAperturaCaja;














