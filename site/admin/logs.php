<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

$admin = current_admin();

/*
|-------------------------------------------------------------------------- 
| CONFIGURAÇÃO
|--------------------------------------------------------------------------
|
| O Mythras já possui auditoria nativa de itens:
|   logs       -> uma ação de movimentação
|   logs_items -> itens recebidos/perdidos nessa ação
|
| Trade concluído gera duas ações (uma por personagem). Para a visão
| administrativa abaixo usamos somente os itens perdidos (lost = 1),
| evitando duplicação.
|
*/

$tradeTableExists = true;
$tradeLogs = [];
$totalTrades = 0;
$totalPages = 1;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$character = trim((string)($_GET['character'] ?? ''));
$item = trim((string)($_GET['item'] ?? ''));
$tradeType = 'TRADE';
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatNumberValue($value): string
{
    return number_format((int)$value, 0, ',', '.');
}

function pageUrl(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;

    return '?' . http_build_query($query);
}

function tradeDate(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d/m/Y H:i:s', $timestamp);
}

/*
|--------------------------------------------------------------------------
| CATÁLOGO REAL DE ITENS
|--------------------------------------------------------------------------
|
| O log nativo grava apenas o item_template_id. O nome vem das tabelas
| reais de itens do servidor. Detectamos as tabelas existentes para que
| o painel continue funcionando mesmo sem tabelas custom_*.
|
*/

$itemNames = [];
$itemNameSources = [];

try {
    $catalogTables = [
        'custom_weapon',
        'custom_armor',
        'custom_etcitem',
        'weapon',
        'armor',
        'etcitem'
    ];

    $placeholders = implode(',', array_fill(0, count($catalogTables), '?'));
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ($placeholders)
    ");
    $stmt->execute($catalogTables);

    $existingTables = [];
    while ($table = $stmt->fetchColumn()) {
        $existingTables[] = strtolower((string)$table);
    }

    foreach ($catalogTables as $table) {
        if (in_array(strtolower($table), $existingTables, true)) {
            $itemNameSources[] = $table;
        }
    }

    foreach ($itemNameSources as $table) {
        $stmt = $pdo->query("
            SELECT item_id, name
            FROM `$table`
            WHERE name IS NOT NULL
              AND name <> ''
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $itemId = (int)$row['item_id'];

            /*
             * custom_* vem antes das tabelas padrão, então um item custom
             * com o mesmo ID mantém o nome personalizado.
             */
            if (!isset($itemNames[$itemId])) {
                $itemNames[$itemId] = (string)$row['name'];
            }
        }
    }
} catch (Throwable $e) {
    /*
     * O painel continua mostrando Item #ID caso o catálogo não possa
     * ser carregado. O log nativo não é afetado.
     */
}

$itemNameSql = '';

if ($itemNameSources) {
    $catalogQueries = [];

    foreach ($itemNameSources as $table) {
        $catalogQueries[] = "
            SELECT item_id, name
            FROM `$table`
            WHERE name IS NOT NULL
              AND name <> ''
        ";
    }

    $itemNameSql = implode(" UNION ALL ", $catalogQueries);
}

/*
|--------------------------------------------------------------------------
| CONSULTA DOS LOGS NATIVOS DO MYTHRAS
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

$where[] = "l.action_type = 'TRADE'";
$where[] = "li.lost = 1";
$where[] = "li.receiver_name <> ''";

if ($character !== '') {
    $where[] = "(
        c.char_name LIKE ?
        OR li.receiver_name LIKE ?
    )";
    $params[] = '%' . $character . '%';
    $params[] = '%' . $character . '%';
}

if ($item !== '') {
    if (ctype_digit($item)) {
        $where[] = "li.item_template_id = ?";
        $params[] = (int)$item;
    } elseif ($itemNameSql !== '') {
        /*
         * Busca pelo nome real do item + ID.
         */
        $where[] = "(
            li.item_template_id IN (
                SELECT catalog.item_id
                FROM ($itemNameSql) AS catalog
                WHERE catalog.name LIKE ?
            )
            OR CAST(li.item_template_id AS CHAR) LIKE ?
        )";
        $params[] = '%' . $item . '%';
        $params[] = '%' . $item . '%';
    } else {
        $where[] = "CAST(li.item_template_id AS CHAR) LIKE ?";
        $params[] = '%' . $item . '%';
    }
}

if ($dateFrom !== '') {
    $where[] = "FROM_UNIXTIME(l.time / 1000) >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $where[] = "FROM_UNIXTIME(l.time / 1000) <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$totalTrades = 0;
$totalPages = 1;

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM logs l
        INNER JOIN logs_items li ON li.log_id = l.log_id
        LEFT JOIN characters c ON c.obj_Id = l.player_object_id
        $whereSql
    ");
    $stmt->execute($params);
    $totalTrades = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $totalTrades = 0;
}

$totalPages = max(1, (int)ceil($totalTrades / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            l.log_id,
            l.player_object_id AS from_char_id,
            COALESCE(c.char_name, CONCAT('#', l.player_object_id)) AS from_char_name,
            li.receiver_name AS to_char_name,
            li.item_template_id AS item_id,
            li.item_count AS amount,
            li.item_enchant_level AS enchant,
            li.item_object_id AS item_object_id,
            FROM_UNIXTIME(l.time / 1000) AS created_at,
            l.action_type AS trade_type
        FROM logs l
        INNER JOIN logs_items li ON li.log_id = l.log_id
        LEFT JOIN characters c ON c.obj_Id = l.player_object_id
        $whereSql
        ORDER BY l.time DESC, l.log_id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $tradeLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tradeLogs = [];
}

/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$stats = [
    'total' => 0,
    'today' => 0,
    'adena' => 0,
    'items' => 0
];

try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE
                WHEN FROM_UNIXTIME(l.time / 1000) >= CURDATE()
                THEN 1 ELSE 0 END), 0) AS today,
            COALESCE(SUM(CASE
                WHEN li.item_template_id = 57
                THEN li.item_count ELSE 0 END), 0) AS adena,
            COALESCE(SUM(CASE
                WHEN li.item_template_id <> 57
                THEN li.item_count ELSE 0 END), 0) AS items
        FROM logs l
        INNER JOIN logs_items li ON li.log_id = l.log_id
        WHERE l.action_type = 'TRADE'
          AND li.lost = 1
          AND li.receiver_name <> ''
    ");
    $row = $stmt->fetch();

    if ($row) {
        $stats['total'] = (int)($row['total'] ?? 0);
        $stats['today'] = (int)($row['today'] ?? 0);
        $stats['adena'] = (int)($row['adena'] ?? 0);
        $stats['items'] = (int)($row['items'] ?? 0);
    }
} catch (Throwable $e) {
    // Mantém os valores zerados.
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
    Logs — L2 Virtual Boost
</title>

<style>

:root {
    --black:#05070b;
    --panel:#0b1019;
    --panel2:#0e1520;
    --blue:#4ca8ff;
    --cyan:#75d7ff;
    --purple:#8c65ff;
    --gold:#d5b46a;
    --text:#e7edf5;
    --muted:#8995a5;
    --muted2:#5f6b7b;
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
    color:var(--muted2);
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

    border:
        1px solid
        rgba(217,106,106,.2);

    color:var(--red);
    text-decoration:none;
    border-radius:6px;
    font-size:12px;
}

.content {
    margin-left:240px;
    padding:30px;
    min-height:100vh;
}

.topbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
}

.topbar h2 {
    margin:0;
    font-size:24px;
}

.subtitle {
    color:var(--muted2);
    font-size:11px;
    margin-top:6px;
}

.admin-info {
    color:var(--muted);
    font-size:12px;
}

.admin-info strong {
    color:white;
}

.stats {
    display:grid;
    grid-template-columns:repeat(4, minmax(0,1fr));
    gap:12px;
    margin-bottom:20px;
}

.stat {
    background:
        linear-gradient(
            145deg,
            rgba(14,21,32,.96),
            rgba(8,11,18,.96)
        );

    border:1px solid var(--line);
    border-radius:8px;
    padding:18px;
}

.stat-title {
    color:var(--muted);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.stat-value {
    font-size:27px;
    font-weight:bold;
    margin-top:8px;
}

.cyan {
    color:var(--cyan);
}

.green {
    color:var(--green);
}

.gold {
    color:var(--gold);
}

.blue {
    color:#8cbbff;
}

.panel {
    background:rgba(11,16,25,.94);
    border:1px solid var(--line);
    border-radius:9px;
    overflow:hidden;
    margin-bottom:20px;
}

.panel-header {
    padding:18px 20px;
    border-bottom:1px solid var(--line);
}

.panel-header h3 {
    margin:0;
    font-size:14px;
}

.filters {
    padding:18px;
    display:grid;
    grid-template-columns:
        1.4fr
        1.4fr
        1fr
        1fr
        1fr;
    gap:10px;
}

.field label {
    display:block;
    color:var(--muted);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:6px;
}

.field input,
.field select {
    width:100%;
    padding:10px 11px;

    background:#080b12;
    border:1px solid rgba(130,170,220,.18);
    border-radius:5px;

    color:white;
    outline:none;
}

.field input:focus,
.field select:focus {
    border-color:var(--blue);
}

.filter-buttons {
    grid-column:1 / -1;
    display:flex;
    gap:8px;
}

.btn {
    display:inline-block;
    padding:10px 16px;
    border-radius:5px;
    text-decoration:none;
    font-size:11px;
    cursor:pointer;
}

.btn-primary {
    border:1px solid #1d647d;
    background:#0d2835;
    color:#4ed9ff;
}

.btn-clear {
    border:1px solid rgba(130,170,220,.15);
    background:#080b12;
    color:var(--muted);
}

.status {
    display:flex;
    align-items:center;
    gap:9px;

    padding:13px 16px;
    margin-bottom:20px;

    border-radius:7px;
    border:1px solid rgba(99,211,154,.15);
    background:rgba(99,211,154,.045);

    color:var(--muted);
    font-size:12px;
}

.status-dot {
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--green);
    box-shadow:0 0 9px rgba(99,211,154,.65);
}

.warning {
    border-color:rgba(213,180,106,.22);
    background:rgba(213,180,106,.05);
}

.warning .status-dot {
    background:var(--gold);
    box-shadow:0 0 9px rgba(213,180,106,.55);
}

.table-wrap {
    overflow-x:auto;
}

table {
    width:100%;
    min-width:1050px;
    border-collapse:collapse;
}

th {
    text-align:left;
    padding:12px 14px;

    color:#61768c;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.8px;

    background:#080d15;
    border-bottom:1px solid var(--line);
    white-space:nowrap;
}

td {
    padding:13px 14px;
    border-bottom:1px solid rgba(130,170,220,.08);
    font-size:12px;
    white-space:nowrap;
    vertical-align:middle;
}

tr:hover td {
    background:rgba(76,168,255,.025);
}

.player {
    color:#edf5ff;
    font-weight:700;
}

.arrow {
    color:var(--muted2);
    padding:0 6px;
}

.item-name {
    color:#dceafa;
    font-weight:600;
}

.item-id {
    display:block;
    color:#536678;
    font-size:10px;
    margin-top:4px;
}

.enchant {
    color:var(--gold);
    font-weight:700;
}

.amount {
    color:#b9cbe0;
}

.adena {
    color:var(--cyan);
    font-weight:700;
}

.type {
    display:inline-block;
    padding:4px 8px;
    border-radius:4px;
    border:1px solid rgba(76,168,255,.15);
    background:rgba(76,168,255,.06);
    color:#8dc9ff;
    font-size:9px;
    font-weight:700;
    letter-spacing:.5px;
}

.date {
    color:#8a9bad;
}

.empty {
    padding:55px 25px;
    text-align:center;
    color:var(--muted2);
    font-size:13px;
}

.empty strong {
    display:block;
    color:#dceafa;
    font-size:15px;
    margin-bottom:9px;
}

.pagination {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
    padding:16px;
    border-top:1px solid var(--line);
}

.pagination a,
.pagination span {
    min-width:34px;
    text-align:center;
    padding:8px 10px;
    border-radius:5px;
    text-decoration:none;
    font-size:11px;
}

.pagination a {
    color:#8eb5d8;
    border:1px solid rgba(130,170,220,.14);
}

.pagination a:hover {
    border-color:var(--blue);
    color:white;
}

.pagination .current {
    color:white;
    background:rgba(76,168,255,.13);
    border:1px solid rgba(76,168,255,.3);
}

.pagination .muted {
    color:#46576a;
}

@media(max-width:1050px) {
    .stats {
        grid-template-columns:repeat(2,1fr);
    }

    .filters {
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:700px) {
    .sidebar {
        position:relative;
        width:100%;
        height:auto;
        border-right:none;
        border-bottom:1px solid var(--line);
    }

    .logout {
        position:static;
        margin-top:20px;
    }

    .content {
        margin-left:0;
        padding:20px;
    }

    .topbar {
        align-items:flex-start;
        gap:12px;
        flex-direction:column;
    }

    .stats,
    .filters {
        grid-template-columns:1fr;
    }

    .filter-buttons {
        grid-column:auto;
    }
}

</style>

</head>

<body>

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

        <a href="index.php">
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

        <a href="logs.php" class="active">
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

    <div class="topbar">

        <div>

            <h2>
                Logs de Transações
            </h2>

            <div class="subtitle">
                Controle e auditoria de movimentação entre personagens
            </div>

        </div>

        <div class="admin-info">

            Administrador:

            <strong>
                <?= h($admin['username'] ?? 'Admin') ?>
            </strong>

        </div>

    </div>

    <div class="status">
    <span class="status-dot"></span>
    <strong style="color:var(--green);">AUDITORIA ATIVA</strong>
    <span>
        Leitura direta do sistema nativo de logs de trade do L2 Mythras.
    </span>
</div>

    <section class="stats">

        <div class="stat">

            <div class="stat-title">
                Total de movimentações
            </div>

            <div class="stat-value cyan">
                <?= formatNumberValue($stats['total']) ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                Movimentações hoje
            </div>

            <div class="stat-value green">
                <?= formatNumberValue($stats['today']) ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                Adena movimentada
            </div>

            <div class="stat-value gold">
                <?= formatNumberValue($stats['adena']) ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                Itens movimentados
            </div>

            <div class="stat-value blue">
                <?= formatNumberValue($stats['items']) ?>
            </div>

        </div>

    </section>

    <div class="panel">

        <div class="panel-header">

            <h3>
                FILTRAR TRANSAÇÕES
            </h3>

        </div>

        <form method="get" class="filters">

            <div class="field">

                <label>
                    Personagem
                </label>

                <input
                    type="text"
                    name="character"
                    value="<?= h($character) ?>"
                    placeholder="Origem ou destino"
                >

            </div>

            <div class="field">

                <label>
                    Item
                </label>

                <input
                    type="text"
                    name="item"
                    value="<?= h($item) ?>"
                    placeholder="Nome ou ID"
                >

            </div>

            <div class="field">

                <label>
                    Tipo
                </label>

                <select name="type">
    <option value="">Todos os Trades</option>
    <option value="TRADE" selected>Trade entre jogadores</option>
</select>

            </div>

            <div class="field">

                <label>
                    Data inicial
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="<?= h($dateFrom) ?>"
                >

            </div>

            <div class="field">

                <label>
                    Data final
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="<?= h($dateTo) ?>"
                >

            </div>

            <div class="filter-buttons">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    FILTRAR
                </button>

                <a
                    href="logs.php"
                    class="btn btn-clear"
                >
                    LIMPAR
                </a>

            </div>

        </form>

    </div>

    <div class="panel">

        <div class="panel-header">

            <h3>
                HISTÓRICO DE TRANSAÇÕES
            </h3>

        </div>

        <?php if (!$tradeLogs): ?>

            <div class="empty">

                <strong>
                    Nenhuma transação encontrada.
                </strong>

                Ajuste os filtros ou aguarde novas movimentações.

            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Data
                            </th>

                            <th>
                                Origem
                            </th>

                            <th>
                                Destino
                            </th>

                            <th>
                                Item
                            </th>

                            <th>
                                Qtd.
                            </th>

                            <th>
                                Enchant
                            </th>

                            <th>
                                Adena
                            </th>

                            <th>
                                Tipo
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($tradeLogs as $log): ?>

                        <tr>

                            <td class="date">
                                <?= h(tradeDate($log['created_at'] ?? null)) ?>
                            </td>

                            <td>

                                <span class="player">
                                    <?= h($log['from_char_name'] ?? '-') ?>
                                </span>

                            </td>

                            <td>

                                <span class="arrow">
                                    →
                                </span>

                                <span class="player">
                                    <?= h($log['to_char_name'] ?? '-') ?>
                                </span>

                            </td>

                            <td>
    <span class="item-name">
        <?= (int)($log['item_id'] ?? 0) === 57
    ? 'Adena'
    : h($itemNames[(int)($log['item_id'] ?? 0)] ?? ('Item #' . (string)($log['item_id'] ?? '-'))) ?>
    </span>
    <span class="item-id">
        ID <?= h((string)($log['item_id'] ?? '-')) ?>
        <?php if ((int)($log['item_object_id'] ?? -1) >= 0): ?>
            · Object <?= h((string)$log['item_object_id']) ?>
        <?php endif; ?>
    </span>
</td>

                            <td class="amount">
    <?= (int)($log['item_id'] ?? 0) === 57 ? '-' : formatNumberValue($log['amount'] ?? 0) ?>
</td>

                            <td class="enchant">

                                <?php
                                $enchant = (int)($log['enchant'] ?? 0);
                                ?>

                                <?= $enchant > 0 ? '+' . $enchant : '-' ?>

                            </td>

                            <td class="adena">
    <?php if ((int)($log['item_id'] ?? 0) === 57): ?>
        <?= formatNumberValue($log['amount'] ?? 0) ?>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

                            <td>

                                <span class="type">TRADE</span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalPages > 1): ?>

                <div class="pagination">

                    <?php if ($page > 1): ?>

                        <a href="<?= h(pageUrl($page - 1)) ?>">
                            ←
                        </a>

                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>

                        <?php if ($p === $page): ?>

                            <span class="current">
                                <?= $p ?>
                            </span>

                        <?php else: ?>

                            <a href="<?= h(pageUrl($p)) ?>">
                                <?= $p ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>

                        <a href="<?= h(pageUrl($page + 1)) ?>">
                            →
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

</body>

</html>
