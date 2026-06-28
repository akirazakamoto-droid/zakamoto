// ------------------------------------------------------------------
// Menu burger (sempre attivo) + galleria masonry con infinite scroll.
// L'ordine delle opere è quello del feed (id DESC = inserimento recente).
// Distribuzione round-robin nelle colonne: l'ordine di lettura resta
// dall'alto verso il basso e da sinistra verso destra.
// ------------------------------------------------------------------

/* ---------- Galleria (il menu burger è in menu.js, caricato ovunque) ---------- */
(function () {
  var masonry  = document.getElementById('masonry');
  var sentinel = document.getElementById('sentinel');
  var loader   = document.getElementById('loader');
  if (!masonry) return;

  var FEED_SRC = masonry.getAttribute('data-src') || '';   // '' = opere, 'atelier' = archivio
  var FEED_W = '';   // filtro serie (cat). vuoto = tutte
  try { FEED_W = new URLSearchParams(location.search).get('serie') || ''; } catch (e) {}
  // seed casuale per sessione: ordine random ma coerente tra le pagine dell'infinite scroll
  var SEED = Math.floor(Math.random() * 2000000000) + 1;
  var ROWS    = masonry.classList.contains('masonry-rows'); // layout a righe (ordine rigoroso)
  var HIDE_MODE = masonry.getAttribute('data-mode') === 'hide'; // clic = oscura opera
  var BATCH   = 20;
  var items   = [];      // tutte le opere caricate, in ordine
  var offset  = 0;
  var total   = null;
  var loading = false;
  var done    = false;
  var cols    = 0;
  var colEls  = [];
  var colH    = [];   // altezze stimate delle colonne (per il bilanciamento)
  var lbPending = null;   // indice opera da mostrare nel lightbox dopo il fetch

  function colCount() {
    var w = window.innerWidth;
    if (w <= 640) return 1;
    if (w <= 1100) return 2;
    return 3;
  }

  // (Ri)costruisce le colonne e ridistribuisce tutti gli item in ordine.
  function layout() {
    masonry.innerHTML = '';
    colEls = [];
    if (!ROWS) {
      cols = colCount();
      colH = [];
      for (var i = 0; i < cols; i++) {
        var c = document.createElement('div');
        c.className = 'mcol';
        masonry.appendChild(c);
        colEls.push(c);
        colH.push(0);
      }
    }
    for (var k = 0; k < items.length; k++) place(items[k], k);
  }

  // Inserisce un singolo item: in modalità righe va direttamente nel contenitore
  // (ordine rigoroso left->right, top->bottom); altrimenti round-robin per colonne.
  function place(it, idx) {
    var fig = document.createElement('figure');
    fig.className = 'tile';
    var href = it.nolink ? '#' : ('dettaglio.php?id=' + it.id);
    var cap = '';
    if (it.title) cap += '<span class="t">' + esc(it.title) + '</span>';
    if (it.meta)  cap += '<span class="m">' + esc(it.meta) + '</span>';
    fig.innerHTML =
      '<a class="tile-link" href="' + href + '" data-idx="' + idx + '">' +
        '<img src="' + esc(it.thumb) + '" alt="' + esc(it.title) + '" loading="lazy" decoding="async">' +
      '</a>' +
      (cap ? '<figcaption>' + cap + '</figcaption>' : '');
    if (ROWS) { masonry.appendChild(fig); return; }
    // colonna più corta (bilanciamento per altezza stimata)
    var t = 0;
    for (var k = 1; k < cols; k++) if (colH[k] < colH[t]) t = k;
    colEls[t].appendChild(fig);
    var colW = colEls[t].clientWidth || (masonry.clientWidth / cols) || 300;
    var rh = it.rh || 1;
    colH[t] += colW * rh + 46;   // immagine stimata + didascalia
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  // testo sicuro con URL resi cliccabili
  function linkify(s) {
    return esc(s).replace(/(https?:\/\/[^\s]+)/g, function (u) {
      return '<a class="lb-link" href="' + u + '" target="_blank" rel="noopener">' + u + '</a>';
    });
  }

  function fetchMore() {
    if (loading || done) return;
    loading = true;
    loader.style.display = 'block';
    var url = 'feed.php?offset=' + offset + '&limit=' + BATCH +
              (FEED_SRC ? '&src=' + encodeURIComponent(FEED_SRC) : '') +
              (FEED_W ? '&w=' + encodeURIComponent(FEED_W) : '') +
              '&seed=' + SEED;
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        total = data.total;
        var fresh = data.items || [];
        var startIdx = items.length;
        for (var i = 0; i < fresh.length; i++) {
          items.push(fresh[i]);
          place(fresh[i], startIdx + i);   // append senza re-layout
        }
        offset += fresh.length;
        if (fresh.length === 0 || offset >= total) { done = true; loader.style.display = 'none'; }
        loading = false;
        // se il lightbox attende un'opera non ancora caricata, mostrala ora
        if (lbPending !== null && lbPending < items.length) {
          var p = lbPending; lbPending = null; showLb(p);
        }
      })
      .catch(function () { loading = false; loader.textContent = 'Errore di caricamento'; });
  }

  // Primo render
  layout();
  fetchMore();

  // ---- Barra serie (filtro per cat) ----
  function resetFeed() {
    items = []; offset = 0; total = null; done = false; loading = false;
    layout();                       // svuota e ricrea le colonne
    loader.style.display = 'block';
    fetchMore();
  }
  var seriesBar = document.querySelector('.series-bar');
  if (seriesBar) {
    var markActive = function () {
      seriesBar.querySelectorAll('.series-link').forEach(function (b) {
        b.classList.toggle('active', (b.getAttribute('data-w') || '') === FEED_W);
      });
    };
    markActive();
    seriesBar.addEventListener('click', function (e) {
      var b = e.target.closest('.series-link');
      if (!b) return;
      var k = b.getAttribute('data-w') || '';
      if (k === FEED_W) return;
      FEED_W = k;
      markActive();
      try {
        var u = new URL(location.href);
        if (k) u.searchParams.set('serie', k); else u.searchParams.delete('serie');
        history.replaceState(null, '', u);
      } catch (e2) {}
      window.scrollTo(0, 0);
      resetFeed();
    });
  }

  // Infinite scroll
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) fetchMore();
    }, { rootMargin: '600px 0px' }).observe(sentinel);
  } else {
    window.addEventListener('scroll', function () {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 600) fetchMore();
    });
  }

  // Re-layout su resize quando cambia il numero di colonne
  var rt;
  window.addEventListener('resize', function () {
    clearTimeout(rt);
    rt = setTimeout(function () { if (!ROWS && colCount() !== cols) layout(); }, 200);
  });

  /* ---------- Lightbox / carosello (delegation + frecce + dissolvenza) ---------- */
  var lb    = document.getElementById('lightbox');
  var lbImg = document.getElementById('lb-img');
  var lbCap = document.getElementById('lb-cap');
  var lbTit = document.getElementById('lb-title');
  var lbMet = document.getElementById('lb-meta');
  var lbPrev = document.getElementById('lbPrev');
  var lbNext = document.getElementById('lbNext');
  var lbIndex = -1;

  // Mostra l'opera idx con dissolvenza verso il bianco e poi in entrata.
  function showLb(idx) {
    if (idx < 0 || idx >= items.length) return;
    lbIndex = idx;
    var it = items[idx];
    lbImg.classList.add('is-fading');
    lbCap.classList.add('is-fading');
    setTimeout(function () {
      lbImg.onload = function () {
        lbImg.classList.remove('is-fading');
        lbCap.classList.remove('is-fading');
      };
      lbImg.src = it.big;
      lbImg.alt = it.title || '';
      lbTit.textContent = it.title || '';
      var base = it.meta || it.date || '';
      var html = esc(base);
      var extraHtml = '';
      if (it.extra_link) {
        extraHtml = '<a class="lb-link" href="' + esc(it.extra_link) + '" target="_blank" rel="noopener">' + esc(it.extra || it.extra_link) + '</a>';
      } else if (it.extra) {
        extraHtml = linkify(it.extra);
      }
      if (extraHtml) html += ' - ' + extraHtml;
      if (it.project && it.project.length) {
        html += '<span class="lb-project">Project on motolese.com: ' + it.project.map(function (l) {
          return '<a class="lb-link" href="' + esc(l[1]) + '" target="_blank" rel="noopener">' + esc(l[0]) + '</a>';
        }).join(' · ') + '</span>';
      }
      lbMet.innerHTML = html;
      var lbOwner = document.getElementById('lb-owner');
      if (lbOwner) {
        var o = it.owner;
        if (o) {
          var line, priv = '';
          if (o.mine) {
            line = 'Owner: ' + (o.name || '') + (o.archive ? ' · Archive No. ' + o.archive : '');
          } else {
            line = 'Collector: ' + (o.code || '') + (o.archive ? ' · Archive No. ' + o.archive : '');
            priv = "Owner details are hidden for privacy — only the collector can view the full details of their own works.";
          }
          var authBtn = '';
          if (o.archive) {
            var authUrl = location.origin + '/autentica.php?id=' + it.id;
            authBtn = '<div class="lb-auth-wrap"><a class="lb-auth" href="' + authUrl + '" target="_blank" rel="noopener">Print certificate</a></div>';
          }
          lbOwner.innerHTML = '<div class="lb-owner-line">' + esc(line) + '</div>' + authBtn +
            (priv ? '<div class="lb-owner-priv">' + esc(priv) + '</div>' : '');
          lbOwner.style.display = '';
        } else {
          lbOwner.innerHTML = '';
          lbOwner.style.display = 'none';
        }
      }
      if (lbImg.complete) {                 // immagine in cache: onload può non scattare
        requestAnimationFrame(function () {
          lbImg.classList.remove('is-fading');
          lbCap.classList.remove('is-fading');
        });
      }
    }, 200);
  }

  function openLb(idx) {
    lb.classList.add('open');
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    showLb(idx);
  }
  function closeLb() {
    lb.classList.remove('open');
    lb.setAttribute('aria-hidden', 'true');
    lbImg.src = '';
    lbIndex = -1;
    document.body.style.overflow = '';
    var m = document.getElementById('lbShareMenu'); if (m) m.hidden = true;
  }
  // Naviga; se l'opera successiva non è ancora caricata, la carica e poi la mostra.
  function go(dir) {
    var t = lbIndex + dir;
    if (t < 0) return;
    if (t >= items.length) {
      if (done) return;
      lbPending = t;
      fetchMore();
      return;
    }
    showLb(t);
  }

  masonry.addEventListener('click', function (e) {
    var a = e.target.closest('.tile-link');
    if (!a) return;
    e.preventDefault();
    var idx = parseInt(a.getAttribute('data-idx'), 10) || 0;
    if (HIDE_MODE) {
      var it = items[idx];
      if (!it || !it.id) return;
      if (!confirm('Oscurare questa opera da Art?')) return;
      var fig = a.closest('.tile');
      fetch('hide_work.php?id=' + it.id, { method: 'POST', credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (j && j.ok && fig) {
            fig.style.transition = 'opacity .3s ease';
            fig.style.opacity = '0';
            setTimeout(function () { fig.remove(); }, 300);
          } else { alert('Errore: impossibile oscurare.'); }
        })
        .catch(function () { alert('Errore di rete.'); });
      return;
    }
    openLb(idx);
  });

  // --- Social sharing ---
  var lbShare = document.getElementById('lbShare');
  var lbShareMenu = document.getElementById('lbShareMenu');
  var lbCopy = document.getElementById('lbCopy');

  function shareUrl() {
    var it = items[lbIndex];
    var base = location.origin + location.pathname.replace(/[^\/]*$/, '');
    if (!it || it.nolink) return location.href;
    return base + 'dettaglio.php?id=' + it.id;
  }
  function shareTitle() {
    var it = items[lbIndex];
    return (it && it.title) ? it.title + ' — Akira Zakamoto' : 'Akira Zakamoto';
  }
  function hideShareMenu() { if (lbShareMenu) lbShareMenu.hidden = true; }
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
  // Condivisione nativa: include il FILE immagine + titolo + link (dove supportato).
  function tryNativeShare() {
    if (!navigator.share) return Promise.resolve(false);
    var it = items[lbIndex];
    if (!it) return Promise.resolve(false);
    var url = shareUrl(), title = shareTitle();
    var urlShare = function () {
      return navigator.share({ title: title, text: title, url: url })
        .then(function () { return true; }, function () { return false; });
    };
    if (navigator.canShare) {
      return fetch(it.big).then(function (r) { return r.blob(); }).then(function (blob) {
        var ext = (blob.type && blob.type.indexOf('png') > -1) ? 'png' : 'jpg';
        var file = new File([blob], 'zakamoto.' + ext, { type: blob.type || 'image/jpeg' });
        var data = { files: [file], title: title, text: title + ' — ' + url };
        if (navigator.canShare(data)) {
          return navigator.share(data).then(function () { return true; }, function () { return false; });
        }
        return urlShare();
      }).catch(urlShare);
    }
    return urlShare();
  }

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
      else { window.prompt('Copy link:', url); }
    });
  }

  if (lb) {
    lb.addEventListener('click', function (e) {
      if (e.target === lb || e.target.classList.contains('lb-close')) closeLb();
      else if (!e.target.closest('.lb-share-wrap')) hideShareMenu();
    });
    lbPrev.addEventListener('click', function (e) { e.stopPropagation(); hideShareMenu(); go(-1); });
    lbNext.addEventListener('click', function (e) { e.stopPropagation(); hideShareMenu(); go(1); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'ArrowLeft')  go(-1);
      if (e.key === 'ArrowRight') go(1);
      if (e.key === 'Escape')     closeLb();
    });
  }
})();
