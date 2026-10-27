<?php
// ------------------------------------------------------------------
//  Smart Visitor System — settings. Edit this file only.
// ------------------------------------------------------------------
return [
    // Name shown on the phone page and on the TV
    'house_name' => 'Our Home',

    // Database (XAMPP default: root / empty password / port 3306)
    // MAMP default: port 8889, user root, password root
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'visitor_system',
    'db_user' => 'root',
    'db_pass' => '',

    // Leave empty to auto-detect your PC's Wi-Fi IP for the QR code.
    // Only fill this if the QR shows a wrong address, e.g.
    // 'http://192.168.1.20/simple_visitor_system'
    'base_url' => '',

    'timezone' => 'Asia/Kolkata',

    // Same visitor tapping Submit twice within this many seconds = counted once
    'cooldown_seconds' => 20,
];
