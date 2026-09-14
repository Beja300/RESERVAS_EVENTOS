/**
 * geo.js — Detección automática de latitud/longitud.
 *
 * La latitud/longitud NO se escribe a mano: se obtiene de forma automática
 * usando primero la API de geolocalización del navegador (precisa) y, si el
 * usuario no da permiso o no se puede, con respaldo por IP vía el endpoint
 * existente ApiController::geolocate (ip-api.com).
 *
 * La página lo cablea así:
 *   <input type="hidden" name="latitude"  id="latitude">
 *   <input type="hidden" name="longitude" id="longitude">
 *   <span id="geo-display">...</span>                 (texto read-only)
 *   <button type="button" id="geo-detect-btn">...     (opcional)
 *   <div id="geo-status" data-geo-auto="1|0">         (1 = detectar al cargar)
 *
 * El <input> hidden vive dentro del <form> y viaja con su FormData, así que
 * el servidor recibe latitude/longitude igual que antes, sin cambios.
 */
(function (window, document) {
  'use strict';

  function getBasePath() {
    var parts = (window.location.pathname || '').split('/');
    parts.pop();
    return parts.join('/');
  }

  var latInput = document.getElementById('latitude');
  var lngInput = document.getElementById('longitude');
  var displayEl = document.getElementById('geo-display');
  var btn = document.getElementById('geo-detect-btn');
  var container = document.getElementById('geo-status');

  if (!latInput || !lngInput) {
    return;
  }

  function round(value) {
    return Number(parseFloat(value).toFixed(6));
  }

  function render(lat, lng) {
    latInput.value = String(lat);
    lngInput.value = String(lng);
    if (displayEl) {
      displayEl.textContent = 'Lat ' + String(lat) + ' · Lon ' + String(lng);
      displayEl.classList.remove('muted');
    }
  }

  function fallbackByIp() {
    var url = getBasePath() + '/index.php?controller=api&action=geolocate';

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json && json.ok && json.lat != null && json.lon != null) {
          render(round(json.lat), round(json.lon));
          window.App && App.toast('Ubicación detectada por IP (aproximada).', 'info');
        } else if (latInput.value === '') {
          window.App && App.toast((json && json.message) || 'No se pudo detectar la ubicación.', 'info');
        }
      })
      .catch(function () {
        if (latInput.value === '') {
          window.App && App.toast('No se pudo detectar la ubicación.', 'info');
        }
      });
  }

  function detect() {
    if (!('geolocation' in navigator)) {
      fallbackByIp();
      return;
    }

    navigator.geolocation.getCurrentPosition(
      function (position) {
        render(round(position.coords.latitude), round(position.coords.longitude));
        window.App && App.toast('Ubicación detectada correctamente.', 'success');
      },
      function () {
        fallbackByIp();
      },
      { timeout: 6000, maximumAge: 600000 }
    );
  }

  if (btn) {
    btn.addEventListener('click', detect);
  }

  if (container && container.getAttribute('data-geo-auto') === '1') {
    detect();
  }
})(window, document);