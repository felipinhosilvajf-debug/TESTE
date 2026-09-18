<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$character = trim((string) ($_GET['character'] ?? ''));
$account   = trim((string) ($_GET['account'] ?? ''));
$status    = trim((string) ($_GET['status'] ?? ''));

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatNumber(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function formatDate(?int $timestamp): string
{
    if (!$timestamp) {
        return 'Nunca';
    }

    return date('d/m/Y H:i', $timestamp);
}

function statusClass(int $online): string
{
    return $online === 1 ? 'online' : 'offline';
}

function statusText(int $online): string
{
    return $online === 1 ? 'ONLINE' : 'OFFLINE';
}

function buildPageUrl(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;

    return '?' . http_build_query($query);
}


/*
|--------------------------------------------------------------------------
| FILTROS SQL
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($character !== '') {
    $where[] = 'c.char_name LIKE ?';
    $params[] = '%' . $character . '%';
}

if ($account !== '') {
    $where[] = 'c.account_name LIKE ?';
    $params[] = '%' . $account . '%';
}

if ($status === 'online') {
    $where[] = 'c.online = 1';
} elseif ($status === 'offline') {
    $where[] = 'c.online = 0';
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$totalPlayers = 0;
$totalOnline = 0;
$totalStaff = 0;

try {

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_players,

            COALESCE(
                SUM(
                    CASE
                        WHEN online = 1
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS total_online,

            COALESCE(
                SUM(
                    CASE
                        WHEN accesslevel > 0
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS total_staff

        FROM characters
    ");

    $row = $stmt->fetch();

    if ($row) {
        $totalPlayers = (int) $row['total_players'];
        $totalOnline = (int) $row['total_online'];
        $totalStaff = (int) $row['total_staff'];
    }

} catch (Throwable $e) {
}


/*
|--------------------------------------------------------------------------
| CONTAGEM
|--------------------------------------------------------------------------
*/

$totalHistory = 0;

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM characters c
        $whereSql
    ");

    $stmt->execute($params);

    $totalHistory = (int) $stmt->fetchColumn();

} catch (Throwable $e) {
}

$totalPages = max(
    1,
    (int) ceil($totalHistory / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}


/*
|--------------------------------------------------------------------------
| JOGADORES
|--------------------------------------------------------------------------
*/

$players = [];

try {

    $stmt = $pdo->prepare("
        SELECT

            c.obj_Id,
            c.char_name,
            c.account_name,
            c.title,
            c.accesslevel,
            c.online,
            c.onlinetime,
            c.lastAccess,
            c.karma,
            c.pvpkills,
            c.pkkills,

            COALESCE(
                (
                    SELECT SUM(i.count)
                    FROM items i
                    WHERE i.owner_id = c.obj_Id
                      AND i.item_id = 57
                      AND i.count > 0
                      AND i.loc = 'INVENTORY'
                ),
                0
            ) AS adena

        FROM characters c

        $whereSql

        ORDER BY
            c.online DESC,
            c.char_name ASC

        LIMIT $perPage OFFSET $offset
    ");

    $stmt->execute($params);

    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $players = [];
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

<title>Jogadores | Virtual Boost</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap"
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
    width: calc(100% - 230px);
    padding: 25px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.page-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 22px;
    color: #f2f7fc;
}

.page-subtitle {
    color: #71849a;
    font-size: 13px;
    margin-top: 5px;
}

.live {
    border: 1px solid #193a4c;
    background: #0b1721;
    color: #4ddaff;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}

.card {
    background: #0c131d;
    border: 1px solid #172535;
    border-radius: 9px;
    padding: 16px;
}

.card-label {
    color: #708398;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.card-value {
    margin-top: 7px;
    font-family: 'Orbitron', sans-serif;
    font-size: 21px;
    color: #edf5ff;
}

.cyan {
    color: #38d5ff !important;
}

.gold {
    color: #e4bd51 !important;
}

.green {
    color: #56d68a !important;
}


/*
|--------------------------------------------------------------------------
| PANEL
|--------------------------------------------------------------------------
*/

.panel {
    background: #0c131d;
    border: 1px solid #172535;
    border-radius: 9px;
    overflow: hidden;
    margin-bottom: 18px;
}

.panel-header {
    padding: 13px 15px;
    border-bottom: 1px solid #172535;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 13px;
    color: #dceafa;
}

.panel-info {
    color: #667b91;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

.filters {
    padding: 13px 15px;
    border-bottom: 1px solid #172535;
    display: grid;
    grid-template-columns: 1.5fr 1.5fr 1fr auto;
    gap: 8px;
}

input,
select {
    width: 100%;
    background: #080e16;
    border: 1px solid #1b2b3c;
    color: #d9e5f1;
    border-radius: 6px;
    padding: 8px 9px;
    outline: none;
    font-family: 'Rajdhani', sans-serif;
}

button {
    border: 1px solid #1d647d;
    background: #0d2835;
    color: #4ed9ff;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 10px 12px;
    color: #61768c;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .6px;
    background: #091019;
    border-bottom: 1px solid #172535;
    white-space: nowrap;
}

td {
    padding: 10px 12px;
    border-bottom: 1px solid #111d29;
    font-size: 13px;
    white-space: nowrap;
}

tr:hover td {
    background: #0e1823;
}

.char {
    color: #e1ebf5;
    font-weight: 700;
    font-size: 14px;
}

.account {
    color: #64788e;
    font-size: 11px;
    margin-top: 2px;
}

.title {
    color: #8093a7;
    font-size: 11px;
    margin-top: 2px;
}

.status {
    display: inline-block;
    padding: 3px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}

.status.online {
    background: #0c2c20;
    color: #57dc91;
    border: 1px solid #14553b;
}

.status.offline {
    background: #171f29;
    color: #71849a;
    border: 1px solid #263442;
}

.staff {
    display: inline-block;
    padding: 3px 7px;
    border-radius: 4px;
    background: #30280e;
    color: #e2c35d;
    border: 1px solid #5b4a17;
    font-size: 10px;
    font-weight: 700;
}

.player {
    color: #65798e;
    font-size: 11px;
}

.adena {
    color: #e4bd51;
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
}

.stat {
    color: #b6c4d2;
}

.last-access {
    color: #8a9caf;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

.empty {
    text-align: center;
    padding: 35px;
    color: #60758b;
}


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    padding: 15px;
}

.pagination a,
.pagination span {
    min-width: 32px;
    text-align: center;
    padding: 6px 9px;
    border-radius: 5px;
    border: 1px solid #1a2938;
    color: #8093a7;
    font-size: 12px;
}

.pagination .current {
    background: #123345;
    border-color: #1d617a;
    color: #43d6ff;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 1100px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .filters {
        grid-template-columns: 1fr 1fr;
    }

}

@media (max-width: 700px) {

    .sidebar {
        width: 65px;
    }

    .content {
        margin-left: 65px;
        width: calc(100% - 65px);
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .filters {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="layout">


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
        >
            Dashboard
        </a>

        <a href="players.php"
		class="active">
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


<main class="content">


<div class="page-header">

<div>

    <div class="page-title">
        JOGADORES
    </div>

    <div class="page-subtitle">
        Consulta e monitoramento dos personagens do servidor.
    </div>

</div>

<div class="live">
    ● DADOS DO SERVIDOR
</div>

</div>


<!-- CARDS -->

<section class="cards">


<div class="card">

    <div class="card-label">
        Personagens
    </div>

    <div class="card-value cyan">
        <?= formatNumber($totalPlayers) ?>
    </div>

</div>


<div class="card">

    <div class="card-label">
        Jogadores Online
    </div>

    <div class="card-value green">
        <?= formatNumber($totalOnline) ?>
    </div>

</div>


<div class="card">

    <div class="card-label">
        Staff
    </div>

    <div class="card-value gold">
        <?= formatNumber($totalStaff) ?>
    </div>

</div>


</section>


<!-- JOGADORES -->

<section class="panel">


<div class="panel-header">

    <div class="panel-title">
        LISTA DE JOGADORES
    </div>

    <div class="panel-info">
        <?= formatNumber($totalHistory) ?> registros
    </div>

</div>


<!-- FILTROS -->

<form method="GET" class="filters">


<input
    type="text"
    name="character"
    placeholder="Personagem"
    value="<?= h($character) ?>"
>


<input
    type="text"
    name="account"
    placeholder="Conta"
    value="<?= h($account) ?>"
>


<select name="status">

    <option value="">
        Todos os jogadores
    </option>

    <option
        value="online"
        <?= $status === 'online' ? 'selected' : '' ?>
    >
        Somente Online
    </option>

    <option
        value="offline"
        <?= $status === 'offline' ? 'selected' : '' ?>
    >
        Somente Offline
    </option>

</select>


<button type="submit">
    FILTRAR
</button>


</form>


<div class="table-wrap">

<table>

<thead>

<tr>

    <th>
        Personagem
    </th>

    <th>
        Status
    </th>

    <th>
        Staff
    </th>

    <th>
        PvP
    </th>

    <th>
        PK
    </th>

    <th>
        Karma
    </th>

    <th>
        Adena
    </th>

    <th>
        Último acesso
    </th>

</tr>

</thead>


<tbody>


<?php if (empty($players)): ?>

<tr>

    <td
        colspan="8"
        class="empty"
    >
        Nenhum jogador encontrado.
    </td>

</tr>


<?php else: ?>


<?php foreach ($players as $player): ?>


<tr>


<td>

    <div class="char">
        <?= h($player['char_name']) ?>
    </div>

    <div class="account">
        <?= h($player['account_name']) ?>
    </div>

    <?php if (!empty($player['title'])): ?>

        <div class="title">
            <?= h($player['title']) ?>
        </div>

    <?php endif; ?>

</td>


<td>

    <span class="status <?= statusClass((int) $player['online']) ?>">

        <?= statusText((int) $player['online']) ?>

    </span>

</td>


<td>

    <?php if ((int) $player['accesslevel'] > 0): ?>

        <span class="staff">
            STAFF
        </span>

    <?php else: ?>

        <span class="player">
            JOGADOR
        </span>

    <?php endif; ?>

</td>


<td class="stat">

    <?= formatNumber((int) $player['pvpkills']) ?>

</td>


<td class="stat">

    <?= formatNumber((int) $player['pkkills']) ?>

</td>


<td class="stat">

    <?= formatNumber((int) $player['karma']) ?>

</td>


<td class="adena">

    <?= formatNumber((int) $player['adena']) ?>

</td>


<td class="last-access">

    <?= h(
        formatDate(
            (int) $player['lastAccess']
        )
    ) ?>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>


<!-- PAGINAÇÃO -->

<?php if ($totalPages > 1): ?>

<div class="pagination">


<?php if ($page > 1): ?>

<a href="<?= h(buildPageUrl($page - 1)) ?>">
    ‹
</a>

<?php endif; ?>


<?php

$startPage = max(1, $page - 2);
$endPage = min($totalPages, $page + 2);

for (
    $i = $startPage;
    $i <= $endPage;
    $i++
):

?>


<?php if ($i === $page): ?>

<span class="current">
    <?= $i ?>
</span>

<?php else: ?>

<a href="<?= h(buildPageUrl($i)) ?>">
    <?= $i ?>
</a>

<?php endif; ?>


<?php endfor; ?>


<?php if ($page < $totalPages): ?>

<a href="<?= h(buildPageUrl($page + 1)) ?>">
    ›
</a>

<?php endif; ?>


</div>

<?php endif; ?>


</section>


</main>

</div>

</body>

</html>