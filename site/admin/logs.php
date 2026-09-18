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
| Esta página é somente leitura.
|
| O painel espera uma tabela "trade_logs" para o histórico permanente
| das trades realizadas no GameServer.
|
| IMPORTANTE:
| O painel não inventa movimentações. Se a tabela ainda não existir,
| a página informa isso e nenhuma alteração é feita no banco.
|
*/

$tradeTableExists = false;
$tradeLogs = [];
$totalTrades = 0;
$totalPages = 1;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$character = trim((string)($_GET['character'] ?? ''));
$item = trim((string)($_GET['item'] ?? ''));
$tradeType = trim((string)($_GET['type'] ?? ''));
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
| VERIFICAÇÃO DA TABELA
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'trade_logs'
    ");

    $stmt->execute();

    $tradeTableExists = (int)$stmt->fetchColumn() > 0;
} catch (Throwable $e) {
    $tradeTableExists = false;
}

/*
|--------------------------------------------------------------------------
| FILTROS / CONSULTA
|--------------------------------------------------------------------------
|
| O código abaixo usa os nomes de coluna planejados para o auditor de trade:
|
| id
| from_char_id
| from_char_name
| to_char_id
| to_char_name
| item_id
| item_name
| amount
| enchant
| adena
| trade_type
| created_at
|
*/

if ($tradeTableExists) {

    $where = [];
    $params = [];

    if ($character !== '') {
        $where[] = "(
            from_char_name LIKE ?
            OR to_char_name LIKE ?
        )";

        $params[] = '%' . $character . '%';
        $params[] = '%' . $character . '%';
    }

    if ($item !== '') {
        $where[] = "(
            item_name LIKE ?
            OR CAST(item_id AS CHAR) LIKE ?
        )";

        $params[] = '%' . $item . '%';
        $params[] = '%' . $item . '%';
    }

    if ($tradeType !== '') {
        $where[] = "trade_type = ?";
        $params[] = $tradeType;
    }

    if ($dateFrom !== '') {
        $where[] = "created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }

    if ($dateTo !== '') {
        $where[] = "created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }

    $whereSql = '';

    if ($where) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    /*
    |----------------------------------------------------------------------
    | TOTAL
    |----------------------------------------------------------------------
    */

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM trade_logs
            $whereSql
        ");

        $stmt->execute($params);

        $totalTrades = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $totalTrades = 0;
    }

    $totalPages = max(
        1,
        (int)ceil($totalTrades / $perPage)
    );

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    /*
    |----------------------------------------------------------------------
    | REGISTROS
    |----------------------------------------------------------------------
    */

    try {
        $stmt = $pdo->prepare("
            SELECT
                id,
                from_char_id,
                from_char_name,
                to_char_id,
                to_char_name,
                item_id,
                item_name,
                amount,
                enchant,
                adena,
                trade_type,
                created_at
            FROM trade_logs
            $whereSql
            ORDER BY id DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);

        $tradeLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $tradeLogs = [];
    }
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

if ($tradeTableExists) {

    try {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(
                    SUM(
                        CASE
                            WHEN created_at >= CURDATE()
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS today,
                COALESCE(SUM(adena), 0) AS adena,
                COALESCE(SUM(amount), 0) AS items
            FROM trade_logs
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
                Auditoria de movimentação de itens e Adena entre personagens
            </div>

        </div>

        <div class="admin-info">

            Administrador:

            <strong>
                <?= h($admin['username'] ?? 'Admin') ?>
            </strong>

        </div>

    </div>

    <?php if ($tradeTableExists): ?>

        <div class="status">

            <span class="status-dot"></span>

            <strong style="color:var(--green);">
                AUDITORIA ATIVA
            </strong>

            <span>
                Histórico de trades disponível para consulta.
            </span>

        </div>

    <?php else: ?>

        <div class="status warning">

            <span class="status-dot"></span>

            <strong style="color:var(--gold);">
                AGUARDANDO REGISTRO DE TRADE
            </strong>

            <span>
                A tabela <strong>trade_logs</strong> ainda não existe. O painel não cria registros automaticamente.
            </span>

        </div>

    <?php endif; ?>

    <section class="stats">

        <div class="stat">

            <div class="stat-title">
                Total de registros
            </div>

            <div class="stat-value cyan">
                <?= formatNumberValue($stats['total']) ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                Trades hoje
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

                    <option value="">
                        Todos
                    </option>

                    <option
                        value="TRADE"
                        <?= $tradeType === 'TRADE' ? 'selected' : '' ?>
                    >
                        Trade
                    </option>

                    <option
                        value="PRIVATE_STORE"
                        <?= $tradeType === 'PRIVATE_STORE' ? 'selected' : '' ?>
                    >
                        Private Store
                    </option>

                    <option
                        value="MAIL"
                        <?= $tradeType === 'MAIL' ? 'selected' : '' ?>
                    >
                        Mail
                    </option>

                    <option
                        value="OTHER"
                        <?= $tradeType === 'OTHER' ? 'selected' : '' ?>
                    >
                        Outros
                    </option>

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

        <?php if (!$tradeTableExists): ?>

            <div class="empty">

                <strong>
                    Nenhum histórico de trade disponível ainda.
                </strong>

                O painel está preparado para receber os registros do GameServer,
                mas o histórico permanente precisa ser gravado pelo servidor.

                <br><br>

                <span style="color:#667b91;">
                    Próxima etapa: integrar o registro de trades do L2 Mythras
                    à tabela <b>trade_logs</b>.
                </span>

            </div>

        <?php elseif (!$tradeLogs): ?>

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
                                    <?= h($log['item_name'] ?? '-') ?>
                                </span>

                                <?php if (isset($log['item_id'])): ?>

                                    <span class="item-id">
                                        ID <?= h((string)$log['item_id']) ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="amount">
                                <?= formatNumberValue($log['amount'] ?? 0) ?>
                            </td>

                            <td class="enchant">

                                <?php
                                $enchant = (int)($log['enchant'] ?? 0);
                                ?>

                                <?= $enchant > 0 ? '+' . $enchant : '-' ?>

                            </td>

                            <td class="adena">

                                <?php
                                $adena = (int)($log['adena'] ?? 0);
                                ?>

                                <?= $adena > 0
                                    ? formatNumberValue($adena)
                                    : '-'
                                ?>

                            </td>

                            <td>

                                <span class="type">
                                    <?= h($log['trade_type'] ?? 'TRADE') ?>
                                </span>

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
