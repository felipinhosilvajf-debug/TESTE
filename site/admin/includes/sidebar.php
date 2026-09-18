<?php
declare(strict_types=1);

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

function sidebarActive(string $page): string
{
    global $currentPage;

    return $currentPage === $page ? 'active' : '';
}
?>

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
            href="index.php"
            class="active"
        >
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

        <a href="castles.php">
            Castles
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