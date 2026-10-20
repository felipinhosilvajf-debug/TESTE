<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // O db.php pode estar na raiz do site ou, em algumas instalações, dentro de /site.
    $dbCandidates = [
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db.php',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db.php',
    ];
    $dbFile = null;
    foreach ($dbCandidates as $candidate) {
        if (is_file($candidate)) {
            $dbFile = $candidate;
            break;
        }
    }
    if ($dbFile === null) {
        throw new RuntimeException('Arquivo de conexão db.php não encontrado.');
    }

    require_once $dbFile;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('A conexão PDO não foi inicializada.');
    }

    $sql = "
        SELECT
            c.ID AS castle_id,
            c.name AS castle_name,
            c.tax_percent,
            cd.clan_id,
            (
                SELECT cs.name
                FROM clan_subpledges cs
                WHERE cs.clan_id = cd.clan_id
                ORDER BY cs.type ASC
                LIMIT 1
            ) AS clan_name
        FROM castle c
        LEFT JOIN clan_data cd ON cd.hasCastle = c.ID
        ORDER BY c.ID ASC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $castles = [];

    foreach ($rows as $row) {
        $owner = trim((string)($row['clan_name'] ?? ''));
        $castles[] = [
            'id' => (int)$row['castle_id'],
            'name' => (string)$row['castle_name'],
            'owner' => $owner !== '' ? $owner : null,
            'taxPercent' => (float)($row['tax_percent'] ?? 0),
        ];
    }

    echo json_encode(
        ['success' => true, 'castles' => $castles],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    error_log('[castle-status.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(
        ['success' => false, 'castles' => [], 'error' => 'Não foi possível consultar os castelos.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}
