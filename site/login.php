<?php
require 'leitor.php';

if (isset($_SESSION['jogador_nick'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nick = trim($_POST['nick']);
    $senha = trim($_POST['senha']);

    $dados = carregarConta($nick);

    if ($dados !== false) {
        // Verifica a chave 'Senha' do seu arquivo
        if (isset($dados['Senha']) && $dados['Senha'] === $senha) {
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
  <title>Login - Área do Jogador</title>
  <script src="https://tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen p-4">
  <form method="POST" class="bg-gray-800 p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-700">
    <h2 class="text-2xl font-bold text-orange-500 mb-2 text-center">Painel do Jogador</h2>
    <p class="text-gray-400 text-sm text-center mb-6">Entre com seus dados do servidor</p>
    
    <?php if($erro): ?>
      <div class="bg-red-500/20 border border-red-500 text-red-300 p-3 rounded mb-4 text-sm text-center">
        <?= $erro ?>
      </div>
    <?php endif; ?>

    <label class="block text-sm text-gray-300 mb-1">Nick no Jogo</label>
    <input type="text" name="nick" placeholder="Ex: Carl_Johnson" required class="w-full p-3 rounded bg-gray-700 border border-gray-600 mb-4 focus:outline-none focus:border-orange-500">

    <label class="block text-sm text-gray-300 mb-1">Senha</label>
    <input type="password" name="senha" placeholder="Sua senha" required class="w-full p-3 rounded bg-gray-700 border border-gray-600 mb-6 focus:outline-none focus:border-orange-500">

    <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 py-3 rounded font-bold transition">Acessar Conta</button>
    <a href="../index.html" class="block text-center text-gray-400 hover:text-white text-sm mt-4">← Voltar ao site</a>
  </form>
</body>
</html>
