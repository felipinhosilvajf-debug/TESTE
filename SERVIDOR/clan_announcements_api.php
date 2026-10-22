<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (empty($_SESSION['user_login'])) respond(401, ['ok'=>false,'error'=>'Faça login para acessar o clã.']);
if (empty($_SESSION['vb_clan_csrf'])) $_SESSION['vb_clan_csrf'] = bin2hex(random_bytes(32));

try {
    require_once __DIR__ . '/admin/includes/database.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT c.obj_Id AS character_id, c.char_name, c.clanid,
                    COALESCE(r.rank_level, 1) AS rank_level,
                    cs.name AS clan_name, cs.leader_id
             FROM characters c
             LEFT JOIN vb_clan_ranks r ON r.clan_id=c.clanid AND r.character_id=c.obj_Id
             LEFT JOIN clan_subpledges cs ON cs.clan_id=c.clanid AND cs.type=0
             WHERE c.account_name=? AND c.clanid>0
             ORDER BY c.char_name ASC'
        );
        $stmt->execute([$_SESSION['user_login']]);
        $characters = $stmt->fetchAll();
        if (!$characters) respond(403, ['ok'=>false,'error'=>'Sua conta não tem personagem em um clã.']);

        $selectedId = isset($_GET['character_id']) ? (int)$_GET['character_id'] : (int)$characters[0]['character_id'];
        $selected = null;
        foreach ($characters as $character) {
            if ((int)$character['character_id'] === $selectedId) { $selected = $character; break; }
        }
        if (!$selected) respond(403, ['ok'=>false,'error'=>'Personagem inválido para esta conta.']);

        $stmt = $pdo->prepare(
            'SELECT id, title, body, author_character_id, created_at
             FROM vb_clan_announcements WHERE clan_id=?
             ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute([(int)$selected['clanid']]);
        $posts = $stmt->fetchAll();
        $names = [];
        foreach ($posts as &$post) {
            $names[(int)$post['author_character_id']] = null;
        }
        unset($post);
        if ($names) {
            $ids = array_keys($names);
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $nameStmt = $pdo->prepare("SELECT obj_Id, char_name FROM characters WHERE obj_Id IN ($marks)");
            $nameStmt->execute($ids);
            foreach ($nameStmt->fetchAll() as $row) $names[(int)$row['obj_Id']] = $row['char_name'];
        }
        foreach ($posts as &$post) {
            $post['author_name'] = $names[(int)$post['author_character_id']] ?? 'Membro do clã';
            $post['id'] = (int)$post['id'];
            unset($post['author_character_id']);
        }
        unset($post);
        $rank = (int)$selected['rank_level'];
        $isLeader = (int)$selected['character_id'] === (int)$selected['leader_id'];
        respond(200, [
            'ok'=>true,
            'csrf_token'=>$_SESSION['vb_clan_csrf'],
            'character'=>['id'=>(int)$selected['character_id'],'name'=>$selected['char_name']],
            'clan'=>['id'=>(int)$selected['clanid'],'name'=>$selected['clan_name']],
            'can_publish'=>$isLeader || $rank >= 5,
            'can_delete'=>$isLeader || $rank >= 5,
            'posts'=>$posts
        ]);
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
        header('Allow: GET, POST, DELETE');
        respond(405, ['ok'=>false,'error'=>'Método não permitido.']);
    }

    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals((string)$_SESSION['vb_clan_csrf'], $csrf)) {
        respond(403, ['ok'=>false,'error'=>'Sessão de segurança expirada. Atualize a página e tente novamente.']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(400, ['ok'=>false,'error'=>'Dados inválidos.']);
    $characterId = (int)($input['character_id'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $body = trim((string)($input['body'] ?? ''));

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $postId = (int)($input['post_id'] ?? 0);
        if ($postId < 1) respond(400, ['ok'=>false,'error'=>'Notícia inválida.']);
        $stmt = $pdo->prepare(
            'SELECT c.obj_Id, c.clanid, COALESCE(r.rank_level, 1) AS rank_level, cs.leader_id
             FROM characters c
             LEFT JOIN vb_clan_ranks r ON r.clan_id=c.clanid AND r.character_id=c.obj_Id
             LEFT JOIN clan_subpledges cs ON cs.clan_id=c.clanid AND cs.type=0
             WHERE c.account_name=? AND c.obj_Id=? AND c.clanid>0 LIMIT 1'
        );
        $stmt->execute([$_SESSION['user_login'], $characterId]);
        $actor = $stmt->fetch();
        if (!$actor) respond(403, ['ok'=>false,'error'=>'Personagem inválido para esta conta ou sem clã.']);
        if ((int)$actor['obj_Id'] !== (int)$actor['leader_id'] && (int)$actor['rank_level'] < 5) {
            respond(403, ['ok'=>false,'error'=>'Somente o líder ou membros a partir de Capitão podem apagar notícias.']);
        }
        $stmt = $pdo->prepare('DELETE FROM vb_clan_announcements WHERE id=? AND clan_id=?');
        $stmt->execute([$postId, (int)$actor['clanid']]);
        if ($stmt->rowCount() < 1) respond(404, ['ok'=>false,'error'=>'Notícia não encontrada neste clã.']);
        respond(200, ['ok'=>true,'message'=>'Notícia apagada.']);
    }

    if ($title === '' || mb_strlen($title) > 120) respond(400, ['ok'=>false,'error'=>'O título é obrigatório e deve ter até 120 caracteres.']);
    if ($body === '' || mb_strlen($body) > 5000) respond(400, ['ok'=>false,'error'=>'A mensagem é obrigatória e deve ter até 5000 caracteres.']);

    $stmt = $pdo->prepare(
        'SELECT c.obj_Id, c.char_name, c.clanid,
                COALESCE(r.rank_level, 1) AS rank_level,
                cs.leader_id
         FROM characters c
         LEFT JOIN vb_clan_ranks r ON r.clan_id=c.clanid AND r.character_id=c.obj_Id
         LEFT JOIN clan_subpledges cs ON cs.clan_id=c.clanid AND cs.type=0
         WHERE c.account_name=? AND c.obj_Id=? AND c.clanid>0 LIMIT 1'
    );
    $stmt->execute([$_SESSION['user_login'], $characterId]);
    $author = $stmt->fetch();
    if (!$author) respond(403, ['ok'=>false,'error'=>'Esse personagem não pertence à sua conta ou não está em um clã.']);
    if ((int)$author['obj_Id'] !== (int)$author['leader_id'] && (int)$author['rank_level'] < 5) {
        respond(403, ['ok'=>false,'error'=>'Somente o líder ou membros a partir de Capitão podem publicar notícias.']);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO vb_clan_announcements (clan_id, author_character_id, title, body)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([(int)$author['clanid'], (int)$author['obj_Id'], $title, $body]);
    respond(201, ['ok'=>true,'message'=>'Notícia publicada para o clã.']);
} catch (Throwable $e) {
    error_log('Clan announcements API error: ' . $e->getMessage());
    respond(500, ['ok'=>false,'error'=>'Não foi possível carregar ou publicar a notícia. Verifique o log do servidor.']);
}
