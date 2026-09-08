/**
 * location.js — Combos en cascada con búsqueda provincia → cantón → distrito.
 *
 * Hace UNA sola petición al dataset completo anidado de ApiController:
 *   GET index.php?controller=api&action=locations   -> {provincia: {cantón: [distritos]}}
 *
 * Cada <select> nativo usa data-level ("province" | "canton" | "district") y un
 * valor a preseleccionar opcional en data-value. Se construye encima un combo
 * con búsqueda (input + lista filtrable + navegación por teclado) y se mantiene
 * sincronizado el <select> nativo para conservar la validación y el POST.
 */
(function (window, document) {
  'use strict';

  var API_URL = 'index.php?controller=api&action=locations';

  function getBasePath() {
    var parts = (window.location.pathname || '').split('/');
    parts.pop();
    return parts.join('/');
  }

  /**
   * Crea el combo (input + lista) alrededor de un <select> nativo opcional.
   * options: { items, placeholder, onSelect(value), onClear }
   */
  function Combo(select, options) {
    this.select = select;
    this.options = options;
    this.value = '';
    this.filter = '';

    this.root = document.createElement('div');
    this.root.className = 'combo';

    this.input = document.createElement('input');
    this.input.className = 'combo-input form-control';
    this.input.type = 'text';
    this.input.placeholder = options.placeholder || '— Selecciona —';
    this.input.autocomplete = 'off';

    this.menu = document.createElement('ul');
    this.menu.className = 'combo-menu';
    this.menu.setAttribute('role', 'listbox');

    this.root.appendChild(this.input);
    this.root.appendChild(this.menu);

    if (this.select) {
      this.select.style.display = 'none';
      this.select.parentNode.insertBefore(this.root, this.select);
    }

    this.setDisabled(this.select ? this.select.disabled : false);

    this._bindEvents();
  }

  Combo.prototype._bindEvents = function () {
    var self = this;

    this.input.addEventListener('click', function (e) {
      e.stopPropagation();
      if (self.input.value === self.value) {
        self.filter = '';
      }
      self.open();
    });

    this.input.addEventListener('input', function () {
      self.filter = self.input.value;
      self.value = '';
      self.active = null;
      self.render();
      self.open();
    });

    this.input.addEventListener('focus', function () {
      self.open();
    });

    this.input.addEventListener('keydown', function (e) {
      self._onKeydown(e);
    });

    document.addEventListener('click', function (e) {
      if (!self.root.contains(e.target)) {
        self.close();
      }
    });
  };

  Combo.prototype._onKeydown = function (e) {
    var items = this._visibleItems();
    if (!this.isOpen) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        this.open();
      }
      return;
    }
    var idx = items.indexOf(this.active);
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      idx = Math.min(idx + 1, items.length - 1);
      this.active = items[Math.max(idx, 0)];
      this._scrollActive();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      idx = Math.max(idx - 1, 0);
      this.active = items[Math.max(idx, 0)];
      this._scrollActive();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (this.active != null) {
        this.choose(this.active);
      }
    } else if (e.key === 'Escape') {
      e.preventDefault();
      this.close();
    }
  };

  Combo.prototype._scrollActive = function () {
    var node = this.menu.querySelector('.is-active');
    if (node && node.scrollIntoView) {
      node.scrollIntoView({ block: 'nearest' });
    }
  };

  Combo.prototype.setItems = function (items) {
    this.items = items || [];
    this.filter = '';
    this.input.value = '';
    this.active = null;
    this.close();
    this.render();
  };

  /**
   * Puebla el <select> nativo con <option> reales para que la validación
   * HTML5 del navegador pueda asignar un valor y no quede "not focusable".
   * Mantiene una primera opción vacía "-- Selecciona --".
   */
  Combo.prototype.setNativeOptions = function (values, selected) {
    if (!this.select) {
      return;
    }
    this.select.innerHTML = '';
    var placeholder = (this.select.options && this.select.options[0] && this.select.options[0].textContent) || '— Selecciona —';
    var first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    this.select.appendChild(first);
    (values || []).forEach(function (v) {
      var o = document.createElement('option');
      o.value = v;
      o.textContent = v;
      this.select.appendChild(o);
    }, this);
    this.syncSelectValue(selected || '');
  };

  Combo.prototype.syncSelectValue = function (value) {
    if (this.select) {
      this.select.value = value || this.value || '';
    }
  };

  Combo.prototype.setValue = function (value) {
    this.value = value || '';
    this.input.value = value || '';
    this.active = null;
    this.close();
  };

  Combo.prototype.setDisabled = function (disabled) {
    this.disabled = !!disabled;
    this.root.classList.toggle('is-disabled', this.disabled);
    this.input.disabled = this.disabled;
    if (this.disabled) {
      this.close();
      this.setValue('');
    }
  };

  Combo.prototype.isDisabled = function () {
    return this.disabled;
  };

  Combo.prototype._visibleItems = function () {
    var q = (this.filter || '').toLowerCase().trim();
    if (!q) {
      return this.items.slice();
    }
    return this.items.filter(function (it) {
      return it.toLowerCase().indexOf(q) !== -1;
    });
  };

  Combo.prototype.render = function () {
    var self = this;
    var items = this._visibleItems();

    this.menu.innerHTML = '';

    var emptyState = document.createElement('li');
    emptyState.className = 'combo-item is-empty';
    emptyState.textContent = items.length ? '' : 'Sin resultados';
    if (!items.length) {
      this.menu.appendChild(emptyState);
      return;
    }

    items.forEach(function (it) {
      var li = document.createElement('li');
      li.className = 'combo-item';
      li.setAttribute('role', 'option');
      li.setAttribute('data-text', it);
      li.textContent = it;

      li.addEventListener('click', function () {
        self.choose(it);
      });
      li.addEventListener('mousemove', function () {
        self.active = it;
        self._markActive();
      });

      self.menu.appendChild(li);
    });

    this._markActive();
  };

  Combo.prototype._markActive = function () {
    var self = this;
    var nodes = this.menu.querySelectorAll('.combo-item');
    [].forEach.call(nodes, function (node) {
      node.classList.toggle('is-active', node.getAttribute('data-text') === self.active);
    });
  };

  Combo.prototype.choose = function (value) {
    this.setValue(value);
    if (this.options.onSelect) {
      this.options.onSelect(value);
    }
    this.close();
  };

  Combo.prototype.clear = function () {
    this.setValue('');
    if (this.options.onClear) {
      this.options.onClear();
    }
  };

  Combo.prototype.open = function () {
    if (this.disabled) {
      return;
    }
    if (!this.menu.firstChild) {
      this.render();
    }
    this.active = this.items.length ? this.items[0] : null;
    this.render();
    this.isOpen = true;
    this.root.classList.add('is-open');
  };

  Combo.prototype.close = function () {
    this.isOpen = false;
    this.root.classList.remove('is-open');
    if (!this.value) {
      this.input.value = '';
    }
    this.filter = '';
  };

  /**
   * Gestiona un grupo completo (provincia/cantón/distrito) sincronizando
   * los tres combos y los <select> nativos.
   */
  function Group(provinceSel, cantonSel, districtSel) {
    this.provinceSel = provinceSel;
    this.cantonSel = cantonSel;
    this.districtSel = districtSel;
    this.data = {};

    this.provinceCombo = new Combo(provinceSel, {
      placeholder: provinceSel.options[0].textContent,
      onSelect: this.onProvinceChange.bind(this),
      onClear: this.onProvinceClear.bind(this)
    });
    this.cantonCombo = new Combo(cantonSel, {
      placeholder: cantonSel.options[0].textContent,
      onSelect: this.onCantonChange.bind(this),
      onClear: this.onCantonClear.bind(this)
    });
    this.districtCombo = new Combo(districtSel, {
      placeholder: districtSel.options[0].textContent,
      onSelect: this.onDistrictChange.bind(this),
      onClear: this.onDistrictClear.bind(this)
    });

    this.cantonCombo.setDisabled(true);
    this.districtCombo.setDisabled(true);
    this.syncNative();
  }

  Group.prototype.provinces = function () {
    return Object.keys(this.data);
  };

  Group.prototype.cantons = function (province) {
    var map = this.data[province] || {};
    return Object.keys(map);
  };

  Group.prototype.districts = function (province, canton) {
    var map = this.data[province] || {};
    return (map[canton] || []);
  };

  Group.prototype.syncNative = function () {
    this.provinceSel.disabled = this.provinceCombo.isDisabled();
    this.cantonSel.disabled = this.cantonCombo.isDisabled();
    this.districtSel.disabled = this.districtCombo.isDisabled();
    this.provinceSel.value = this.provinceCombo.value;
    this.cantonSel.value = this.cantonCombo.value;
    this.districtSel.value = this.districtCombo.value;
  };

  Group.prototype.onProvinceChange = function (province) {
    this.cantonCombo.setDisabled(false);
    this.cantonCombo.setItems(this.cantons(province));
    this.cantonCombo.setNativeOptions(this.cantons(province));
    this.districtCombo.setDisabled(true);
    this.districtCombo.setItems([]);
    this.districtCombo.setNativeOptions([]);
    this.syncNative();
  };

  Group.prototype.onProvinceClear = function () {
    this.cantonCombo.setDisabled(true);
    this.cantonCombo.setItems([]);
    this.cantonCombo.setNativeOptions([]);
    this.districtCombo.setDisabled(true);
    this.districtCombo.setItems([]);
    this.districtCombo.setNativeOptions([]);
    this.syncNative();
  };

  Group.prototype.onCantonChange = function (canton) {
    var province = this.provinceCombo.value;
    this.districtCombo.setDisabled(false);
    this.districtCombo.setItems(this.districts(province, canton));
    this.districtCombo.setNativeOptions(this.districts(province, canton));
    this.syncNative();
  };

  Group.prototype.onCantonClear = function () {
    this.districtCombo.setDisabled(true);
    this.districtCombo.setItems([]);
    this.districtCombo.setNativeOptions([]);
    this.syncNative();
  };

  Group.prototype.onDistrictChange = function () {
    this.syncNative();
  };

  Group.prototype.onDistrictClear = function () {
    this.syncNative();
  };

  Group.prototype.applyPreselection = function () {
    var province = this.provinceSel.getAttribute('data-value') || '';
    var canton = this.cantonSel.getAttribute('data-value') || '';
    var district = this.districtSel.getAttribute('data-value') || '';

    if (province && this.provinces().indexOf(province) !== -1) {
      this.provinceCombo.setNativeOptions(this.provinces(), province);
      this.cantonCombo.setDisabled(false);
      this.cantonCombo.setItems(this.cantons(province));
      this.cantonCombo.setNativeOptions(this.cantons(province), canton);

      if (canton && this.cantons(province).indexOf(canton) !== -1) {
        this.cantonCombo.setValue(canton);
        this.districtCombo.setDisabled(false);
        this.districtCombo.setItems(this.districts(province, canton));
        this.districtCombo.setNativeOptions(this.districts(province, canton), district);

        if (district && this.districts(province, canton).indexOf(district) !== -1) {
          this.districtCombo.setValue(district);
        }
      }
    }
    this.syncNative();
  };

  function init() {
    var provinceSel = document.querySelector('select[data-level="province"]');
    var cantonSel = document.querySelector('select[data-level="canton"]');
    var districtSel = document.querySelector('select[data-level="district"]');

    if (!provinceSel || !cantonSel || !districtSel) {
      return;
    }

    var group = new Group(provinceSel, cantonSel, districtSel);

    fetch(getBasePath() + '/' + API_URL)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        group.data = data;
        group.provinceCombo.setItems(group.provinces());
        group.provinceCombo.setNativeOptions(group.provinces());
        group.applyPreselection();
      })
      .catch(function () {
        // Dataset no disponible: se deja el <select> nativo visible para fallback.
        group.provinceCombo.root.style.display = 'none';
        group.cantonCombo.root.style.display = 'none';
        group.districtCombo.root.style.display = 'none';
        group.provinceSel.style.display = '';
        group.cantonSel.style.display = '';
        group.districtSel.style.display = '';
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);