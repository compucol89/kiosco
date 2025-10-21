// File: src/components/ui/Select.jsx
// Styled select dropdown with label and error state
// Exists to unify dropdown visuals with Input component
// Related files: src/components/ui/Input.jsx

import React from 'react';

/**
 * Select dropdown reutilizable
 * 
 * @param {string} label - Etiqueta del campo
 * @param {string} help - Texto de ayuda
 * @param {string} error - Mensaje de error
 * @param {boolean} required - Si es obligatorio
 * @param {ReactNode} children - Opciones del select
 */
export function Select({ 
  label, 
  help, 
  error, 
  required = false,
  children, 
  className = '',
  containerClassName = '',
  ...props 
}) {
  const selectId = props.id || props.name || `select-${Math.random().toString(36).substr(2, 9)}`;
  
  return (
    <label htmlFor={selectId} className={`block space-y-1.5 ${containerClassName}`}>
      {label && (
        <span className="text-sm font-medium text-grayn-700">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </span>
      )}
      <select
        id={selectId}
        className={`
          w-full rounded-md border bg-white px-3 py-2 text-grayn-900 
          shadow-soft transition-colors duration-200
          focus:outline-none focus:ring-2 focus:ring-offset-1
          disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-grayn-50
          ${error 
            ? 'border-red-500 focus:border-red-500 focus:ring-red-300' 
            : 'border-grayn-300 focus:border-primary focus:ring-primary/40'
          }
          ${className}
        `}
        {...props}
      >
        {children}
      </select>
      {help && !error && (
        <span className="text-xs text-grayn-500">{help}</span>
      )}
      {error && (
        <span className="text-xs text-red-600 flex items-center gap-1">
          <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd"/>
          </svg>
          {error}
        </span>
      )}
    </label>
  );
}

export default Select;

