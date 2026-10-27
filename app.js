(function () {
  'use strict';
  var $ = function (s) { return document.querySelector(s); };
  var screens = { 1: $('#s1'), 2: $('#s2'), 3: $('#s3') };
  var dots = document.querySelectorAll('.steps i');
  var st = { mobile: '', exists: false, first: '', hasPhoto: false, photo: null };

  function show(n) {
    for (var k in screens) screens[k].classList.toggle('active', +k === n);
    for (var i = 0; i < dots.length; i++) dots[i].classList.toggle('on', i === n - 1);
    window.scrollTo(0, 0);
  }
  function busy(form, on) { form.querySelector('.btn').classList.toggle('busy', on); }
  function fail(id, msg, field) {
    $(id).textContent = msg || '';
    if (field) {
      field.classList.remove('bad'); void field.offsetWidth; field.classList.add('bad');
      var inp = field.querySelector('input'); if (inp) inp.focus();
    }
  }
  function clearBad() { var b = document.querySelectorAll('.bad'); for (var i = 0; i < b.length; i++) b[i].classList.remove('bad'); }

  function post(fd) {
    return fetch('api.php', { method: 'POST', body: fd, cache: 'no-store' }).then(function (r) {
      return r.json().then(function (j) { return j; }, function () { throw new Error('bad'); });
    });
  }
  var NET = 'Couldn’t reach the server. Check your Wi-Fi and try again.';

  /* ---------- Step 1: mobile ---------- */
  var f1 = $('#f1'), mobile = $('#mobile');
  mobile.addEventListener('input', function () { $('#e1').textContent = ''; mobile.parentNode.classList.remove('bad'); });
  f1.addEventListener('submit', function (e) {
    e.preventDefault();
    var digits = mobile.value.replace(/\D+/g, '');
    if (digits.length < 10) return fail('#e1', 'Please enter a valid mobile number.', mobile.parentNode);
    var fd = new FormData(); fd.append('action', 'lookup'); fd.append('mobile', mobile.value);
    busy(f1, true);
    post(fd).then(function (j) {
      busy(f1, false);
      if (!j.ok) return fail('#e1', j.error, mobile.parentNode);
      st.mobile = j.mobile; st.exists = j.exists; st.first = j.first_name; st.hasPhoto = j.has_photo;
      prepareStep2(); show(2);
      setTimeout(function () { var t = st.exists ? null : $('#name'); if (t) t.focus(); }, 350);
    }).catch(function () { busy(f1, false); fail('#e1', NET); });
  });

  /* ---------- Step 2: details ---------- */
  var s2 = $('#s2'), f2 = $('#f2');
  function prepareStep2() {
    s2.classList.toggle('is-returning', st.exists);
    s2.classList.toggle('has-photo', st.exists && st.hasPhoto);
    if (st.exists) {
      $('#s2-eyebrow').textContent = 'Welcome back';
      $('#s2-title').innerHTML = 'Good to see you,<br><span id="fn"></span>.';
      $('#fn').textContent = st.first;
      $('#s2-lead').textContent = 'We found your saved details. Just tell us why you’re visiting today.';
    } else {
      $('#s2-eyebrow').textContent = 'Your details';
      $('#s2-title').innerHTML = 'Tell us a little<br>about yourself.';
      $('#s2-lead').textContent = 'Since this is your first visit, we need a few quick details.';
    }
  }
  $('#back').addEventListener('click', function () { show(1); setTimeout(function () { mobile.focus(); }, 300); });

  // purpose chips
  var other = $('#other'), otherWrap = $('#other-wrap');
  f2.addEventListener('change', function (e) {
    if (e.target.name === 'p') {
      var isOther = e.target.value === 'Other';
      otherWrap.hidden = !isOther;
      if (isOther) other.focus();
      $('#e2').textContent = ''; clearBad();
    }
  });
  $('#name').addEventListener('input', function () { $('#e2').textContent = ''; this.parentNode.classList.remove('bad'); });
  other.addEventListener('input', function () { $('#e2').textContent = ''; this.parentNode.classList.remove('bad'); });

  // photo: compress on-device so the upload is ~100 KB even on slow Wi-Fi
  function compress(file) {
    return new Promise(function (res) {
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () {
        var max = 720, w0 = img.naturalWidth, h0 = img.naturalHeight, r = Math.min(1, max / Math.max(w0, h0));
        var c = document.createElement('canvas'); c.width = Math.round(w0 * r); c.height = Math.round(h0 * r);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        if (c.toBlob) c.toBlob(function (b) { res(b || file); }, 'image/jpeg', 0.82); else res(file);
      };
      img.onerror = function () { URL.revokeObjectURL(url); res(file); };
      img.src = url;
    });
  }
  function onFile(e) {
    var f = e.target.files && e.target.files[0];
    if (!f) return;
    compress(f).then(function (blob) {
      st.photo = blob;
      var im = $('#avatar-img');
      if (im.src) URL.revokeObjectURL(im.src);
      im.src = URL.createObjectURL(blob); im.hidden = false;
      $('#avatar').classList.add('has');
      $('#photo-title').textContent = 'Looking good';
      $('#take').textContent = 'Retake';
    });
    e.target.value = '';
  }
  $('#cam').addEventListener('change', onFile);
  $('#gal').addEventListener('change', onFile);
  $('#avatar').addEventListener('click', function () { $('#cam').click(); });
  $('#take').addEventListener('click', function () { $('#cam').click(); });
  $('#pick').addEventListener('click', function () { $('#gal').click(); });

  f2.addEventListener('submit', function (e) {
    e.preventDefault();
    var name = $('#name').value.trim();
    if (!st.exists && name.length < 2) return fail('#e2', 'Please enter your name.', $('#name').parentNode);
    var sel = f2.querySelector('input[name=p]:checked');
    if (!sel) return fail('#e2', 'Please choose a reason for your visit.');
    var purpose = sel.value === 'Other' ? other.value.trim() : sel.value;
    if (!purpose) return fail('#e2', 'Please tell us briefly why you’re visiting.', otherWrap);

    var fd = new FormData();
    fd.append('action', 'checkin'); fd.append('mobile', st.mobile);
    fd.append('name', name); fd.append('purpose', purpose);
    fd.append('website', f2.elements.website.value);
    if (st.photo && !(st.exists && st.hasPhoto)) fd.append('photo', st.photo, 'photo.jpg');

    busy(f2, true);
    post(fd).then(function (j) {
      busy(f2, false);
      if (!j.ok) return fail('#e2', j.error);
      $('#s3-title').textContent = 'Thank you, ' + (j.first_name || 'friend') + '.';
      $('#sum-name').textContent = j.name || name || st.first;
      $('#sum-purpose').textContent = j.purpose || purpose;
      $('#sum-time').textContent = j.time || '';
      show(3);
    }).catch(function () { busy(f2, false); fail('#e2', NET); });
  });

  /* ---------- Step 3 ---------- */
  $('#again').addEventListener('click', function () {
    f1.reset(); f2.reset(); st = { mobile: '', exists: false, first: '', hasPhoto: false, photo: null };
    var im = $('#avatar-img'); if (im.src) URL.revokeObjectURL(im.src); im.removeAttribute('src'); im.hidden = true;
    $('#avatar').classList.remove('has'); $('#photo-title').innerHTML = 'Add a photo <em>optional</em>'; $('#take').textContent = 'Take selfie';
    otherWrap.hidden = true; $('#e1').textContent = ''; $('#e2').textContent = '';
    show(1);
  });
})();
