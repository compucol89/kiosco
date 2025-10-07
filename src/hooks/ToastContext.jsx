import React, { createContext, useContext, useState } from 'react';

// Create context
const ToastContext = createContext();

// Toast provider component
export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  // Add a new toast
  const addToast = (message, type = 'info', duration = 3000) => {
    const id = Math.random().toString(36).substring(2, 9);
    const newToast = { id, message, type, duration };
    
    setToasts((currentToasts) => [...currentToasts, newToast]);
    
    // Automatically remove toast after duration
    setTimeout(() => {
      removeToast(id);
    }, duration);
    
    return id;
  };

  // Remove a toast by id
  const removeToast = (id) => {
    setToasts((currentToasts) => currentToasts.filter(toast => toast.id !== id));
  };

  return (
    <ToastContext.Provider value={{ toasts, addToast, removeToast }}>
      {children}
      
      {/* Toast container */}
      {toasts.length > 0 && (
        <div className="fixed top-4 right-4 z-50 flex flex-col gap-2">
          {toasts.map((toast) => (
            <div 
              key={toast.id} 
              className={`p-3 rounded shadow-lg flex items-center ${
                toast.type === 'success' ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 
                toast.type === 'error' ? 'bg-red-100 text-red-800 border-l-4 border-red-500' : 
                toast.type === 'warning' ? 'bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500' :
                'bg-blue-100 text-blue-800 border-l-4 border-blue-500'
              }`}
            >
              <span>{toast.message}</span>
              <button 
                onClick={() => removeToast(toast.id)}
                className="ml-3 text-sm font-bold"
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}
    </ToastContext.Provider>
  );
};

// Custom hook for using toast context
export const useToasts = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToasts must be used within a ToastProvider');
  }
  return context;
};

export default ToastContext; 