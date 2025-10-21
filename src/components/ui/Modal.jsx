// File: src/components/ui/Modal.jsx
// Controlled modal overlay without external dependencies
// Exists to show dialogs, forms, and confirmations consistently
// Related files: src/components/ui/Button.jsx, src/components/ui/Card.jsx

import React, { useEffect } from 'react';

/**
 * Modal controlado sin dependencias externas
 * 
 * @param {boolean} open - Si el modal está abierto
 * @param {function} onClose - Callback al cerrar
 * @param {string} title - Título del modal
 * @param {ReactNode} children - Contenido del modal
 * @param {ReactNode} footer - Botones del footer
 * @param {string} size - Tamaño: sm, md, lg, xl
 * @param {boolean} closeOnOverlay - Si cierra al hacer click fuera (default: true)
 */
export function Modal({ 
  open, 
  onClose, 
  title, 
  children, 
  footer,
  size = 'md',
  closeOnOverlay = true,
  className = ''
}) {
  // Cerrar con ESC
  useEffect(() => {
    if (!open) return;
    
    const handleEsc = (e) => {
      if (e.key === 'Escape' && onClose) {
        onClose();
      }
    };
    
    document.addEventListener('keydown', handleEsc);
    return () => document.removeEventListener('keydown', handleEsc);
  }, [open, onClose]);

  // Prevenir scroll del body cuando está abierto
  useEffect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [open]);

  if (!open) return null;

  const sizeMap = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-full mx-4'
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in">
      {/* Overlay */}
      <div 
        className="absolute inset-0 bg-black/50 backdrop-blur-sm" 
        onClick={closeOnOverlay ? onClose : undefined}
      />
      
      {/* Modal */}
      <div 
        className={`
          relative z-10 w-full ${sizeMap[size]} 
          rounded-lg bg-white shadow-strong animate-slide-up
          ${className}
        `}
      >
        {/* Header */}
        {(title || onClose) && (
          <div className="flex items-center justify-between gap-3 border-b border-grayn-200 px-5 py-4">
            {title && (
              <h3 className="text-lg font-semibold text-grayn-900">
                {title}
              </h3>
            )}
            {onClose && (
              <button
                onClick={onClose}
                className="
                  rounded-md p-1 text-grayn-400 transition-colors
                  hover:bg-grayn-100 hover:text-grayn-600
                  focus:outline-none focus:ring-2 focus:ring-primary/60
                "
                aria-label="Cerrar modal"
              >
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>
        )}

        {/* Body */}
        <div className="px-5 py-4 max-h-[calc(100vh-16rem)] overflow-y-auto">
          {children}
        </div>

        {/* Footer */}
        {footer && (
          <div className="flex items-center justify-end gap-2 border-t border-grayn-200 px-5 py-4">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}

export default Modal;

