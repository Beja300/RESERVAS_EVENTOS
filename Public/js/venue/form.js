(function () {
  var form = document.querySelector('form[data-ajax-venue-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      App.ajax(form.getAttribute('action'), { body: new FormData(form) }).then(function (r) {
        window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
        if (r.ok) {
          setTimeout(function () { window.location.href = App.actionUrl('venue', 'list'); }, 700);
        }
      });
    });
  }
})();