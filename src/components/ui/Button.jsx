// File: src/components/ui/Button.jsx
// Reusable button with variants, sizes, and loading state
// Exists to unify all action buttons across the app
// Related files: src/components/ui/Input.jsx, src/components/ui/Card.jsx

import React from 'react';

const baseClasses = "inline-flex items-center justify-center rounded-md font-medium transition-colors duration-200 shadow-soft disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2";

const variants = {
  primary: "bg-primary text-white hover:bg-primary-hover focus:ring-primary/60",
  secondary: "bg-grayn-100 text-grayn-900 hover:bg-grayn-300 focus:ring-grayn-400",
  danger: "bg-red-600 text-white hover:bg-red-700 focus:ring-red-500",
  success: "bg-green-600 text-white hover:bg-green-700 focus:ring-green-500",
  ghost: "bg-transparent text-grayn-900 hover:bg-grayn-100 shadow-none focus:ring-grayn-400",
  outline: "border-2 border-primary text-primary bg-white hover:bg-primary/5 focus:ring-primary/60"
};

const sizes = {
  xs: "text-xs px-2.5 py-1.5",
  sm: "text-sm px-3 py-2",
  md: "text-sm px-3.5 py-2.5",
  lg: "text-base px-4 py-3",
  xl: "text-lg px-5 py-3.5"
};

/**
 * Botón reutilizable con variantes y estados
 * 
 * @param {string} variant - Estilo: primary, secondary, danger, success, ghost, outline
 * @param {string} size - Tamaño: xs, sm, md, lg, xl
 * @param {boolean} loading - Muestra spinner de carga
 * @param {boolean} disabled - Deshabilita el botón
 * @param {string} as - Componente base (button, a, Link)
 * @param {ReactNode} children - Contenido del botón
 */
export function Button({ 
  as: Component = 'button', 
  variant = 'primary', 
  size = 'md', 
  loading = false, 
  disabled = false,
  className = '',
  children, 
  ...props 
}) {
  return (
    <Component 
      className={`${baseClasses} ${variants[variant]} ${sizes[size]} ${className}`}
      disabled={disabled || loading}
      {...props}
    >
      {loading && (
        <span className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/50 border-t-transparent" />
      )}
      {children}
    </Component>
  );
}

export default Button;

