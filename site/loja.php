<?php
require __DIR__ . '/leitor.php';
if (!isset($_SESSION['jogador_nick'])) {
    header('Location: login.php');
    exit;
}
$nick = $_SESSION['jogador_nick'];
$dados = carregarConta($nick);
if ($dados === false) {
    session_destroy();
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['loja_csrf'])) $_SESSION['loja_csrf'] = bin2hex(random_bytes(32));
$pacotes = [
    5000 => 2.00,
    10000 => 4.00,
    15000 => 6.00,
    20000 => 8.00,
    30000 => 12.00,
];
function hLoja($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#07131d">
<title>Apoie o projeto · Virtual Boost RP</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 50% 0,rgba(30,180,205,.14),transparent 38%),#07131d;color:#edf8fb;font-family:Inter,Arial,sans-serif}.wrap{width:min(980px,92%);margin:auto;padding:42px 0 70px}.back{color:#8af4ff;text-decoration:none;font-size:11px;font-weight:800}.head{text-align:center;margin:35px 0}.tag{color:#e9c879;font-size:10px;font-weight:800;letter-spacing:2px}.head h1{font:900 clamp(42px,8vw,68px)/.95 'Barlow Condensed';margin:10px 0;text-transform:uppercase}.head p{color:#91aab4;font-size:13px;line-height:1.7}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.pack{padding:23px;border:1px solid rgba(112,207,222,.2);border-radius:9px;background:linear-gradient(145deg,#102936,#0b1d29);text-align:center}.pack strong{display:block;font:900 34px 'Barlow Condensed';color:#e9c879}.pack small{color:#9db5bd;font-size:10px;letter-spacing:1px}.price{font:800 25px 'Barlow Condensed';margin:15px 0}.pack button{width:100%;padding:13px;border:0;border-radius:5px;background:linear-gradient(100deg,#e9c879,#fff0b2);color:#241d0b;font-weight:900;cursor:pointer}.pack button:hover{filter:brightness(1.08)}.note{text-align:center;color:#78939e;font-size:10px;margin-top:24px}.pack form{margin:0}
</style>
</head>
<body><main class="wrap">
<a class="back" href="dashboard.php">← VOLTAR AO PAINEL</a>
<header class="head"><div class="tag">VIRTUAL BOOST ROLEPLAY</div><h1>APOIE O PROJETO</h1><p>Escolha seu pacote de Coins. Após a confirmação do pagamento,<br>as moedas ficarão agendadas para resgate dentro do servidor.</p></header>
<div class="grid">
<?php foreach ($pacotes as $coins => $valor): ?>
<article class="pack">
<strong><?= number_format($coins, 0, ',', '.') ?></strong><small>COINS</small>
<div class="price">R$ <?= number_format($valor, 2, ',', '.') ?></div>
<form method="post" action="criar_pagamento.php">
<input type="hidden" name="csrf" value="<?= hLoja($_SESSION['loja_csrf']) ?>">
<input type="hidden" name="pacote" value="<?= (int)$coins ?>">
<button type="submit">APOIAR AGORA ↗</button>
</form>
</article>
<?php endforeach; ?>
</div>
<p class="note">Pagamento processado em ambiente seguro do Mercado Pago. O resgate é feito no servidor.</p>
</main></body></html>
