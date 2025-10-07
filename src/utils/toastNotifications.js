// src/utils/toastNotifications.js
// Sistema de notificaciones toast amigables y modernas
// Reemplaza alert() con mensajes más elegantes
// RELEVANT FILES: ProductFormModal.jsx, ProductosPage.jsx

export const showToast = (message, type = 'info', duration = 4000) => {
  // Crear el contenedor de toasts si no existe
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = 'fixed top-4 right-4 z-[9999] space-y-2';
    document.body.appendChild(toastContainer);
  }

  // Crear el toast
  const toast = document.createElement('div');
  const toastId = 'toast-' + Date.now();
  toast.id = toastId;

  // Configurar estilos según tipo
  const styles = {
    success: {
      bg: 'bg-green-500',
      icon: '✅',
      border: 'border-green-400'
    },
    error: {
      bg: 'bg-red-500',
      icon: '❌',
      border: 'border-red-400'
    },
    warning: {
      bg: 'bg-yellow-500',
      icon: '⚠️',
      border: 'border-yellow-400'
    },
    info: {
      bg: 'bg-blue-500',
      icon: 'ℹ️',
      border: 'border-blue-400'
    }
  };

  const style = styles[type] || styles.info;

  toast.className = `${style.bg} text-white px-6 py-4 rounded-lg shadow-lg border-l-4 ${style.border} transform translate-x-full transition-all duration-300 max-w-sm`;
  
  toast.innerHTML = `
    <div class="flex items-center gap-3">
      <span class="text-lg">${style.icon}</span>
      <div class="flex-1">
        <p class="font-medium">${message}</p>
      </div>
      <button class="hover:bg-white hover:bg-opacity-20 rounded p-1 transition-colors" onclick="removeToast('${toastId}')">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>
  `;

  // Agregar al contenedor
  toastContainer.appendChild(toast);

  // Animar entrada
  setTimeout(() => {
    toast.classList.remove('translate-x-full');
    toast.classList.add('translate-x-0');
  }, 100);

  // Auto-remover
  setTimeout(() => {
    window.removeToast(toastId);
  }, duration);

  return toastId;
};

// Función global para remover toast
const removeToast = (toastId) => {
  const toast = document.getElementById(toastId);
  if (toast) {
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => {
      toast.remove();
      
      // Limpiar contenedor si está vacío
      const container = document.getElementById('toast-container');
      if (container && container.children.length === 0) {
        container.remove();
      }
    }, 300);
  }
};

// Asignar a window después de definir
window.removeToast = removeToast;

// Funciones de conveniencia
export const showSuccess = (message, duration) => showToast(message, 'success', duration);
export const showError = (message, duration) => showToast(message, 'error', duration);
export const showWarning = (message, duration) => showToast(message, 'warning', duration);
export const showInfo = (message, duration) => showToast(message, 'info', duration);
