<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../admin/includes/auth.php';

// NÃO chamar require_admin() aqui.
// Este arquivo é público e retorna somente a estatística pública.

global $pdo;

$totalAdenaSemStaff = 0;

try {
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(i.count), 0) AS total_adena
        FROM items i
        INNER JOIN characters c
            ON c.obj_Id = i.owner_id
        WHERE i.item_id = 57
          AND i.owner_id > 0
          AND i.count > 0
          AND c.accesslevel <> 100
    ");

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $totalAdenaSemStaff = (int) $row['total_adena'];
    }

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'adena' => $totalAdenaSemStaff
]);