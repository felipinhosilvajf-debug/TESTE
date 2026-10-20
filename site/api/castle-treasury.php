<?php
declare(strict_types=1);

/*
 * API pública somente de leitura para o total guardado nos cofres dos castelos.
 * Usa a mesma conexão PDO do site, definida em /db.php.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // Procura o db.php na raiz do site e, como alternativa, na pasta /site.
    $dbCandidates = [
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db.php',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db.php',
    ];
    $rootDb = null;
    foreach ($dbCandidates as $candidate) {
        if (is_file($candidate)) {
            $rootDb = $candidate;
            break;
        }
    }

    if ($rootDb === null) {
        throw new RuntimeException('Arquivo db.php não encontrado nos caminhos esperados.');
    }

    require_once $rootDb;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Conexão do banco indisponível.');
    }

    $stmt = $pdo->query(
        'SELECT COALESCE(SUM(treasury), 0) FROM castle'
    );

    $total = (int) $stmt->fetchColumn();

    echo json_encode(
        [
            'success' => true,
            'castleTreasury' => $total
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    error_log('[castle-treasury.php] ' . $e->getMessage());
    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'castleTreasury' => null,
            'error' => 'Não foi possível consultar o tesouro dos castelos.'
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}
