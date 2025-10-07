// src/components/productos/components/LazyModals.jsx
// Lazy loading de modales para optimización de bundle
// Solo se cargan cuando son necesarios
// RELEVANT FILES: ProductosPage.jsx

import { lazy } from 'react';

// ⚡ OPTIMIZACIÓN: Lazy load de modales pesados
export const ProductFormModal = lazy(() => import('./ProductFormModal'));
export const ProductDetailModal = lazy(() => import('./ProductDetailModal'));
export const ProductImportModal = lazy(() => import('./ProductImportModal'));

// Re-export directo para compatibilidad
export { default as ProductFormModalDirect } from './ProductFormModal';
export { default as ProductDetailModalDirect } from './ProductDetailModal';
export { default as ProductImportModalDirect } from './ProductImportModal';
