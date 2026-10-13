(function () {
  var main = document.getElementById('galleryMain');
  var thumbs = document.getElementById('galleryThumbs');
  if (!main || !document.querySelector('.gallery-thumb')) return;

  thumbs.addEventListener('click', function (e) {
    var btn = e.target.closest('.gallery-thumb');
    if (!btn || !btn.getAttribute('data-src')) return;

    main.src = btn.getAttribute('data-src');
    thumbs.querySelectorAll('.gallery-thumb').forEach(function (t) {
      t.classList.toggle('is-active', t === btn);
    });
  });
})();