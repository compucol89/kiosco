import React from 'react';
import { CheckCircle, XCircle, Clock } from 'lucide-react';
import { useCaja } from '../contexts/CajaContext';

const CajaStatusIndicator = () => {
  const { cajaAbierta, turnoActivo, loading } = useCaja();

  if (loading) {
    return (
      <div className="flex items-center px-3 py-1 bg-gray-100 rounded-lg">
        <Clock className="w-4 h-4 text-gray-500 mr-2 animate-spin" />
        <span className="text-sm text-gray-600">Verificando caja...</span>
      </div>
    );
  }

  if (cajaAbierta && turnoActivo) {
    return (
      <div className="flex items-center px-3 py-1 bg-green-100 border border-green-300 rounded-lg">
        <CheckCircle className="w-4 h-4 text-green-600 mr-2" />
        <div className="text-sm">
          <span className="font-medium text-green-800">Caja Abierta</span>
          <div className="text-xs text-green-600">
            ${parseFloat(turnoActivo.monto_apertura || 0).toLocaleString('es-AR')} inicial
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center px-3 py-1 bg-red-100 border border-red-300 rounded-lg">
      <XCircle className="w-4 h-4 text-red-600 mr-2" />
      <div className="text-sm">
        <span className="font-medium text-red-800">Caja Cerrada</span>
        <div className="text-xs text-red-600">Debe abrir caja</div>
      </div>
    </div>
  );
};

export default CajaStatusIndicator;
