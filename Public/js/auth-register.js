/**
 * auth-register.js — Script específico de App/View/Auth/Register.php
 *
 * - Alterna entre las pestañas "Soy cliente" / "Soy propietario".
 * - Cambia la acción del formulario según la pestaña activa.
 * Depende del núcleo App (app.js) que debe cargarse antes.
 */
(function (window, document) {
  'use strict';

  function showTab(tab) {
    document.querySelectorAll('.tabs button').forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-tab') === tab);
    });
    document.getElementById('panel-client').classList.toggle('active', tab === 'client');
    document.getElementById('panel-owner').classList.toggle('active', tab === 'owner');
    var form = document.getElementById('registerForm');
    form.action = (tab === 'owner')
      ? document.getElementById('registerForm').getAttribute('data-action-owner')
      : document.getElementById('registerForm').getAttribute('data-action-client');
  }

  function init() {
    var tabButtons = document.querySelectorAll('.tabs button[data-tab]');
    for (var i = 0; i < tabButtons.length; i++) {
      (function (btn) {
        btn.addEventListener('click', function () {
          showTab(btn.getAttribute('data-tab'));
        });
      })(tabButtons[i]);
    }
  }

  // Exponer para uso global (compatibilidad) y arrancar en DOM ready.
  window.showTab = showTab;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
