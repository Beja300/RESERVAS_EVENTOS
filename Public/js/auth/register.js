(function () {
  [['password', 'passwordToggle'], ['ownerPassword', 'ownerPasswordToggle']].forEach(function (pair) {
    var input = document.getElementById(pair[0]);
    var toggle = document.getElementById(pair[1]);

    toggle.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      toggle.textContent = show ? 'Ocultar' : 'Mostrar';
      toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
      input.focus();
    });
  });
})();