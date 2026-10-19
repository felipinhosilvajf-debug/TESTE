<?php
declare(strict_types=1);

/*
 * Eleição do Virtual Boost — versão inicial para testes.
 * A identidade do eleitor precisa vir da sessão autenticada do site do jogo.
 * Esta página aceita apenas chaves de conta dedicadas; não usa a sessão do admin.
 */
require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

global $pdo;

function election_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function election_adena($value): string
{
    return number_format((int) $value, 0, ',', '.');
}

if (empty($_SESSION['vb_election_csrf']) || !is_string($_SESSION['vb_election_csrf'])) {
    $_SESSION['vb_election_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['vb_election_csrf'];

/*
 * Uma linha por conta: a restrição UNIQUE é a garantia final contra voto duplicado.
 * A tabela não altera tesouro, taxa nem propriedade de castelo.
 */
$setupError = null;
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vb_political_election_votes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            account_name VARCHAR(45) NOT NULL,
            castle_id INT NOT NULL,
            candidate_leader_id INT NOT NULL,
            candidate_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_vb_election_account (account_name),
            KEY idx_vb_election_candidate (castle_id, candidate_leader_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Throwable $e) {
    $setupError = 'Não foi possível preparar a tabela de votação. Verifique as permissões de CREATE TABLE do usuário do banco.';
}

$accountName = '';
foreach (['account_name', 'l2_account', 'account'] as $sessionKey) {
    if (isset($_SESSION[$sessionKey]) && is_string($_SESSION[$sessionKey])) {
        $candidateAccount = trim($_SESSION[$sessionKey]);
        if ($candidateAccount !== '' && strlen($candidateAccount) <= 45) {
            $accountName = $candidateAccount;
            break;
        }
    }
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupError) {
    $submittedCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($accountName === '') {
        $message = 'Entre na sua conta do jogo pelo site para votar. A página ainda precisa compartilhar a sessão de login do site.';
        $messageType = 'error';
    } elseif (!hash_equals($csrf, $submittedCsrf)) {
        $message = 'Sua sessão expirou ou a validação de segurança falhou. Atualize a página e tente novamente.';
        $messageType = 'error';
    } else {
        $castleId = filter_input(INPUT_POST, 'castle_id', FILTER_VALIDATE_INT);
        if (!$castleId || $castleId < 1) {
            $message = 'Selecione um candidato válido.';
            $messageType = 'error';
        } else {
            try {
                $candidateSql = "
                    SELECT c.ID AS castle_id, c.name AS castle_name,
                           cs.leader_id, ch.char_name AS leader_name
                    FROM castle c
                    INNER JOIN clan_data cd ON cd.hasCastle = c.ID
                    INNER JOIN clan_subpledges cs ON cs.clan_id = cd.clan_id
                    INNER JOIN characters ch ON ch.obj_Id = cs.leader_id
                    WHERE c.ID = ?
                    LIMIT 1
                ";
                $candidateStmt = $pdo->prepare($candidateSql);
                $candidateStmt->execute([$castleId]);
                $candidate = $candidateStmt->fetch(PDO::FETCH_ASSOC);

                if (!$candidate || empty($candidate['leader_id']) || empty($candidate['leader_name'])) {
                    $message = 'Esse castelo não possui um líder elegível no momento.';
                    $messageType = 'error';
                } else {
                    $voteStmt = $pdo->prepare("
                        INSERT INTO vb_political_election_votes
                            (account_name, castle_id, candidate_leader_id, candidate_name)
                        VALUES (?, ?, ?, ?)
                    ");
                    try {
                        $voteStmt->execute([
                            $accountName,
                            (int) $candidate['castle_id'],
                            (int) $candidate['leader_id'],
                            (string) $candidate['leader_name']
                        ]);
                        $message = 'Voto registrado! Nesta versão de teste, o voto é contabilizado, mas não transfere impostos nem altera o dono do castelo.';
                        $messageType = 'success';
                    } catch (PDOException $e) {
                        if ((string) $e->getCode() === '23000') {
                            $message = 'Sua conta já votou nesta eleição. Cada conta pode registrar apenas um voto.';
                        } else {
                            throw $e;
                        }
                        $messageType = 'error';
                    }
                }
            } catch (Throwable $e) {
                $message = 'Não foi possível registrar o voto. Confira a conexão e a estrutura do banco.';
                $messageType = 'error';
            }
        }
    }
}

$castles = [];
$totalTreasury = 0;
$totalCollected = 0;
$totalVotes = 0;
$alreadyVoted = false;

try {
    $sql = "
        SELECT c.ID AS castle_id, c.name AS castle_name, c.tax_percent,
               c.treasury, c.collected_shops, cd.clan_id,
               cs.name AS clan_name, cs.leader_id, ch.char_name AS leader_name
        FROM castle c
        LEFT JOIN clan_data cd ON cd.hasCastle = c.ID
        LEFT JOIN clan_subpledges cs ON cs.clan_id = cd.clan_id
        LEFT JOIN characters ch ON ch.obj_Id = cs.leader_id
        ORDER BY c.ID ASC
    ";
    $castles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($castles as $castle) {
        $totalTreasury += (int) ($castle['treasury'] ?? 0);
        $totalCollected += (int) ($castle['collected_shops'] ?? 0);
    }
    if (!$setupError) {
        $totalVotes = (int) $pdo->query("SELECT COUNT(*) FROM vb_political_election_votes")->fetchColumn();
        if ($accountName !== '') {
            $voteCheck = $pdo->prepare("SELECT 1 FROM vb_political_election_votes WHERE account_name = ? LIMIT 1");
            $voteCheck->execute([$accountName]);
            $alreadyVoted = (bool) $voteCheck->fetchColumn();
        }
    }
} catch (Throwable $e) {
    if (!$setupError) {
        $setupError = 'Não foi possível ler os castelos ou os dados da eleição. Confirme se as tabelas do servidor estão no banco conectado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eleição Política — L2 Virtual Boost</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#05070b;--panel:#0b111b;--panel2:#0e1724;--line:rgba(117,215,255,.14);--blue:#4ca8ff;--cyan:#75d7ff;--purple:#8c65ff;--gold:#d5b46a;--text:#e7edf5;--muted:#8995a5;--green:#63d39a;--red:#ff7777}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(76,168,255,.12),transparent 32%),radial-gradient(circle at 0 80%,rgba(140,101,255,.09),transparent 28%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;min-height:100vh}
.wrap{max-width:1400px;margin:auto;padding:32px 24px 60px}.eyebrow{color:var(--cyan);font:700 11px Orbitron,sans-serif;letter-spacing:3px;text-transform:uppercase}
header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:28px}h1{font:700 clamp(25px,4vw,38px) Orbitron,sans-serif;letter-spacing:-1px;margin:10px 0;color:white}header p{color:var(--muted);margin:8px 0;line-height:1.6;max-width:750px}.live{border:1px solid var(--line);background:#0b111b;padding:12px 15px;border-radius:8px;color:var(--cyan);font:600 11px Orbitron,sans-serif;white-space:nowrap}
.hero{border:1px solid rgba(117,215,255,.2);border-radius:14px;padding:28px;background:linear-gradient(115deg,rgba(22,105,170,.22),rgba(86,52,184,.15) 55%,rgba(11,17,27,.9));margin-bottom:22px;position:relative;overflow:hidden}.hero:after{content:"";position:absolute;right:-40px;top:-90px;width:280px;height:280px;border:1px solid rgba(117,215,255,.13);border-radius:50%;box-shadow:0 0 0 30px rgba(117,215,255,.025),0 0 0 60px rgba(117,215,255,.02);pointer-events:none}.hero h2{font:700 18px Orbitron,sans-serif;margin:0 0 10px}.hero p{max-width:800px;color:#aabaca;line-height:1.7;margin:0}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:24px}.stat,.panel,.castle{background:linear-gradient(145deg,rgba(14,23,36,.98),rgba(8,13,21,.98));border:1px solid var(--line);border-radius:10px}.stat{padding:19px}.stat small{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px}.stat strong{font:700 clamp(18px,2.4vw,25px) Orbitron,sans-serif;color:var(--cyan);overflow-wrap:anywhere}.stat .gold{color:var(--gold)}
.section-head{display:flex;justify-content:space-between;align-items:end;gap:16px;margin:28px 0 15px}.section-head h2{font:700 18px Orbitron,sans-serif;margin:0}.section-head p{color:var(--muted);font-size:13px;margin:7px 0 0}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.castle{padding:20px;position:relative;overflow:hidden}.castle:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(var(--cyan),var(--purple))}.castle-top{display:flex;justify-content:space-between;gap:12px;align-items:center}.castle h3{font:700 15px Orbitron,sans-serif;margin:0;color:white}.id{font-size:10px;color:#64778a;margin-top:7px}.pill{display:inline-flex;padding:6px 9px;border-radius:5px;font-size:10px;font-weight:700;letter-spacing:.5px;background:rgba(99,211,154,.09);border:1px solid rgba(99,211,154,.18);color:var(--green);white-space:nowrap}.pill.empty{color:var(--muted);background:rgba(137,149,165,.07);border-color:var(--line)}.leader{margin:20px 0 15px;padding:14px;background:rgba(2,7,13,.55);border:1px solid rgba(117,215,255,.08);border-radius:7px}.leader small{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}.leader strong{font-size:17px;color:#fff}.clan{font-size:12px;color:var(--cyan);margin-top:5px}.metrics{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}.metric{padding:10px;background:rgba(255,255,255,.02);border-radius:6px}.metric small{display:block;color:var(--muted);font-size:10px;margin-bottom:5px}.metric b{font-size:13px;color:#dbe7f1}.metric b.gold{color:var(--gold)}.vote{width:100%;border:0;border-radius:6px;padding:12px 15px;background:linear-gradient(90deg,#1769aa,#5634b8);color:white;font:700 11px Orbitron,sans-serif;letter-spacing:.6px;cursor:pointer;transition:.2s}.vote:hover{filter:brightness(1.18);transform:translateY(-1px)}.vote:disabled{opacity:.35;cursor:not-allowed;transform:none}.notice{padding:14px 16px;border:1px solid var(--line);border-radius:8px;background:rgba(11,17,27,.8);color:#afbdcb;font-size:13px;line-height:1.6;margin-bottom:20px}.notice.success{border-color:rgba(99,211,154,.3);color:var(--green)}.notice.error{border-color:rgba(255,119,119,.3);color:var(--red)}.notice strong{color:white}.foot{color:#667789;font-size:12px;line-height:1.7;margin-top:28px;border-top:1px solid var(--line);padding-top:18px}.empty-state{padding:30px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:10px}
@media(max-width:1050px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.wrap{padding:22px 14px 40px}header{display:block}.live{display:inline-block;margin-top:12px}.grid,.stats{grid-template-columns:1fr}.hero{padding:22px}.section-head{display:block}}
</style>
</head>
<body>
<div class="wrap">
<header>
  <div><div class="eyebrow">Virtual Boost · Governança do Reino</div><h1>ELEIÇÃO POLÍTICA</h1><p>O poder de decidir o futuro das cidades está nas mãos dos jogadores. Conheça os líderes dos castelos, confira os recursos e escolha quem merece representar o reino.</p></div>
  <div class="live">● DADOS DO BANCO</div>
</header>

<section class="hero">
  <div class="eyebrow">Uma nova era começa</div>
  <h2>Seu voto. Seu reino. Seu legado.</h2>
  <p>Escolha um líder de castelo para apoiar nesta eleição de teste. A página consulta os castelos e os dados econômicos diretamente no banco atual do servidor. Cada conta pode registrar um único voto nesta eleição.</p>
</section>

<?php if ($setupError): ?><div class="notice error"><?= election_h($setupError) ?></div><?php endif; ?>
<?php if ($message !== ''): ?><div class="notice <?= election_h($messageType) ?>"><?= election_h($message) ?></div><?php endif; ?>

<section class="stats">
  <div class="stat"><small>Castelos catalogados</small><strong><?= count($castles) ?></strong></div>
  <div class="stat"><small>Votos registrados</small><strong><?= election_h($totalVotes) ?></strong></div>
  <div class="stat"><small>Tesouro dos castelos</small><strong class="gold"><?= election_h(election_adena($totalTreasury)) ?></strong></div>
  <div class="stat"><small>Arrecadação em lojas</small><strong class="gold"><?= election_h(election_adena($totalCollected)) ?></strong></div>
</section>

<?php if ($accountName === ''): ?>
<div class="notice"><strong>Login necessário para votar.</strong> A sessão de uma conta de jogador ainda não foi identificada neste projeto. Depois de conectar o login do site, a página aceitará a conta autenticada e bloqueará votos repetidos. Não use um campo de texto livre para identificar eleitores.</div>
<?php elseif ($alreadyVoted): ?>
<div class="notice success"><strong>Voto já registrado.</strong> Esta conta já participou da eleição. A regra de voto único é reforçada pelo banco de dados.</div>
<?php else: ?>
<div class="notice">Conta reconhecida pela sessão: <strong><?= election_h($accountName) ?></strong>. Escolha um dos líderes listados abaixo para registrar seu voto.</div>
<?php endif; ?>

<div class="section-head"><div><h2>Castelos e candidatos</h2><p>Líderes e recursos atuais, carregados do banco do servidor.</p></div></div>
<?php if (!$castles): ?>
<div class="empty-state">Nenhum castelo foi retornado pelo banco. Confira a conexão configurada em database.php.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($castles as $castle):
    $hasLeader = !empty($castle['leader_id']) && !empty($castle['leader_name']);
    $castleId = (int) $castle['castle_id'];
?>
<article class="castle">
  <div class="castle-top"><div><h3><?= election_h($castle['castle_name']) ?></h3><div class="id">CASTLE ID · <?= $castleId ?></div></div><span class="pill <?= $hasLeader ? '' : 'empty' ?>"><?= $hasLeader ? 'LÍDER ELEGÍVEL' : 'SEM LÍDER' ?></span></div>
  <div class="leader"><small>Líder do clã proprietário</small><strong><?= $hasLeader ? election_h($castle['leader_name']) : 'Nenhum líder encontrado' ?></strong><div class="clan"><?= !empty($castle['clan_name']) ? 'Clã: ' . election_h($castle['clan_name']) : 'Castelo sem clã proprietário identificado' ?></div></div>
  <div class="metrics">
    <div class="metric"><small>Imposto atual</small><b><?= election_h(rtrim(rtrim(number_format((float)($castle['tax_percent'] ?? 0), 2, ',', '.'), '0'), ',')) ?>%</b></div>
    <div class="metric"><small>Tesouro</small><b class="gold"><?= election_h(election_adena($castle['treasury'] ?? 0)) ?> adena</b></div>
    <div class="metric"><small>Arrecadação de lojas</small><b class="gold"><?= election_h(election_adena($castle['collected_shops'] ?? 0)) ?> adena</b></div>
    <div class="metric"><small>Recursos somados</small><b><?= election_h(election_adena((int)($castle['treasury'] ?? 0) + (int)($castle['collected_shops'] ?? 0))) ?> adena</b></div>
  </div>
  <form method="post" onsubmit="return confirm('Confirma seu voto para o líder <?= election_h($castle['leader_name'] ?? 'deste castelo') ?>? Não será possível votar novamente nesta eleição.');">
    <input type="hidden" name="csrf_token" value="<?= election_h($csrf) ?>">
    <input type="hidden" name="castle_id" value="<?= $castleId ?>">
    <button class="vote" type="submit" <?= (!$hasLeader || $accountName === '' || $alreadyVoted || $setupError) ? 'disabled' : '' ?>><?= $alreadyVoted ? 'VOTO JÁ REGISTRADO' : 'VOTAR NESTE LÍDER' ?></button>
  </form>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="foot">VERSÃO DE TESTE · Os dados de castelos e impostos são apenas exibidos para consulta. Esta página registra votos, mas não muda o proprietário do castelo, não altera taxas e não movimenta adena. Para abrir a eleição aos jogadores, é necessário integrar a sessão real de login do site; para conceder poderes políticos e transferir impostos, é necessário implementar e validar a integração com o servidor Java.</div>
</div>
</body>
</html>
