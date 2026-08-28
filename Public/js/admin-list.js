/**
 * admin-list.js — Script específico de App/View/Admin/List.php
 *
 * - Filtro en vivo de la tabla de usuarios (buscar por nombre, correo, teléfono).
 * Todas las acciones destructivas ya usan data-confirm (gestionado por el
 * núcleo App.initConfirm), por lo que aquí solo se configura el filtro.
 */
(function (window, document) {
  'use strict';

  function init() {
    if (window.App) {
      App.initTableFilter();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
