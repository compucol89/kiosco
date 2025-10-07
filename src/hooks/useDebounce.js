// src/hooks/useDebounce.js
// Hook para implementar debounce en React
// Optimiza la búsqueda y filtrado evitando llamadas excesivas
// RELEVANT FILES: useProductSearch.js, InventarioInteligente.jsx

import { useState, useEffect } from 'react';

export const useDebounce = (value, delay) => {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
};
