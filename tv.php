<?php require __DIR__ . '/db.php'; $house = h($CONFIG['house_name']); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Visitor Display · <?= $house ?></title>
<link rel="stylesheet" href="tv.css">
</head>
<body>

<!-- Browsers only allow sound after one key press / click. Press OK once. -->
<div id="unlock" class="unlock">
  <div class="unlock-card">
    <div class="bell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg></div>
    <h2>Turn on visitor alerts</h2>
    <p>Press <b>OK</b> on your remote once to enable the notification sound.</p>
    <button id="unlock-btn" type="button">Enable sound</button>
  </div>
</div>

<div id="flash" class="flash"></div>

<div class="wrap">
  <header class="bar">
    <div class="brand">
      <span class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/></svg></span>
      <span class="bt"><b><?= $house ?></b><small>Visitor display</small></span>
    </div>
    <div class="right">
      <span class="status" id="status"><i></i><span id="status-t">Connecting</span></span>
      <button id="snd" class="chipbtn" type="button"><span id="snd-t">Sound off</span></button>
      <span class="clock" id="clock"></span>
    </div>
  </header>

  <div class="body">
    <section class="stage">
      <div id="idle" class="idle">
        <div class="idle-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/></svg></div>
        <h1>All quiet at the door</h1>
        <p>When someone checks in, their details will appear here with a chime.</p>
      </div>

      <div id="arrival" class="arrival" style="display:none">
        <div class="photo"><img id="ph" alt="" style="display:none"><div id="init" class="init"></div></div>
        <div class="info">
          <div class="badge" id="badge"><i></i><span id="badge-t">Latest arrival</span></div>
          <h1 id="nm"></h1>
          <div class="pill" id="pp"></div>
          <ul class="facts">
            <li><span>Mobile</span><b id="mb"></b></li>
            <li><span>Arrived</span><b id="ar"></b><em id="at"></em></li>
          </ul>
          <div class="tag" id="rt"></div>
        </div>
      </div>
    </section>

    <aside class="side">
      <div class="qr-card">
        <div id="qr" class="qr"></div>
        <div class="qr-t"><b>Scan to check in</b><span id="qr-url"></span></div>
      </div>
      <div class="recent" id="recent-box" style="display:none">
        <h3>Earlier</h3>
        <ul id="recent"></ul>
      </div>
    </aside>
  </div>
</div>

<audio id="chime" src="chime.wav" preload="auto"></audio>
<script>window.APP_URL = <?= json_encode(app_url() . 'index.php', JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="qrcode.js"></script>
<script src="tv.js"></script>
</body>
</html>
