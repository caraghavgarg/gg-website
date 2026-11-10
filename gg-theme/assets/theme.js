/* GG Theme: mobile menu and dropdown toggles. */
(function () {
  var body = document.body;
  var burger = document.querySelector('.nav-burger');
  var head = document.querySelector('.primary-nav');
  if (burger) {
    burger.addEventListener('click', function () {
      var open = body.classList.toggle('nav-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
  }
  // Add a toggle button to every item that has a sub-menu (used on touch and small screens).
  document.querySelectorAll('.primary-nav .menu-item-has-children, .toolbar-nav .menu-item-has-children').forEach(function (li) {
    var a = li.querySelector(':scope > a');
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'sub-toggle';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-label', 'Show submenu: ' + (a ? a.textContent.trim() : ''));
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = li.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    li.insertBefore(btn, li.querySelector('.sub-menu'));
    // On touch screens wider than the mobile layout, the first tap opens the dropdown.
    if (a) {
      a.addEventListener('click', function (e) {
        if (window.matchMedia('(hover: none) and (min-width: 981px)').matches && !li.classList.contains('is-open')) {
          e.preventDefault();
          document.querySelectorAll('.menu-item-has-children.is-open').forEach(function (o) { if (o !== li) o.classList.remove('is-open'); });
          li.classList.add('is-open');
        }
      });
    }
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.primary-nav, .toolbar-nav')) {
      document.querySelectorAll('.menu-item-has-children.is-open').forEach(function (o) { o.classList.remove('is-open'); });
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.menu-item-has-children.is-open').forEach(function (o) { o.classList.remove('is-open'); });
    if (body.classList.contains('nav-open') && burger) { body.classList.remove('nav-open'); burger.setAttribute('aria-expanded', 'false'); burger.focus(); }
  });
  // Picture tiles: arrows slide one tile at a time.
  var track = document.querySelector('.tiles'), prev = document.querySelector('.arrow.l'), next = document.querySelector('.arrow.r');
  if (track && prev && next) {
    var step = function () { var t = track.querySelector('.tile'); return t ? t.getBoundingClientRect().width + 10 : 300; };
    var sync = function () { prev.disabled = track.scrollLeft < 4; next.disabled = track.scrollLeft + track.clientWidth > track.scrollWidth - 4; };
    prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
  }
})();
