<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

/*
|--------------------------------------------------------------------------
| FUNÇÕES
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatAdena($value)
{
    return number_format((int) $value, 0, ',', '.');
}

function formatPercent($value)
{
    return rtrim(
        rtrim(number_format((float) $value, 2, ',', '.'), '0'),
        ','
    );
}


/* ELEIÇÃO POLÍTICA — controles administrativos */
$electionError = '';
$electionNotice = '';
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS vb_political_election_votes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        election_id BIGINT UNSIGNED NOT NULL,
        account_name VARCHAR(45) NOT NULL,
        castle_id INT NOT NULL,
        candidate_leader_id INT NOT NULL,
        candidate_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vb_election_account (election_id, account_name),
        KEY idx_vb_election_candidate (election_id, candidate_leader_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Migração simples caso a tabela de votos da versão inicial já exista.
    $colCheck = $pdo->query("SHOW COLUMNS FROM vb_political_election_votes LIKE 'election_id'")->fetch(PDO::FETCH_ASSOC);
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE vb_political_election_votes ADD COLUMN election_id BIGINT UNSIGNED NOT NULL DEFAULT 1 FIRST");
        try { $pdo->exec("ALTER TABLE vb_political_election_votes DROP INDEX uq_vb_election_account"); } catch (Throwable $ignored) {}
        try { $pdo->exec("ALTER TABLE vb_political_election_votes ADD UNIQUE KEY uq_vb_election_account (election_id, account_name)"); } catch (Throwable $ignored) {}
    }
} catch (Throwable $e) {
    $electionError = 'Não foi possível preparar as tabelas da eleição. Verifique as permissões do banco e a estrutura existente.';
}
function vb_election_winner(PDO $pdo, int $electionId): ?array {
    $stmt = $pdo->prepare("SELECT candidate_leader_id, candidate_name, COUNT(*) AS votes
        FROM vb_political_election_votes WHERE election_id = ?
        GROUP BY candidate_leader_id, candidate_name
        ORDER BY votes DESC, candidate_name ASC LIMIT 1");
    $stmt->execute([$electionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$electionError) {
    $token = isset($_POST['election_csrf']) && is_string($_POST['election_csrf']) ? $_POST['election_csrf'] : null;
    $action = isset($_POST['election_action']) && is_string($_POST['election_action']) ? $_POST['election_action'] : '';
    if (!verify_csrf($token)) {
        $electionError = 'A validação de segurança expirou. Atualize a página e tente novamente.';
    } else {
        try {
            $pdo->beginTransaction();
            $activeStmt = $pdo->query("SELECT * FROM vb_political_elections WHERE status IN ('open','paused') ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $activeElection = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($action === 'start') {
                if ($activeElection) {
                    throw new RuntimeException('Já existe uma eleição aberta ou pausada. Encerre-a antes de iniciar outra.');
                }
                $hours = filter_input(INPUT_POST, 'duration_hours', FILTER_VALIDATE_INT);
                if (!$hours || $hours < 1 || $hours > 720) {
                    throw new RuntimeException('Informe uma duração entre 1 e 720 horas.');
                }
                $stmt = $pdo->prepare("INSERT INTO vb_political_elections (status, starts_at, ends_at, remaining_seconds, created_by) VALUES ('open', NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR), ?)");
                $stmt->execute([$hours, (int) ($_SESSION['admin_id'] ?? 0)]);
                $electionNotice = 'Eleição iniciada com sucesso.';
                audit('election_started', 'Duração: ' . $hours . ' horas');
            } elseif (!$activeElection) {
                throw new RuntimeException('Não existe eleição aberta ou pausada para essa ação.');
            } elseif ($action === 'pause' && $activeElection['status'] === 'open') {
                $stmt = $pdo->prepare("UPDATE vb_political_elections SET status='paused', remaining_seconds=GREATEST(TIMESTAMPDIFF(SECOND,NOW(),ends_at),0), paused_at=NOW() WHERE id=?");
                $stmt->execute([(int)$activeElection['id']]);
                $electionNotice = 'Eleição pausada. O tempo restante foi preservado.';
                audit('election_paused', 'Eleição #' . $activeElection['id']);
            } elseif ($action === 'resume' && $activeElection['status'] === 'paused') {
                $stmt = $pdo->prepare("UPDATE vb_political_elections SET status='open', starts_at=starts_at, ends_at=DATE_ADD(NOW(), INTERVAL remaining_seconds SECOND), paused_at=NULL WHERE id=?");
                $stmt->execute([(int)$activeElection['id']]);
                $electionNotice = 'Eleição retomada.';
                audit('election_resumed', 'Eleição #' . $activeElection['id']);
            } elseif (in_array($action, ['finish','force_finish'], true)) {
                $winner = vb_election_winner($pdo, (int)$activeElection['id']);
                $stmt = $pdo->prepare("UPDATE vb_political_elections SET status='finished', ends_at=NOW(), winner_leader_id=?, winner_name=?, forced=? WHERE id=?");
                $stmt->execute([$winner['candidate_leader_id'] ?? null, $winner['candidate_name'] ?? null, $action === 'force_finish' ? 1 : 0, (int)$activeElection['id']]);
                $electionNotice = $winner
                    ? 'Eleição encerrada. Vencedor apurado: ' . $winner['candidate_name'] . ' (' . $winner['votes'] . ' voto(s)). A troca de dono do castelo continua manual.'
                    : 'Eleição encerrada sem votos; nenhum vencedor foi definido.';
                audit($action === 'force_finish' ? 'election_force_finished' : 'election_finished', 'Eleição #' . $activeElection['id'] . '; vencedor: ' . ($winner['candidate_name'] ?? 'sem votos'));
            } elseif ($action === 'cancel') {
                $stmt = $pdo->prepare("UPDATE vb_political_elections SET status='cancelled', ends_at=NOW() WHERE id=?");
                $stmt->execute([(int)$activeElection['id']]);
                $electionNotice = 'Eleição cancelada. Os votos ficam preservados para auditoria, mas não há vencedor oficial.';
                audit('election_cancelled', 'Eleição #' . $activeElection['id']);
            } else {
                throw new RuntimeException('Ação indisponível para o estado atual da eleição.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $electionError = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível aplicar a ação da eleição. Verifique o banco de dados.';
        }
    }
}
$activeElection = null;
$latestElection = null;
$electionVoteCounts = [];
if (!$electionError) {
    try {
        $activeStmt = $pdo->query("SELECT * FROM vb_political_elections WHERE status IN ('open','paused') ORDER BY id DESC LIMIT 1");
        $activeElection = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($activeElection && $activeElection['status'] === 'open' && strtotime($activeElection['ends_at']) <= time()) {
            $winner = vb_election_winner($pdo, (int)$activeElection['id']);
            $finish = $pdo->prepare("UPDATE vb_political_elections SET status='finished', winner_leader_id=?, winner_name=?, forced=0 WHERE id=? AND status='open' AND ends_at <= NOW()");
            $finish->execute([$winner['candidate_leader_id'] ?? null, $winner['candidate_name'] ?? null, (int)$activeElection['id']]);
            $activeElection = null;
        }
        $latestStmt = $pdo->query("SELECT * FROM vb_political_elections ORDER BY id DESC LIMIT 1");
        $latestElection = $latestStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($latestElection) {
            $votesStmt = $pdo->prepare("SELECT candidate_leader_id, MAX(candidate_name) AS candidate_name, COUNT(*) AS votes FROM vb_political_election_votes WHERE election_id=? GROUP BY candidate_leader_id ORDER BY votes DESC, candidate_name ASC");
            $votesStmt->execute([(int)$latestElection['id']]);
            $electionVoteCounts = $votesStmt->fetchAll(PDO::FETCH_ASSOC);
            $latestElection['total_votes'] = array_sum(array_map(static fn($v) => (int)$v['votes'], $electionVoteCounts));
        }
    } catch (Throwable $e) {
        $electionError = 'Não foi possível carregar o estado atual da eleição.';
    }
}
$electionCsrf = csrf_token();

/*
|--------------------------------------------------------------------------
| CASTELOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.ID AS castle_id,
        c.name AS castle_name,
        c.tax_percent,
        c.treasury,
        c.collected_shops,

        cd.clan_id,

        cs.name AS clan_name,
        cs.leader_id,

        ch.char_name AS leader_name

    FROM castle c

    LEFT JOIN clan_data cd
        ON cd.hasCastle = c.ID

    LEFT JOIN clan_subpledges cs
        ON cs.clan_id = cd.clan_id

    LEFT JOIN characters ch
        ON ch.obj_Id = cs.leader_id

    ORDER BY c.ID ASC
";

try {

    $stmt = $pdo->query($sql);
    $castles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $castles = array();

    $databaseError = $e->getMessage();
}

/*
|--------------------------------------------------------------------------
| TOTAIS
|--------------------------------------------------------------------------
*/

$totalCastles = count($castles);

$totalTreasury = 0;
$totalCollectShops = 0;

foreach ($castles as $castle) {

    $totalTreasury += (int) $castle['treasury'];
    $totalCollectShops += (int) $castle['collected_shops'];
}

$totalCastleMoney = $totalTreasury + $totalCollectShops;

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>Castelos — Administração</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>
.btn{border:1px solid rgba(117,215,255,.24);background:linear-gradient(90deg,rgba(23,105,170,.35),rgba(86,52,184,.25));color:#dceeff;padding:11px 15px;border-radius:6px;font:700 11px Orbitron,sans-serif;letter-spacing:.4px;cursor:pointer}.btn:hover{filter:brightness(1.2)}


:root {

    --black:#05070b;
    --black2:#080b12;

    --panel:#0b1019;
    --panel2:#0e1520;

    --blue:#4ca8ff;
    --blue2:#1769aa;

    --cyan:#75d7ff;

    --purple:#8c65ff;
    --purple2:#5634b8;

    --gold:#d5b46a;

    --text:#e7edf5;
    --muted:#8995a5;

    --green:#63d39a;
    --red:#d96a6a;

    --line:rgba(130,170,220,.16);

}

* {
    box-sizing:border-box;
}

html,
body {

    margin:0;
    padding:0;

    min-height:100%;

}

body {

    background:
        radial-gradient(
            circle at top right,
            rgba(76,168,255,.08),
            transparent 30%
        ),
        radial-gradient(
            circle at bottom left,
            rgba(140,101,255,.06),
            transparent 30%
        ),
        var(--black);

    color:var(--text);

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

.sidebar {

    position:fixed;

    left:0;
    top:0;
    bottom:0;

    width:240px;

    background:
        linear-gradient(
            180deg,
            #0b1019,
            #080b12
        );

    border-right:1px solid var(--line);

    padding:25px 15px;

    z-index:100;

}

.logo {

    padding:10px 12px 28px;

    border-bottom:1px solid var(--line);

    margin-bottom:20px;

}

.logo h1 {

    margin:0;

    font-size:19px;

    letter-spacing:1.5px;

}

.logo span {

    display:block;

    margin-top:8px;

    color:var(--cyan);

    font-size:10px;

    letter-spacing:2px;

}

.menu-title {

    font-size:10px;

    color:#5f6b7b;

    letter-spacing:2px;

    padding:10px 12px;

}

.menu a {

    display:block;

    padding:12px;

    margin:4px 0;

    color:var(--muted);

    text-decoration:none;

    border-radius:6px;

    font-size:13px;

    transition:.2s;

}

.menu a:hover,
.menu a.active {

    background:
        linear-gradient(
            90deg,
            rgba(76,168,255,.13),
            rgba(140,101,255,.08)
        );

    color:white;

    border-left:2px solid var(--blue);

}

.logout {

    position:absolute;

    left:15px;
    right:15px;
    bottom:20px;

}

.logout a {

    display:block;

    text-align:center;

    padding:11px;

    border:1px solid rgba(217,106,106,.2);

    color:#d96a6a;

    text-decoration:none;

    border-radius:6px;

    font-size:12px;

}

/*
|--------------------------------------------------------------------------
| CONTENT
|--------------------------------------------------------------------------
*/

.content {
    margin-left: 230px;
    padding: 30px;
    overflow-x: auto;
}

.page-header {
    margin-bottom: 28px;
}

.page-header h1 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 25px;
    color: #ffffff;
}

.page-header p {
    margin: 7px 0 0;
    color: #65798d;
    font-size: 14px;
}

/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    padding: 20px;
    transition: .2s;
}

.card:hover {
    border-color: rgba(57,213,255,.20);
}

.card-label {
    color: #63778a;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.card-value {
    color: #39d5ff;
    font-family: 'Orbitron', sans-serif;
    font-size: 22px;
    font-weight: 700;
}

.card-sub {
    color: #536678;
    font-size: 12px;
    margin-top: 6px;
}

/*
|--------------------------------------------------------------------------
| PANEL
|--------------------------------------------------------------------------
*/

.panel {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    overflow: hidden;
}

.panel-header {
    padding: 20px 22px;
    border-bottom: 1px solid #182535;
}

.panel-header h2 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 15px;
    color: #ffffff;
}

.panel-header p {
    margin: 6px 0 0;
    color: #5f7285;
    font-size: 12px;
}

/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

.error-box {
    margin-bottom: 25px;
    background: rgba(255,70,70,.08);
    border: 1px solid rgba(255,70,70,.25);
    color: #ff7070;
    border-radius: 7px;
    padding: 15px 18px;
    font-size: 13px;
}

/*
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
*/

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

th {
    text-align: left;
    color: #5d7185;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 13px 16px;
    background: #080d15;
    border-bottom: 1px solid #182535;
}

td {
    padding: 15px 16px;
    border-bottom: 1px solid rgba(24,37,53,.65);
    font-size: 14px;
    vertical-align: middle;
    color: #b8c8d7;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: rgba(57,213,255,.025);
}

.castle-name {
    font-family: 'Orbitron', sans-serif;
    font-weight: 600;
    color: #ffffff;
    font-size: 13px;
}

.castle-id {
    color: #536678;
    font-size: 11px;
    margin-top: 5px;
}

.clan-name {
    color: #dce7f0;
    font-weight: 600;
}

.leader-name {
    color: #9fb0bf;
}

.muted {
    color: #536678;
    font-size: 12px;
}

.treasury {
    color: #39d5ff;
    font-weight: 700;
    white-space: nowrap;
}

.collect {
    color: #d5a93a;
    font-weight: 700;
    white-space: nowrap;
}

.total {
    color: #ffffff;
    font-weight: 700;
    white-space: nowrap;
}

.tax {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 5px;
    background: rgba(213,169,58,.09);
    border: 1px solid rgba(213,169,58,.15);
    color: #d5a93a;
    font-weight: 700;
    font-size: 12px;
}

.empty {
    padding: 50px 20px;
    text-align: center;
    color: #536678;
}

/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 1100px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        border-right: none;
        border-bottom: 1px solid rgba(57,213,255,.12);
    }

    .content {
        margin-left: 0;
        padding: 20px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<!--
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
-->

<aside class="sidebar">

    <div class="logo">

        <h1>
            L2 VIRTUAL BOOST
        </h1>

        <span>
            ADMINISTRATION
        </span>

    </div>


    <div class="menu">

        <div class="menu-title">
            PRINCIPAL
        </div>

        <a
            href="index.php">
            Dashboard
        </a>

        <a href="players.php">
            Players
        </a>

        <a href="donates.php">
            Donates
        </a>


        <div class="menu-title">
            SEGURANÇA
        </div>

        <a href="anticheat.php">
            Anti-Cheat
        </a>

        <a href="punishments.php">
            Punishments
        </a>


        <div class="menu-title">
            SERVIDOR
        </div>

        <a href="economy.php">
            Economy
        </a>

        <a href="castles.php"
		class="active">
            Castles
        </a>

        <a href="castles.php#election-control">
            Eleição Política
        </a>

        <a href="logs.php">
            Logs
        </a>


        <div class="menu-title">
            SISTEMA
        </div>

        <a href="administrators.php">
            Administrators
        </a>

        <a href="settings.php">
            Settings
        </a>

    </div>


    <div class="logout">

        <a href="logout.php">
            SAIR DO PAINEL
        </a>

    </div>

</aside>

<!--
|--------------------------------------------------------------------------
| CONTEÚDO
|--------------------------------------------------------------------------
-->

<main class="content">


<div class="page-header">

    <h1>
        Castelos
    </h1>

    <p>
        Monitoramento dos castelos, proprietários, taxas e valores armazenados.
    </p>

</div>


<section id="election-control" class="panel" style="margin-bottom:25px;">
  <div class="panel-header">
    <h2>Controle da Eleição Política</h2>
    <p>Inicie e administre a votação. O sistema registra o vencedor; a mudança do proprietário do castelo é manual.</p>
  </div>
  <div style="padding:20px 22px;">
    <?php if ($electionError !== ''): ?><div class="error-box"><?= h($electionError) ?></div><?php endif; ?>
    <?php if ($electionNotice !== ''): ?><div style="margin-bottom:15px;padding:13px 15px;border:1px solid rgba(99,211,154,.3);border-radius:7px;color:#63d39a;background:rgba(99,211,154,.06);"><?= h($electionNotice) ?></div><?php endif; ?>
    <?php if ($activeElection): ?>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <div class="card"><div class="card-label">Eleição #<?= (int)$activeElection['id'] ?></div><div class="card-value" style="font-size:18px;"><?= $activeElection['status'] === 'open' ? 'ABERTA' : 'PAUSADA' ?></div></div>
        <div class="card"><div class="card-label">Início</div><div style="color:#e7edf5;"><?= h($activeElection['starts_at']) ?></div></div>
        <div class="card"><div class="card-label">Fim previsto</div><div style="color:#e7edf5;"><?= h($activeElection['ends_at']) ?></div></div>
        <div class="card"><div class="card-label">Votos registrados</div><div class="card-value" style="font-size:18px;"><?= (int)($latestElection['total_votes'] ?? 0) ?></div></div>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
        <?php if ($activeElection['status'] === 'open'): ?>
          <form method="post"><input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>"><input type="hidden" name="election_action" value="pause"><button class="btn" type="submit">Pausar eleição</button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>"><input type="hidden" name="election_action" value="resume"><button class="btn" type="submit">Retomar eleição</button></form>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('Finalizar agora e apurar o vencedor pela quantidade de votos?');"><input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>"><input type="hidden" name="election_action" value="finish"><button class="btn" type="submit">Finalizar e apurar</button></form>
        <form method="post" onsubmit="return confirm('ENCERRAR FORÇADAMENTE agora? O resultado será congelado com a apuração atual.');"><input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>"><input type="hidden" name="election_action" value="force_finish"><button class="btn" type="submit" style="border-color:#d96a6a;color:#ff9a9a;">Encerrar à força</button></form>
        <form method="post" onsubmit="return confirm('Cancelar a eleição? Não haverá vencedor oficial.');"><input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>"><input type="hidden" name="election_action" value="cancel"><button class="btn" type="submit">Cancelar</button></form>
      </div>
    <?php else: ?>
      <form method="post" style="display:flex;align-items:end;flex-wrap:wrap;gap:12px;">
        <input type="hidden" name="election_csrf" value="<?= h($electionCsrf) ?>">
        <input type="hidden" name="election_action" value="start">
        <label style="display:grid;gap:7px;color:#8995a5;font-size:12px;">Duração da eleição (horas)
          <input type="number" name="duration_hours" min="1" max="720" value="24" required style="background:#080d15;color:#e7edf5;border:1px solid rgba(130,170,220,.25);padding:12px;border-radius:6px;width:200px;">
        </label>
        <button class="btn" type="submit">Iniciar eleição</button>
      </form>
    <?php endif; ?>
    <?php if ($latestElection): ?>
      <div style="border-top:1px solid #182535;margin-top:20px;padding-top:17px;">
        <div style="color:#8995a5;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">Última eleição · #<?= (int)$latestElection['id'] ?> · Estado: <?= h($latestElection['status']) ?></div>
        <div style="display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap;color:#e7edf5;">
          <span>Votos apurados: <strong><?= (int)($latestElection['total_votes'] ?? 0) ?></strong></span>
          <span>Vencedor registrado: <strong style="color:#75d7ff;"><?= h($latestElection['winner_name'] ?: 'Ainda não definido') ?></strong></span>
          <?php if (!empty($latestElection['forced'])): ?><span style="color:#ff9a9a;">Encerramento forçado</span><?php endif; ?>
        </div>
        <?php if ($electionVoteCounts): ?>
          <div style="margin-top:14px;display:grid;gap:7px;">
          <?php foreach ($electionVoteCounts as $vc): ?>
            <div style="display:flex;justify-content:space-between;gap:15px;padding:10px 12px;background:#080d15;border-radius:5px;color:#b7c6d8;"><span><?= h($vc['candidate_name']) ?></span><strong><?= (int)$vc['votes'] ?> voto(s)</strong></div>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <p style="color:#8995a5;font-size:12px;line-height:1.6;margin-bottom:0;">Empate: o sistema usa o nome do candidato como critério estável de desempate. Nenhuma alteração automática é feita na propriedade do castelo.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if (isset($databaseError)): ?>

    <div class="error-box">

        <strong>
            Erro ao consultar os castelos:
        </strong>

        <br>

        <?= h($databaseError) ?>

    </div>






<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
-->

<section class="cards">

    <div class="card">

        <div class="card-label">
            Castelos
        </div>

        <div class="card-value">
            <?= number_format($totalCastles, 0, ',', '.') ?>
        </div>

        <div class="card-sub">
            Castelos cadastrados
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Tesouro total
        </div>

        <div class="card-value">
            <?= formatAdena($totalTreasury) ?>
        </div>

        <div class="card-sub">
            Adena nos cofres
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Collect Shops
        </div>

        <div class="card-value">
            <?= formatAdena($totalCollectShops) ?>
        </div>

        <div class="card-sub">
            Arrecadação das lojas
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Total controlado
        </div>

        <div class="card-value">
            <?= formatAdena($totalCastleMoney) ?>
        </div>

        <div class="card-sub">
            Treasury + Collect Shops
        </div>

    </div>

</section>


<!--
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
-->

<section class="panel">

    <div class="panel-header">

        <h2>
            Controle dos Castelos
        </h2>

        <p>
            Informações atuais registradas na tabela castle.
        </p>

    </div>


    <?php if (empty($castles)): ?>

        <div class="empty">
            Nenhum castelo encontrado.
        </div>

    <?php else: ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Castelo
                        </th>

                        <th>
                            Clã
                        </th>

                        <th>
                            Líder
                        </th>

                        <th>
                            Taxa
                        </th>

                        <th>
                            Treasury
                        </th>

                        <th>
                            Collect Shops
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($castles as $castle): ?>

                    <?php

                    $treasury = (int) $castle['treasury'];

                    $collectShops = (int) $castle['collected_shops'];

                    $total = $treasury + $collectShops;

                    ?>

                    <tr>

                        <!-- CASTELO -->

                        <td>

                            <div class="castle-name">
                                <?= h($castle['castle_name']) ?>
                            </div>

                            <div class="castle-id">
                                ID <?= (int) $castle['castle_id'] ?>
                            </div>

                        </td>


                        <!-- CLÃ -->

                        <td>

                            <?php if (!empty($castle['clan_name'])): ?>

                                <div class="clan-name">
                                    <?= h($castle['clan_name']) ?>
                                </div>

                                <?php if (!empty($castle['clan_id'])): ?>

                                    <div class="muted">
                                        Clan ID:
                                        <?= (int) $castle['clan_id'] ?>
                                    </div>

                                <?php endif; ?>

                            <?php else: ?>

                                <span class="muted">
                                    Sem proprietário
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- LÍDER -->

                        <td>

                            <?php if (!empty($castle['leader_name'])): ?>

                                <div class="leader-name">
                                    <?= h($castle['leader_name']) ?>
                                </div>

                            <?php elseif (!empty($castle['leader_id'])): ?>

                                <div class="muted">
                                    Leader ID:
                                    <?= (int) $castle['leader_id'] ?>
                                </div>

                            <?php else: ?>

                                <span class="muted">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- TAXA -->

                        <td>

                            <span class="tax">
                                <?= formatPercent($castle['tax_percent']) ?>%
                            </span>

                        </td>


                        <!-- TESOURO -->

                        <td>

                            <span class="treasury">
                                <?= formatAdena($treasury) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>


                        <!-- COLLECT SHOPS -->

                        <td>

                            <span class="collect">
                                <?= formatAdena($collectShops) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>


                        <!-- TOTAL -->

                        <td>

                            <span class="total">
                                <?= formatAdena($total) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


</main>

</body>

</html>
