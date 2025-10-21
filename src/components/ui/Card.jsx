// File: src/components/ui/Card.jsx
// Container for sections with consistent padding and shadows
// Exists to unify section layouts across the app
// Related files: src/components/ui/Badge.jsx, src/components/Dashboard.jsx

import React from 'react';

/**
 * Contenedor de tarjeta reutilizable
 * 
 * @param {string} title - Título de la tarjeta
 * @param {ReactNode} actions - Acciones/botones del header
 * @param {ReactNode} children - Contenido de la tarjeta
 * @param {boolean} noPadding - Quita el padding interno
 * @param {string} variant - Estilo: default, outlined, elevated
 */
export function Card({ 
  title, 
  actions, 
  children, 
  noPadding = false,
  variant = 'default',
  className = '',
  headerClassName = '',
  bodyClassName = ''
}) {
  const variants = {
    default: 'bg-white shadow-soft',
    outlined: 'bg-white border border-grayn-200',
    elevated: 'bg-white shadow-medium hover:shadow-strong transition-shadow duration-200',
    flat: 'bg-grayn-50'
  };

  return (
    <section className={`rounded-lg overflow-hidden ${variants[variant]} ${className}`}>
      {(title || actions) && (
        <header className={`
          px-4 py-3 border-b border-grayn-200 
          flex items-center justify-between gap-3
          ${headerClassName}
        `}>
          {title && (
            <h3 className="text-base font-semibold text-grayn-900">
              {title}
            </h3>
          )}
          {actions && <div className="flex items-center gap-2">{actions}</div>}
        </header>
      )}
      <div className={`${!noPadding ? 'p-4' : ''} ${bodyClassName}`}>
        {children}
      </div>
    </section>
  );
}

export default Card;

