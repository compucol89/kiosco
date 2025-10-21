<?php
// File: api/pricing_config.php
// Centralized dynamic pricing configuration for time-based price adjustments
// Exists to define when and how much to increase prices for specific categories/products
// Related files: api/pricing_engine.php, api/productos_pos_optimizado.php, api/procesar_venta_ultra_rapida.php

return array (
  'enabled' => true,
  'timezone' => 'America/Argentina/Buenos_Aires',
  'rules' => 
  array (
    0 => 
    array (
      'id' => 'alcoholic-friday',
      'name' => 'Bebidas alcohólicas - Viernes noche',
      'description' => 'Incremento de precios viernes desde las 18:00',
      'enabled' => true,
      'type' => 'category',
      'category_slug' => 'bebidas-alcoholicas',
      'days' => 
      array (
        0 => 'fri',
      ),
      'from' => '18:00',
      'to' => '23:59',
      'percent_inc' => 15.0,
    ),
    1 => 
    array (
      'id' => 'alcoholic-saturday',
      'name' => 'Bebidas alcohólicas - Sábado noche',
      'description' => 'Incremento de precios sábado desde las 18:00',
      'enabled' => true,
      'type' => 'category',
      'category_slug' => 'bebidas-alcoholicas',
      'days' => 
      array (
        0 => 'sat',
      ),
      'from' => '18:00',
      'to' => '23:59',
      'percent_inc' => 20.0,
    ),
  ),
  'round' => 
  array (
    'enabled' => true,
    'decimals' => 2,
    'mode' => 1,
  ),
  'logging' => 
  array (
    'enabled' => true,
    'log_file' => 'C:\\laragon\\www\\kiosco\\api/logs/pricing_adjustments.log',
  ),
  'limits' => 
  array (
    'max_increase_percent' => 50.0,
    'max_decrease_percent' => 30.0,
  ),
);
