<?php
// Connection + small helpers shared by every page.
// The database and tables are created automatically on first run.

$CONFIG = require __DIR__ . '/config.php';
date_default_timezone_set($CONFIG['timezone']);

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function install_schema(PDO $pdo, $dbname) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        photo VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        visitor_id INT UNSIGNED NOT NULL,
        purpose VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_visits_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
    ) CHARACTER SET utf8mb4");
}

$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$dsnBase = 'mysql:host=' . $CONFIG['db_host'] . ';port=' . (int)$CONFIG['db_port'] . ';charset=utf8mb4';

try {
    try {
        $pdo = new PDO($dsnBase . ';dbname=' . $CONFIG['db_name'], $CONFIG['db_user'], $CONFIG['db_pass'], $opts);
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) !== 1049) throw $e;   // 1049 = database doesn't exist yet
        $pdo = new PDO($dsnBase, $CONFIG['db_user'], $CONFIG['db_pass'], $opts);
        install_schema($pdo, $CONFIG['db_name']);
    }
    // Make MySQL use the same timezone as PHP so times on the TV are correct
    $pdo->exec("SET time_zone = '" . date('P') . "'");
} catch (PDOException $e) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'api.php' || $script === 'latest.php') {
        json_out(['ok' => false, 'error' => 'Database connection failed.'], 500);
    }
    http_response_code(500);
    exit('<h3>Database connection failed</h3><p>Start MySQL and check the db_* values in <b>config.php</b>.</p><pre>' . h($e->getMessage()) . '</pre>');
}

// ---------- helpers ----------

function normalize_mobile($raw) {
    $d = preg_replace('/\D+/', '', (string)$raw);
    if (strlen($d) === 12 && substr($d, 0, 2) === '91') $d = substr($d, 2);
    if (strlen($d) === 11 && $d[0] === '0') $d = substr($d, 1);
    return preg_match('/^[0-9]{10,15}$/', $d) ? $d : '';
}

function clean_text($s, $max) {
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$s);
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    return mb_substr($s, 0, $max, 'UTF-8');
}

function first_word($name) {
    $p = preg_split('/\s+/u', trim((string)$name));
    return $p[0] ?? '';
}

// Resolve a stored photo path to a URL only if the file really exists
function photo_url($path) {
    if (!$path) return '';
    $name = basename(str_replace('\\', '/', (string)$path));
    return is_file(__DIR__ . '/uploads/' . $name) ? 'uploads/' . rawurlencode($name) : '';
}

// Resize + re-encode an uploaded image as a small JPEG. Returns [path|null, error|null]
function save_photo($tmp) {
    $info = @getimagesize($tmp);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return [null, 'Please use a JPG, PNG or WEBP photo.'];
    }
    if ($info[0] * $info[1] > 40000000) return [null, 'That photo is too large.'];

    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return [null, 'Could not save photo (folder permission).'];
    $name = bin2hex(random_bytes(12)) . '.jpg';

    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $src = @imagecreatefromstring(file_get_contents($tmp));
        if (!$src) return [null, 'Could not read that photo.'];
        $w = imagesx($src); $h = imagesy($src);
        $max = 720; $r = min(1, $max / max($w, $h));
        $nw = max(1, (int)round($w * $r)); $nh = max(1, (int)round($h * $r));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = imagejpeg($dst, $dir . '/' . $name, 82);
        imagedestroy($src); imagedestroy($dst);
        return $ok ? ['uploads/' . $name, null] : [null, 'Could not save photo (folder permission).'];
    }
    // GD not installed: store as-is (extension forced from verified mime type)
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime']];
    $name = preg_replace('/\.jpg$/', '.' . $ext, $name);
    return move_uploaded_file($tmp, $dir . '/' . $name) ? ['uploads/' . $name, null] : [null, 'Could not save photo (folder permission).'];
}

// The address the QR code should open (this folder's index.php on your Wi-Fi)
function lan_ip() {
    $s = @stream_socket_client('udp://8.8.8.8:53', $en, $es, 1);   // no packet is sent
    if ($s) {
        $n = stream_socket_get_name($s, false); fclose($s);
        $ip = $n ? explode(':', $n)[0] : '';
        if ($ip && $ip !== '0.0.0.0' && $ip !== '127.0.0.1') return $ip;
    }
    $ip = gethostbyname(gethostname());
    return $ip ?: '127.0.0.1';
}

function app_url() {
    global $CONFIG;
    if (!empty($CONFIG['base_url'])) return rtrim($CONFIG['base_url'], '/') . '/';
    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $hostFull = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host   = preg_replace('/:\d+$/', '', $hostFull);
    $port   = (int)($_SERVER['SERVER_PORT'] ?? 80);
    if (in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) $host = lan_ip();
    $p = ($port === 80 && !$https) || ($port === 443 && $https) ? '' : ':' . $port;
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $p . $dir . '/';
}
