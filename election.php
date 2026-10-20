<?php
declare(strict_types=1);

/* Eleição pública do Virtual Boost. Usa a sessão criada por process_autg.php. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$accountName = isset($_SESSION['user_login']) && is_string($_SESSION['user_login']) ? trim($_SESSION['user_login']) : '';
require_once __DIR__ . '/admin/includes/database.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco da eleição.');
}
function election_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function election_num($v): string { return number_format((int)$v, 0, ',', '.'); }
if (empty($_SESSION['vb_election_csrf']) || !is_string($_SESSION['vb_election_csrf'])) $_SESSION['vb_election_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['vb_election_csrf'];
$error = '';
$notice = '';
$election = null;
$castles = [];
$votes = [];
$totalVotes = 0;
$alreadyVoted = false;
try {
    $activeStmt = $pdo->query("SELECT * FROM vb_political_elections WHERE status IN ('open','paused') ORDER BY id DESC LIMIT 1");
    $election = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($election && $election['status'] === 'open' && strtotime($election['ends_at']) <= time()) {
        $winnerStmt = $pdo->prepare("SELECT candidate_leader_id, candidate_name FROM vb_political_election_votes WHERE election_id=? GROUP BY candidate_leader_id,candidate_name ORDER BY COUNT(*) DESC,candidate_name ASC LIMIT 1");
        $winnerStmt->execute([(int)$election['id']]);
        $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $finish = $pdo->prepare("UPDATE vb_political_elections SET status='finished',winner_leader_id=?,winner_name=?,forced=0 WHERE id=? AND status='open' AND ends_at<=NOW()");
        $finish->execute([$winner['candidate_leader_id'] ?? null, $winner['candidate_name'] ?? null, (int)$election['id']]);
        $election = null;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if ($accountName === '') {
            $error = 'Entre na sua conta pelo site para votar.';
        } elseif (!hash_equals($csrf, $postedToken)) {
            $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
        } elseif (!$election || $election['status'] !== 'open') {
            $error = 'Não há uma eleição aberta para receber votos neste momento.';
        } else {
            $castleId = filter_input(INPUT_POST, 'castle_id', FILTER_VALIDATE_INT);
            if (!$castleId || $castleId < 1) {
                $error = 'Selecione um candidato válido.';
            } else {
                $candidateStmt = $pdo->prepare("SELECT c.ID AS castle_id, c.name AS castle_name, cs.leader_id, ch.char_name AS leader_name
                    FROM castle c
                    INNER JOIN clan_data cd ON cd.hasCastle=c.ID
                    INNER JOIN clan_subpledges cs ON cs.clan_id=cd.clan_id
                    INNER JOIN characters ch ON ch.obj_Id=cs.leader_id
                    WHERE c.ID=? LIMIT 1");
                $candidateStmt->execute([$castleId]);
                $candidate = $candidateStmt->fetch(PDO::FETCH_ASSOC);
                if (!$candidate || empty($candidate['leader_id']) || empty($candidate['leader_name'])) {
                    $error = 'Esse castelo não possui um líder elegível.';
                } else {
                    try {
                        $vote = $pdo->prepare("INSERT INTO vb_political_election_votes (election_id,account_name,castle_id,candidate_leader_id,candidate_name) VALUES (?,?,?,?,?)");
                        $vote->execute([(int)$election['id'], $accountName, (int)$candidate['castle_id'], (int)$candidate['leader_id'], (string)$candidate['leader_name']]);
                        $notice = 'Seu voto foi registrado com sucesso.';
                    } catch (PDOException $e) {
                        if ((string)$e->getCode() === '23000') $error = 'Sua conta já votou nesta eleição.';
                        else throw $e;
                    }
                }
            }
        }
    }
    $sql = "SELECT c.ID AS castle_id,c.name AS castle_name,c.tax_percent,c.treasury,c.collected_shops,cd.clan_id,cs.name AS clan_name,cs.leader_id,ch.char_name AS leader_name
        FROM castle c
        LEFT JOIN clan_data cd ON cd.hasCastle=c.ID
        LEFT JOIN clan_subpledges cs ON cs.clan_id=cd.clan_id
        LEFT JOIN characters ch ON ch.obj_Id=cs.leader_id
        ORDER BY c.ID ASC";
    $castles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if ($election) {
        $voteCountStmt = $pdo->prepare("SELECT candidate_leader_id,MAX(candidate_name) AS candidate_name,COUNT(*) AS votes FROM vb_political_election_votes WHERE election_id=? GROUP BY candidate_leader_id ORDER BY votes DESC,candidate_name ASC");
        $voteCountStmt->execute([(int)$election['id']]);
        $votes = $voteCountStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalVotes = array_sum(array_map(static fn($v) => (int)$v['votes'], $votes));
        if ($accountName !== '') {
            $check = $pdo->prepare("SELECT 1 FROM vb_political_election_votes WHERE election_id=? AND account_name=? LIMIT 1");
            $check->execute([(int)$election['id'], $accountName]);
            $alreadyVoted = (bool)$check->fetchColumn();
        }
    } else {
        $last = $pdo->query("SELECT * FROM vb_political_elections ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $election = $last;
            $voteCountStmt = $pdo->prepare("SELECT candidate_leader_id,MAX(candidate_name) AS candidate_name,COUNT(*) AS votes FROM vb_political_election_votes WHERE election_id=? GROUP BY candidate_leader_id ORDER BY votes DESC,candidate_name ASC");
            $voteCountStmt->execute([(int)$election['id']]);
            $votes = $voteCountStmt->fetchAll(PDO::FETCH_ASSOC);
            $totalVotes = array_sum(array_map(static fn($v) => (int)$v['votes'], $votes));
        }
    }
} catch (Throwable $e) {
    $error = $error ?: 'Não foi possível carregar a eleição. Confira as permissões e a estrutura do banco.';
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
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(76,168,255,.12),transparent 32%),radial-gradient(circle at 0 80%,rgba(140,101,255,.09),transparent 28%),var(--bg);color:var(--text);font:16px Rajdhani,Arial,sans-serif;min-height:100vh}.wrap{max-width:1380px;margin:auto;padding:32px 22px 60px}.eyebrow{color:var(--cyan);font:700 11px Orbitron,sans-serif;letter-spacing:3px}h1{font:700 clamp(26px,4vw,38px) Orbitron,sans-serif;margin:12px 0;color:#fff}h2{font:700 17px Orbitron,sans-serif;margin:0 0 10px}p{line-height:1.65;color:var(--muted)}.hero,.panel,.stat,.castle{background:linear-gradient(145deg,rgba(14,23,36,.98),rgba(8,13,21,.98));border:1px solid var(--line);border-radius:10px}.hero{padding:26px;margin:22px 0}.hero p{max-width:800px;margin-bottom:0}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-bottom:22px}.stat{padding:18px}.stat small{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}.stat strong{font:700 20px Orbitron,sans-serif;color:var(--cyan)}.panel{padding:20px;margin-bottom:22px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.castle{padding:18px}.castle h3{font:700 14px Orbitron,sans-serif;margin:0 0 15px}.leader{background:#080d15;border:1px solid rgba(117,215,255,.1);border-radius:6px;padding:13px;margin-bottom:12px}.leader small{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;margin-bottom:5px}.leader strong{font-size:17px;color:#fff}.metric{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid rgba(117,215,255,.07);font-size:13px}.metric span{color:var(--muted)}.metric b{color:#dce7f0;text-align:right}.vote{width:100%;margin-top:14px;padding:12px;border:0;border-radius:5px;background:linear-gradient(90deg,#1769aa,#5634b8);color:white;font:700 11px Orbitron,sans-serif;cursor:pointer}.vote:disabled{opacity:.35;cursor:not-allowed}.notice{padding:13px 15px;margin:15px 0;border-radius:7px;border:1px solid var(--line);background:#080d15;color:#b7c6d8;line-height:1.6}.notice.error{color:var(--red);border-color:rgba(255,119,119,.25)}.notice.success{color:var(--green);border-color:rgba(99,211,154,.25)}.leaderboard{display:grid;gap:8px;margin-top:12px}.score{display:flex;justify-content:space-between;gap:12px;padding:11px 12px;background:#080d15;border-radius:5px}.score span{color:#c6d5e5}.score b{color:var(--cyan)}.foot{border-top:1px solid var(--line);padding-top:18px;margin-top:26px;color:#65778b;font-size:12px;line-height:1.7}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.wrap{padding:20px 13px}.grid,.stats{grid-template-columns:1fr}}
</style></head><body><main class="wrap">
<div class="eyebrow">VIRTUAL BOOST · GOVERNANÇA DO REINO</div><h1>ELEIÇÃO POLÍTICA</h1>
<p>O reino escolhe seus representantes. Os dados dos castelos são consultados no banco atual; cada conta autenticada pode votar uma vez por eleição.</p>
<section class="hero"><div class="eyebrow">UMA NOVA ERA COMEÇA</div><h2 style="margin-top:10px;">Seu voto. Seu reino. Seu legado.</h2>
<?php if ($isOpen): ?><p>Eleição aberta para votação. O prazo termina em <strong style="color:#fff;"><?= election_h($election['ends_at']) ?></strong>. O tempo restante é atualizado ao recarregar a página.</p>
<?php elseif ($isPaused): ?><p style="color:#d5b46a;">A eleição está pausada pelo administrador. Nenhum voto será aceito até ser retomada.</p>
<?php elseif ($election && $election['status']==='finished'): ?><p>Eleição encerrada. Vencedor registrado: <strong style="color:#75d7ff;"><?= election_h($election['winner_name'] ?: 'Sem vencedor — nenhum voto') ?></strong>. A alteração do dono do castelo será feita manualmente.</p>
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
<?php if ($accountName===''): ?><div class="notice"><strong style="color:#fff;">Login necessário.</strong> Entre na sua conta do Virtual Boost para votar. A identificação é feita automaticamente pela sessão do site.</div>
<?php elseif ($isOpen && $alreadyVoted): ?><div class="notice success">Sua conta já registrou um voto nesta eleição. Obrigado por participar!</div>
<?php elseif ($isOpen): ?><div class="notice">Conta autenticada: <strong style="color:#fff;"><?= election_h($accountName) ?></strong>. Escolha um dos líderes abaixo para votar.</div>
<?php endif; ?>
<div style="display:flex;justify-content:space-between;gap:15px;align-items:end;flex-wrap:wrap;margin:28px 0 14px;"><div><h2>Castelos e candidatos</h2><p style="margin:6px 0 0;">O candidato listado é o líder atual do clã que possui o castelo.</p></div></div>
<?php if (!$castles): ?><div class="notice">Nenhum castelo foi retornado pelo banco.</div><?php else: ?><section class="grid">
<?php foreach ($castles as $castle): $hasLeader=!empty($castle['leader_id'])&&!empty($castle['leader_name']); ?>
<article class="castle"><h3><?= election_h($castle['castle_name']) ?></h3>
<div class="leader"><small>Candidato / líder atual</small><strong><?= election_h($hasLeader?$castle['leader_name']:'Sem líder elegível') ?></strong><?php if (!empty($castle['clan_name'])): ?><div style="color:#75d7ff;font-size:12px;margin-top:5px;"><?= election_h($castle['clan_name']) ?></div><?php endif; ?></div>
<div class="metric"><span>Imposto</span><b><?= election_h($castle['tax_percent'] ?? 0) ?>%</b></div><div class="metric"><span>Tesouro</span><b><?= election_num($castle['treasury'] ?? 0) ?> adena</b></div><div class="metric"><span>Arrecadação lojas</span><b><?= election_num($castle['collected_shops'] ?? 0) ?> adena</b></div>
<form method="post"><input type="hidden" name="csrf_token" value="<?= election_h($csrf) ?>"><input type="hidden" name="castle_id" value="<?= (int)$castle['castle_id'] ?>"><button class="vote" type="submit" <?= (!$isOpen||$accountName===''||$alreadyVoted||!$hasLeader)?'disabled':'' ?>>VOTAR NESTE LÍDER</button></form>
</article><?php endforeach; ?></section><?php endif; ?>
<section class="panel" style="margin-top:22px;"><h2>Apuração dos votos</h2><?php if (!$votes): ?><p>Nenhum voto registrado para esta eleição.</p><?php else: ?><div class="leaderboard"><?php foreach ($votes as $v): ?><div class="score"><span><?= election_h($v['candidate_name']) ?></span><b><?= (int)$v['votes'] ?> voto(s)</b></div><?php endforeach; ?></div><?php endif; ?></section>
<div class="foot">A eleição registra votos e o resultado para consulta administrativa. Ela não transfere adena, não altera impostos e não troca automaticamente o dono do castelo. A definição final do proprietário continuará manual no banco/servidor, conforme configurado pelo administrador.</div>
</main>
<?php if ($isOpen): ?><script>
(function(){var left=<?= (int)$secondsLeft ?>;var el=document.getElementById('countdown');function tick(){if(left<=0){el.textContent='00:00:00';window.setTimeout(function(){window.location.reload();},1200);return;}var h=Math.floor(left/3600),m=Math.floor((left%3600)/60),s=left%60;el.textContent=[h,m,s].map(function(n){return String(n).padStart(2,'0')}).join(':');left--;if(left>=0)window.setTimeout(tick,1000);}tick();})();
</script><?php endif; ?>
</body></html>