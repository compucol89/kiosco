// File: src/components/ui/Empty.jsx
// Empty state component for when there's no data to display
// Exists to show user-friendly messages instead of blank screens
// Related files: src/components/ui/SkeletonRow.jsx

import React from 'react';

/**
 * Estado vacío para cuando no hay datos
 * 
 * @param {string} title - Título del mensaje
 * @param {string} description - Descripción adicional
 * @param {ReactNode} icon - Ícono personalizado
 * @param {ReactNode} action - Botón de acción (opcional)
 */
export function Empty({ 
  title = 'Sin datos', 
  description = 'No se encontraron resultados',
  icon,
  action,
  className = ''
}) {
  return (
    <div className={`
      flex flex-col items-center justify-center 
      rounded-lg border-2 border-dashed border-grayn-300 
      bg-grayn-25 px-6 py-12 text-center
      ${className}
    `}>
      {/* Ícono */}
      {icon ? (
        <div className="mb-4 text-grayn-400">{icon}</div>
      ) : (
        <svg 
          className="mb-4 h-12 w-12 text-grayn-400" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
        >
          <path 
            strokeLinecap="round" 
            strokeLinejoin="round" 
            strokeWidth={1.5} 
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" 
          />
        </svg>
      )}

      {/* Título */}
      <p className="mb-2 text-sm font-semibold text-grayn-900">
        {title}
      </p>

      {/* Descripción */}
      {description && (
        <p className="mb-4 text-xs text-grayn-500 max-w-xs">
          {description}
        </p>
      )}

      {/* Acción */}
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}

export default Empty;

