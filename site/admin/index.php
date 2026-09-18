<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_admin();

$admin = current_admin();

/*
|--------------------------------------------------------------------------
| ONLINE
|--------------------------------------------------------------------------
*/

$onlinePlayers = 0;

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM characters
        WHERE online = 1
    ");

    $onlinePlayers = (int)($stmt->fetch()['total'] ?? 0);

} catch (Throwable $e) {
    $onlinePlayers = 0;
}


/*
|--------------------------------------------------------------------------
| TOTAL DE CONTAS
|--------------------------------------------------------------------------
*/

$totalAccounts = 0;

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM accounts
    ");

    $totalAccounts = (int)($stmt->fetch()['total'] ?? 0);

} catch (Throwable $e) {
    $totalAccounts = 0;
}


/*
|--------------------------------------------------------------------------
| ANTI-CHEAT
|--------------------------------------------------------------------------
*/

$antiCheatTotal = 0;
$antiCheatHigh = 0;
$antiCheatCritical = 0;

try {

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM anticheat_events
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");

    $antiCheatTotal = (int)($stmt->fetch()['total'] ?? 0);


    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM anticheat_events
        WHERE severity = 'HIGH'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");

    $antiCheatHigh = (int)($stmt->fetch()['total'] ?? 0);


    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM anticheat_events
        WHERE severity = 'CRITICAL'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");

    $antiCheatCritical = (int)($stmt->fetch()['total'] ?? 0);

} catch (Throwable $e) {

    $antiCheatTotal = 0;
    $antiCheatHigh = 0;
    $antiCheatCritical = 0;
}


/*
|--------------------------------------------------------------------------
| ÚLTIMOS EVENTOS
|--------------------------------------------------------------------------
*/

$recentEvents = [];

try {

    $stmt = $pdo->query("
        SELECT
            id,
            character_name,
            account_name,
            ip_address,
            event_type,
            severity,
            score,
            details,
            created_at
        FROM anticheat_events
        ORDER BY id DESC
        LIMIT 10
    ");

    $recentEvents = $stmt->fetchAll();

} catch (Throwable $e) {

    $recentEvents = [];
}

?>
<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    L2 Virtual Boost — Administração
</title>

<style>

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

    margin-left:240px;

    padding:30px;

    min-height:100vh;

}

.topbar {

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:30px;

}

.topbar h2 {

    margin:0;

    font-size:24px;

}

.admin-info {

    color:var(--muted);

    font-size:13px;

}

.admin-info strong {

    color:white;

}


/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

.cards {

    display:grid;

    grid-template-columns:
        repeat(4, minmax(0,1fr));

    gap:16px;

    margin-bottom:25px;

}

.card {

    background:
        linear-gradient(
            145deg,
            rgba(14,21,32,.95),
            rgba(8,11,18,.95)
        );

    border:1px solid var(--line);

    border-radius:9px;

    padding:22px;

    position:relative;

    overflow:hidden;

}

.card::after {

    content:"";

    position:absolute;

    width:100px;
    height:100px;

    right:-40px;
    top:-40px;

    background:rgba(76,168,255,.08);

    border-radius:50%;

}

.card-title {

    color:var(--muted);

    font-size:11px;

    letter-spacing:1px;

    text-transform:uppercase;

}

.card-value {

    font-size:31px;

    font-weight:bold;

    margin-top:10px;

}

.card-info {

    margin-top:8px;

    color:#5f6b7b;

    font-size:11px;

}

.green {
    color:var(--green);
}

.red {
    color:var(--red);
}

.blue {
    color:var(--cyan);
}

.gold {
    color:var(--gold);
}


/*
|--------------------------------------------------------------------------
| PANELS
|--------------------------------------------------------------------------
*/

.panel {

    background:
        rgba(11,16,25,.94);

    border:1px solid var(--line);

    border-radius:9px;

    overflow:hidden;

    margin-bottom:20px;

}

.panel-header {

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:18px 20px;

    border-bottom:1px solid var(--line);

}

.panel-header h3 {

    margin:0;

    font-size:14px;

    letter-spacing:.5px;

}

.panel-header a {

    color:var(--cyan);

    text-decoration:none;

    font-size:11px;

}

table {

    width:100%;

    border-collapse:collapse;

}

th {

    text-align:left;

    padding:12px 16px;

    color:#5f6b7b;

    font-size:10px;

    letter-spacing:1px;

    text-transform:uppercase;

    border-bottom:1px solid var(--line);

}

td {

    padding:14px 16px;

    border-bottom:1px solid rgba(130,170,220,.08);

    font-size:12px;

}

tr:last-child td {

    border-bottom:0;

}

.badge {

    display:inline-block;

    padding:4px 8px;

    border-radius:4px;

    font-size:9px;

    font-weight:bold;

    letter-spacing:.5px;

}

.badge-low {

    background:rgba(99,211,154,.1);

    color:var(--green);

}

.badge-medium {

    background:rgba(213,180,106,.1);

    color:var(--gold);

}

.badge-high {

    background:rgba(217,106,106,.12);

    color:#ff9b9b;

}

.badge-critical {

    background:rgba(217,106,106,.22);

    color:#ff6d6d;

}

.empty {

    padding:40px;

    text-align:center;

    color:#5f6b7b;

    font-size:13px;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media(max-width:1000px) {

    .cards {

        grid-template-columns:
            repeat(2,1fr);

    }

}

@media(max-width:700px) {

    .sidebar {

        position:relative;

        width:100%;

        height:auto;

        bottom:auto;

    }

    .logout {

        position:static;

        margin-top:20px;

    }

    .content {

        margin-left:0;

        padding:20px;

    }

    .cards {

        grid-template-columns:1fr;

    }

    .topbar {

        align-items:flex-start;

        gap:10px;

        flex-direction:column;

    }

    table {

        min-width:700px;

    }

    .panel {

        overflow-x:auto;

    }

}

/* =========================================================
   EFEITO DE PARTICULAS
========================================================= */
/* Container que cobre a tela toda fixo ao fundo */
.particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    pointer-events: none; /* Garante que os cliques passem direto pelas partículas */
    z-index: 1; /* Fica acima do fundo escuro, mas atrás do conteúdo principal */
}

/* Estilo individual de cada ponto/fagulha */
.particle {
    position: absolute;
    bottom: -20px;
    background: radial-gradient(circle, rgba(56,189,248,1) 0%, rgba(245,158,11,0.8) 100%);
    border-radius: 50%;
    animation: riseUp 6s linear infinite;
    opacity: 0;
}

/* Animação que faz o ponto subir e sumir gradualmente */
@keyframes riseUp {
    0% {
        transform: translateY(0) scale(0.8);
        opacity: 0;
    }
    20% {
        opacity: 0.8;
    }
    80% {
        opacity: 0.4;
    }
    100% {
        transform: translateY(-105vh) scale(1.2);
        opacity: 0;
    }
}

/* Distribuição aleatória de tamanho, posição horizontal e tempo de atraso */
.particle:nth-child(1) { left: 20%; width: 4px; height: 4px; animation-duration: 5s; animation-delay: 0s; }
.particle:nth-child(2) { left: 35%; width: 6px; height: 6px; animation-duration: 7s; animation-delay: 2s; }
.particle:nth-child(3) { left: 40%; width: 3px; height: 3px; animation-duration: 4s; animation-delay: 1s; }
.particle:nth-child(4) { left: 55%; width: 5px; height: 5px; animation-duration: 6s; animation-delay: 3s; }
.particle:nth-child(5) { left: 70%; width: 4px; height: 4px; animation-duration: 8s; animation-delay: 1.5s; }
.particle:nth-child(6) { left: 85%; width: 6px; height: 6px; animation-duration: 5.5s; animation-delay: 2.5s; }
.particle:nth-child(7) { left: 95%; width: 3px; height: 3px; animation-duration: 6.5s; animation-delay: 0.5s; }

</style>

</head>

<body>

<div class="particles-container">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
	<div class="particle"></div>
    <!-- Adicione quantas partículas quiser para densidade -->
</div>

<!-- =========================================================
     SIDEBAR
========================================================= -->

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


<!-- =========================================================
     CONTENT
========================================================= -->

<main class="content">


    <div class="topbar">

        <div>

            <h2>
                Dashboard
            </h2>

            <div
                style="
                    color:#5f6b7b;
                    font-size:11px;
                    margin-top:5px;
                "
            >
                Visão geral do servidor
            </div>

        </div>


        <div class="admin-info">

            Administrador:

            <strong>
                <?= htmlspecialchars(
                    $admin['username'] ?? 'Admin',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>

    </div>


    <!-- =====================================================
         CARDS
    ====================================================== -->

    <section class="cards">


        <div class="card">

            <div class="card-title">
                Players Online
            </div>

            <div class="card-value green">
                <?= number_format(
                    $onlinePlayers,
                    0,
                    ',',
                    '.'
                ) ?>
            </div>

            <div class="card-info">
                Personagens conectados
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Contas
            </div>

            <div class="card-value blue">
                <?= number_format(
                    $totalAccounts,
                    0,
                    ',',
                    '.'
                ) ?>
            </div>

            <div class="card-info">
                Contas cadastradas
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Anti-Cheat
            </div>

            <div class="card-value gold">
                <?= number_format(
                    $antiCheatTotal,
                    0,
                    ',',
                    '.'
                ) ?>
            </div>

            <div class="card-info">
                Eventos nas últimas 24 horas
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Alto risco
            </div>

            <div class="card-value red">
                <?= number_format(
                    $antiCheatHigh + $antiCheatCritical,
                    0,
                    ',',
                    '.'
                ) ?>
            </div>

            <div class="card-info">

                <?= $antiCheatCritical ?>
                críticos

            </div>

        </div>


    </section>


    <!-- =====================================================
         STATUS
    ====================================================== -->

    <div class="panel">

        <div class="panel-header">

            <h3>
                STATUS DO SERVIDOR
            </h3>

        </div>

        <table>

            <tbody>

                <tr>

                    <td>
                        LoginServer
                    </td>

                    <td>
                        <span class="badge badge-low">
                            ONLINE
                        </span>
                    </td>

                </tr>

                <tr>

                    <td>
                        GameServer
                    </td>

                    <td>
                        <span class="badge badge-low">
                            ONLINE
                        </span>
                    </td>

                </tr>

                <tr>

                    <td>
                        Banco de Dados
                    </td>

                    <td>
                        <span class="badge badge-low">
                            ONLINE
                        </span>
                    </td>

                </tr>

                <tr>

                    <td>
                        Anti-Cheat
                    </td>

                    <td>

                        <span class="badge badge-medium">
                            MONITORAMENTO
                        </span>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <!-- =====================================================
         ANTI-CHEAT EVENTS
    ====================================================== -->

    <div class="panel">

        <div class="panel-header">

            <h3>
                ÚLTIMOS EVENTOS ANTI-CHEAT
            </h3>

            <a href="anticheat.php">
                VER TODOS →
            </a>

        </div>


        <?php if (!$recentEvents): ?>

            <div class="empty">

                Nenhum evento Anti-Cheat registrado.

                <br><br>

                O sistema está pronto para começar o monitoramento.

            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Personagem
                        </th>

                        <th>
                            Evento
                        </th>

                        <th>
                            Severidade
                        </th>

                        <th>
                            Score
                        </th>

                        <th>
                            Data
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($recentEvents as $event): ?>

                    <?php

                    $severity =
                        strtoupper(
                            (string)$event['severity']
                        );

                    $badgeClass =
                        'badge-' .
                        strtolower($severity);

                    ?>

                    <tr>

                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    (string)(
                                        $event['character_name']
                                        ?: '-'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </strong>

                            <?php if (!empty($event['account_name'])): ?>

                                <div
                                    style="
                                        color:#5f6b7b;
                                        font-size:10px;
                                        margin-top:4px;
                                    "
                                >

                                    <?= htmlspecialchars(
                                        (string)$event['account_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                (string)$event['event_type'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <span
                                class="badge <?= $badgeClass ?>"
                            >

                                <?= htmlspecialchars(
                                    $severity,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <strong>

                                <?= (int)$event['score'] ?>

                            </strong>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                (string)$event['created_at'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>


</main>

</body>

</html>