/**
 * location-map.js — Selector de ubicación con mapa (Leaflet + OpenStreetMap),
 * al estilo Google Maps, para el formulario del local.
 *
 * El owner NO teclea latitud/longitud: hace clic en el mapa o arrastra el
 * marcador, o busca una dirección/cantón (Nominatim) y el punto elegido
 * llena los <input type="hidden"> #latitude / #longitude.
 *
 * Requiere una página con:
 *   <div id="venue-map"></div>
 *   <input type="search" id="venue-map-search">
 *   <ul id="venue-map-results"></ul>
 *   <span id="geo-display"></span>
 *   <input type="hidden" name="latitude"  id="latitude">
 *   <input type="hidden" name="longitude" id="longitude">
 *   <select id="province"> / <select id="canton">  (para re-centrar al cambiarlos)
 */
(function (window, document, L) {
  'use strict';

  if (!L || !document.getElementById('venue-map')) {
    return;
  }

  function getBasePath() {
    var parts = (window.location.pathname || '').split('/');
    parts.pop();
    return parts.join('/');
  }

  var base = getBasePath();

  L.Icon.Default.imagePath = base + '/vendor/leaflet/images';

  var mapEl = document.getElementById('venue-map');
  var latInput = document.getElementById('latitude');
  var lngInput = document.getElementById('longitude');
  var displayEl = document.getElementById('geo-display');
  var searchInput = document.getElementById('venue-map-search');
  var resultsEl = document.getElementById('venue-map-results');
  var provinceSel = document.getElementById('province');
  var cantonSel = document.getElementById('canton');

  var CR_CENTER = [9.7489, -83.7534];
  var zoom = latInput.value && lngInput.value ? 14 : 7;

  var map = L.map(mapEl).setView(CR_CENTER, zoom);

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  var marker = null;

  function round(value) {
    return Number(parseFloat(value).toFixed(6));
  }

  function syncDisplay() {
    if (latInput.value && lngInput.value) {
      displayEl.textContent = 'Lat ' + latInput.value + ' · Lon ' + lngInput.value;
      displayEl.classList.remove('muted');
    } else {
      displayEl.textContent = 'Marca el punto en el mapa';
      displayEl.classList.add('muted');
    }
  }

  function setCoords(latitude, longitude) {
    latInput.value = String(round(latitude));
    lngInput.value = String(round(longitude));
    syncDisplay();
  }

  function placeMarker(latLng) {
    if (marker === null) {
      marker = L.marker([latLng.lat, latLng.lng], { draggable: true }).addTo(map);
      marker.on('dragend', function () {
        setCoords(marker.getLatLng().lat, marker.getLatLng().lng);
      });
      marker.on('click', function () {
        setCoords(marker.getLatLng().lat, marker.getLatLng().lng);
      });
    } else {
      marker.setLatLng([latLng.lat, latLng.lng]);
    }
    setCoords(latLng.lat, latLng.lng);
  }

  map.on('click', function (e) {
    placeMarker(e.latlng);
  });

  // =========================================================
  // Preselección: si el local ya tiene coordenadas guardadas.
  // =========================================================
  if (latInput.value && lngInput.value) {
    var storedLat = parseFloat(latInput.value);
    var storedLng = parseFloat(lngInput.value);
    if (!isNaN(storedLat) && !isNaN(storedLng)) {
      map.setView([storedLat, storedLng], 14);
      placeMarker({ lat: storedLat, lng: storedLng });
    }
  }

  // =========================================================
  // Búsqueda tipo Google Maps via Nominatim (OpenStreetMap).
  // =========================================================
  function nominatimSearch(query, limit) {
    var url = 'https://nominatim.openstreetmap.org/search'
      + '?format=json&countrycodes=cr&limit=' + (limit || 5)
      + '&q=' + encodeURIComponent(query);
    return fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); });
  }

  var searchTimer = null;

  function clearResults() {
    resultsEl.innerHTML = '';
    resultsEl.classList.remove('is-open');
  }

  function runSearch(query) {
    clearResults();
    if (!query.trim()) {
      return;
    }

    nominatimSearch(query, 5).then(function (items) {
      if (!items || !items.length) {
        return;
      }
      resultsEl.innerHTML = '';
      items.forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = item.display_name;
        li.addEventListener('click', function () {
          pickSearchResult(item);
        });
        resultsEl.appendChild(li);
      });
      resultsEl.classList.add('is-open');
    }).catch(function () {
      window.App && App.toast('No se pudo buscar la dirección.', 'error');
    });
  }

  function pickSearchResult(item) {
    var lat = parseFloat(item.lat);
    var lng = parseFloat(item.lon);
    searchInput.value = item.display_name;
    clearResults();
    if (isNaN(lat) || isNaN(lng)) {
      return;
    }
    map.setView([lat, lng], 16);
    placeMarker({ lat: lat, lng: lng });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      if (searchTimer) {
        clearTimeout(searchTimer);
      }
      searchTimer = setTimeout(function () {
        runSearch(searchInput.value);
      }, 350);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        runSearch(searchInput.value);
      } else if (e.key === 'Escape') {
        clearResults();
      }
    });

    document.addEventListener('click', function (e) {
      var box = e.target.closest ? e.target.closest('.geo-search-box') : null;
      if (!box) {
        clearResults();
      }
    });
  }

  // =========================================================
  // Re-centrar el mapa al cambiar provincia/cantón en los combos.
  // Solo si el owner aún no ha marcado un punto.
  // =========================================================
  function recenterToAddress() {
    if (latInput.value && lngInput.value) {
      return;
    }
    var prov = (provinceSel && provinceSel.value) || '';
    var cant = (cantonSel && cantonSel.value) || '';
    if (!prov && !cant) {
      return;
    }
    var q = [cant, prov].filter(Boolean).join(', ') + ', Costa Rica';

    nominatimSearch(q, 1).then(function (items) {
      if (!items || !items.length) {
        return;
      }
      map.setView([parseFloat(items[0].lat), parseFloat(items[0].lon)], cant ? 13 : 11);
    }).catch(function () {});
  }

  if (provinceSel) {
    provinceSel.addEventListener('change', recenterToAddress);
  }
  if (cantonSel) {
    cantonSel.addEventListener('change', recenterToAddress);
  }

  // =========================================================
  // Validación cliente: no se guarda sin marcar la ubicación.
  // Se registra en fase de captura para correr antes que el AJAX de form.js.
  // =========================================================
  window.addEventListener('submit', function (e) {
    if (e.target && e.target.matches && e.target.matches('form[data-ajax-venue-form]')) {
      if (!latInput.value || !lngInput.value) {
        e.preventDefault();
        e.stopPropagation();
        window.App && App.toast('Marca la ubicación en el mapa antes de guardar.', 'error');
      }
    }
  }, true);
})(window, document, window.L);