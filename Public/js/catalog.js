/**
 * catalog.js — Script específico de App/View/Venue/Catalog.php
 *
 * - Búsqueda en vivo de locales por nombre/tipo/capacidad en las tarjetas
 *   del catálogo (usa data-card-filter del núcleo App).
 */
(function (window, document) {
  'use strict';

  function init() {
    if (window.App) {
      App.initCardFilter();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
