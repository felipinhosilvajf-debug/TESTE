<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

/*
|--------------------------------------------------------------------------
| FUNÇÕES
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatAdena($value)
{
    return number_format((int) $value, 0, ',', '.');
}

function formatPercent($value)
{
    return rtrim(
        rtrim(number_format((float) $value, 2, ',', '.'), '0'),
        ','
    );
}

/*
|--------------------------------------------------------------------------
| CASTELOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.ID AS castle_id,
        c.name AS castle_name,
        c.tax_percent,
        c.treasury,
        c.collected_shops,

        cd.clan_id,

        cs.name AS clan_name,
        cs.leader_id,

        ch.char_name AS leader_name

    FROM castle c

    LEFT JOIN clan_data cd
        ON cd.hasCastle = c.ID

    LEFT JOIN clan_subpledges cs
        ON cs.clan_id = cd.clan_id

    LEFT JOIN characters ch
        ON ch.obj_Id = cs.leader_id

    ORDER BY c.ID ASC
";

try {

    $stmt = $pdo->query($sql);
    $castles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $castles = array();

    $databaseError = $e->getMessage();
}

/*
|--------------------------------------------------------------------------
| TOTAIS
|--------------------------------------------------------------------------
*/

$totalCastles = count($castles);

$totalTreasury = 0;
$totalCollectShops = 0;

foreach ($castles as $castle) {

    $totalTreasury += (int) $castle['treasury'];
    $totalCollectShops += (int) $castle['collected_shops'];
}

$totalCastleMoney = $totalTreasury + $totalCollectShops;

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

>

<title>Castelos — Administração</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

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
    margin-left: 230px;
    padding: 30px;
    overflow-x: auto;
}

.page-header {
    margin-bottom: 28px;
}

.page-header h1 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 25px;
    color: #ffffff;
}

.page-header p {
    margin: 7px 0 0;
    color: #65798d;
    font-size: 14px;
}

/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    padding: 20px;
    transition: .2s;
}

.card:hover {
    border-color: rgba(57,213,255,.20);
}

.card-label {
    color: #63778a;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.card-value {
    color: #39d5ff;
    font-family: 'Orbitron', sans-serif;
    font-size: 22px;
    font-weight: 700;
}

.card-sub {
    color: #536678;
    font-size: 12px;
    margin-top: 6px;
}

/*
|--------------------------------------------------------------------------
| PANEL
|--------------------------------------------------------------------------
*/

.panel {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    overflow: hidden;
}

.panel-header {
    padding: 20px 22px;
    border-bottom: 1px solid #182535;
}

.panel-header h2 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 15px;
    color: #ffffff;
}

.panel-header p {
    margin: 6px 0 0;
    color: #5f7285;
    font-size: 12px;
}

/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

.error-box {
    margin-bottom: 25px;
    background: rgba(255,70,70,.08);
    border: 1px solid rgba(255,70,70,.25);
    color: #ff7070;
    border-radius: 7px;
    padding: 15px 18px;
    font-size: 13px;
}

/*
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
*/

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

th {
    text-align: left;
    color: #5d7185;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 13px 16px;
    background: #080d15;
    border-bottom: 1px solid #182535;
}

td {
    padding: 15px 16px;
    border-bottom: 1px solid rgba(24,37,53,.65);
    font-size: 14px;
    vertical-align: middle;
    color: #b8c8d7;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover td {
    background: rgba(57,213,255,.025);
}

.castle-name {
    font-family: 'Orbitron', sans-serif;
    font-weight: 600;
    color: #ffffff;
    font-size: 13px;
}

.castle-id {
    color: #536678;
    font-size: 11px;
    margin-top: 5px;
}

.clan-name {
    color: #dce7f0;
    font-weight: 600;
}

.leader-name {
    color: #9fb0bf;
}

.muted {
    color: #536678;
    font-size: 12px;
}

.treasury {
    color: #39d5ff;
    font-weight: 700;
    white-space: nowrap;
}

.collect {
    color: #d5a93a;
    font-weight: 700;
    white-space: nowrap;
}

.total {
    color: #ffffff;
    font-weight: 700;
    white-space: nowrap;
}

.tax {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 5px;
    background: rgba(213,169,58,.09);
    border: 1px solid rgba(213,169,58,.15);
    color: #d5a93a;
    font-weight: 700;
    font-size: 12px;
}

.empty {
    padding: 50px 20px;
    text-align: center;
    color: #536678;
}

/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 1100px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        border-right: none;
        border-bottom: 1px solid rgba(57,213,255,.12);
    }

    .content {
        margin-left: 0;
        padding: 20px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<!--
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
-->

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
            href="index.php">
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

        <a href="castles.php"
		class="active">
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

<!--
|--------------------------------------------------------------------------
| CONTEÚDO
|--------------------------------------------------------------------------
-->

<main class="content">


<div class="page-header">

    <h1>
        Castelos
    </h1>

    <p>
        Monitoramento dos castelos, proprietários, taxas e valores armazenados.
    </p>

</div>


<?php if (isset($databaseError)): ?>

    <div class="error-box">

        <strong>
            Erro ao consultar os castelos:
        </strong>

        <br>

        <?= h($databaseError) ?>

    </div>

<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
-->

<section class="cards">

    <div class="card">

        <div class="card-label">
            Castelos
        </div>

        <div class="card-value">
            <?= number_format($totalCastles, 0, ',', '.') ?>
        </div>

        <div class="card-sub">
            Castelos cadastrados
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Tesouro total
        </div>

        <div class="card-value">
            <?= formatAdena($totalTreasury) ?>
        </div>

        <div class="card-sub">
            Adena nos cofres
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Collect Shops
        </div>

        <div class="card-value">
            <?= formatAdena($totalCollectShops) ?>
        </div>

        <div class="card-sub">
            Arrecadação das lojas
        </div>

    </div>


    <div class="card">

        <div class="card-label">
            Total controlado
        </div>

        <div class="card-value">
            <?= formatAdena($totalCastleMoney) ?>
        </div>

        <div class="card-sub">
            Treasury + Collect Shops
        </div>

    </div>

</section>


<!--
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
-->

<section class="panel">

    <div class="panel-header">

        <h2>
            Controle dos Castelos
        </h2>

        <p>
            Informações atuais registradas na tabela castle.
        </p>

    </div>


    <?php if (empty($castles)): ?>

        <div class="empty">
            Nenhum castelo encontrado.
        </div>

    <?php else: ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Castelo
                        </th>

                        <th>
                            Clã
                        </th>

                        <th>
                            Líder
                        </th>

                        <th>
                            Taxa
                        </th>

                        <th>
                            Treasury
                        </th>

                        <th>
                            Collect Shops
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($castles as $castle): ?>

                    <?php

                    $treasury = (int) $castle['treasury'];

                    $collectShops = (int) $castle['collected_shops'];

                    $total = $treasury + $collectShops;

                    ?>

                    <tr>

                        <!-- CASTELO -->

                        <td>

                            <div class="castle-name">
                                <?= h($castle['castle_name']) ?>
                            </div>

                            <div class="castle-id">
                                ID <?= (int) $castle['castle_id'] ?>
                            </div>

                        </td>


                        <!-- CLÃ -->

                        <td>

                            <?php if (!empty($castle['clan_name'])): ?>

                                <div class="clan-name">
                                    <?= h($castle['clan_name']) ?>
                                </div>

                                <?php if (!empty($castle['clan_id'])): ?>

                                    <div class="muted">
                                        Clan ID:
                                        <?= (int) $castle['clan_id'] ?>
                                    </div>

                                <?php endif; ?>

                            <?php else: ?>

                                <span class="muted">
                                    Sem proprietário
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- LÍDER -->

                        <td>

                            <?php if (!empty($castle['leader_name'])): ?>

                                <div class="leader-name">
                                    <?= h($castle['leader_name']) ?>
                                </div>

                            <?php elseif (!empty($castle['leader_id'])): ?>

                                <div class="muted">
                                    Leader ID:
                                    <?= (int) $castle['leader_id'] ?>
                                </div>

                            <?php else: ?>

                                <span class="muted">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- TAXA -->

                        <td>

                            <span class="tax">
                                <?= formatPercent($castle['tax_percent']) ?>%
                            </span>

                        </td>


                        <!-- TESOURO -->

                        <td>

                            <span class="treasury">
                                <?= formatAdena($treasury) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>


                        <!-- COLLECT SHOPS -->

                        <td>

                            <span class="collect">
                                <?= formatAdena($collectShops) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>


                        <!-- TOTAL -->

                        <td>

                            <span class="total">
                                <?= formatAdena($total) ?>
                            </span>

                            <div class="muted">
                                Adena
                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


</main>

</body>

</html>
