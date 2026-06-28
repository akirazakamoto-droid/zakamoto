// Menu burger — attivo su tutte le pagine (incluso dal footer).
(function () {
  var btn = document.getElementById('navToggle');
  var ov  = document.getElementById('navOverlay');
  if (!btn || !ov) return;
  function toggle(open) {
    var willOpen = open !== undefined ? open : !document.body.classList.contains('nav-open');
    document.body.classList.toggle('nav-open', willOpen);
    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    ov.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
  }
  btn.addEventListener('click', function () { toggle(); });
  ov.addEventListener('click', function (e) { if (e.target.tagName === 'A') toggle(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });
})();

// Toggle tema chiaro/scuro (persistito in localStorage).
(function () {
  var tt = document.getElementById('themeToggle');
  if (!tt) return;
  tt.addEventListener('click', function () {
    var on = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('zk-theme', on ? 'dark' : 'light'); } catch (e) {}
  });
})();
