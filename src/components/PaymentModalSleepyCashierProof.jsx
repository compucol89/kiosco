import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
    CreditCard, Banknote, Smartphone, QrCode, 
    X, CheckCircle, DollarSign
} from 'lucide-react';
import descuentosService from '../services/descuentosService';

/**
 * 🚀 MODAL DE PAGO McDONALD'S GRADE
 * 
 * DISEÑADO PARA VENDEDORES CANSADOS:
 * - CERO scroll (garantizado)
 * - Total IMPOSIBLE de ignorar
 * - 2 clicks máximo por venta
 * - Shortcuts de teclado obvios
 * - SIN campos manuales innecesarios
 */
const PaymentModalSleepyCashierProof = ({ 
    isOpen, 
    onClose, 
    totalAmount, 
    onPaymentComplete,
    cartItems = [],
    discountInfo = null 
}) => {
    // Estados ultra-simples
    const [selectedMethod, setSelectedMethod] = useState('efectivo');
    const [cashReceived, setCashReceived] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [error, setError] = useState('');
    
    // Referencias para shortcuts
    const modalRef = useRef(null);
    
    // 💰 CÁLCULO DE DESCUENTOS (desde configuración real)
    const [descuentosConfig, setDescuentosConfig] = useState({
        efectivo: 0,
        transferencia: 0,
        tarjeta: 0,
        qr: 0
    });
    
    // 🧮 CÁLCULOS PRINCIPALES (ultra-simplificados)
    const calculateTotalsWithDiscount = useCallback(() => {
        const originalTotal = parseFloat(totalAmount) || 0;
        const discountPercentage = descuentosConfig[selectedMethod] || 0;
        
        let eligibleSubtotal = 0;
        cartItems.forEach(item => {
            const isEligible = item.aplica_descuento_forma_pago !== false && 
                             item.aplica_descuento_forma_pago !== 0 && 
                             item.aplica_descuento_forma_pago !== "0";
            if (isEligible) {
                eligibleSubtotal += (item.price * item.quantity);
            }
        });
        
        const discountAmount = eligibleSubtotal * (discountPercentage / 100);
        const finalTotal = originalTotal - discountAmount;
        
        return {
            originalTotal,
            discountPercentage,
            discountAmount,
            finalTotal: Math.max(0, finalTotal),
            hasDiscount: discountPercentage > 0
        };
    }, [totalAmount, selectedMethod, descuentosConfig, cartItems]);
    
    // 🔄 CARGAR DESCUENTOS AL ABRIR EL MODAL
    useEffect(() => {
        if (isOpen) {
            const cargarDescuentos = async () => {
                try {
                    const resultado = await descuentosService.obtenerDescuentos();
                    if (resultado.success) {
                        setDescuentosConfig(resultado.descuentos);
                    }
                } catch (error) {
                    console.error('Error cargando descuentos:', error);
                    // Mantener valores por defecto (0) si hay error
                }
            };
            
            cargarDescuentos();
        }
    }, [isOpen]);
    
    const totals = calculateTotalsWithDiscount();
    const received = parseFloat(cashReceived) || 0;
    const change = received - totals.finalTotal;
    const isValidAmount = received >= totals.finalTotal;
    
    // 💰 SUGERENCIAS INTELIGENTES SOLO PARA EFECTIVO
    const generateSmartSuggestions = useCallback((amount) => {
        const total = parseFloat(amount) || 0;
        if (total <= 0) return [];
        
        let suggestions = [];
        
        if (total < 1000) {
            const roundedUp = Math.ceil(total / 100) * 100;
            suggestions = [roundedUp, 1000, 2000, 5000];
        } else if (total < 5000) {
            const roundedUp = Math.ceil(total / 500) * 500;
            suggestions = [roundedUp, 5000, 10000, 20000];
        } else {
            const roundedUp = Math.ceil(total / 1000) * 1000;
            const next5k = Math.ceil(total / 5000) * 5000;
            suggestions = [roundedUp, next5k, 50000, 100000];
        }
        
        return [...new Set(suggestions)]
            .filter(amount => amount >= total)
            .slice(0, 4)
            .sort((a, b) => a - b);
    }, []);
    
    const suggestions = generateSmartSuggestions(totals.finalTotal);
    
    // ⌨️ SHORTCUTS DE TECLADO ULTRA-SIMPLES
    useEffect(() => {
        if (!isOpen) return;
        
        const handleKeyPress = (e) => {
            // F6-F8 para cambio de método
            if (e.key === 'F6') {
                e.preventDefault();
                setSelectedMethod('tarjeta');
            }
            if (e.key === 'F7') {
                e.preventDefault();
                setSelectedMethod('transferencia');
            }
            if (e.key === 'F8') {
                e.preventDefault();
                setSelectedMethod('qr');
            }
            
            // Enter para confirmar
            if (e.key === 'Enter' && (isValidAmount || selectedMethod !== 'efectivo')) {
                e.preventDefault();
                handlePayment();
            }
            
            // Escape para cerrar
            if (e.key === 'Escape') {
                e.preventDefault();
                onClose();
            }
        };
        
        document.addEventListener('keydown', handleKeyPress);
        return () => document.removeEventListener('keydown', handleKeyPress);
    }, [isOpen, isValidAmount, selectedMethod]);
    
    // 💳 MÉTODOS DE PAGO (ultra-simples)
    const paymentMethods = [
        {
            id: 'efectivo',
            label: 'EFECTIVO',
            icon: Banknote,
            color: 'green',
            shortcut: '',
            hasDiscount: descuentosConfig.efectivo > 0
        },
        {
            id: 'tarjeta',
            label: 'TARJETA',
            icon: CreditCard,
            color: 'blue',
            shortcut: 'F6',
            hasDiscount: descuentosConfig.tarjeta > 0
        },
        {
            id: 'transferencia',
            label: 'TRANSFERENCIA',
            icon: Smartphone,
            color: 'purple',
            shortcut: 'F7',
            hasDiscount: descuentosConfig.transferencia > 0
        },
        {
            id: 'qr',
            label: 'QR/DIGITAL',
            icon: QrCode,
            color: 'indigo',
            shortcut: 'F8',
            hasDiscount: descuentosConfig.qr > 0
        }
    ];
    
    // 💰 PROCESAMIENTO DE PAGO (ultra-simple)
    const handlePayment = useCallback(async () => {
        if (!isValidAmount && selectedMethod === 'efectivo') {
            setError('Monto insuficiente');
            return;
        }
        
        setIsProcessing(true);
        setError('');
        
        try {
            const paymentData = {
                method: selectedMethod,
                total: totals.finalTotal,
                originalTotal: totals.originalTotal,
                discountAmount: totals.discountAmount,
                discountPercentage: totals.discountPercentage,
                received: selectedMethod === 'efectivo' ? received : totals.finalTotal,
                change: selectedMethod === 'efectivo' ? Math.max(0, change) : 0,
                cartItems,
                discountInfo
            };
            
            await onPaymentComplete(paymentData);
            
        } catch (error) {
            setError(error.message || 'Error al procesar');
        } finally {
            setIsProcessing(false);
        }
    }, [totals, selectedMethod, received, change, isValidAmount, cartItems, discountInfo, onPaymentComplete]);
    
    // 💰 HANDLE SUGGESTION CLICK
    const handleSuggestionClick = useCallback((amount) => {
        setCashReceived(amount.toString());
        setError('');
    }, []);
    
    if (!isOpen) return null;
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
            <div 
                ref={modalRef}
                className="bg-white rounded-xl w-full max-w-2xl shadow-2xl relative"
            >
                {/* ❌ BOTÓN CERRAR MINIMALISTA */}
                <div className="absolute top-4 right-4 z-10">
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>
                
                {/* 💵 TOTAL A COBRAR - COMPACTO */}
                <div className="p-4 bg-green-50 border-b-2 border-green-200 rounded-t-xl">
                    <div className="text-center">
                        <div className="text-lg font-bold text-green-800 mb-2">
                            💵 TOTAL A COBRAR
                        </div>
                        <div className="text-5xl font-black text-green-900 bg-green-200 rounded-lg py-4 px-6 inline-block border-4 border-green-400">
                            ${totals.finalTotal.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                        </div>
                        {totals.hasDiscount && (
                            <div className="mt-3 bg-yellow-100 rounded-lg p-2 inline-block">
                                <div className="text-sm font-bold text-yellow-800">
                                    🎉 DESCUENTO {totals.discountPercentage}% APLICADO
                                </div>
                                <div className="text-xs text-yellow-700">
                                    Precio original: ${totals.originalTotal.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
                
                {/* 💳 MÉTODOS DE PAGO - COMPACTOS */}
                <div className="p-3">
                    <h3 className="text-base font-bold text-gray-900 mb-3 text-center">
                        MÉTODO DE PAGO:
                    </h3>
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-3">
                        {paymentMethods.map(method => {
                            const isSelected = selectedMethod === method.id;
                            const colorClasses = {
                                green: isSelected ? 'bg-green-500 border-green-600 text-white' : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-green-100',
                                blue: isSelected ? 'bg-blue-500 border-blue-600 text-white' : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-blue-100',
                                purple: isSelected ? 'bg-purple-500 border-purple-600 text-white' : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-purple-100',
                                indigo: isSelected ? 'bg-indigo-500 border-indigo-600 text-white' : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-indigo-100'
                            };
                            
                            return (
                                <button
                                    key={method.id}
                                    onClick={() => setSelectedMethod(method.id)}
                                    className={`p-3 border-2 rounded-lg transition-all duration-200 ${colorClasses[method.color]} ${isSelected ? 'transform scale-105 shadow-lg' : ''}`}
                                    style={{ minHeight: '60px' }}
                                >
                                    <div className="flex flex-col items-center space-y-1">
                                        <method.icon className="w-5 h-5" />
                                        <div className="text-xs font-bold text-center">{method.label}</div>
                                        {isSelected && (
                                            <CheckCircle className="w-4 h-4" />
                                        )}
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                    
                    {/* 💰 SECCIÓN ESPECÍFICA POR MÉTODO */}
                    {selectedMethod === 'efectivo' ? (
                        <div className="bg-blue-50 rounded-lg p-3 mb-3">
                            {/* 💳 CAMPO MANUAL DE EFECTIVO - COMPACTO */}
                            <div className="bg-white rounded-lg border-2 border-green-400 p-3 mb-2 shadow-lg">
                                <label className="block text-sm font-bold text-green-800 mb-2 text-center">
                                    💰 ESCRIBE EL MONTO RECIBIDO
                                </label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-green-600 font-bold text-lg">$</span>
                                    <input
                                        type="text"
                                        value={cashReceived}
                                        onChange={(e) => {
                                            const valor = e.target.value;
                                            // Permitir solo números y punto decimal
                                            if (valor === '' || /^\d*\.?\d*$/.test(valor)) {
                                                setCashReceived(valor);
                                                setError('');
                                            }
                                        }}
                                        onFocus={(e) => e.target.select()}
                                        inputMode="decimal"
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' || e.key === 'Tab') {
                                                e.preventDefault();
                                                const amount = parseFloat(e.target.value);
                                                if (amount >= totals.finalTotal) {
                                                    // Enfocar el botón de procesar pago
                                                    const processButton = document.querySelector('[data-payment-process]');
                                                    if (processButton) processButton.focus();
                                                } else {
                                                    setError(`Monto mínimo: $${totals.finalTotal.toLocaleString('es-AR')}`);
                                                }
                                            }
                                        }}
                                        className="w-full pl-10 pr-4 py-2 text-center text-xl font-bold border-2 border-green-400 rounded-lg focus:ring-2 focus:ring-green-300 focus:border-green-500 bg-white"
                                        placeholder={`${totals.finalTotal.toLocaleString('es-AR')}`}
                                        autoComplete="off"
                                        autoFocus
                                    />
                                </div>
                                <p className="text-xs text-green-600 mt-1 text-center">
                                    ⌨️ Presiona ENTER para continuar
                                </p>
                            </div>
                            
                            <h4 className="text-base font-bold text-blue-900 mb-2 text-center">
                                📱 O USA MONTOS RÁPIDOS (Click para usar)
                            </h4>
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-2">
                                {suggestions.map((amount) => (
                                    <button
                                        key={amount}
                                        onClick={() => handleSuggestionClick(amount)}
                                        className={`${amount.toString() === cashReceived 
                                            ? 'bg-green-500 text-white transform scale-105' 
                                            : 'bg-white text-blue-900 hover:bg-blue-100'
                                        } border-2 border-blue-300 rounded-lg p-3 text-center transition-all font-bold`}
                                    >
                                        <div className="text-lg font-black">
                                            ${amount.toLocaleString()}
                                        </div>
                                        {amount.toString() === cashReceived && (
                                            <div className="text-xs mt-1">✅ SELECCIONADO</div>
                                        )}
                                    </button>
                                ))}
                            </div>
                            
                            {/* CAMBIO DISPLAY */}
                            {received > 0 && (
                                <div className="text-center">
                                    {received < totals.finalTotal ? (
                                        <div className="bg-red-100 rounded-lg p-3">
                                            <div className="text-red-800 font-bold">
                                                ⚠️ FALTAN: ${(totals.finalTotal - received).toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                            </div>
                                        </div>
                                    ) : received > totals.finalTotal ? (
                                        <div className="bg-green-100 rounded-lg p-3">
                                            <div className="text-green-800 font-bold">
                                                💰 CAMBIO: ${change.toLocaleString('es-AR', {minimumFractionDigits: 2})}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="bg-blue-100 rounded-lg p-3">
                                            <div className="text-blue-800 font-bold">
                                                ✅ MONTO EXACTO
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="bg-blue-50 rounded-lg p-6 text-center mb-6">
                            <div className="max-w-md mx-auto">
                                <div className="text-lg font-bold text-blue-900 mb-2">
                                    {selectedMethod === 'tarjeta' && '💳 Inserte o pase la tarjeta'}
                                    {selectedMethod === 'transferencia' && '📱 Cliente realizará transferencia'}
                                    {selectedMethod === 'qr' && '📱 Cliente escaneará con su app'}
                                </div>
                                <div className="text-sm text-blue-700">
                                    {selectedMethod === 'tarjeta' && 'Procesar cuando el terminal confirme'}
                                    {selectedMethod === 'transferencia' && 'Confirmar cuando reciba el dinero'}
                                    {selectedMethod === 'qr' && 'Confirmar cuando reciba notificación'}
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {/* ⚠️ ERROR DISPLAY */}
                    {error && (
                        <div className="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-center">
                            <span className="text-red-800 font-bold">⚠️ {error}</span>
                        </div>
                    )}
                    
                </div>
                
                {/* 🎯 BOTONES DE ACCIÓN - COMPACTOS */}
                <div className="p-4 border-t border-gray-200 bg-white rounded-b-xl">
                    <div className="flex space-x-4">
                        <button
                            onClick={onClose}
                            disabled={isProcessing}
                            className="flex-1 py-4 border-3 border-red-300 bg-red-50 text-red-800 rounded-lg hover:bg-red-100 disabled:opacity-50 font-bold text-lg transition-colors"
                        >
                            ❌ CANCELAR
                        </button>
                        
                        <button
                            onClick={handlePayment}
                            data-payment-process
                            disabled={
                                isProcessing || 
                                (selectedMethod === 'efectivo' && !isValidAmount)
                            }
                            className={`flex-2 py-4 rounded-lg font-black text-lg transition-all duration-200 ${
                                isProcessing ? 
                                'bg-gray-400 text-white cursor-not-allowed' :
                                (isValidAmount || selectedMethod !== 'efectivo') ?
                                'bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700 transform hover:scale-105 shadow-lg' :
                                'bg-gray-300 text-gray-600 cursor-not-allowed'
                            }`}
                            style={{ flex: '2' }}
                        >
                            {isProcessing ? (
                                <div className="flex items-center justify-center space-x-2">
                                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                                    <span>⏳ PROCESANDO...</span>
                                </div>
                            ) : (
                                <>
                                    ✅ CONFIRMAR PAGO
                                    {selectedMethod !== 'efectivo' && (
                                        <div className="text-sm opacity-80 mt-1">
                                            CON {paymentMethods.find(m => m.id === selectedMethod)?.label}
                                        </div>
                                    )}
                                </>
                            )}
                        </button>
                    </div>
                    
                    {/* 📱 SHORTCUTS REMINDER */}
                    <div className="mt-3 text-center">
                        <div className="bg-gray-100 rounded-lg p-2">
                            <p className="text-xs text-gray-600 font-medium">
                                💡 <strong>SHORTCUTS:</strong> F6=Tarjeta | F7=Transferencia | F8=QR | Enter=Confirmar | Esc=Cancelar
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PaymentModalSleepyCashierProof;

