(function () {
  var form = document.querySelector('form[data-promotion-form]');
  if (!form) {
    return;
  }

  var start = form.querySelector('#startDate');
  var end = form.querySelector('#endDate');

  function validateRange() {
    if (!start || !end || !start.value || !end.value) {
      return true;
    }

    var isValid = end.value >= start.value;
    var container = end.closest('.form-group');
    var err = container ? container.querySelector('.field-error') : null;

    if (!err && container) {
      err = document.createElement('div');
      err.className = 'field-error';
      container.appendChild(err);
    }

    if (err) {
      err.textContent = isValid ? '' : 'La fecha de fin no puede ser anterior a la de inicio.';
    }
    end.classList.toggle('is-invalid', !isValid);
    return isValid;
  }

  if (start) {
    start.addEventListener('change', validateRange);
  }
  if (end) {
    end.addEventListener('change', validateRange);
  }

  form.addEventListener('submit', function (e) {
    if (!validateRange()) {
      e.preventDefault();
      if (end) end.focus();
      window.App && App.toast('Revisa las fechas de la promoción.', 'error');
    }
  });
})();
