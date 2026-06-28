// Lightbox per le cover (zoom immagine come i quadri) con dissolvenza e sharing.
(function () {
  var cards = Array.prototype.slice.call(document.querySelectorAll('.cover-zoom'));
  var lb = document.getElementById('lightbox');
  if (!cards.length || !lb) return;

  var lbImg = document.getElementById('lb-img');
  var lbTit = document.getElementById('lb-title');
  var lbShare = document.getElementById('lbShare');
  var lbShareMenu = document.getElementById('lbShareMenu');
  var lbCopy = document.getElementById('lbCopy');
  var idx = -1;

  var items = cards.map(function (c) {
    return { img: c.getAttribute('data-img'), title: c.getAttribute('data-title') || '' };
  });

  function absUrl(u) {
    if (!u) return location.href;
    return /^https?:\/\//.test(u) ? u : (location.origin + u);
  }
  function shareUrl()  { return location.origin + location.pathname; }
  function shareTitle(){ var it = items[idx]; return (it && it.title) ? it.title + ' — Akira Zakamoto' : 'Akira Zakamoto'; }
  function hideMenu()  { if (lbShareMenu) lbShareMenu.hidden = true; }

  function show(i) {
    if (i < 0 || i >= items.length) return;
    idx = i;
    var it = items[i];
    lbImg.classList.add('is-fading');
    setTimeout(function () {
      lbImg.onload = function () { lbImg.classList.remove('is-fading'); };
      lbImg.src = it.img;
      lbImg.alt = it.title;
      lbTit.textContent = it.title;
      if (lbImg.complete) requestAnimationFrame(function () { lbImg.classList.remove('is-fading'); });
    }, 180);
  }
  function open(i) {
    lb.classList.add('open');
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    show(i);
  }
  function close() {
    lb.classList.remove('open');
    lb.setAttribute('aria-hidden', 'true');
    lbImg.src = '';
    idx = -1;
    document.body.style.overflow = '';
    hideMenu();
  }

  function refreshShareLinks() {
    if (!lbShareMenu) return;
    var u = encodeURIComponent(shareUrl());
    var t = encodeURIComponent(shareTitle());
    var map = {
      facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + u,
      x:        'https://twitter.com/intent/tweet?url=' + u + '&text=' + t,
      whatsapp: 'https://wa.me/?text=' + t + '%20' + u
    };
    lbShareMenu.querySelectorAll('a[data-net]').forEach(function (a) {
      a.href = map[a.getAttribute('data-net')] || '#';
    });
  }
  function tryNativeShare() {
    if (!navigator.share) return Promise.resolve(false);
    var it = items[idx];
    if (!it) return Promise.resolve(false);
    var url = shareUrl(), title = shareTitle();
    var urlShare = function () {
      return navigator.share({ title: title, text: title, url: url }).then(function () { return true; }, function () { return false; });
    };
    if (navigator.canShare) {
      return fetch(it.img).then(function (r) { return r.blob(); }).then(function (blob) {
        var ext = (blob.type && blob.type.indexOf('png') > -1) ? 'png' : 'jpg';
        var file = new File([blob], 'zakamoto.' + ext, { type: blob.type || 'image/jpeg' });
        var data = { files: [file], title: title, text: title + ' — ' + url };
        if (navigator.canShare(data)) return navigator.share(data).then(function () { return true; }, function () { return false; });
        return urlShare();
      }).catch(urlShare);
    }
    return urlShare();
  }

  cards.forEach(function (c, i) { c.addEventListener('click', function () { open(i); }); });

  if (lbShare) {
    lbShare.addEventListener('click', function (e) {
      e.stopPropagation();
      tryNativeShare().then(function (ok) {
        if (!ok) { refreshShareLinks(); lbShareMenu.hidden = !lbShareMenu.hidden; }
      });
    });
  }
  if (lbCopy) {
    lbCopy.addEventListener('click', function (e) {
      e.stopPropagation();
      var url = shareUrl();
      var done = function () { lbCopy.textContent = 'Link copied'; setTimeout(function () { lbCopy.textContent = 'Copy link'; }, 1500); };
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, done);
      else window.prompt('Copy link:', url);
    });
  }

  lb.addEventListener('click', function (e) {
    if (e.target === lb || e.target.classList.contains('lb-close')) close();
    else if (!e.target.closest('.lb-share-wrap')) hideMenu();
  });
  document.addEventListener('keydown', function (e) {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
  });
})();
