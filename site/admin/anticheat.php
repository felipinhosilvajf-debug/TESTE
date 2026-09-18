<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_admin();

$admin = current_admin();


// =========================================================
// FILTROS
// =========================================================

$severity = strtoupper(trim($_GET['severity'] ?? ''));
$eventType = trim($_GET['event_type'] ?? '');
$character = trim($_GET['character'] ?? '');
$ip = trim($_GET['ip'] ?? '');
$hwid = trim($_GET['hwid'] ?? '');
$scoreMin = isset($_GET['score_min']) ? (int) $_GET['score_min'] : 0;

$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 50;

$offset = ($page - 1) * $perPage;


// =========================================================
// CONDIÇÕES
// =========================================================

$where = [];
$params = [];


// Severidade

if (
    $severity !== ''
    && in_array(
        $severity,
        ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'],
        true
    )
) {
    $where[] = 'severity = ?';
    $params[] = $severity;
}


// Tipo do evento

if ($eventType !== '') {

    $where[] = 'event_type LIKE ?';

    $params[] = '%' . $eventType . '%';
}


// Personagem

if ($character !== '') {

    $where[] = 'character_name LIKE ?';

    $params[] = '%' . $character . '%';
}


// IP

if ($ip !== '') {

    $where[] = 'ip_address LIKE ?';

    $params[] = '%' . $ip . '%';
}


// HWID

if ($hwid !== '') {

    $where[] = 'hwid LIKE ?';

    $params[] = '%' . $hwid . '%';
}


// Score

if ($scoreMin > 0) {

    $where[] = 'score >= ?';

    $params[] = $scoreMin;
}


$whereSql = '';

if (!empty($where)) {

    $whereSql = 'WHERE ' . implode(' AND ', $where);

}


// =========================================================
// TOTAL
// =========================================================

$total = 0;

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM anticheat_events
        $whereSql
    ");

    $stmt->execute($params);

    $total = (int)($stmt->fetch()['total'] ?? 0);

} catch (Throwable $e) {

    $total = 0;

}


$totalPages = max(
    1,
    (int)ceil($total / $perPage)
);


// =========================================================
// EVENTOS
// =========================================================

$events = [];

try {

    $sql = "
        SELECT
            id,
            character_name,
            account_name,
            ip_address,
            hwid,
            event_type,
            severity,
            score,
            details,
            created_at
        FROM anticheat_events
        $whereSql
        ORDER BY id DESC
        LIMIT $perPage OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $events = $stmt->fetchAll();

} catch (Throwable $e) {

    $events = [];

}


// =========================================================
// ESTATÍSTICAS
// =========================================================

$stats = [
    'total' => 0,
    'low' => 0,
    'medium' => 0,
    'high' => 0,
    'critical' => 0
];

try {

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN severity = 'LOW'
                    THEN 1
                    ELSE 0
                END
            ) AS low,

            SUM(
                CASE
                    WHEN severity = 'MEDIUM'
                    THEN 1
                    ELSE 0
                END
            ) AS medium,

            SUM(
                CASE
                    WHEN severity = 'HIGH'
                    THEN 1
                    ELSE 0
                END
            ) AS high,

            SUM(
                CASE
                    WHEN severity = 'CRITICAL'
                    THEN 1
                    ELSE 0
                END
            ) AS critical

        FROM anticheat_events
        WHERE created_at >= DATE_SUB(
            NOW(),
            INTERVAL 24 HOUR
        )
    ");

    $result = $stmt->fetch();

    if ($result) {

        $stats['total'] =
            (int)($result['total'] ?? 0);

        $stats['low'] =
            (int)($result['low'] ?? 0);

        $stats['medium'] =
            (int)($result['medium'] ?? 0);

        $stats['high'] =
            (int)($result['high'] ?? 0);

        $stats['critical'] =
            (int)($result['critical'] ?? 0);
    }

} catch (Throwable $e) {

    // Mantém os valores zerados.
}


// =========================================================
// URL PAGINAÇÃO
// =========================================================

function pageUrl(int $page): string
{
    $query = $_GET;

    $query['page'] = $page;

    return '?' . http_build_query($query);
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
    Anti-Cheat — L2 Virtual Boost
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


/* =========================================================
   SIDEBAR
========================================================= */

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

    padding:
        10px
        12px
        28px;

    border-bottom:
        1px solid var(--line);

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

    padding:
        10px
        12px;

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

    border-left:
        2px solid var(--blue);

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


/* =========================================================
   CONTENT
========================================================= */

.content {

    margin-left:240px;

    padding:30px;

    min-height:100vh;

}

.topbar {

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:25px;

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


/* =========================================================
   MONITOR STATUS
========================================================= */

.monitor {

    display:flex;

    align-items:center;

    gap:10px;

    padding:14px 18px;

    margin-bottom:20px;

    background:
        rgba(99,211,154,.06);

    border:
        1px solid
        rgba(99,211,154,.16);

    border-radius:8px;

}

.monitor-dot {

    width:9px;

    height:9px;

    border-radius:50%;

    background:var(--green);

    box-shadow:
        0 0 10px
        rgba(99,211,154,.7);

}

.monitor strong {

    color:var(--green);

    font-size:12px;

}

.monitor span {

    color:var(--muted);

    font-size:11px;

}


/* =========================================================
   STATS
========================================================= */

.stats {

    display:grid;

    grid-template-columns:
        repeat(5, minmax(0,1fr));

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

    border:
        1px solid var(--line);

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

.stat-total .stat-value {

    color:var(--cyan);

}

.stat-low .stat-value {

    color:var(--green);

}

.stat-medium .stat-value {

    color:var(--gold);

}

.stat-high .stat-value {

    color:#ff9b9b;

}

.stat-critical .stat-value {

    color:#ff6464;

}


/* =========================================================
   FILTERS
========================================================= */

.panel {

    background:
        rgba(11,16,25,.94);

    border:
        1px solid var(--line);

    border-radius:9px;

    overflow:hidden;

    margin-bottom:20px;

}

.panel-header {

    padding:18px 20px;

    border-bottom:
        1px solid var(--line);

}

.panel-header h3 {

    margin:0;

    font-size:14px;

}

.filters {

    padding:18px;

    display:grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap:12px;

}

.field label {

    display:block;

    color:var(--muted);

    font-size:10px;

    margin-bottom:6px;

    text-transform:uppercase;

    letter-spacing:1px;

}

.field input,
.field select {

    width:100%;

    padding:11px;

    border:
        1px solid
        rgba(130,170,220,.18);

    background:#080b12;

    color:white;

    border-radius:5px;

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

    padding:
        10px
        16px;

    border:0;

    border-radius:5px;

    color:white;

    text-decoration:none;

    font-size:11px;

    cursor:pointer;

}

.btn-primary {

    background:
        linear-gradient(
            90deg,
            var(--blue2),
            var(--purple2)
        );

}

.btn-secondary {

    background:#141c28;

    border:
        1px solid var(--line);

}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {

    overflow-x:auto;

}

table {

    width:100%;

    min-width:1050px;

    border-collapse:collapse;

}

th {

    text-align:left;

    padding:
        12px
        14px;

    color:var(--muted2);

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:1px;

    border-bottom:
        1px solid var(--line);

}

td {

    padding:
        13px
        14px;

    font-size:11px;

    border-bottom:
        1px solid
        rgba(130,170,220,.07);

    vertical-align:top;

}

tr:hover td {

    background:
        rgba(76,168,255,.025);

}

.character {

    font-weight:bold;

    color:white;

}

.account {

    color:var(--muted2);

    font-size:9px;

    margin-top:4px;

}

.ip {

    color:var(--cyan);

    font-family:monospace;

}

.hwid {

    max-width:150px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

    color:var(--muted);

    font-family:monospace;

}

.event {

    color:#dbe5f0;

    font-weight:bold;

}

.details {

    max-width:260px;

    color:var(--muted);

    line-height:1.5;

}

.score {

    font-size:15px;

    font-weight:bold;

}

.score-normal {

    color:var(--green);

}

.score-medium {

    color:var(--gold);

}

.score-high {

    color:#ff9b9b;

}

.score-critical {

    color:#ff6464;

}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:inline-block;

    padding:
        4px
        8px;

    border-radius:4px;

    font-size:8px;

    font-weight:bold;

    letter-spacing:.5px;

}

.badge-low {

    color:var(--green);

    background:
        rgba(99,211,154,.10);

}

.badge-medium {

    color:var(--gold);

    background:
        rgba(213,180,106,.10);

}

.badge-high {

    color:#ff9b9b;

    background:
        rgba(217,106,106,.12);

}

.badge-critical {

    color:#ff6464;

    background:
        rgba(217,106,106,.22);

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    text-align:center;

    padding:50px 20px;

    color:var(--muted2);

    font-size:12px;

}


/* =========================================================
   PAGINATION
========================================================= */

.pagination {

    display:flex;

    justify-content:center;

    align-items:center;

    gap:5px;

    padding:18px;

    border-top:
        1px solid var(--line);

}

.pagination a,
.pagination span {

    min-width:32px;

    text-align:center;

    padding:8px;

    border-radius:4px;

    text-decoration:none;

    font-size:10px;

}

.pagination a {

    color:var(--muted);

    background:#080b12;

    border:
        1px solid var(--line);

}

.pagination a:hover {

    color:white;

    border-color:var(--blue);

}

.pagination .current {

    color:white;

    background:
        linear-gradient(
            90deg,
            var(--blue2),
            var(--purple2)
        );

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(3,1fr);

    }

    .filters {

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

    .topbar {

        flex-direction:column;

        align-items:flex-start;

        gap:10px;

    }

    .stats {

        grid-template-columns:1fr;

    }

    .filters {

        grid-template-columns:1fr;

    }

}

</style>

</head>

<body>

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

    <a
        href="anticheat.php"
        class="active"
    >
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
            Anti-Cheat
        </h2>

        <div class="subtitle">

            Monitoramento e análise de comportamento

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
     MONITOR STATUS
====================================================== -->

<div class="monitor">

    <div class="monitor-dot"></div>

    <strong>
        MONITORAMENTO ATIVO
    </strong>

    <span>
        Modo observação — nenhuma punição automática
    </span>

</div>


<!-- =====================================================
     STATS
====================================================== -->

<section class="stats">


    <div class="stat stat-total">

        <div class="stat-title">
            Eventos — 24h
        </div>

        <div class="stat-value">
            <?= number_format(
                $stats['total'],
                0,
                ',',
                '.'
            ) ?>
        </div>

    </div>


    <div class="stat stat-low">

        <div class="stat-title">
            Low
        </div>

        <div class="stat-value">
            <?= number_format(
                $stats['low'],
                0,
                ',',
                '.'
            ) ?>
        </div>

    </div>


    <div class="stat stat-medium">

        <div class="stat-title">
            Medium
        </div>

        <div class="stat-value">
            <?= number_format(
                $stats['medium'],
                0,
                ',',
                '.'
            ) ?>
        </div>

    </div>


    <div class="stat stat-high">

        <div class="stat-title">
            High
        </div>

        <div class="stat-value">
            <?= number_format(
                $stats['high'],
                0,
                ',',
                '.'
            ) ?>
        </div>

    </div>


    <div class="stat stat-critical">

        <div class="stat-title">
            Critical
        </div>

        <div class="stat-value">
            <?= number_format(
                $stats['critical'],
                0,
                ',',
                '.'
            ) ?>
        </div>

    </div>


</section>


<!-- =====================================================
     FILTROS
====================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            FILTRAR EVENTOS
        </h3>

    </div>


    <form method="GET">

        <div class="filters">


            <div class="field">

                <label>
                    Personagem
                </label>

                <input
                    type="text"
                    name="character"
                    value="<?= htmlspecialchars(
                        $character,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Nome do personagem"
                >

            </div>


            <div class="field">

                <label>
                    IP
                </label>

                <input
                    type="text"
                    name="ip"
                    value="<?= htmlspecialchars(
                        $ip,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Ex.: 192.168"
                >

            </div>


            <div class="field">

                <label>
                    HWID
                </label>

                <input
                    type="text"
                    name="hwid"
                    value="<?= htmlspecialchars(
                        $hwid,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="HWID"
                >

            </div>


            <div class="field">

                <label>
                    Tipo do evento
                </label>

                <input
                    type="text"
                    name="event_type"
                    value="<?= htmlspecialchars(
                        $eventType,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="ATTACK_RATE"
                >

            </div>


            <div class="field">

                <label>
                    Severidade
                </label>

                <select name="severity">

                    <option value="">
                        Todas
                    </option>

                    <option
                        value="LOW"
                        <?= $severity === 'LOW'
                            ? 'selected'
                            : '' ?>
                    >
                        LOW
                    </option>

                    <option
                        value="MEDIUM"
                        <?= $severity === 'MEDIUM'
                            ? 'selected'
                            : '' ?>
                    >
                        MEDIUM
                    </option>

                    <option
                        value="HIGH"
                        <?= $severity === 'HIGH'
                            ? 'selected'
                            : '' ?>
                    >
                        HIGH
                    </option>

                    <option
                        value="CRITICAL"
                        <?= $severity === 'CRITICAL'
                            ? 'selected'
                            : '' ?>
                    >
                        CRITICAL
                    </option>

                </select>

            </div>


            <div class="field">

                <label>
                    Score mínimo
                </label>

                <input
                    type="number"
                    name="score_min"
                    min="0"
                    max="255"
                    value="<?= $scoreMin > 0
                        ? $scoreMin
                        : '' ?>"
                    placeholder="Ex.: 60"
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
                    href="anticheat.php"
                    class="btn btn-secondary"
                >
                    LIMPAR
                </a>

            </div>


        </div>

    </form>

</div>


<!-- =====================================================
     EVENTOS
====================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>

            EVENTOS DETECTADOS

            <span
                style="
                    color:#5f6b7b;
                    font-size:10px;
                    margin-left:8px;
                "
            >

                <?= number_format(
                    $total,
                    0,
                    ',',
                    '.'
                ) ?>

                registros

            </span>

        </h3>

    </div>


    <?php if (empty($events)): ?>

        <div class="empty">

            Nenhum evento encontrado.

            <br><br>

            O Anti-Cheat ainda não recebeu dados
            ou os filtros não encontraram resultados.

        </div>

    <?php else: ?>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Personagem
                        </th>

                        <th>
                            IP
                        </th>

                        <th>
                            HWID
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
                            Detalhes
                        </th>

                        <th>
                            Data
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($events as $event): ?>


                    <?php

                    $eventSeverity =
                        strtoupper(
                            (string)$event['severity']
                        );

                    $badgeClass =
                        'badge-' .
                        strtolower(
                            $eventSeverity
                        );


                    $score =
                        (int)$event['score'];


                    if ($score >= 80) {

                        $scoreClass =
                            'score-critical';

                    } elseif ($score >= 60) {

                        $scoreClass =
                            'score-high';

                    } elseif ($score >= 30) {

                        $scoreClass =
                            'score-medium';

                    } else {

                        $scoreClass =
                            'score-normal';

                    }

                    ?>


                    <tr>


                        <!-- PERSONAGEM -->

                        <td>

                            <div class="character">

                                <?= htmlspecialchars(
                                    (string)(
                                        $event['character_name']
                                        ?: '-'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>


                            <?php if (
                                !empty(
                                    $event['account_name']
                                )
                            ): ?>

                                <div class="account">

                                    <?= htmlspecialchars(
                                        (string)$event[
                                            'account_name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- IP -->

                        <td>

                            <div class="ip">

                                <?= htmlspecialchars(
                                    (string)(
                                        $event['ip_address']
                                        ?: '-'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        </td>


                        <!-- HWID -->

                        <td>

                            <div
                                class="hwid"
                                title="<?= htmlspecialchars(
                                    (string)(
                                        $event['hwid']
                                        ?: '-'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    (string)(
                                        $event['hwid']
                                        ?: '-'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        </td>


                        <!-- EVENTO -->

                        <td>

                            <div class="event">

                                <?= htmlspecialchars(
                                    (string)$event[
                                        'event_type'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        </td>


                        <!-- SEVERIDADE -->

                        <td>

                            <span
                                class="badge <?= $badgeClass ?>"
                            >

                                <?= htmlspecialchars(
                                    $eventSeverity,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </td>


                        <!-- SCORE -->

                        <td>

                            <span
                                class="score <?= $scoreClass ?>"
                            >

                                <?= $score ?>

                            </span>

                        </td>


                        <!-- DETALHES -->

                        <td>

                            <div class="details">

                                <?= nl2br(
                                    htmlspecialchars(
                                        (string)(
                                            $event['details']
                                            ?: '-'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>

                            </div>

                        </td>


                        <!-- DATA -->

                        <td>

                            <?= htmlspecialchars(
                                (string)(
                                    $event['created_at']
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>


        <!-- =================================================
             PAGINAÇÃO
        ================================================== -->

        <?php if ($totalPages > 1): ?>

            <div class="pagination">


                <?php if ($page > 1): ?>

                    <a
                        href="<?= htmlspecialchars(
                            pageUrl($page - 1),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ‹
                    </a>

                <?php endif; ?>


                <?php

                $startPage =
                    max(
                        1,
                        $page - 2
                    );

                $endPage =
                    min(
                        $totalPages,
                        $page + 2
                    );

                ?>


                <?php for (
                    $i = $startPage;
                    $i <= $endPage;
                    $i++
                ): ?>


                    <?php if ($i === $page): ?>

                        <span class="current">
                            <?= $i ?>
                        </span>

                    <?php else: ?>

                        <a
                            href="<?= htmlspecialchars(
                                pageUrl($i),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endif; ?>


                <?php endfor; ?>


                <?php if ($page < $totalPages): ?>

                    <a
                        href="<?= htmlspecialchars(
                            pageUrl($page + 1),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ›
                    </a>

                <?php endif; ?>


            </div>

        <?php endif; ?>


    <?php endif; ?>

</div>

</main>

</body>

</html>
