// File: src/components/ui/SkeletonRow.jsx
// Loading skeleton placeholders
// Exists to show visual feedback while data is loading
// Related files: src/components/ui/Empty.jsx

import React from 'react';

/**
 * Skeleton loader para mostrar mientras carga
 * 
 * @param {number} rows - Número de filas
 * @param {string} height - Altura de cada fila
 */
export function SkeletonRow({ 
  rows = 1, 
  height = 'h-9',
  className = '' 
}) {
  return (
    <>
      {Array.from({ length: rows }).map((_, i) => (
        <div 
          key={i} 
          className={`
            ${height} w-full animate-pulse rounded-md bg-grayn-100
            ${className}
          `}
        />
      ))}
    </>
  );
}

/**
 * Skeleton para tarjetas
 */
export function SkeletonCard({ 
  hasHeader = true,
  bodyRows = 3,
  className = '' 
}) {
  return (
    <div className={`rounded-lg bg-white p-4 shadow-soft ${className}`}>
      {hasHeader && (
        <div className="mb-4 h-6 w-1/3 animate-pulse rounded bg-grayn-100" />
      )}
      <div className="space-y-3">
        {Array.from({ length: bodyRows }).map((_, i) => (
          <div 
            key={i} 
            className="h-4 w-full animate-pulse rounded bg-grayn-100"
            style={{ width: `${Math.random() * 30 + 70}%` }}
          />
        ))}
      </div>
    </div>
  );
}

/**
 * Skeleton para tabla
 */
export function SkeletonTable({ 
  columns = 4, 
  rows = 5,
  className = '' 
}) {
  return (
    <div className={`overflow-hidden rounded-lg border border-grayn-200 ${className}`}>
      {/* Header */}
      <div className="grid gap-4 border-b border-grayn-200 bg-grayn-50 px-4 py-3" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}>
        {Array.from({ length: columns }).map((_, i) => (
          <div key={i} className="h-4 animate-pulse rounded bg-grayn-200" />
        ))}
      </div>
      
      {/* Rows */}
      {Array.from({ length: rows }).map((_, rowIdx) => (
        <div 
          key={rowIdx} 
          className="grid gap-4 border-b border-grayn-100 px-4 py-3"
          style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}
        >
          {Array.from({ length: columns }).map((_, colIdx) => (
            <div key={colIdx} className="h-4 animate-pulse rounded bg-grayn-100" />
          ))}
        </div>
      ))}
    </div>
  );
}

export default SkeletonRow;

