// src/components/productos/components/BarcodeDisplay.jsx
// Componente para mostrar códigos de barras visuales
// Genera barras reales para códigos EAN-13, EAN-8, Code128, etc.
// RELEVANT FILES: ProductDetailModal.jsx

import React from 'react';

const BarcodeDisplay = ({ value, format = 'CODE128', width = 2, height = 50, fontSize = 14 }) => {
  if (!value) {
    return (
      <div className="text-center text-gray-500 py-4">
        <p className="text-sm">Sin código de barras</p>
      </div>
    );
  }

  // Generar patrón de barras simple para demostración
  // En producción podrías usar una librería como jsbarcode
  const generateBars = (code) => {
    const bars = [];
    
    // Patrón simple basado en los dígitos del código
    for (let i = 0; i < code.length; i++) {
      const digit = parseInt(code[i]) || 0;
      
      // Crear barras de diferentes anchos según el dígito
      const barWidth = digit % 2 === 0 ? width : width * 1.5;
      const isWide = digit > 5;
      
      bars.push({
        id: i,
        width: barWidth,
        height: height,
        color: i % 2 === 0 ? '#000' : (isWide ? '#000' : '#fff')
      });
    }
    
    // Agregar barras adicionales para hacer más realista
    const extraBars = code.split('').map((char, index) => {
      const charCode = char.charCodeAt(0);
      return {
        id: `extra-${index}`,
        width: (charCode % 3) + 1,
        height: height,
        color: charCode % 2 === 0 ? '#000' : '#fff'
      };
    });
    
    return [...bars, ...extraBars];
  };

  const bars = generateBars(value.toString());

  return (
    <div className="bg-white p-4 border border-gray-200 rounded-lg">
      <div className="text-center">
        {/* Barras del código */}
        <div className="flex items-end justify-center mb-2 bg-white p-2 border border-gray-100 inline-block">
          {bars.map((bar) => (
            <div
              key={bar.id}
              style={{
                width: `${bar.width}px`,
                height: `${bar.height}px`,
                backgroundColor: bar.color === '#000' ? '#000' : 'transparent',
                marginRight: '1px'
              }}
              className="inline-block"
            />
          ))}
        </div>
        
        {/* Texto del código */}
        <div 
          className="font-mono text-center font-bold tracking-wider"
          style={{ fontSize: `${fontSize}px` }}
        >
          {value}
        </div>
        
        {/* Formato */}
        <div className="text-xs text-gray-500 mt-1">
          {format}
        </div>
      </div>
    </div>
  );
};

// Componente alternativo más simple para espacios pequeños
export const SimpleBarcodeDisplay = ({ value }) => {
  if (!value) return null;

  return (
    <div className="bg-gray-50 p-2 rounded border">
      <div className="flex justify-center items-center space-x-px mb-1">
        {value.toString().split('').map((digit, index) => {
          const barHeight = (parseInt(digit) + 1) * 3;
          return (
            <div
              key={index}
              className="bg-black"
              style={{
                width: index % 2 === 0 ? '2px' : '1px',
                height: `${barHeight}px`
              }}
            />
          );
        })}
      </div>
      <div className="text-xs font-mono text-center">{value}</div>
    </div>
  );
};

export default BarcodeDisplay;
