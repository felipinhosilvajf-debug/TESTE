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

// Tratamento de valores financeiros e status
$dinheiroMao = (int)($d['Dinheiro'] ?? $d['Dinheiro Mao'] ?? 0);
$dinheiroBanco = (int)($d['GranaBanco'] ?? $d['Dinheiro Banco'] ?? 0);
$coins = (int)($d['Coins'] ?? 0);
$skinId = (int)($d['Skin'] ?? 0);
$nivel = (int)($d['Level'] ?? 0);
$respeito = (int)($d['Respeito'] ?? 0);
$horas = (int)($d['HorasJogadas'] ?? 0);
$ultimoLogin = $d['LU'] ?? 'Desconhecido';

// Propriedades (255 geralmente significa "Nenhuma" em GMs GF/RPG)
$casa = ($d['ChaveCasa'] ?? 255) != 255 && ($d['ChaveCasa'] ?? 0) > 0 ? "Casa ID #" . $d['ChaveCasa'] : "Não possui";
$empresa = ($d['ChaveEmpresa'] ?? 255) != 255 && ($d['ChaveEmpresa'] ?? 0) > 0 ? "Empresa ID #" . $d['ChaveEmpresa'] : "Não possui";
$fazenda = ($d['ChaveFazenda'] ?? 255) != 255 && ($d['ChaveFazenda'] ?? 0) > 0 ? "Fazenda ID #" . $d['ChaveFazenda'] : "Não possui";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Painel de <?= htmlspecialchars($nick) ?></title>
  <script src="https://tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 p-4 md:p-8 font-sans">

  <div class="max-w-6xl mx-auto">
    <!-- Cabeçalho -->
    <div class="flex flex-wrap justify-between items-center bg-gray-800 p-6 rounded-xl border border-gray-700 mb-6 gap-4">
      <div class="flex items-center gap-4">
        <img src="https://open.mp<?= $skinId ?>.png" alt="Skin" class="w-16 h-16 rounded-full bg-gray-700 object-cover border-2 border-orange-500">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-white"><?= htmlspecialchars($nick) ?></h1>
          <p class="text-gray-400 text-sm">Nível <?= $nivel ?> (EXP: <?= $respeito ?>) • <?= $horas ?>h jogadas • Último login: <?= $ultimoLogin ?></p>
        </div>
      </div>
      <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-5 py-2.5 rounded-lg font-bold text-sm transition">Sair da Conta</a>
    </div>

    <!-- Cards Financeiros -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
        <p class="text-gray-400 text-sm">Dinheiro em Mãos</p>
        <p class="text-3xl font-bold text-green-400 mt-1">$ <?= number_format($dinheiroMao, 0, ',', '.') ?></p>
      </div>
      <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
        <p class="text-gray-400 text-sm">Saldo Bancário</p>
        <p class="text-3xl font-bold text-blue-400 mt-1">$ <?= number_format($dinheiroBanco, 0, ',', '.') ?></p>
      </div>
      <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
        <p class="text-gray-400 text-sm">Coins / VIP</p>
        <p class="text-3xl font-bold text-yellow-400 mt-1"><?= number_format($coins, 0, ',', '.') ?> Coins</p>
      </div>
    </div>

    <!-- Grid de Informações -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Licenças e Validades -->
      <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
        <h2 class="text-xl font-bold text-orange-400 mb-4">Habilitações e Licenças</h2>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between items-center border-b border-gray-700 pb-2">
            <span>Carteira de Carro (Terrestre)</span>
            <span class="<?= !empty($d['CarLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['CarLic']) ? 'Ativa (' . ($d['ValidadeCarro'] ?? 0) . ' dias)' : 'Não Possui' ?>
            </span>
          </div>
          <div class="flex justify-between items-center border-b border-gray-700 pb-2">
            <span>Carteira de Moto</span>
            <span class="<?= !empty($d['MotoLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['MotoLic']) ? 'Ativa (' . ($d['ValidadeMoto'] ?? 0) . ' dias)' : 'Não Possui' ?>
            </span>
          </div>
          <div class="flex justify-between items-center border-b border-gray-700 pb-2">
            <span>Caminhão / Ônibus</span>
            <span class="<?= !empty($d['CamOniLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['CamOniLic']) ? 'Ativa (' . ($d['ValidadeOnibus'] ?? 0) . ' dias)' : 'Não Possui' ?>
            </span>
          </div>
          <div class="flex justify-between items-center border-b border-gray-700 pb-2">
            <span>Licença de Voo (Aérea)</span>
            <span class="<?= !empty($d['FlyLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['FlyLic']) ? 'Ativa (' . ($d['ValidadeAviao'] ?? 0) . ' dias)' : 'Não Possui' ?>
            </span>
          </div>
          <div class="flex justify-between items-center border-b border-gray-700 pb-2">
            <span>Licença Náutica (Barcos)</span>
            <span class="<?= !empty($d['BoatLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['BoatLic']) ? 'Ativa (' . ($d['ValidadeBarco'] ?? 0) . ' dias)' : 'Não Possui' ?>
            </span>
          </div>
          <div class="flex justify-between items-center">
            <span>Porte de Armas</span>
            <span class="<?= !empty($d['GunLic']) ? 'text-green-400 font-bold' : 'text-red-400' ?>">
              <?= !empty($d['GunLic']) ? 'Possui' : 'Não Possui' ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Patrimônio e Status -->
      <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
        <h2 class="text-xl font-bold text-orange-400 mb-4">Patrimônio e Dados Gerais</h2>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-gray-400">Casa:</span>
            <span class="font-semibold"><?= $casa ?></span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-gray-400">Empresa:</span>
            <span class="font-semibold"><?= $empresa ?></span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-gray-400">Fazenda:</span>
            <span class="font-semibold"><?= $fazenda ?></span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-gray-400">Estado Civil:</span>
            <span class="font-semibold"><?= ($d['Married'] ?? 0) == 1 ? 'Casado com ' . ($d['MarriedTo'] ?? '') : 'Solteiro(a)' ?></span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-gray-400">Celular:</span>
            <span class="font-semibold"><?= ($d['Celular'] ?? 0) > 0 ? $d['Celular'] : 'Não possui' ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-400">Matou / Morreu:</span>
            <span class="font-semibold"><?= $d['Kills'] ?? 0 ?> / <?= $d['Deaths'] ?? 0 ?></span>
          </div>
        </div>
      </div>

    </div>
  </div>

</body>
</html>
