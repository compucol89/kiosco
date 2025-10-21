// File: src/components/ui/Badge.jsx
// Small badge for status indicators
// Exists to show status/categories in a compact, colored format
// Related files: src/components/ui/Card.jsx

import React from 'react';

/**
 * Badge para indicadores de estado
 * 
 * @param {string} color - Color: gray, green, red, blue, yellow, purple, orange
 * @param {string} size - Tamaño: sm, md, lg
 * @param {boolean} dot - Muestra un punto antes del texto
 * @param {ReactNode} children - Texto del badge
 */
export function Badge({ 
  color = 'gray', 
  size = 'md',
  dot = false,
  className = '',
  children 
}) {
  const colorMap = {
    gray: 'bg-grayn-100 text-grayn-700',
    green: 'bg-green-100 text-green-800',
    red: 'bg-red-100 text-red-700',
    blue: 'bg-blue-100 text-blue-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    purple: 'bg-purple-100 text-purple-800',
    orange: 'bg-orange-100 text-orange-800',
  };

  const dotColorMap = {
    gray: 'bg-grayn-500',
    green: 'bg-green-600',
    red: 'bg-red-600',
    blue: 'bg-blue-600',
    yellow: 'bg-yellow-600',
    purple: 'bg-purple-600',
    orange: 'bg-orange-600',
  };

  const sizeMap = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-xs px-2.5 py-0.5',
    lg: 'text-sm px-3 py-1',
  };

  return (
    <span 
      className={`
        inline-flex items-center gap-1.5 rounded-full font-medium
        ${colorMap[color]} ${sizeMap[size]} ${className}
      `}
    >
      {dot && <span className={`h-1.5 w-1.5 rounded-full ${dotColorMap[color]}`} />}
      {children}
    </span>
  );
}

export default Badge;

