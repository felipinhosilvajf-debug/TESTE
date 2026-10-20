<?php
declare(strict_types=1);
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
function sidebarActive(string $page): string {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<aside class="sidebar">
  <div class="logo"><h1>L2 VIRTUAL BOOST</h1><span>ADMINISTRATION</span></div>
  <div class="menu">
    <div class="menu-title">PRINCIPAL</div>
    <a href="index.php" class="<?= sidebarActive('index.php') ?>">Dashboard</a>
    <a href="players.php" class="<?= sidebarActive('players.php') ?>">Players</a>
    <a href="donates.php" class="<?= sidebarActive('donates.php') ?>">Donates</a>
    <div class="menu-title">SEGURANÇA</div>
    <a href="anticheat.php" class="<?= sidebarActive('anticheat.php') ?>">Anti-Cheat</a>
    <a href="punishments.php" class="<?= sidebarActive('punishments.php') ?>">Punishments</a>
    <div class="menu-title">SERVIDOR</div>
    <a href="economy.php" class="<?= sidebarActive('economy.php') ?>">Economy</a>
    <a href="castles.php" class="<?= sidebarActive('castles.php') ?>">Castles</a>
    <a href="castles.php#election-control" class="<?= sidebarActive('castles.php') ?>">Eleição Política</a>
    <a href="../election.php">Página pública da eleição</a>
    <a href="logs.php" class="<?= sidebarActive('logs.php') ?>">Logs</a>
    <div class="menu-title">SISTEMA</div>
    <a href="administrators.php" class="<?= sidebarActive('administrators.php') ?>">Administrators</a>
    <a href="settings.php" class="<?= sidebarActive('settings.php') ?>">Settings</a>
  </div>
  <div class="logout"><a href="logout.php">SAIR DO PAINEL</a></div>
</aside>
