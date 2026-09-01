/**
 * auth-register.js — Script específico de App/View/Auth/Register.php
 *
 * - Alterna entre las pestañas "Soy cliente" / "Soy propietario".
 * - Cambia la acción del formulario según la pestaña activa.
 * Depende del núcleo App (app.js) que debe cargarse antes.
 */
(function (window, document) {
  'use strict';

  function setPanels(tab) {
    var clientPanel = document.getElementById('panel-client');
    var ownerPanel = document.getElementById('panel-owner');

    clientPanel.classList.toggle('active', tab === 'client');
    ownerPanel.classList.toggle('active', tab === 'owner');

    // Habilitar solo los campos del panel activo para evitar duplicados
    // y que los "required" del panel oculto bloqueen el envío.
    toggleFields(clientPanel, tab === 'client');
    toggleFields(ownerPanel, tab === 'owner');
  }

  function toggleFields(panel, enabled) {
    var fields = panel.querySelectorAll('input, select, textarea');
    for (var i = 0; i < fields.length; i++) {
      fields[i].disabled = !enabled;
    }
  }

  function showTab(tab) {
    document.querySelectorAll('.tabs button').forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-tab') === tab);
    });
    setPanels(tab);
    var form = document.getElementById('registerForm');
    form.action = (tab === 'owner')
      ? form.getAttribute('data-action-owner')
      : form.getAttribute('data-action-client');
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
    // Sincronizar con el estado inicial del DOM (pestaña por defecto).
    setPanels('client');
  }

  // Exponer para uso global (compatibilidad) y arrancar en DOM ready.
  window.showTab = showTab;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
