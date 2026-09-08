(function () {
  var form = document.querySelector('form[data-ajax-service-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      App.ajax(form.getAttribute('action'), { body: new FormData(form) }).then(function (r) {
        window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
        if (r.ok) {
          var venueIdInput = form.querySelector('[name="venueId"]');
          var vid = venueIdInput ? venueIdInput.value : '';
          setTimeout(function () {
            window.location.href = App.actionUrl('service', 'list', { venueId: vid });
          }, 700);
        }
      });
    });
  }
})();