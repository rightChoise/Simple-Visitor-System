<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Invalid request.'], 405);

$action = $_POST['action'] ?? '';
$mobile = normalize_mobile($_POST['mobile'] ?? '');
if ($mobile === '') json_out(['ok' => false, 'error' => 'Enter a valid mobile number (10–15 digits).'], 422);

$stmt = $pdo->prepare('SELECT id, name, photo FROM visitors WHERE mobile = ? LIMIT 1');
$stmt->execute([$mobile]);
$visitor = $stmt->fetch();

// ---- Step 1: is this a returning visitor? (reveals first name only) ----
if ($action === 'lookup') {
    json_out([
        'ok'         => true,
        'mobile'     => $mobile,
        'exists'     => (bool)$visitor,
        'first_name' => $visitor ? first_word($visitor['name']) : '',
        'has_photo'  => $visitor ? photo_url($visitor['photo']) !== '' : false,
    ]);
}

// ---- Step 2: record the visit ----
if ($action === 'checkin') {
    // Hidden honeypot field: bots fill it, humans never see it
    if (trim($_POST['website'] ?? '') !== '') json_out(['ok' => true, 'first_name' => 'Guest']);

    $purpose = clean_text($_POST['purpose'] ?? '', 100);
    if ($purpose === '') json_out(['ok' => false, 'error' => 'Please choose or enter the reason for your visit.'], 422);

    // Flood guard: protects the TV if the QR gets scanned by many people at once
    $n = (int)$pdo->query('SELECT COUNT(*) FROM visits WHERE created_at > NOW() - INTERVAL 60 SECOND')->fetchColumn();
    if ($n >= 10) json_out(['ok' => false, 'error' => 'Too many check-ins right now. Please try again in a minute.'], 429);

    if (!$visitor) {
        $name = clean_text($_POST['name'] ?? '', 60);
        if (mb_strlen($name, 'UTF-8') < 2) json_out(['ok' => false, 'error' => 'Please enter your name.'], 422);
    } else {
        $name = $visitor['name'];
    }

    // Optional photo (new visitors, or returning ones who have none yet)
    $photoPath = '';
    $needsPhoto = !$visitor || photo_url($visitor['photo']) === '';
    if ($needsPhoto && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 6 * 1024 * 1024) {
            json_out(['ok' => false, 'error' => 'Photo upload failed. Please try a smaller photo.'], 422);
        }
        list($photoPath, $err) = save_photo($_FILES['photo']['tmp_name']);
        if ($err) json_out(['ok' => false, 'error' => $err], 422);
    }

    try {
        $pdo->beginTransaction();
        if (!$visitor) {
            try {
                $pdo->prepare('INSERT INTO visitors (mobile, name, photo) VALUES (?, ?, ?)')->execute([$mobile, $name, $photoPath]);
                $visitorId = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {            // two phones with same number at the same instant
                $stmt->execute([$mobile]);
                $visitor = $stmt->fetch();
                if (!$visitor) throw $e;
                $visitorId = (int)$visitor['id'];
            }
        } else {
            $visitorId = (int)$visitor['id'];
            if ($photoPath !== '') $pdo->prepare('UPDATE visitors SET photo = ? WHERE id = ?')->execute([$photoPath, $visitorId]);
        }

        // Double-tap protection
        $d = $pdo->prepare('SELECT id FROM visits WHERE visitor_id = ? AND created_at > NOW() - INTERVAL ' . (int)$CONFIG['cooldown_seconds'] . ' SECOND LIMIT 1');
        $d->execute([$visitorId]);
        if (!$d->fetch()) {
            $pdo->prepare('INSERT INTO visits (visitor_id, purpose) VALUES (?, ?)')->execute([$visitorId, $purpose]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_out(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
    }

    json_out(['ok' => true, 'first_name' => first_word($name), 'name' => $name, 'purpose' => $purpose, 'time' => date('g:i A')]);
}

json_out(['ok' => false, 'error' => 'Unknown action.'], 400);
