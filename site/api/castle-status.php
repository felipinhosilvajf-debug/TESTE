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

    // Lista oficial dos nove castelos clássicos de Lineage II.
    // Mantém todos visíveis mesmo se a tabela castle do servidor estiver incompleta.
    $allCastles = [
        1 => ['name' => 'Gludio', 'owner' => null, 'taxPercent' => 0],
        2 => ['name' => 'Dion', 'owner' => null, 'taxPercent' => 0],
        3 => ['name' => 'Giran', 'owner' => null, 'taxPercent' => 0],
        4 => ['name' => 'Oren', 'owner' => null, 'taxPercent' => 0],
        5 => ['name' => 'Aden', 'owner' => null, 'taxPercent' => 0],
        6 => ['name' => 'Innadril', 'owner' => null, 'taxPercent' => 0],
        7 => ['name' => 'Goddard', 'owner' => null, 'taxPercent' => 0],
        8 => ['name' => 'Rune', 'owner' => null, 'taxPercent' => 0],
        9 => ['name' => 'Schuttgart', 'owner' => null, 'taxPercent' => 0],
    ];

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

    // Sobrescreve os valores padrão com os dados reais encontrados no banco.
    foreach ($rows as $row) {
        $id = (int)($row['castle_id'] ?? 0);
        if ($id < 1 || $id > 9) {
            continue;
        }

        $owner = trim((string)($row['clan_name'] ?? ''));
        $allCastles[$id] = [
            'name' => (string)($row['castle_name'] ?: $allCastles[$id]['name']),
            'owner' => $owner !== '' ? $owner : null,
            'taxPercent' => (float)($row['tax_percent'] ?? 0),
        ];
    }

    $castles = [];
    foreach ($allCastles as $id => $castle) {
        $castles[] = [
            'id' => $id,
            'name' => $castle['name'],
            'owner' => $castle['owner'],
            'taxPercent' => $castle['taxPercent'],
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
