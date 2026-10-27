<?php require __DIR__ . '/db.php'; $house = h($CONFIG['house_name']); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f5f4ef">
<title>Visitor Check-in · <?= $house ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="glow" aria-hidden="true"></div>

<main class="app">
  <header class="top">
    <div class="brand">
      <span class="logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/></svg>
      </span>
      <span class="brand-text"><b><?= $house ?></b><small>Visitor check-in</small></span>
    </div>
    <div class="steps" aria-hidden="true"><i class="on"></i><i></i><i></i></div>
  </header>

  <!-- STEP 1 · mobile number -->
  <section class="screen active" id="s1">
    <p class="eyebrow">Welcome</p>
    <h1>Let us know<br>you’re here.</h1>
    <p class="lead">Enter your mobile number to begin. It takes less than 20 seconds.</p>

    <form id="f1" novalidate>
      <label for="mobile">Mobile number</label>
      <div class="field">
        <input id="mobile" name="mobile" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" placeholder="98765 43210" aria-describedby="e1">
      </div>
      <p class="err" id="e1" role="alert"></p>
      <button class="btn" type="submit"><span class="lbl">Continue</span><svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg><span class="spin"></span></button>
    </form>
  </section>

  <!-- STEP 2 · details -->
  <section class="screen" id="s2">
    <button class="back" type="button" id="back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
      <span>Change number</span>
    </button>

    <p class="eyebrow" id="s2-eyebrow">Your details</p>
    <h1 id="s2-title">Tell us a little<br>about yourself.</h1>
    <p class="lead" id="s2-lead"></p>

    <form id="f2" novalidate autocomplete="off">
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

      <div class="photo only-photo">
        <button type="button" class="avatar" id="avatar" aria-label="Take a selfie">
          <img id="avatar-img" alt="" hidden>
          <svg class="cam" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/></svg>
        </button>
        <div class="photo-text">
          <b id="photo-title">Add a photo <em>optional</em></b>
          <span>Helps the family recognise you.</span>
          <div class="photo-links"><button type="button" id="take">Take selfie</button><i>·</i><button type="button" id="pick">Choose photo</button></div>
        </div>
        <input type="file" id="cam" accept="image/*" capture="user" hidden>
        <input type="file" id="gal" accept="image/*" hidden>
      </div>

      <div class="only-new">
        <label for="name">Full name</label>
        <div class="field"><input id="name" name="name" type="text" autocomplete="name" maxlength="60" placeholder="Your name"></div>
      </div>

      <label id="purpose-label">Reason for visit</label>
      <div class="chips" role="radiogroup" aria-labelledby="purpose-label">
        <label class="chip"><input type="radio" name="p" value="Guest"><span>Guest</span></label>
        <label class="chip"><input type="radio" name="p" value="Delivery"><span>Delivery</span></label>
        <label class="chip"><input type="radio" name="p" value="Family"><span>Family</span></label>
        <label class="chip"><input type="radio" name="p" value="Service / Repair"><span>Service / Repair</span></label>
        <label class="chip"><input type="radio" name="p" value="Meeting"><span>Meeting</span></label>
        <label class="chip"><input type="radio" name="p" value="Other"><span>Other</span></label>
      </div>
      <div class="field other" id="other-wrap" hidden><input id="other" type="text" maxlength="100" placeholder="Tell us briefly"></div>

      <p class="err" id="e2" role="alert"></p>
      <button class="btn" type="submit"><span class="lbl">Notify the family</span><svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg><span class="spin"></span></button>
    </form>
  </section>

  <!-- STEP 3 · success -->
  <section class="screen" id="s3">
    <div class="done" aria-hidden="true">
      <span class="ring"></span>
      <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="25" class="c"/><path d="M15 27l8 8 14-16" class="t"/></svg>
    </div>
    <p class="eyebrow center">Checked in</p>
    <h1 class="center" id="s3-title">Thank you.</h1>
    <p class="lead center">The family has been notified. Please wait here — someone will be with you shortly.</p>

    <dl class="summary">
      <div><dt>Name</dt><dd id="sum-name"></dd></div>
      <div><dt>Reason</dt><dd id="sum-purpose"></dd></div>
      <div><dt>Time</dt><dd id="sum-time"></dd></div>
    </dl>
    <button class="btn ghost" type="button" id="again"><span class="lbl">Check in someone else</span></button>
  </section>

  <p class="privacy">Your details are used only for this visit and are visible only to the household.</p>
</main>

<script src="app.js"></script>
</body>
</html>
