<?php
require 'leitor.php';

if (!isset($_SESSION['jogador_nick'])) {
    header("Location: login.php");
    exit;
}

$nick = $_SESSION['jogador_nick'];
$d = carregarConta($nick);

if (!$d) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$dinheiroMao = (int)($d['Dinheiro'] ?? $d['Dinheiro Mao'] ?? 0);
$dinheiroBanco = (int)($d['GranaBanco'] ?? $d['Dinheiro Banco'] ?? 0);
$coins = (int)($d['Coins'] ?? 0);
$nivel = (int)($d['Level'] ?? 0);
$respeito = (int)($d['Respeito'] ?? 0);
$horas = (int)($d['HorasJogadas'] ?? 0);

$ultimoLogin = $d['LU'] ?? 'Desconhecido';

// Exibe o aviso enquanto houver Coins aguardando resgate no arquivo do jogador.
$moedasAguardando = 0;
try {
    require_once __DIR__ . '/mp_lib.php';
    $mpCfgDashboard = mpConfig();
    $nickSeguroDashboard = mpSafeNick($nick);
    $arquivoAgendado = rtrim($mpCfgDashboard['coins_dir'], '\\\\/') . DIRECTORY_SEPARATOR . $nickSeguroDashboard . '.ini';
    if (is_file($arquivoAgendado)) {
        $linhasAgendadas = file($arquivoAgendado, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($linhasAgendadas as $linhaAgendada) {
            if (strpos($linhaAgendada, '=') !== false) {
                [$chaveAgendada, $valorAgendado] = explode('=', $linhaAgendada, 2);
                if (trim($chaveAgendada) === 'Coins') $moedasAguardando = max(0, (int)trim($valorAgendado));
            }
        }
    }
} catch (Throwable $e) {
    error_log('[VirtualBoost Dashboard] ' . $e->getMessage());
}

// Skin atual salva na conta (ID SA-MP).
$skinId = isset($d['Skin']) ? (int)$d['Skin'] : (isset($d['pSkin']) ? (int)$d['pSkin'] : (isset($d['SkinID']) ? (int)$d['SkinID'] : null));
$skinImagem = null;
if ($skinId !== null && $skinId >= 0 && $skinId <= 311 && $skinId !== 74) {
    // O SA-MP Wiki organiza as imagens usando o hash MD5 do nome do arquivo.
    $skinArquivo = 'Skin_' . $skinId . '.png';
    $skinHash = md5($skinArquivo);
    $skinImagem = 'https://sampwiki.blast.hk/wroot/images2/' . substr($skinHash, 0, 1) . '/' . substr($skinHash, 0, 2) . '/' . $skinArquivo;
}

$casa = ($d['ChaveCasa'] ?? 255) != 255 && ($d['ChaveCasa'] ?? 0) > 0 ? "Casa ID #" . $d['ChaveCasa'] : "Não possui";
$empresa = ($d['ChaveEmpresa'] ?? 255) != 255 && ($d['ChaveEmpresa'] ?? 0) > 0 ? "Empresa ID #" . $d['ChaveEmpresa'] : "Não possui";
$fazenda = ($d['ChaveFazenda'] ?? 255) != 255 && ($d['ChaveFazenda'] ?? 0) > 0 ? "Fazenda ID #" . $d['ChaveFazenda'] : "Não possui";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function statusLicenca($ativa, $validade = null) {
    if (!$ativa) return '<span class="status off">NÃO POSSUI</span>';
    $texto = $validade !== null ? 'ATIVA · ' . (int)$validade . ' DIAS' : 'ATIVA';
    return '<span class="status on">' . h($texto) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#07131d">
<title>Documento do cidadão · <?= h($nick) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#07131d;--panel:#0d202c;--panel2:#102936;--cyan:#28d9ef;--cyan2:#8af4ff;--text:#edf8fb;--muted:#8faab5;--line:rgba(112,207,222,.17);--green:#70e0b0;--red:#ff8585;--gold:#e9c879}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 15% 0%,rgba(28,169,190,.13),transparent 35%),var(--bg);color:var(--text);font-family:Inter,Arial,sans-serif;min-height:100vh}
a{color:inherit;text-decoration:none}.shell{width:min(1180px,92%);margin:auto}.topbar{border-bottom:1px solid var(--line);background:rgba(5,17,25,.88);backdrop-filter:blur(14px)}.topinner{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{font:900 25px "Barlow Condensed",sans-serif;letter-spacing:1.4px}.brand span{color:var(--cyan)}.topmeta{font-size:10px;letter-spacing:1.8px;color:var(--muted);text-transform:uppercase}.logout{padding:11px 16px;border:1px solid rgba(255,133,133,.35);border-radius:5px;color:#ffaaaa;font-size:11px;font-weight:800;letter-spacing:.8px}.logout:hover{background:rgba(255,133,133,.1)}
main{padding:38px 0 70px}.eyebrow,.section-kicker{font-size:10px;font-weight:800;letter-spacing:2px;color:var(--cyan);text-transform:uppercase}.pagehead{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:22px}.pagehead h1{font:800 clamp(34px,5vw,52px)/.95 "Barlow Condensed",sans-serif;text-transform:uppercase;margin:8px 0 0}.pagehead p{color:var(--muted);font-size:12px;margin:8px 0 0}
.id-card{position:relative;overflow:hidden;display:grid;grid-template-columns:160px 1fr 190px;gap:24px;align-items:center;padding:25px;background:linear-gradient(115deg,rgba(15,53,67,.98),rgba(10,29,41,.98));border:1px solid rgba(40,217,239,.42);border-radius:9px;box-shadow:0 18px 55px rgba(0,0,0,.22);margin-bottom:22px}.id-card:after{content:"BR";position:absolute;right:175px;top:-50px;font:900 230px "Barlow Condensed";color:rgba(138,244,255,.035);pointer-events:none}.portrait{height:145px;border:1px solid rgba(138,244,255,.5);border-radius:5px;background:linear-gradient(145deg,#1c5261,#0b2634);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}.portrait img{display:block;width:100%;height:100%;object-fit:contain;object-position:center bottom}.portrait .skin-missing{padding:10px;color:#a3cbd3;font:700 9px Inter;letter-spacing:1px;text-align:center}.id-data{position:relative;z-index:1}.id-label{font-size:9px;color:#9ab9c2;letter-spacing:1.7px;text-transform:uppercase}.id-name{font:800 clamp(29px,4vw,42px)/1 "Barlow Condensed";margin:7px 0 17px;text-transform:uppercase;overflow-wrap:anywhere}.id-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.field small{display:block;color:#8daeb8;font-size:8px;letter-spacing:1.4px;text-transform:uppercase;margin-bottom:5px}.field strong{font-size:12px}.id-stamp{position:relative;z-index:1;border-left:1px solid var(--line);padding-left:22px}.stamp{border:1px solid rgba(138,244,255,.4);color:var(--cyan2);padding:13px 10px;text-align:center;font:800 13px "Barlow Condensed";letter-spacing:1.5px;transform:rotate(-4deg)}.stamp small{display:block;font:700 8px Inter;letter-spacing:1.4px;margin-top:5px;color:#a1c6cf}
.payment-notice{position:relative;overflow:hidden;margin:0 0 22px;padding:21px 24px;border:1px solid rgba(233,200,121,.72);border-radius:8px;background:linear-gradient(115deg,rgba(83,61,20,.92),rgba(35,31,22,.96));box-shadow:0 12px 38px rgba(0,0,0,.22),inset 0 0 28px rgba(233,200,121,.07)}.payment-notice:after{content:'✓';position:absolute;right:22px;top:50%;transform:translateY(-55%);font:900 68px 'Barlow Condensed';color:rgba(255,220,140,.12)}.payment-notice strong{display:block;color:#ffe3a0;font:800 25px 'Barlow Condensed';letter-spacing:.5px;text-transform:uppercase}.payment-notice p{position:relative;z-index:1;margin:7px 0 0;color:#f2dfb7;font-size:12px;line-height:1.7}.payment-notice b{color:#fff0c8}.payment-notice .pending-coins{color:#ffe3a0;font-weight:900}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.stat{padding:20px;background:linear-gradient(145deg,var(--panel2),var(--panel));border:1px solid var(--line);border-radius:7px}.stat small{display:block;color:var(--muted);font-size:9px;letter-spacing:1.5px;text-transform:uppercase}.stat strong{display:block;font:800 31px "Barlow Condensed";margin-top:7px}.stat.money strong{color:var(--green)}.stat.bank strong{color:var(--cyan2)}.stat.coins strong{color:var(--gold)}
.content-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.panel{background:linear-gradient(145deg,rgba(15,35,47,.98),rgba(10,26,37,.98));border:1px solid var(--line);border-radius:7px;padding:23px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding-bottom:14px;margin-bottom:8px}.panel h2{font:800 23px "Barlow Condensed";text-transform:uppercase;margin:0}.panel-head span{font-size:8px;color:var(--cyan);letter-spacing:1.4px}.data-row{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:13px 0;border-bottom:1px solid rgba(112,207,222,.09);font-size:11px}.data-row:last-child{border:0}.data-row .label{color:#a9c0c7}.data-row strong{text-align:right;font-size:11px;overflow-wrap:anywhere}.status{font-size:9px;font-weight:800;letter-spacing:.7px}.status.on{color:var(--green)}.status.off{color:var(--red)}.footer-note{margin-top:24px;text-align:center;color:#617f8a;font-size:9px;letter-spacing:1px}
@media(max-width:760px){.id-card{grid-template-columns:105px 1fr;gap:16px;padding:17px}.portrait{height:125px;font-size:43px}.id-stamp{grid-column:1/-1;border-left:0;border-top:1px solid var(--line);padding:14px 0 0}.stamp{max-width:210px;margin:auto}.id-fields{grid-template-columns:1fr 1fr}.content-grid{grid-template-columns:1fr}.pagehead{align-items:flex-start;flex-direction:column}}
@media(max-width:480px){.stats{grid-template-columns:1fr;gap:9px}.stat{padding:15px}.stat strong{font-size:26px}.topmeta{display:none}.id-card{grid-template-columns:1fr}.portrait{width:112px}.id-fields{grid-template-columns:1fr 1fr}.panel{padding:17px}.data-row{font-size:10px}}
</style>
<link rel="stylesheet" href="visual.css?v=1">
</head>
<body>
<div class="vb-background" aria-hidden="true"><video autoplay muted loop playsinline preload="metadata" poster="/img/back.png"><source src="/img/video.mp4" type="video/mp4"></video></div>
<canvas id="vbParticles" aria-hidden="true"></canvas>

<header class="topbar"><div class="shell topinner">
  <a class="brand" href="../index.html">BRASIL <span>VIRTUAL BOOST</span></a>
  <div class="topmeta">Central do cidadão · Área autenticada</div>
  <a href="loja.php" style="padding:11px 15px;border:1px solid rgba(233,200,121,.55);border-radius:5px;color:#e9c879;font-size:10px;font-weight:900;letter-spacing:.7px">APOIAR O PROJETO ✦</a>
  <a class="logout" href="logout.php">SAIR DA CONTA ↗</a>
</div></header>
<main class="shell">
  <div class="pagehead"><div><div class="eyebrow">REGISTRO MUNICIPAL · ACESSO RESTRITO</div><h1>Ficha do cidadão</h1><p>Consulte seus dados, habilitações e patrimônio na cidade.</p></div></div>

  <section class="id-card" aria-label="Identificação do jogador">
    <div class="portrait"><?php if ($skinImagem !== null): ?><img src="<?= h($skinImagem) ?>" alt="Personagem do jogador — skin <?= h($skinId) ?>" loading="lazy"><?php else: ?><span class="skin-missing">SKIN NÃO REGISTRADA</span><?php endif; ?></div>
    <div class="id-data">
      <div class="id-label">Nome de identificação / cidadão</div>
      <div class="id-name"><?= h($nick) ?></div>
      <div class="id-fields">
        <div class="field"><small>Registro</small><strong>#<?= h($nivel) ?>-<?= h($respeito) ?></strong></div>
        <div class="field"><small>Horas registradas</small><strong><?= h($horas) ?> horas</strong></div>
        <div class="field"><small>Último acesso</small><strong><?= h($ultimoLogin) ?></strong></div>
      </div>
    </div>
    <div class="id-stamp"><div class="stamp">VIRTUAL BOOST RP<small>DOCUMENTO DIGITAL · SAMP</small></div></div>
  </section>

  <?php if ($moedasAguardando > 0): ?>
  <section class="payment-notice" role="status" aria-live="polite">
    <strong>✓ Pagamento confirmado!</strong>
    <p>Suas <span class="pending-coins"><?= number_format($moedasAguardando, 0, ',', '.') ?> Coins</span> estão aguardando seu resgate. Entre no servidor e use <b>/menuvip</b> → <b>Receber Moedas</b>.</p>
  </section>
  <?php endif; ?>

  <section class="stats" aria-label="Resumo financeiro">
    <article class="stat money"><small>Dinheiro em mãos</small><strong>R$ <?= number_format($dinheiroMao, 0, ',', '.') ?></strong></article>
    <article class="stat bank"><small>Saldo bancário</small><strong>R$ <?= number_format($dinheiroBanco, 0, ',', '.') ?></strong></article>
    <article class="stat coins"><small>Moedas / VIP</small><strong><?= number_format($coins, 0, ',', '.') ?> <span style="font:700 11px Inter">COINS</span></strong></article>
  </section>

  <div class="content-grid">
    <section class="panel">
      <div class="panel-head"><h2>Habilitações & licenças</h2><span>DETRAN · REGISTRO</span></div>
      <div class="data-row"><span class="label">CNH · Automóvel</span><?= statusLicenca(!empty($d['CarLic']), $d['ValidadeCarro'] ?? 0) ?></div>
      <div class="data-row"><span class="label">CNH · Motocicleta</span><?= statusLicenca(!empty($d['MotoLic']), $d['ValidadeMoto'] ?? 0) ?></div>
      <div class="data-row"><span class="label">Categoria · Caminhão / ônibus</span><?= statusLicenca(!empty($d['CamOniLic']), $d['ValidadeOnibus'] ?? 0) ?></div>
      <div class="data-row"><span class="label">Licença · Aeronaves</span><?= statusLicenca(!empty($d['FlyLic']), $d['ValidadeAviao'] ?? 0) ?></div>
      <div class="data-row"><span class="label">Licença · Embarcações</span><?= statusLicenca(!empty($d['BoatLic']), $d['ValidadeBarco'] ?? 0) ?></div>
      <div class="data-row"><span class="label">Porte de arma</span><?= statusLicenca(!empty($d['GunLic'])) ?></div>
    </section>

    <section class="panel">
      <div class="panel-head"><h2>Patrimônio & dados civis</h2><span>CADASTRO GERAL</span></div>
      <div class="data-row"><span class="label">Residência</span><strong><?= h($casa) ?></strong></div>
      <div class="data-row"><span class="label">Empresa</span><strong><?= h($empresa) ?></strong></div>
      <div class="data-row"><span class="label">Propriedade rural</span><strong><?= h($fazenda) ?></strong></div>
      <div class="data-row"><span class="label">Estado civil</span><strong><?= ($d['Married'] ?? 0) == 1 ? 'Casado com ' . h($d['MarriedTo'] ?? '') : 'Solteiro(a)' ?></strong></div>
      <div class="data-row"><span class="label">Telefone celular</span><strong><?= ($d['Celular'] ?? 0) > 0 ? h($d['Celular']) : 'Não possui' ?></strong></div>
      <div class="data-row"><span class="label">Abates / mortes</span><strong><?= h($d['Kills'] ?? 0) ?> / <?= h($d['Deaths'] ?? 0) ?></strong></div>
    </section>
  </div>
  <div class="footer-note">BRASIL VIDA REAL · SISTEMA DE IDENTIFICAÇÃO DO CIDADÃO</div>
</main>
<script src="visual.js?v=1" defer></script>
</body>
</html>