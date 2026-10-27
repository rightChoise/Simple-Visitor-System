SMART VISITOR SYSTEM  (PHP + MySQL, works on XAMPP / MAMP)
===========================================================

WHAT IT DOES
  1. TV shows a QR code (tv.php).
  2. Visitor scans it -> check-in page opens on their phone (index.php).
  3. They enter mobile, name (+ optional selfie) and reason.
  4. TV plays a soft chime and shows their photo, name, reason and time.

SETUP (5 minutes)
  1. Copy this folder into htdocs:
       XAMPP:  C:\xampp\htdocs\simple_visitor_system\
       MAMP :  /Applications/MAMP/htdocs/simple_visitor_system/
  2. Start Apache + MySQL.
  3. MAMP only: open config.php and set  db_port = 8889  and  db_pass = 'root'
     (XAMPP works with the defaults.)
  4. Open  http://localhost/simple_visitor_system/tv.php  (MAMP: add :8888)
     The database and tables are created automatically. No import needed.
  5. Put the same address on the TV browser. Best: open it on the TV using your
     PC's Wi-Fi IP, e.g.  http://192.168.1.20/simple_visitor_system/tv.php
     (Windows: run  ipconfig ; Mac: System Settings > Wi-Fi > Details)
  6. On the TV press OK once on "Enable sound". Browsers block sound until then.
     (After a TV/browser reload you need to press OK once again.)

IMPORTANT FOR THE QR CODE TO WORK
  - The visitor's phone must reach your PC. Use the same Wi-Fi, or open
    port 80 for visitors if they are on mobile data.
  - Windows asks "Allow Apache through firewall?"  -> tick Private networks.
  - The QR auto-detects your PC's Wi-Fi IP. If it shows a wrong address,
    set base_url in config.php, e.g. 'http://192.168.1.20/simple_visitor_system'
  - A router can hand your PC a new IP after restart. Reserve a fixed IP for
    the PC in the router settings so the QR never changes.

CUSTOMISE  (config.php)
  house_name          name shown on the TV and phone
  timezone            default Asia/Kolkata
  cooldown_seconds    double-tap protection (same person counted once)

FILES
  tv.php  tv.css  tv.js      TV display (plain ES5, light on old TV browsers)
  index.php  style.css  app.js   phone check-in
  api.php                    check-in logic, photo resize, spam guards
  latest.php                 tiny JSON the TV polls every 2 seconds
  db.php  config.php         database, settings, helpers
  chime.wav                  notification sound (replace with any short WAV/MP3)
  qrcode.js                  QR generator (MIT licence, Kazuhiko Arase)
  uploads/                   visitor photos (auto-resized to max 720 px)

TROUBLESHOOTING
  No sound ................ press OK on the TV once; check TV/browser volume.
  QR opens a wrong page ... set base_url in config.php.
  "Database connection failed" ... start MySQL; check db_* in config.php.
  Photos missing .......... make sure the uploads folder is writable.
  TV page not updating .... shows "Reconnecting..." in the top right if the
                            PC is unreachable; it recovers by itself.

PRIVACY / SECURITY NOTES
  Built for a home LAN. Before exposing it to the internet add HTTPS and a
  login for tv.php / latest.php, because they show visitor names and numbers.
  The phone page reveals only a returning visitor's first name, never photo
  or number.
