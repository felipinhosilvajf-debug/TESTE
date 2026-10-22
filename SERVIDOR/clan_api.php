<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user_login'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Faça login para acessar o painel do clã.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/admin/includes/database.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        'SELECT obj_Id, char_name, clanid
         FROM characters
         WHERE account_name = ? AND clanid > 0
         ORDER BY char_name ASC'
    );
    $stmt->execute([$_SESSION['user_login']]);
    $characters = $stmt->fetchAll();

    if (!$characters) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'Nenhum personagem desta conta pertence a um clã.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $clanIds = array_values(array_unique(array_map(
        static fn(array $character): int => (int)$character['clanid'],
        $characters
    )));

    // Por segurança, esta primeira versão é somente leitura.
    $clans = [];
    $clanStmt = $pdo->prepare(
        'SELECT cd.clan_id, cd.clan_level, cd.reputation_score,
                cs.name AS clan_name, cs.leader_id
         FROM clan_data cd
         LEFT JOIN clan_subpledges cs
           ON cs.clan_id = cd.clan_id AND cs.type = 0
         WHERE cd.clan_id = ?'
    );
    $rankStmt = $pdo->prepare(
        'SELECT rank_level FROM vb_clan_ranks
         WHERE clan_id = ? AND character_id = ?'
    );
    // O saldo verdadeiro do castelo fica na tabela do jogo, não na tesouraria personalizada do painel.
    $castleStmt = $pdo->prepare(
        'SELECT id AS castle_id, name AS castle_name, treasury AS castle_treasury
         FROM castle WHERE ownerId = ? LIMIT 1'
    );
    $membersStmt = $pdo->prepare(
        'SELECT c.obj_Id, c.char_name, c.online,
                COALESCE(r.rank_level, 1) AS rank_level
         FROM characters c
         LEFT JOIN vb_clan_ranks r
           ON r.clan_id = c.clanid AND r.character_id = c.obj_Id
         WHERE c.clanid = ?
         ORDER BY rank_level DESC, c.char_name ASC'
    );
    $historyStmt = $pdo->prepare(
        'SELECT l.action_type, l.amount, l.balance_before, l.balance_after,
                l.details, l.created_at, c.char_name
         FROM vb_clan_treasury_log l
         LEFT JOIN characters c ON c.obj_Id = l.character_id
         WHERE l.clan_id = ?
           AND (l.details IS NULL OR l.details NOT LIKE 'Saldo inicial de teste%')
         ORDER BY l.id DESC
         LIMIT 20'
    );

    foreach ($clanIds as $clanId) {
        $clanStmt->execute([$clanId]);
        $clan = $clanStmt->fetch();
        if (!$clan) {
            continue;
        }

        $rankStmt->execute([$clanId, (int)$characters[0]['obj_Id']]);
        $castleStmt->execute([$clanId]);
        $castle = $castleStmt->fetch();

        $membersStmt->execute([$clanId]);
        $members = $membersStmt->fetchAll();

        $historyStmt->execute([$clanId]);
        $history = $historyStmt->fetchAll();

        $clan['clan_id'] = (int)$clan['clan_id'];
        $clan['clan_level'] = (int)$clan['clan_level'];
        $clan['reputation_score'] = (int)$clan['reputation_score'];
        $clan['castle_id'] = $castle ? (int)$castle['castle_id'] : null;
        $clan['castle_name'] = $castle ? $castle['castle_name'] : null;
        $clan['treasury_balance'] = $castle ? (int)$castle['castle_treasury'] : 0;
        $clan['has_castle'] = (bool)$castle;
        $clan['my_rank_level'] = (int)($rankStmt->fetchColumn() ?: 1);
        $clan['members'] = array_map(static function (array $member): array {
            return [
                'character_id' => (int)$member['obj_Id'],
                'name' => $member['char_name'],
                'online' => (bool)$member['online'],
                'rank_level' => (int)$member['rank_level']
            ];
        }, $members);
        $clan['treasury_history'] = array_map(static function (array $entry): array {
            return [
                'action_type' => $entry['action_type'],
                'amount' => (int)$entry['amount'],
                'balance_before' => (int)$entry['balance_before'],
                'balance_after' => (int)$entry['balance_after'],
                'details' => $entry['details'],
                'created_at' => $entry['created_at'],
                'character_name' => $entry['char_name']
            ];
        }, $history);
        $clans[] = $clan;
    }

    echo json_encode([
        'ok' => true,
        'characters' => array_map(static fn(array $character): array => [
            'character_id' => (int)$character['obj_Id'],
            'name' => $character['char_name'],
            'clan_id' => (int)$character['clanid']
        ], $characters),
        'clans' => $clans
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    error_log('Clan API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Não foi possível carregar os dados do clã. Verifique o log do servidor.'
    ], JSON_UNESCAPED_UNICODE);
}
