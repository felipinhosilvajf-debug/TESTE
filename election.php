<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$accountName = isset($_SESSION['user_login']) && is_string($_SESSION['user_login']) ? trim($_SESSION['user_login']) : '';
require_once __DIR__ . '/admin/includes/database.php';
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); exit('Não foi possível conectar ao banco da eleição.'); }
function election_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function election_num($v): string { return number_format((int)$v, 0, ',', '.'); }
function election_initials($name): string {
    $parts = preg_split('/\\s+/u', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '?';
    $first = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1, 'UTF-8') : substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? (function_exists('mb_substr') ? mb_substr($parts[count($parts)-1], 0, 1, 'UTF-8') : substr($parts[count($parts)-1], 0, 1)) : '';
    return strtoupper($first . $last);
}
if (empty($_SESSION['vb_election_csrf']) || !is_string($_SESSION['vb_election_csrf'])) $_SESSION['vb_election_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['vb_election_csrf'];
$error = ''; $notice = ''; $election = null; $castles = []; $candidatesByCastle = []; $myLeaders = [];
$votes = []; $totalVotes = 0; $alreadyVoted = []; $winnersByCastle = [];
// Carrega os castelos independentemente das tabelas da eleição. Assim, uma falha
// na migração das tabelas de votação não deixa a página inteira sem castelos.
try {
    $castleListStmt = $pdo->query("SELECT ID AS castle_id,name AS castle_name,tax_percent,treasury FROM castle ORDER BY ID ASC");
    $castles = $castleListStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $castleLoadError) {
    error_log('[VirtualBoost election.php][castles] ' . get_class($castleLoadError) . ': ' . $castleLoadError->getMessage());
}
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS vb_political_elections (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        status ENUM('open','paused','finished','cancelled') NOT NULL DEFAULT 'open',
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        remaining_seconds INT UNSIGNED NOT NULL DEFAULT 0,
        paused_at DATETIME NULL,
        winner_leader_id INT NULL,
        winner_name VARCHAR(100) NULL,
        forced TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_vb_election_status (status, ends_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vb_political_election_candidates (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        election_id BIGINT UNSIGNED NOT NULL,
        castle_id INT NOT NULL,
        leader_id INT NOT NULL,
        candidate_name VARCHAR(100) NOT NULL,
        clan_id INT NOT NULL,
        clan_name VARCHAR(100) NOT NULL,
        proposal_1 VARCHAR(255) NOT NULL DEFAULT '',
        proposal_2 VARCHAR(255) NOT NULL DEFAULT '',
        proposal_3 VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vb_candidate_castle_leader (election_id, castle_id, leader_id),
        KEY idx_vb_candidate_election_castle (election_id, castle_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS vb_political_election_votes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        election_id BIGINT UNSIGNED NOT NULL,
        account_name VARCHAR(45) NOT NULL,
        castle_id INT NOT NULL,
        candidate_leader_id INT NOT NULL,
        candidate_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vb_election_account (election_id, account_name, castle_id),
        KEY idx_vb_election_candidate (election_id, castle_id, candidate_leader_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Migração incremental para instalações que já tinham tabelas de versões anteriores.
    $candidateColumns = $pdo->query("SHOW COLUMNS FROM vb_political_election_candidates")->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'proposal_1' => "ALTER TABLE vb_political_election_candidates ADD COLUMN proposal_1 VARCHAR(255) NOT NULL DEFAULT ''",
        'proposal_2' => "ALTER TABLE vb_political_election_candidates ADD COLUMN proposal_2 VARCHAR(255) NOT NULL DEFAULT ''",
        'proposal_3' => "ALTER TABLE vb_political_election_candidates ADD COLUMN proposal_3 VARCHAR(255) NOT NULL DEFAULT ''"
    ] as $column => $sql) {
        if (!in_array($column, $candidateColumns, true)) {
            $pdo->exec($sql);
            $candidateColumns[] = $column;
        }
    }
    $voteColumns = $pdo->query("SHOW COLUMNS FROM vb_political_election_votes")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('election_id', $voteColumns, true)) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN election_id BIGINT UNSIGNED NULL FIRST");
        $voteColumns[] = 'election_id';
    }
    if (!in_array('castle_id', $voteColumns, true)) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN castle_id INT NOT NULL DEFAULT 0 AFTER account_name");
        $voteColumns[] = 'castle_id';
    }
    if (!in_array('candidate_leader_id', $voteColumns, true)) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN candidate_leader_id INT NOT NULL DEFAULT 0 AFTER castle_id");
        $voteColumns[] = 'candidate_leader_id';
    }
    if (!in_array('candidate_name', $voteColumns, true)) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN candidate_name VARCHAR(100) NOT NULL DEFAULT '' AFTER candidate_leader_id");
        $voteColumns[] = 'candidate_name';
    }
    if (!in_array('created_at', $voteColumns, true)) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $voteColumns[] = 'created_at';
    }
    $pdo->exec("UPDATE vb_political_election_votes SET election_id=0 WHERE election_id IS NULL");
    $pdo->exec("ALTER TABLE vb_political_election_votes MODIFY election_id BIGINT UNSIGNED NOT NULL");
    // Remove duplicatas legadas por conta/eleição/castelo antes de criar a chave única.
    // Preserva o voto mais antigo de cada combinação.
    $pdo->exec("DELETE newer FROM vb_political_election_votes newer
        INNER JOIN vb_political_election_votes older
          ON newer.election_id=older.election_id
         AND newer.account_name=older.account_name
         AND newer.castle_id=older.castle_id
         AND newer.id>older.id");
    $voteIndexRows = $pdo->query("SHOW INDEX FROM vb_political_election_votes WHERE Key_name='uq_vb_election_account'")->fetchAll(PDO::FETCH_ASSOC);
    usort($voteIndexRows, static function ($a, $b) { return (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']; });
    $voteIndexColumns = array_map(static function ($r) { return (string)$r['Column_name']; }, $voteIndexRows);
    if ($voteIndexRows && $voteIndexColumns !== ['election_id','account_name','castle_id']) {
        $pdo->exec("ALTER TABLE vb_political_election_votes DROP INDEX uq_vb_election_account");
        $voteIndexRows = [];
    }
    if (!$voteIndexRows) $pdo->exec("ALTER TABLE vb_political_election_votes ADD UNIQUE KEY uq_vb_election_account (election_id, account_name, castle_id)");
    $activeStmt = $pdo->query("SELECT * FROM vb_political_elections WHERE status IN ('open','paused') ORDER BY id DESC LIMIT 1");
    $election = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($election && $election['status'] === 'open' && strtotime($election['ends_at']) <= time()) {
        // O encerramento só muda o estado; os vencedores são apurados individualmente por castelo abaixo.
        $finish = $pdo->prepare("UPDATE vb_political_elections SET status='finished',winner_leader_id=NULL,winner_name=NULL,forced=0 WHERE id=? AND status='open' AND ends_at<=NOW()");
        $finish->execute([(int)$election['id']]);
        $election = null;
    }
    // Limpa candidaturas de eleições encerradas/canceladas para não reaparecerem depois.
    $pdo->exec("DELETE c FROM vb_political_election_candidates c
        INNER JOIN vb_political_elections e ON e.id=c.election_id
        WHERE e.status IN ('finished','cancelled')");
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $action = isset($_POST['election_action']) && is_string($_POST['election_action']) ? $_POST['election_action'] : '';
        if ($accountName === '') $error = 'Entre na sua conta pelo site para se candidatar ou votar.';
        elseif (!hash_equals($csrf, $postedToken)) $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
        elseif (!$election || $election['status'] !== 'open') $error = 'Não há uma eleição aberta para receber candidaturas ou votos.';
        elseif ($action === 'register') {
            $castleId = filter_input(INPUT_POST, 'castle_id', FILTER_VALIDATE_INT);
            $leaderId = filter_input(INPUT_POST, 'leader_id', FILTER_VALIDATE_INT);
            if (!$castleId || !$leaderId) $error = 'Selecione um castelo e um personagem líder válido.';
            else {
                $leaderStmt = $pdo->prepare("SELECT cd.clan_id,
                    cs.name AS clan_name,
                    ch.obj_Id AS leader_id, ch.char_name AS leader_name
                    FROM clan_data cd
                    INNER JOIN clan_subpledges cs ON cs.clan_id=cd.clan_id AND cs.type=0
                    INNER JOIN characters ch ON ch.obj_Id=cs.leader_id
                    WHERE ch.account_name=? AND ch.obj_Id=? LIMIT 1");
                $leaderStmt->execute([$accountName, $leaderId]); $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);
                $castleCheck = $pdo->prepare("SELECT ID FROM castle WHERE ID=? LIMIT 1"); $castleCheck->execute([$castleId]);
                if (!$leader) $error = 'Somente o líder de um clã vinculado à sua conta pode se candidatar.';
                elseif (!$castleCheck->fetchColumn()) $error = 'O castelo selecionado não existe.';
                else {
                    $proposal1 = trim((string)($_POST['proposal_1'] ?? ''));
                    $proposal2 = trim((string)($_POST['proposal_2'] ?? ''));
                    $proposal3 = trim((string)($_POST['proposal_3'] ?? ''));
                    if ($proposal1 === '' || $proposal2 === '' || $proposal3 === '') {
                        $error = 'Preencha as três propostas para concluir a candidatura.';
                    } elseif ((function_exists('mb_strlen') ? mb_strlen($proposal1, 'UTF-8') : strlen($proposal1)) > 255 || (function_exists('mb_strlen') ? mb_strlen($proposal2, 'UTF-8') : strlen($proposal2)) > 255 || (function_exists('mb_strlen') ? mb_strlen($proposal3, 'UTF-8') : strlen($proposal3)) > 255) {
                        $error = 'Cada proposta deve ter no máximo 255 caracteres.';
                    } else {
                        $ins = $pdo->prepare("INSERT INTO vb_political_election_candidates (election_id,castle_id,leader_id,candidate_name,clan_id,clan_name,proposal_1,proposal_2,proposal_3) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE candidate_name=VALUES(candidate_name),clan_id=VALUES(clan_id),clan_name=VALUES(clan_name),proposal_1=VALUES(proposal_1),proposal_2=VALUES(proposal_2),proposal_3=VALUES(proposal_3)");
                        $ins->execute([(int)$election['id'], $castleId, (int)$leader['leader_id'], (string)$leader['leader_name'], (int)$leader['clan_id'], (string)$leader['clan_name'], $proposal1, $proposal2, $proposal3]);
                        $notice = $leader['leader_name'].' foi inscrito como candidato ao castelo selecionado.';
                    }
                    $notice = $leader['leader_name'].' foi inscrito como candidato ao castelo selecionado.';
                }
            }
        } elseif ($action === 'vote') {
            $candidateId = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT);
            if (!$candidateId) $error = 'Selecione um candidato válido.';
            else {
                $candidateStmt = $pdo->prepare("SELECT id,castle_id,leader_id,candidate_name FROM vb_political_election_candidates WHERE id=? AND election_id=? LIMIT 1");
                $candidateStmt->execute([$candidateId, (int)$election['id']]); $candidate = $candidateStmt->fetch(PDO::FETCH_ASSOC);
                if (!$candidate) $error = 'Esse candidato não está inscrito nesta eleição.';
                else {
                    try {
                        $vote = $pdo->prepare("INSERT INTO vb_political_election_votes (election_id,account_name,castle_id,candidate_leader_id,candidate_name) VALUES (?,?,?,?,?)");
                        $vote->execute([(int)$election['id'], $accountName, (int)$candidate['castle_id'], (int)$candidate['leader_id'], (string)$candidate['candidate_name']]);
                        $notice = 'Seu voto foi registrado para este castelo.';
                    } catch (PDOException $e) {
                        if ((string)$e->getCode() === '23000') $error = 'Sua conta já votou neste castelo nesta eleição.';
                        else throw $e;
                    }
                }
            }
        } else $error = 'Ação de eleição inválida.';
    }
    $castles = $pdo->query("SELECT ID AS castle_id,name AS castle_name,tax_percent,treasury FROM castle ORDER BY ID ASC")->fetchAll(PDO::FETCH_ASSOC);
    if ($accountName !== '') {
        $leadersStmt = $pdo->prepare("SELECT cd.clan_id,
            cs.name AS clan_name,
            ch.obj_Id AS leader_id,ch.char_name AS leader_name
            FROM clan_data cd
            INNER JOIN clan_subpledges cs ON cs.clan_id=cd.clan_id AND cs.type=0
            INNER JOIN characters ch ON ch.obj_Id=cs.leader_id
            WHERE ch.account_name=? ORDER BY ch.char_name ASC");
        $leadersStmt->execute([$accountName]); $myLeaders = $leadersStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($election) {
        $candStmt = $pdo->prepare("SELECT c.id,c.castle_id,c.leader_id,c.candidate_name,c.clan_id,c.clan_name,c.proposal_1,c.proposal_2,c.proposal_3,COUNT(v.id) AS votes
            FROM vb_political_election_candidates c LEFT JOIN vb_political_election_votes v
            ON v.election_id=c.election_id AND v.castle_id=c.castle_id AND v.candidate_leader_id=c.leader_id
            WHERE c.election_id=? GROUP BY c.id,c.castle_id,c.leader_id,c.candidate_name,c.clan_id,c.clan_name,c.proposal_1,c.proposal_2,c.proposal_3
            ORDER BY c.castle_id ASC,votes DESC,c.candidate_name ASC");
        $candStmt->execute([(int)$election['id']]);
        foreach ($candStmt->fetchAll(PDO::FETCH_ASSOC) as $cand) $candidatesByCastle[(int)$cand['castle_id']][] = $cand;
        $voteCountStmt = $pdo->prepare("SELECT castle_id,candidate_leader_id,MAX(candidate_name) AS candidate_name,COUNT(*) AS votes FROM vb_political_election_votes WHERE election_id=? GROUP BY castle_id,candidate_leader_id ORDER BY castle_id ASC,votes DESC,candidate_name ASC");
        $voteCountStmt->execute([(int)$election['id']]); $votes = $voteCountStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalVotes = array_sum(array_map(static function ($v) { return (int)$v['votes']; }, $votes));
        if ($accountName !== '') {
            $check = $pdo->prepare("SELECT castle_id FROM vb_political_election_votes WHERE election_id=? AND account_name=?");
            $check->execute([(int)$election['id'], $accountName]);
            foreach ($check->fetchAll(PDO::FETCH_COLUMN) as $votedCastle) $alreadyVoted[(int)$votedCastle] = true;
        }
    } else {
        $last = $pdo->query("SELECT * FROM vb_political_elections ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $election = $last;
            $candStmt = $pdo->prepare("SELECT c.id,c.castle_id,c.leader_id,c.candidate_name,c.clan_id,c.clan_name,c.proposal_1,c.proposal_2,c.proposal_3,COUNT(v.id) AS votes
                FROM vb_political_election_candidates c LEFT JOIN vb_political_election_votes v
                ON v.election_id=c.election_id AND v.castle_id=c.castle_id AND v.candidate_leader_id=c.leader_id
                WHERE c.election_id=? GROUP BY c.id,c.castle_id,c.leader_id,c.candidate_name,c.clan_id,c.clan_name,c.proposal_1,c.proposal_2,c.proposal_3
                ORDER BY c.castle_id ASC,votes DESC,c.candidate_name ASC");
            $candStmt->execute([(int)$election['id']]);
            foreach ($candStmt->fetchAll(PDO::FETCH_ASSOC) as $cand) $candidatesByCastle[(int)$cand['castle_id']][] = $cand;
            $voteCountStmt = $pdo->prepare("SELECT castle_id,candidate_leader_id,MAX(candidate_name) AS candidate_name,COUNT(*) AS votes FROM vb_political_election_votes WHERE election_id=? GROUP BY castle_id,candidate_leader_id ORDER BY castle_id ASC,votes DESC,candidate_name ASC");
            $voteCountStmt->execute([(int)$election['id']]); $votes = $voteCountStmt->fetchAll(PDO::FETCH_ASSOC);
            $totalVotes = array_sum(array_map(static function ($v) { return (int)$v['votes']; }, $votes));
        }
    }
    if ($election && $election['status'] === 'finished') {
        foreach ($castles as $castle) {
            $list = $candidatesByCastle[(int)$castle['castle_id']] ?? [];
            if ($list) { usort($list, static function ($a, $b) { $byVotes = (int)$b['votes'] <=> (int)$a['votes']; return $byVotes ?: strcmp((string)$a['candidate_name'], (string)$b['candidate_name']); }); if ((int)$list[0]['votes'] > 0) $winnersByCastle[(int)$castle['castle_id']] = $list[0]; }
        }
    }
} catch (Throwable $e) {
    // Mantém a página segura para jogadores e registra a causa real no log PHP da hospedagem.
    error_log('[VirtualBoost election.php] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        try { $pdo->rollBack(); } catch (Throwable $ignored) {}
    }
    $error = $error ?: 'Não foi possível carregar a eleição. A falha foi registrada no log PHP da hospedagem.';
}
$isOpen = $election && $election['status'] === 'open' && strtotime($election['ends_at']) > time();
$isPaused = $election && $election['status'] === 'paused';
$secondsLeft = $isOpen ? max(0, strtotime($election['ends_at']) - time()) : (int)($election['remaining_seconds'] ?? 0);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Eleição Política — L2 Virtual Boost</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{color-scheme:dark;--bg:#05070b;--panel:#0b111b;--line:rgba(117,215,255,.16);--cyan:#75d7ff;--blue:#4ca8ff;--purple:#8c65ff;--gold:#d5b46a;--text:#e7edf5;--muted:#8995a5;--green:#63d39a;--red:#ff7777}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(76,168,255,.12),transparent 32%),radial-gradient(circle at 0 80%,rgba(140,101,255,.09),transparent 28%),var(--bg);color:var(--text);font:16px Rajdhani,Arial,sans-serif;min-height:100vh}.wrap{position:relative;z-index:1;max-width:1380px;margin:auto;padding:32px 22px 60px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.back-site{display:inline-flex;align-items:center;gap:8px;padding:11px 15px;border:1px solid rgba(117,215,255,.3);border-radius:6px;background:rgba(11,17,27,.85);color:#dff7ff;text-decoration:none;font:700 10px Orbitron,sans-serif;letter-spacing:.6px;transition:background .2s,border-color .2s,transform .2s}.back-site:hover{background:rgba(23,105,170,.25);border-color:var(--cyan);transform:translateY(-1px)}.topbar .eyebrow{margin-left:auto}.eyebrow{color:var(--cyan);font:700 11px Orbitron,sans-serif;letter-spacing:3px}h1{font:700 clamp(26px,4vw,38px) Orbitron,sans-serif;margin:12px 0;color:#fff}h2{font:700 17px Orbitron,sans-serif;margin:0 0 10px}p{line-height:1.65;color:var(--muted)}.hero,.panel,.stat,.castle{background:linear-gradient(145deg,rgba(14,23,36,.98),rgba(8,13,21,.98));border:1px solid var(--line);border-radius:10px}.hero{padding:26px;margin:22px 0}.hero p{max-width:800px;margin-bottom:0}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-bottom:22px}.stat{padding:18px}.stat small{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}.stat strong{font:700 20px Orbitron,sans-serif;color:var(--cyan)}.panel{padding:20px;margin-bottom:22px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.castle{padding:18px}.castle h3{font:700 14px Orbitron,sans-serif;margin:0 0 15px}.leader{background:#080d15;border:1px solid rgba(117,215,255,.1);border-radius:6px;padding:13px;margin-bottom:12px}.leader small{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;margin-bottom:5px}.leader strong{font-size:17px;color:#fff}.metric{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid rgba(117,215,255,.07);font-size:13px}.metric span{color:var(--muted)}.metric b{color:#dce7f0;text-align:right}.vote{width:100%;margin-top:14px;padding:12px;border:0;border-radius:5px;background:linear-gradient(90deg,#1769aa,#5634b8);color:white;font:700 11px Orbitron,sans-serif;cursor:pointer}.vote:disabled{opacity:.35;cursor:not-allowed}.notice{padding:13px 15px;margin:15px 0;border-radius:7px;border:1px solid var(--line);background:#080d15;color:#b7c6d8;line-height:1.6}.notice.error{color:var(--red);border-color:rgba(255,119,119,.25)}.notice.success{color:var(--green);border-color:rgba(99,211,154,.25)}.leaderboard{display:grid;gap:8px;margin-top:12px}.score{display:flex;justify-content:space-between;gap:12px;padding:11px 12px;background:#080d15;border-radius:5px}.score span{color:#c6d5e5}.score b{color:var(--cyan)}.foot{border-top:1px solid var(--line);padding-top:18px;margin-top:26px;color:#65778b;font-size:12px;line-height:1.7}
/* Wiki de liderança: botão compacto e painel expansível */
.wiki-launch{display:flex;justify-content:center;margin:4px 0 24px}
.wiki-toggle{display:inline-flex;align-items:center;gap:9px;padding:10px 15px;border:1px solid rgba(117,215,255,.35);border-radius:7px;background:linear-gradient(110deg,rgba(23,105,170,.2),rgba(86,52,184,.22));color:#dff7ff;font:700 10px Orbitron,sans-serif;letter-spacing:.8px;cursor:pointer;box-shadow:0 0 0 rgba(76,168,255,0);animation:wikiPulse 2.8s ease-in-out infinite;transition:transform .2s,border-color .2s,background .2s}
.wiki-toggle:hover{transform:translateY(-2px);border-color:var(--cyan);background:linear-gradient(110deg,rgba(23,105,170,.34),rgba(86,52,184,.34))}
.wiki-toggle .wiki-icon{font-size:15px;color:var(--cyan)}
.wiki-toggle .wiki-arrow{display:inline-block;transition:transform .25s}
.wiki-toggle[aria-expanded="true"] .wiki-arrow{transform:rotate(180deg)}
@keyframes wikiPulse{0%,100%{box-shadow:0 0 0 0 rgba(76,168,255,0)}50%{box-shadow:0 0 0 4px rgba(76,168,255,.07)}}
.wiki-panel{border:1px solid rgba(117,215,255,.2);border-radius:10px;background:linear-gradient(145deg,rgba(14,23,36,.98),rgba(8,13,21,.98));margin:-10px 0 25px;overflow:hidden}
.wiki-panel[hidden]{display:none}
.wiki-head{padding:22px;border-bottom:1px solid var(--line);background:linear-gradient(100deg,rgba(76,168,255,.08),rgba(140,101,255,.07))}
.wiki-head .eyebrow{margin-bottom:9px}.wiki-head h2{font-size:18px;line-height:1.5}.wiki-head p{margin:8px 0 0;max-width:900px}
.wiki-body{padding:22px}.wiki-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.wiki-section{padding:17px;border:1px solid rgba(117,215,255,.1);border-radius:7px;background:rgba(5,9,15,.45)}
.wiki-section h3{font:700 12px Orbitron,sans-serif;color:var(--cyan);margin:0 0 10px;letter-spacing:.4px}
.wiki-section p,.wiki-section li{font-size:14px;line-height:1.65;color:#aab8c8}
.wiki-section p{margin:7px 0}.wiki-section ul,.wiki-section ol{margin:8px 0 0;padding-left:19px}.wiki-section li{margin:5px 0}
.wiki-note{margin-top:15px;padding:14px 16px;border-left:3px solid var(--gold);background:rgba(213,180,106,.06);color:#c7bd9e;font-size:13px;line-height:1.7}
@media(max-width:620px){.wiki-grid{grid-template-columns:1fr}.wiki-body,.wiki-head{padding:16px}.wiki-toggle{font-size:9px}}
@media(prefers-reduced-motion:reduce){.wiki-toggle{animation:none;transition:none}.wiki-toggle .wiki-arrow{transition:none}}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.wrap{padding:20px 13px}.grid,.stats{grid-template-columns:1fr}}

/* Partículas mágicas discretas no fundo da eleição */
#electionParticles{position:fixed;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;opacity:.72}
@media(prefers-reduced-motion:reduce){#electionParticles{opacity:.35}}

.castle-image-frame{height:145px;margin:0 0 14px;border:1px dashed rgba(117,215,255,.32);border-radius:7px;overflow:hidden;background:linear-gradient(135deg,rgba(76,168,255,.09),rgba(140,101,255,.1));}.castle-image{width:100%;height:100%;object-fit:cover;display:block}.castle-image-placeholder{height:100%;place-items:center;text-align:center;color:#75d7ff;font:700 10px Orbitron,sans-serif;letter-spacing:.7px;line-height:1.8;padding:8px}.castle-image-placeholder small{font:10px Rajdhani,Arial,sans-serif;color:#8995a5;letter-spacing:0;overflow-wrap:anywhere}.proposal-toggle{align-self:flex-start;border:1px solid rgba(117,215,255,.28);background:rgba(76,168,255,.08);color:#bdeeff;border-radius:5px;padding:8px 11px;font:700 10px Orbitron,sans-serif;cursor:pointer}.proposal-card{padding:12px;border:1px solid rgba(140,101,255,.28);border-radius:7px;background:rgba(86,52,184,.08);line-height:1.6}.proposal-card h4{margin:0 0 9px;color:#dff7ff;font:700 11px Orbitron,sans-serif}.proposal-card p{margin:7px 0;color:#c5d1df;font-size:13px}.candidate-card{display:flex;flex-direction:column;gap:12px}.candidate-head{display:flex;align-items:center;gap:12px;min-width:0}.candidate-avatar{width:52px;height:52px;flex:0 0 52px;display:grid;place-items:center;border-radius:50%;border:1px solid rgba(117,215,255,.55);background:radial-gradient(circle at 30% 20%,rgba(117,215,255,.28),rgba(86,52,184,.22) 60%,#080d15);color:#eafaff;font:700 15px Orbitron,sans-serif;box-shadow:0 0 18px rgba(76,168,255,.12)}.candidate-identity{display:grid;gap:4px;min-width:0}.candidate-identity strong{overflow-wrap:anywhere}.candidate-identity .candidate-clan{color:#75d7ff;font-size:12px;letter-spacing:.2px;text-transform:none}.candidate-avatar img{width:72%;height:72%;object-fit:contain;display:block}.candidates-toggle{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;margin-top:14px;padding:12px 14px;border:1px solid rgba(117,215,255,.32);border-radius:7px;background:linear-gradient(110deg,rgba(23,105,170,.14),rgba(86,52,184,.14));color:#dff7ff;font:700 11px Orbitron,sans-serif;cursor:pointer;transition:border-color .2s,background .2s}.candidates-toggle:hover{border-color:var(--cyan);background:linear-gradient(110deg,rgba(23,105,170,.24),rgba(86,52,184,.24))}.candidates-toggle .candidate-arrow{color:var(--cyan);transition:transform .2s}.candidates-toggle[aria-expanded="true"] .candidate-arrow{transform:rotate(180deg)}.candidate-list-extra{margin-top:10px;padding:12px;border:1px solid rgba(117,215,255,.2);border-radius:9px;background:rgba(5,9,16,.8);display:grid;gap:12px}.candidate-list-extra[hidden]{display:none}
</style></head><body><canvas id="electionParticles" aria-hidden="true"></canvas><main class="wrap">
<div class="topbar"><a class="back-site" href="dashboard.php">← RETORNAR PARA O SITE</a><div class="eyebrow">VIRTUAL BOOST · GOVERNANÇA DO REINO</div></div><h1>ELEIÇÃO POLÍTICA</h1>
<p>Líderes de clã se candidatam ao castelo que desejam governar. Cada conta pode votar uma vez em cada castelo.</p>
<section class="hero"><div class="eyebrow">UMA NOVA ERA COMEÇA</div><h2 style="margin-top:10px;">Seu voto. Seu reino. Seu legado.</h2>
<?php if ($isOpen): ?><p>Eleição aberta para votação. O prazo termina em <strong style="color:#fff;"><?= election_h($election['ends_at']) ?></strong>. O tempo restante é atualizado ao recarregar a página.</p>
<?php elseif ($isPaused): ?><p style="color:#d5b46a;">A eleição está pausada pelo administrador. Nenhum voto será aceito até ser retomada.</p>
<?php elseif ($election && $election['status']==='finished'): ?><p>Eleição encerrada. Confira abaixo os vencedores de cada castelo. O resultado passa por validação administrativa e não altera automaticamente o proprietário.</p>
<?php elseif ($election && $election['status']==='cancelled'): ?><p>A última eleição foi cancelada e não possui vencedor oficial.</p>
<?php else: ?><p>Nenhuma eleição foi iniciada no momento. Acompanhe esta página para participar quando a votação abrir.</p><?php endif; ?>
</section>
<?php if ($error): ?><div class="notice error"><?= election_h($error) ?></div><?php endif; ?>
<?php if ($notice): ?><div class="notice success"><?= election_h($notice) ?></div><?php endif; ?>
<section class="stats">
<div class="stat"><small>Estado</small><strong style="font-size:16px;"><?= $isOpen?'ABERTA':($isPaused?'PAUSADA':($election?strtoupper((string)$election['status']):'AGUARDANDO')) ?></strong></div>
<div class="stat"><small>Votos registrados</small><strong><?= (int)$totalVotes ?></strong></div>
<div class="stat"><small>Tempo restante</small><strong id="countdown"><?= $isOpen ? gmdate('H:i:s',$secondsLeft) : '—' ?></strong></div>
</section>
<section class="wiki-launch" aria-label="Wiki da liderança do castelo">
<button class="wiki-toggle" id="wikiToggle" type="button" aria-expanded="false" aria-controls="leadershipWiki"><span class="wiki-icon" aria-hidden="true">✦</span><span>VANTAGENS DA LIDERANÇA</span><span class="wiki-arrow" aria-hidden="true">⌄</span></button>
</section>
<section class="wiki-panel" id="leadershipWiki" hidden>
  <header class="wiki-head"><div class="eyebrow">WIKI OFICIAL · VIRTUAL BOOST</div><h2>O Dono do Castelo, seu Clã e o poder da liderança</h2><p>Entenda o papel político, militar e econômico de um clã que controla um castelo no Lineage II — e o que os números de impostos exibidos nesta página significam.</p></header>
  <div class="wiki-body"><div class="wiki-grid">
    <article class="wiki-section"><h3>01 · O que representa ser dono?</h3><p>O clã proprietário representa uma das forças políticas mais importantes do reino. O castelo é um objetivo estratégico: exige organização, aliados, preparação e capacidade de defender o território diante de outros clãs.</p><ul><li>Prestígio e reconhecimento para o clã e seu líder.</li><li>Responsabilidade pela defesa do castelo e pela coordenação dos membros.</li><li>Participação na disputa pelo controle político e econômico do mundo.</li><li>Um objetivo de longo prazo para recrutar, treinar e unir jogadores.</li></ul></article>
    <article class="wiki-section"><h3>02 · O papel do líder e dos membros</h3><p>O líder define a estratégia, organiza a comunicação e representa o clã. Oficiais e membros ajudam a coordenar grupos, equipamentos, recursos e horários de atividade.</p><ul><li>Planejar ataques, defesa e participação em cercos.</li><li>Definir funções, regras internas e metas coletivas.</li><li>Coordenar alianças sem perder de vista os interesses do próprio clã.</li><li>Manter a comunidade ativa e agir com responsabilidade com os recursos do clã.</li></ul></article>
    <article class="wiki-section"><h3>03 · Impostos: o que são?</h3><p>O líder do castelo tem autoridade para definir o imposto do seu território. Pode mudar a taxa sem aviso prévio e fazer a alteração valer imediatamente, afetando os custos de compras e serviços e a economia local.</p><ul><li><strong>Imposto:</strong> percentual definido pelo líder do castelo.</li><li><strong>Arrecadação:</strong> recursos gerados pelas atividades econômicas do território.</li><li><strong>Tesouro:</strong> recursos sob controle do castelo.</li></ul></article>
    <article class="wiki-section"><h3>04 · Tesouro e economia do castelo</h3><p>O líder exerce poder direto sobre a política econômica do território. Ao alterar o imposto, pode tornar compras e serviços mais caros ou mais acessíveis, influenciando o comércio e a circulação de adena. A mudança pode entrar em vigor imediatamente, sem anúncio antecipado.</p><ul><li>Acompanhe os impostos e os valores exibidos para cada castelo.</li><li>Considere o impacto econômico das decisões da liderança.</li><li>O tesouro e a arrecadação representam recursos ligados ao controle do castelo.</li></ul></article>
    <article class="wiki-section"><h3>05 · Cercos, defesa e diplomacia</h3><p>O controle de um castelo não é apenas um título: pode ser contestado em cercos, conforme as regras e os sistemas habilitados no servidor. A preparação inclui combinar horários, funções, comunicação, equipamentos e plano de defesa.</p><ul><li>Organize os membros e aliados antes do confronto.</li><li>Defina responsáveis por comunicação, ataque e defesa.</li><li>Respeite as regras de cerco e as decisões administrativas do servidor.</li><li>Considere que perder o castelo pode alterar a posição política do clã.</li></ul></article>
    <article class="wiki-section"><h3>06 · Como funciona a eleição do site?</h3><p>A eleição política do Virtual Boost registra votos por conta autenticada, com um voto por conta em cada eleição. O resultado indica o líder mais votado nesta eleição.</p><ul><li>Acompanhe o estado e o prazo mostrados no topo da página.</li><li>Leia as informações do candidato antes de votar.</li><li>Não tente votar várias vezes com a mesma conta.</li><li>O resultado da votação passa por validação administrativa antes de se tornar oficial.</li></ul></article>
    <article class="wiki-section"><h3>07 · Benefícios estratégicos da liderança</h3><ul><li>Construir reputação e identidade para o clã.</li><li>Dar aos membros um objetivo coletivo e uma razão para evoluir em conjunto.</li><li>Fortalecer alianças, rivalidades e a participação nos eventos do reino.</li><li>Ter visibilidade pública como liderança na comunidade.</li><li>Disputar o prestígio e as responsabilidades associados ao controle de um castelo.</li></ul></article>
    <article class="wiki-section"><h3>08 · Transparência e responsabilidades</h3><p>O poder do castelo traz influência e consequências. O líder pode mudar os impostos sem aviso prévio, com efeito imediato e impacto sobre a economia do território. Jogadores, comerciantes e clãs precisam reagir às decisões de quem governa.</p><p>A liderança exige estratégia e responsabilidade. Os resultados eleitorais e as decisões oficiais sobre a propriedade dos castelos são validados pela administração do Virtual Boost.</p></article>
  </div><div class="wiki-note"><strong>O poder do senhor do castelo:</strong> no Virtual Boost, o líder que governa um castelo não representa apenas seu clã: torna-se um <strong>senhor feudal e autoridade política</strong> daquele território. Ele tem poder para definir e alterar os impostos sem aviso prévio e com efeito imediato. Cada decisão pode mudar os custos do comércio, influenciar a circulação de adena e provocar consequências econômicas para todos que vivem, lutam ou negociam naquela região.<br><br><strong>A eleição e a legitimidade:</strong> a votação pública registra a preferência dos jogadores. O resultado passa por apuração e validação da administração antes de se tornar oficial. A eleição acrescenta uma via política ao reino, sem substituir automaticamente a disputa pelo castelo.<br><br><strong>A Siege continua sendo decisiva:</strong> as Sieges preservam seu valor militar e estratégico na disputa territorial. A política e a força militar fazem parte da história do reino: a votação não apaga as batalhas, as conquistas nem o peso de defender ou tomar um castelo.</div></div>
</section>
<?php if ($accountName===''): ?><div class="notice"><strong style="color:#fff;">Login necessário.</strong> Entre na sua conta do Virtual Boost para se candidatar ou votar.</div>
<?php elseif ($isOpen): ?>
<div class="panel"><h2>Inscrever-se como candidato</h2><p>Somente líderes de clã podem se inscrever. Escolha seu personagem líder e o castelo que deseja disputar. O líder do clã proprietário também pode concorrer.</p>
<?php if (!$myLeaders): ?><div class="notice">Não encontramos nenhum líder de clã vinculado à sua conta.</div><?php else: ?>
<form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end;">
<input type="hidden" name="csrf_token" value="<?= election_h($csrf) ?>"><input type="hidden" name="election_action" value="register">
<label style="display:grid;gap:7px;color:#8995a5;">Seu personagem líder<select name="leader_id" required style="padding:12px;background:#080d15;color:#e7edf5;border:1px solid rgba(117,215,255,.2);border-radius:5px;"><?php foreach ($myLeaders as $leader): ?><option value="<?= (int)$leader['leader_id'] ?>"><?= election_h($leader['leader_name'].' — '.$leader['clan_name']) ?></option><?php endforeach; ?></select></label>
<label style="display:grid;gap:7px;color:#8995a5;">Castelo disputado<select name="castle_id" required style="padding:12px;background:#080d15;color:#e7edf5;border:1px solid rgba(117,215,255,.2);border-radius:5px;"><?php foreach ($castles as $castle): ?><option value="<?= (int)$castle['castle_id'] ?>"><?= election_h($castle['castle_name']) ?></option><?php endforeach; ?></select></label>
<label style="display:grid;gap:7px;color:#8995a5;">Proposta 1<textarea name="proposal_1" maxlength="255" required placeholder="Primeira proposta..." style="min-height:70px;padding:10px;background:#080d15;color:#e7edf5;border:1px solid rgba(117,215,255,.2);border-radius:5px;"></textarea></label>
<label style="display:grid;gap:7px;color:#8995a5;">Proposta 2<textarea name="proposal_2" maxlength="255" required placeholder="Segunda proposta..." style="min-height:70px;padding:10px;background:#080d15;color:#e7edf5;border:1px solid rgba(117,215,255,.2);border-radius:5px;"></textarea></label>
<label style="display:grid;gap:7px;color:#8995a5;">Proposta 3<textarea name="proposal_3" maxlength="255" required placeholder="Terceira proposta..." style="min-height:70px;padding:10px;background:#080d15;color:#e7edf5;border:1px solid rgba(117,215,255,.2);border-radius:5px;"></textarea></label>
<button class="vote" type="submit" style="margin-top:0;">CANDIDATAR-SE</button></form><?php endif; ?></div>
<?php endif; ?>
<div style="display:flex;justify-content:space-between;gap:15px;align-items:end;flex-wrap:wrap;margin:28px 0 14px;"><div><h2>Candidatos por castelo</h2><p style="margin:6px 0 0;">Os candidatos se inscrevem para um território específico. Veja as propostas e vote em cada castelo.</p></div></div>
<?php if (!$castles): ?><div class="notice">Nenhum castelo foi retornado pelo banco.</div><?php else: ?><section class="grid">
<?php foreach ($castles as $castle): $castleId=(int)$castle['castle_id']; $castleCandidates=$candidatesByCastle[$castleId] ?? []; ?>
<article class="castle"><h3><?= election_h($castle['castle_name']) ?></h3>
<div class="castle-image-frame"><img class="castle-image" src="/site/assets/images/castles/castle-<?= $castleId ?>.jpg" alt="Imagem do castelo <?= election_h($castle['castle_name']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';"><div class="castle-image-placeholder" style="display:none;">COLOQUE A IMAGEM<br><small>/site/assets/images/castles/castle-<?= $castleId ?>.jpg</small></div></div>
<div class="metric"><span>Imposto atual</span><b><?= election_h($castle['tax_percent'] ?? 0) ?>%</b></div><div class="metric"><span>Tesouro</span><b><?= election_num($castle['treasury'] ?? 0) ?> adena</b></div>
<?php if ($election && $election['status']==='finished'): ?>
<div class="leader" style="margin-top:13px;"><small>Vencedor deste castelo</small><strong><?= !empty($winnersByCastle[$castleId]) ? election_h($winnersByCastle[$castleId]['candidate_name']) : 'Sem vencedor' ?></strong><?php if (!empty($winnersByCastle[$castleId])): ?><div style="color:#75d7ff;font-size:12px;margin-top:5px;"><?= election_h($winnersByCastle[$castleId]['clan_name']) ?> · <?= (int)$winnersByCastle[$castleId]['votes'] ?> voto(s)</div><?php endif; ?></div>
<?php endif; ?>
<button type="button" class="candidates-toggle" aria-expanded="false" aria-controls="candidate-list-<?= $castleId ?>" onclick="var p=document.getElementById('candidate-list-<?= $castleId ?>');var open=p.hidden;p.hidden=!open;this.setAttribute('aria-expanded',open?'true':'false');"><span><?= count($castleCandidates) ?> Candidatos</span><span class="candidate-arrow" aria-hidden="true">⌄</span></button>
<div class="candidate-list-extra" id="candidate-list-<?= $castleId ?>" hidden>
<?php if (!$castleCandidates): ?><div class="notice">Ainda não há candidatos inscritos para este castelo.</div><?php else: ?>
<?php foreach ($castleCandidates as $cand): ?><div class="leader candidate-card"><div class="candidate-head"><div class="candidate-avatar" aria-hidden="true"><img src="/site/assets/images/candidate-icon.png" alt="" onerror="this.style.display='none';"></div><div class="candidate-identity"><small>CANDIDATO</small><strong><?= election_h($cand['candidate_name']) ?></strong><small class="candidate-clan">Clã: <?= election_h($cand['clan_name'] ?: 'Clã não informado') ?></small></div></div><div class="metric"><span>Votos</span><b><?= (int)$cand['votes'] ?></b></div>
<button type="button" class="proposal-toggle" aria-expanded="false" onclick="var p=document.getElementById('proposal-<?= (int)$cand['id'] ?>');var open=p.hidden;p.hidden=!open;this.setAttribute('aria-expanded',open?'true':'false');this.textContent=open?'OCULTAR PROPOSTAS':'PROPOSTAS';">PROPOSTAS</button>
<div class="proposal-card" id="proposal-<?= (int)$cand['id'] ?>" hidden><h4>Propostas de <?= election_h($cand['candidate_name']) ?></h4><p><strong>1.</strong> <?= election_h($cand['proposal_1'] ?? 'Não informada') ?></p><p><strong>2.</strong> <?= election_h($cand['proposal_2'] ?? 'Não informada') ?></p><p><strong>3.</strong> <?= election_h($cand['proposal_3'] ?? 'Não informada') ?></p><p><strong>Clã:</strong> <?= election_h($cand['clan_name']) ?></p></div>
<?php if ($isOpen && $accountName!==''): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= election_h($csrf) ?>"><input type="hidden" name="election_action" value="vote"><input type="hidden" name="candidate_id" value="<?= (int)$cand['id'] ?>"><button class="vote" type="submit" <?= !empty($alreadyVoted[$castleId])?'disabled':'' ?>><?= !empty($alreadyVoted[$castleId])?'VOTO JÁ REGISTRADO':'VOTAR NESTE CANDIDATO' ?></button></form><?php endif; ?></div><?php endforeach; ?>
<?php endif; ?></div></article><?php endforeach; ?></section><?php endif; ?>
<section class="panel" style="margin-top:22px;"><h2>Resultado por castelo</h2>
<?php if ($election && $election['status']==='finished'): ?><div class="leaderboard"><?php foreach ($castles as $castle): $winner=$winnersByCastle[(int)$castle['castle_id']] ?? null; ?><div class="score"><span><?= election_h($castle['castle_name']) ?> — <?= $winner ? election_h($winner['candidate_name'].' ('.$winner['clan_name'].')') : 'Sem vencedor' ?></span><b><?= $winner ? (int)$winner['votes'].' voto(s)' : '0 votos' ?></b></div><?php endforeach; ?></div><p>Os resultados são encaminhados para validação administrativa. A propriedade dos castelos não é alterada automaticamente.</p>
<?php elseif (!$votes): ?><p>Nenhum voto registrado para esta eleição.</p><?php else: ?><div class="leaderboard"><?php foreach ($votes as $v): ?><div class="score"><span><?= election_h(($castles[array_search((int)$v['castle_id'], array_column($castles,'castle_id'))]['castle_name'] ?? 'Castelo').' — '.$v['candidate_name']) ?></span><b><?= (int)$v['votes'] ?> voto(s)</b></div><?php endforeach; ?></div><?php endif; ?></section>
<div class="foot">O resultado da eleição será apurado e validado pela administração antes de qualquer anúncio oficial. A votação representa a escolha política dos jogadores; a disputa militar pelas fortalezas continua tendo seu lugar na história do reino.</div>
</main>
<script>
(function(){var button=document.getElementById('wikiToggle');var panel=document.getElementById('leadershipWiki');if(!button||!panel)return;button.addEventListener('click',function(){var expanded=button.getAttribute('aria-expanded')==='true';button.setAttribute('aria-expanded',String(!expanded));panel.hidden=expanded;if(!expanded){panel.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'nearest'});}});})();
</script>
<?php if ($isOpen): ?><script>
(function(){var left=<?= (int)$secondsLeft ?>;var el=document.getElementById('countdown');function tick(){if(left<=0){el.textContent='00:00:00';window.setTimeout(function(){window.location.reload();},1200);return;}var h=Math.floor(left/3600),m=Math.floor((left%3600)/60),s=left%60;el.textContent=[h,m,s].map(function(n){return String(n).padStart(2,'0')}).join(':');left--;if(left>=0)window.setTimeout(tick,1000);}tick();})();
</script><?php endif; ?>

<script>
(function(){
  var canvas=document.getElementById('electionParticles');
  if(!canvas)return;
  var ctx=canvas.getContext('2d');
  if(!ctx)return;
  var reduced=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var particles=[],w=0,h=0,raf=0;
  function resize(){
    w=window.innerWidth;h=window.innerHeight;
    var dpr=Math.min(window.devicePixelRatio||1,1.5);
    canvas.width=Math.floor(w*dpr);canvas.height=Math.floor(h*dpr);
    canvas.style.width=w+'px';canvas.style.height=h+'px';ctx.setTransform(dpr,0,0,dpr,0,0);
    var count=Math.min(58,Math.max(22,Math.floor(w*h/23000)));
    particles=Array.from({length:count},function(){return{x:Math.random()*w,y:Math.random()*h,r:.7+Math.random()*1.7,vx:(Math.random()-.5)*.22,vy:-.12-Math.random()*.2,a:.18+Math.random()*.45,hue:Math.random()>.25?195:260};});
    draw();
  }
  function draw(){
    ctx.clearRect(0,0,w,h);
    for(var i=0;i<particles.length;i++){
      var p=particles[i];
      if(!reduced){p.x+=p.vx;p.y+=p.vy;if(p.y<-5){p.y=h+5;p.x=Math.random()*w;}if(p.x<-5)p.x=w+5;if(p.x>w+5)p.x=-5;}
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle='hsla('+p.hue+',100%,75%,'+p.a+')';ctx.shadowBlur=8;ctx.shadowColor='hsla('+p.hue+',100%,65%,.45)';ctx.fill();ctx.shadowBlur=0;
      for(var j=i+1;j<particles.length;j++){
        var q=particles[j],dx=p.x-q.x,dy=p.y-q.y,dist=Math.sqrt(dx*dx+dy*dy);
        if(dist<105){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);ctx.strokeStyle='rgba(100,190,255,'+((1-dist/105)*.10)+')';ctx.lineWidth=.6;ctx.stroke();}
      }
    }
    if(!reduced)raf=window.requestAnimationFrame(draw);
  }
  window.addEventListener('resize',resize,{passive:true});
  resize();
  if(reduced)window.cancelAnimationFrame(raf);
})();
</script>
</body></html>
