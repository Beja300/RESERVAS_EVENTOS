(function () {
  var input = document.getElementById('password');
  var toggle = document.getElementById('passwordToggle');

  toggle.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'Ocultar' : 'Mostrar';
    toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    input.focus();
  });
})();