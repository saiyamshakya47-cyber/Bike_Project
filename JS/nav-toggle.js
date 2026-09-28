// Mobile navigation toggle for the homepage navbar.
(function () {
  var toggle = document.querySelector('.site-nav__toggle');
  var menu = document.getElementById('site-nav-menu');

  if (!toggle || !menu) {
    return;
  }

  toggle.addEventListener('click', function () {
    var open = menu.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Close the menu after choosing a destination.
  menu.addEventListener('click', function (event) {
    if (event.target.tagName === 'A') {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
})();
