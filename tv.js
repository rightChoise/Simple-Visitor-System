/* TV display logic — plain ES5 so it runs on older smart-TV browsers */
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var audio = $('chime');
  var lastId = null;        // newest visit id we have shown
  var soundOn = false, muted = false, enabledAt = 0;
  var latestAgo = 0, recvAt = 0, newUntil = 0;

  /* ---------- QR code ---------- */
  function renderQR(text) {
    try {
      var q = qrcode(0, 'M'); q.addData(text); q.make();
      var n = q.getModuleCount(), m = 2, size = n + m * 2, d = '', r, c, s;
      for (r = 0; r < n; r++) {
        c = 0;
        while (c < n) {
          if (q.isDark(r, c)) {
            s = c; while (c < n && q.isDark(r, c)) c++;
            d += 'M' + (s + m) + ' ' + (r + m) + 'h' + (c - s) + 'v1h-' + (c - s) + 'z';
          } else c++;
        }
      }
      $('qr').innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size +
        '" shape-rendering="crispEdges"><path d="' + d + '" fill="#0c1411"/></svg>';
    } catch (e) { $('qr').textContent = 'QR error'; }
    $('qr-url').textContent = text.replace(/^https?:\/\//, '').replace(/index\.php$/, '');
  }

  /* ---------- sound ---------- */
  function updateSnd() {
    var b = $('snd'), t = $('snd-t');
    if (!soundOn) { t.textContent = 'Sound off'; b.className = 'chipbtn'; }
    else if (muted) { t.textContent = 'Muted'; b.className = 'chipbtn'; }
    else { t.textContent = 'Sound on'; b.className = 'chipbtn on'; }
  }
  function chime() {
    if (!soundOn || muted) return;
    try {
      audio.currentTime = 0;
      var p = audio.play();
      if (p && p.catch) p.catch(function () { soundOn = false; showUnlock(); updateSnd(); });
    } catch (e) {}
  }
  function showUnlock() { $('unlock').style.display = 'flex'; try { $('unlock-btn').focus(); } catch (e) {} }
  function enableSound() {
    if (soundOn) return;
    soundOn = true; enabledAt = new Date().getTime();
    $('unlock').style.display = 'none';
    updateSnd();
    try {
      audio.currentTime = 0;
      var p = audio.play();           // plays once so you know it works
      if (p && p.catch) p.catch(function () { soundOn = false; showUnlock(); updateSnd(); });
    } catch (e) {}
  }
  document.addEventListener('click', enableSound);
  document.addEventListener('keydown', enableSound);
  document.addEventListener('touchstart', enableSound);
  $('snd').addEventListener('click', function () {
    if (!soundOn || new Date().getTime() - enabledAt < 600) return;
    muted = !muted; updateSnd();
    if (!muted) chime();
  });

  /* ---------- rendering ---------- */
  function ago(s) {
    if (s < 45) return 'Just now';
    var m = Math.round(s / 60);
    if (m < 60) return m + ' min ago';
    var h = Math.floor(m / 60);
    if (h < 24) return h + (h === 1 ? ' hour ago' : ' hours ago');
    var d = Math.floor(h / 24);
    return d + (d === 1 ? ' day ago' : ' days ago');
  }
  function initial(name) { return (name || '?').replace(/^\s+/, '').charAt(0).toUpperCase(); }

  function setBadge(isNew) {
    $('badge').className = 'badge' + (isNew ? ' new' : '');
    $('badge-t').textContent = isNew ? 'New arrival' : 'Latest arrival';
  }

  function renderLatest(v, isNew) {
    $('idle').style.display = 'none';
    var a = $('arrival'); a.style.display = 'flex';
    $('nm').textContent = v.name;
    $('nm').className = v.name.length > 24 ? 'xl' : (v.name.length > 15 ? 'lg' : '');
    $('pp').textContent = v.purpose;
    $('mb').textContent = v.mobile;
    $('at').textContent = v.label;
    $('rt').textContent = v.returning ? 'Returning visitor' : 'First visit';
    $('init').textContent = initial(v.name);
    var img = $('ph');
    if (v.photo_url) {
      img.onerror = function () { img.style.display = 'none'; };
      img.onload = function () { img.style.display = 'block'; };
      img.src = v.photo_url;
    } else { img.style.display = 'none'; img.removeAttribute('src'); }
    setBadge(isNew);
    if (isNew) {
      a.className = 'arrival'; void a.offsetWidth; a.className = 'arrival pop';
      var f = $('flash'); f.className = 'flash'; void f.offsetWidth; f.className = 'flash go';
    }
  }

  function renderRecent(list) {
    var box = $('recent-box'), ul = $('recent');
    ul.innerHTML = '';
    if (!list || !list.length) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    for (var i = 0; i < list.length && i < 4; i++) {
      var v = list[i];
      var li = document.createElement('li');
      var av = document.createElement('span'); av.className = 'av';
      if (v.photo_url) av.style.backgroundImage = 'url("' + v.photo_url.replace(/"/g, '%22') + '")'; else av.textContent = initial(v.name);
      var tx = document.createElement('span'); tx.className = 'tx';
      var b = document.createElement('b'); b.textContent = v.name;
      var sm = document.createElement('small'); sm.textContent = v.purpose;
      tx.appendChild(b); tx.appendChild(sm);
      var tm = document.createElement('span'); tm.className = 'tm'; tm.textContent = ago(v.ago);
      li.appendChild(av); li.appendChild(tx); li.appendChild(tm); ul.appendChild(li);
    }
  }

  function tick() {
    var now = new Date(), h = now.getHours(), mi = now.getMinutes();
    $('clock').textContent = ((h % 12) || 12) + ':' + (mi < 10 ? '0' : '') + mi + ' ' + (h < 12 ? 'AM' : 'PM');
    if (lastId) {
      $('ar').textContent = ago(latestAgo + Math.round((now.getTime() - recvAt) / 1000));
      if (newUntil && now.getTime() > newUntil) { newUntil = 0; setBadge(false); }
    }
  }

  /* ---------- polling ---------- */
  function setStatus(ok) {
    $('status').className = 'status' + (ok ? ' ok' : '');
    $('status-t').textContent = ok ? 'Live' : 'Reconnecting…';
  }
  function handle(d) {
    var v = d.latest;
    if (!v) { lastId = 0; $('idle').style.display = 'block'; $('arrival').style.display = 'none'; renderRecent([]); return; }
    var isNew = lastId !== null && v.id !== lastId;
    if (v.id !== lastId) {
      renderLatest(v, isNew);
      if (isNew) { chime(); newUntil = new Date().getTime() + 25000; }
    }
    lastId = v.id; latestAgo = v.ago; recvAt = new Date().getTime();
    renderRecent(d.recent); tick();
  }
  function poll() {
    var x = new XMLHttpRequest();
    x.open('GET', 'latest.php?t=' + new Date().getTime(), true);
    x.timeout = 6000;
    x.onload = function () {
      var ok = false;
      try { handle(JSON.parse(x.responseText)); ok = true; } catch (e) {}
      setStatus(ok); setTimeout(poll, 2000);
    };
    x.onerror = x.ontimeout = function () { setStatus(false); setTimeout(poll, 3000); };
    x.send();
  }

  /* ---------- keep the screen awake where supported ---------- */
  function wake() { try { if (navigator.wakeLock) navigator.wakeLock.request('screen').catch(function () {}); } catch (e) {} }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) wake(); });

  renderQR(window.APP_URL);
  updateSnd(); tick(); setInterval(tick, 15000);
  showUnlock(); wake(); poll();
})();
