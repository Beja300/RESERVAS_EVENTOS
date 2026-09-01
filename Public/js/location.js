/**
 * location.js — Combos en cascada provincia → cantón → distrito.
 *
 * Consume el dataset gratuito de ApiController (ubicaciones de Costa Rica):
 *   GET index.php?controller=api&action=locations                -> provincias
 *   GET index.php?controller=api&action=locations&provincia=X    -> {canton: [distritos]}
 *
 * Cada <select> usa data-level ("province" | "canton" | "district") y un
 * valor a preseleccionar opcional en data-value.
 */
(function (window, document) {
  'use strict';

  var API_URL = 'index.php?controller=api&action=locations';

  function fillOptions(select, values, placeholder) {
    var selected = select.getAttribute('data-value') || '';
    select.innerHTML = '<option value="">' + placeholder + '</option>';
    values.forEach(function (v) {
      var opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      if (v === selected) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });
    select.disabled = false;
    return selected;
  }

  function getBasePath() {
    var script = (window.location.pathname || '').split('/');
    script.pop();
    return script.join('/');
  }

  function init() {
    var provinceSel = document.querySelector('select[data-level="province"]');
    var cantonSel = document.querySelector('select[data-level="canton"]');
    var districtSel = document.querySelector('select[data-level="district"]');

    if (!provinceSel || !cantonSel || !districtSel) {
      return;
    }

    var provincesCache = {};
    var cantonDistricts = {};

    var provincePlaceholder = provinceSel.options[0] ? provinceSel.options[0].textContent : '— Selecciona —';
    var cantonPlaceholder = cantonSel.options[0] ? cantonSel.options[0].textContent : '— Selecciona —';
    var districtPlaceholder = districtSel.options[0] ? districtSel.options[0].textContent : '— Selecciona —';

    function loadProvinces() {
      var url = getBasePath() + '/' + API_URL;
      fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (provinces) {
          var selected = fillOptions(provinceSel, provinces, provincePlaceholder);
          provinceSel.setAttribute('data-value', selected);
          if (selected) {
            selectProvince(selected);
          }
        })
        .catch(function () { /* dataset no disponible */ });
    }

    function selectProvince(province) {
      var url = getBasePath() + '/' + API_URL + '&provincia=' + encodeURIComponent(province);
      fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (data) {
          var cantones = Object.keys(data);
          cantonDistricts = data;
          var selected = fillOptions(cantonSel, cantones, cantonPlaceholder);
          cantonSel.setAttribute('data-value', selected);
          if (selected) {
            selectCanton(selected);
          } else {
            districtSel.disabled = true;
            districtSel.innerHTML = '<option value="">' + districtPlaceholder + '</option>';
          }
        })
        .catch(function () { /* sin cantones */ });
    }

    function selectCanton(canton) {
      var districts = cantonDistricts[canton] || [];
      var selected = fillOptions(districtSel, districts, districtPlaceholder);
      districtSel.setAttribute('data-value', selected);
    }

    provinceSel.addEventListener('change', function () {
      districtSel.disabled = true;
      districtSel.innerHTML = '<option value="">' + districtPlaceholder + '</option>';
      if (provinceSel.value) {
        selectProvince(provinceSel.value);
      } else {
        cantonSel.disabled = true;
        cantonSel.innerHTML = '<option value="">' + cantonPlaceholder + '</option>';
      }
    });

    cantonSel.addEventListener('change', function () {
      if (cantonSel.value) {
        selectCanton(cantonSel.value);
      } else {
        districtSel.disabled = true;
        districtSel.innerHTML = '<option value="">— Selecciona —</option>';
      }
    });

    loadProvinces();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);