<?php
// Tiny JSON endpoint the TV polls every ~2 seconds.
require __DIR__ . '/db.php';

$rows = $pdo->query("SELECT vi.id, v.name, v.mobile, v.photo, vi.purpose,
        TIMESTAMPDIFF(SECOND, vi.created_at, NOW()) AS ago,
        DATE_FORMAT(vi.created_at, '%e %b, %l:%i %p') AS label,
        (SELECT COUNT(*) FROM visits x WHERE x.visitor_id = vi.visitor_id AND x.id < vi.id) AS prev
    FROM visits vi JOIN visitors v ON v.id = vi.visitor_id
    ORDER BY vi.id DESC LIMIT 5")->fetchAll();

$list = [];
foreach ($rows as $r) {
    $list[] = [
        'id'        => (int)$r['id'],
        'name'      => $r['name'],
        'mobile'    => $r['mobile'],
        'purpose'   => $r['purpose'],
        'photo_url' => photo_url($r['photo']),
        'ago'       => max(0, (int)$r['ago']),
        'label'     => $r['label'],
        'returning' => (int)$r['prev'] > 0,
    ];
}
json_out(['ok' => true, 'latest' => $list[0] ?? null, 'recent' => array_slice($list, 1)]);
