<?php
require 'leitor.php';

if (isset($_SESSION['jogador_nick'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nick = trim($_POST['nick'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    $dados = carregarConta($nick);

    if ($dados !== false) {
        if (isset($dados['Senha']) && $dados['Senha'] === $senha) {
            session_regenerate_id(true);
            $_SESSION['jogador_nick'] = $nick;
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Conta não encontrada no servidor.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#07131d">
<title>Identificação do cidadão · Brasil Vida Real</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#07131d;--panel:#0d202c;--cyan:#28d9ef;--cyan2:#8af4ff;--text:#edf8fb;--muted:#91aab4;--line:rgba(112,207,222,.2);--red:#ff8d8d}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 50% 0%,rgba(28,169,190,.18),transparent 42%),linear-gradient(145deg,#07131d,#091c29 65%,#061019);font-family:Inter,Arial,sans-serif;color:var(--text)}
a{color:inherit;text-decoration:none}.login-wrap{width:min(440px,100%)}.brand{display:block;text-align:center;font:900 30px "Barlow Condensed";letter-spacing:1.8px;margin-bottom:24px}.brand span{color:var(--cyan)}.card{position:relative;overflow:hidden;padding:34px;background:linear-gradient(145deg,rgba(15,42,55,.98),rgba(9,27,39,.98));border:1px solid rgba(40,217,239,.36);border-radius:9px;box-shadow:0 25px 80px rgba(0,0,0,.38)}.card:before{content:"";position:absolute;left:0;right:0;top:0;height:3px;background:linear-gradient(90deg,transparent,var(--cyan),transparent)}.kicker{text-align:center;color:var(--cyan);font-size:9px;font-weight:800;letter-spacing:2.2px;text-transform:uppercase}.symbol{width:58px;height:58px;border:1px solid rgba(138,244,255,.45);border-radius:50%;display:grid;place-items:center;margin:0 auto 16px;color:var(--cyan2);font:900 25px "Barlow Condensed";background:rgba(40,217,239,.07)}h1{text-align:center;font:800 36px "Barlow Condensed";text-transform:uppercase;margin:8px 0 5px}.subtitle{text-align:center;color:var(--muted);font-size:11px;line-height:1.7;margin:0 0 26px}.notice{padding:12px 13px;border:1px solid rgba(255,141,141,.35);background:rgba(255,100,100,.08);color:#ffb1b1;border-radius:5px;font-size:11px;text-align:center;margin-bottom:18px}label{display:block;color:#b8d0d7;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin:17px 0 8px}input{width:100%;padding:14px 15px;border-radius:5px;border:1px solid var(--line);background:#071923;color:var(--text);font:500 13px Inter;outline:none;transition:.2s}input::placeholder{color:#587581}input:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(40,217,239,.1)}button{width:100%;margin-top:24px;padding:15px;border:0;border-radius:5px;background:var(--cyan);color:#06202a;font:900 12px Inter;letter-spacing:1px;cursor:pointer;transition:.2s}button:hover{background:var(--cyan2);transform:translateY(-1px)}.back{display:block;text-align:center;color:#8ba7b1;font-size:10px;margin-top:22px}.back:hover{color:var(--cyan2)}.foot{text-align:center;color:#55717c;font-size:8px;letter-spacing:1.5px;margin-top:19px}
@media(max-width:480px){.card{padding:26px 21px}.brand{font-size:26px}}
</style>
<link rel="stylesheet" href="visual.css?v=1">
</head>
<body>
<div class="vb-background" aria-hidden="true"><video autoplay muted loop playsinline preload="metadata" poster="/img/back.png"><source src="/img/video.mp4" type="video/mp4"></video></div>
<canvas id="vbParticles" aria-hidden="true"></canvas>

<div class="login-wrap">
  <a class="brand" href="../index.html">VIRTUAL BOOST <span>RolePlay [BR]</span></a>
  <main class="card">
    <div class="symbol">BR</div>
    <div class="kicker">Portal oficial · acesso seguro</div>
    <h1>Identificação do cidadão</h1>
    <p class="subtitle">Entre com os mesmos dados da sua conta dentro do servidor SAMP.</p>

    <?php if ($erro): ?>
      <div class="notice" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on">
      <label for="nick">Nick no jogo</label>
      <input id="nick" type="text" name="nick" placeholder="Ex.: Carl_Johnson" maxlength="24" autocomplete="username" required>
      <label for="senha">Senha da conta</label>
      <input id="senha" type="password" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
      <button type="submit">ACESSAR MINHA CONTA ↗</button>
    </form>
    <a class="back" href="../index.html">← VOLTAR AO SITE PRINCIPAL</a>
  </main>
  <div class="foot">BRASIL VIRTUAL BOOST · SISTEMA DE IDENTIFICAÇÃO DIGITAL</div>
</div>
<script src="visual.js?v=1" defer></script>
</body>
</html>