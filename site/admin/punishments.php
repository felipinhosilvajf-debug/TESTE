<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

$message = '';
$error = '';

$csrf = csrf_token();

$admin = current_admin();
$adminName = (string)($admin['username'] ?? 'ADMIN');

function punishmentDate(int $timestamp): string
{
    if ($timestamp <= 0) {
        return 'Permanente';
    }

    return date('d/m/Y H:i', $timestamp);
}

function durationTimestamp(string $duration): int
{
    if ($duration === 'permanent') {
        return 0;
    }

    $seconds = (int)$duration;

    if ($seconds <= 0) {
        return 0;
    }

    return time() + $seconds;
}

/*
|--------------------------------------------------------------------------
| AÇÕES
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!verify_csrf($_POST['csrf'] ?? '')) {
            throw new RuntimeException('Token de segurança inválido.');
        }

        $action = trim((string)($_POST['action'] ?? ''));
        $targetType = trim((string)($_POST['target_type'] ?? ''));
        $target = trim((string)($_POST['target'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $duration = trim((string)($_POST['duration'] ?? 'permanent'));

        if ($target === '') {
            throw new RuntimeException('Informe o alvo da punição.');
        }

        if ($reason === '') {
            throw new RuntimeException('O motivo é obrigatório.');
        }

        /*
        |--------------------------------------------------------------------------
        | BAN DE PERSONAGEM
        |--------------------------------------------------------------------------
        */

        if ($action === 'ban_character') {

            $stmt = $pdo->prepare("
                SELECT
                    obj_Id,
                    char_name,
                    account_name
                FROM characters
                WHERE char_name = ?
                LIMIT 1
            ");

            $stmt->execute([$target]);

            $character = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$character) {
                throw new RuntimeException('Personagem não encontrado.');
            }

            $endban = durationTimestamp($duration);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT obj_Id
                FROM bans
                WHERE obj_Id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                (int)$character['obj_Id']
            ]);

            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {

                $stmt = $pdo->prepare("
                    UPDATE bans
                    SET
                        account_name = ?,
                        baned = ?,
                        unban = ?,
                        reason = ?,
                        GM = ?,
                        endban = ?
                    WHERE obj_Id = ?
                ");

                $stmt->execute([
                    $character['account_name'],
                    date('Y-m-d H:i:s'),
                    '',
                    $reason,
                    $adminName,
                    $endban,
                    (int)$character['obj_Id']
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO bans
                    (
                        account_name,
                        obj_Id,
                        baned,
                        unban,
                        reason,
                        GM,
                        endban,
                        karma
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0)
                ");

                $stmt->execute([
                    $character['account_name'],
                    (int)$character['obj_Id'],
                    date('Y-m-d H:i:s'),
                    '',
                    $reason,
                    $adminName,
                    $endban
                ]);
            }

            audit(
                'PUNISHMENT_BAN_CHARACTER',
                'Personagem: ' . $character['char_name'] .
                ' | Conta: ' . $character['account_name'] .
                ' | Duração: ' . ($endban > 0 ? punishmentDate($endban) : 'Permanente') .
                ' | Motivo: ' . $reason
            );

            $pdo->commit();

            $message = 'Banimento do personagem aplicado com sucesso.';
        }

        /*
        |--------------------------------------------------------------------------
        | DESBAN DE PERSONAGEM
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'unban_character') {

            $stmt = $pdo->prepare("
                SELECT
                    obj_Id,
                    char_name,
                    account_name
                FROM characters
                WHERE char_name = ?
                LIMIT 1
            ");

            $stmt->execute([$target]);

            $character = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$character) {
                throw new RuntimeException('Personagem não encontrado.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT obj_Id
                FROM bans
                WHERE obj_Id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                (int)$character['obj_Id']
            ]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Esse personagem não possui um banimento registrado.');
            }

            $stmt = $pdo->prepare("
                DELETE FROM bans
                WHERE obj_Id = ?
            ");

            $stmt->execute([
                (int)$character['obj_Id']
            ]);

            audit(
                'PUNISHMENT_UNBAN_CHARACTER',
                'Personagem: ' . $character['char_name'] .
                ' | Conta: ' . $character['account_name'] .
                ' | Motivo da retirada: ' . $reason
            );

            $pdo->commit();

            $message = 'Banimento do personagem retirado com sucesso.';
        }

        /*
        |--------------------------------------------------------------------------
        | BAN DE CONTA
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'ban_account') {

            $stmt = $pdo->prepare("
                SELECT login
                FROM accounts
                WHERE login = ?
                LIMIT 1
            ");

            $stmt->execute([$target]);

            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                throw new RuntimeException('Conta não encontrada.');
            }

            $endban = durationTimestamp($duration);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE accounts
                SET ban_expire = ?
                WHERE login = ?
            ");

            $stmt->execute([
                $endban,
                $target
            ]);

            audit(
                'PUNISHMENT_BAN_ACCOUNT',
                'Conta: ' . $target .
                ' | Duração: ' . ($endban > 0 ? punishmentDate($endban) : 'Permanente') .
                ' | Motivo: ' . $reason
            );

            $pdo->commit();

            $message = 'Banimento da conta aplicado com sucesso.';
        }

        /*
        |--------------------------------------------------------------------------
        | DESBAN DE CONTA
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'unban_account') {

            $stmt = $pdo->prepare("
                SELECT login
                FROM accounts
                WHERE login = ?
                LIMIT 1
            ");

            $stmt->execute([$target]);

            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                throw new RuntimeException('Conta não encontrada.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE accounts
                SET ban_expire = 0
                WHERE login = ?
            ");

            $stmt->execute([
                $target
            ]);

            audit(
                'PUNISHMENT_UNBAN_ACCOUNT',
                'Conta: ' . $target .
                ' | Motivo da retirada: ' . $reason
            );

            $pdo->commit();

            $message = 'Banimento da conta retirado com sucesso.';
        }

        /*
        |--------------------------------------------------------------------------
        | AINDA NÃO EXECUTAR DIRETO PELO BANCO
        |--------------------------------------------------------------------------
        */

        elseif (in_array($action, [
            'ban_ip',
            'unban_ip',
            'ban_hwid',
            'unban_hwid',
            'jail',
            'unjail',
            'kick'
        ], true)) {

            throw new RuntimeException(
                'Essa operação será integrada diretamente à lógica do GameServer na próxima etapa.'
            );
        }

        else {
            throw new RuntimeException('Ação inválida.');
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| BANIMENTOS DE PERSONAGENS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        b.obj_Id,
        b.account_name,
        b.baned,
        b.unban,
        b.reason,
        b.GM,
        b.endban,
        c.char_name
    FROM bans b
    LEFT JOIN characters c
        ON c.obj_Id = b.obj_Id
    ORDER BY b.endban DESC, b.obj_Id DESC
    LIMIT 100
");

$bans = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CONTAS BANIDAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        login,
        ban_expire,
        last_ip,
        last_access
    FROM accounts
    WHERE ban_expire > 0
    ORDER BY ban_expire DESC
    LIMIT 100
");

$accountBans = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$totalCharacterBans = (int)$pdo->query("
    SELECT COUNT(*)
    FROM bans
")->fetchColumn();

$totalAccountBans = (int)$pdo->query("
    SELECT COUNT(*)
    FROM accounts
    WHERE ban_expire > 0
")->fetchColumn();

$permanentCharacterBans = (int)$pdo->query("
    SELECT COUNT(*)
    FROM bans
    WHERE endban = 0
")->fetchColumn();

$activeTimedCharacterBans = (int)$pdo->query("
    SELECT COUNT(*)
    FROM bans
    WHERE endban > UNIX_TIMESTAMP()
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| HISTÓRICO DA STAFF
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        admin_id,
        action,
        details,
        ip_address,
        created_at
    FROM admin_audit_log
    WHERE action LIKE 'PUNISHMENT_%'
    ORDER BY id DESC
    LIMIT 100
");

$punishmentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Punições — Administração</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

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

.content {
    margin-left: 230px;
    padding: 30px;
}

.header {
    margin-bottom: 28px;
}

.header h1 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 25px;
    color: #fff;
}

.header p {
    margin: 7px 0 0;
    color: #65798d;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    padding: 20px;
}

.card-label {
    color: #63778a;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.card-value {
    margin-top: 7px;
    color: #39d5ff;
    font-family: 'Orbitron', sans-serif;
    font-size: 24px;
    font-weight: 700;
}

.grid {
    display: grid;
    grid-template-columns: 430px 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.panel {
    background: #0b111b;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 8px;
    padding: 22px;
    margin-bottom: 20px;
}

.panel h2 {
    margin: 0 0 20px;
    font-family: 'Orbitron', sans-serif;
    font-size: 15px;
    color: #fff;
}

.operation-box {
    border: 1px solid #172738;
    border-radius: 7px;
    padding: 15px;
    margin-bottom: 15px;
}

.operation-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
    color: #39d5ff;
    margin-bottom: 14px;
}

.operation-title.red {
    color: #ff7070;
}

label {
    display: block;
    margin-bottom: 7px;
    color: #8194a7;
    font-size: 13px;
}

input,
select,
textarea {
    width: 100%;
    background: #070b12;
    border: 1px solid #1b2a3a;
    border-radius: 5px;
    color: #dce9f4;
    padding: 11px;
    outline: none;
    font-family: 'Rajdhani', sans-serif;
    margin-bottom: 15px;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #39d5ff;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

button {
    width: 100%;
    border: 0;
    border-radius: 5px;
    padding: 12px;
    background: #39d5ff;
    color: #031018;
    font-weight: 800;
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
}

button:hover {
    filter: brightness(1.08);
}

button.danger {
    background: #ff5555;
    color: #fff;
}

.message {
    background: rgba(57,213,255,.08);
    border: 1px solid rgba(57,213,255,.25);
    color: #39d5ff;
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.error {
    background: rgba(255,70,70,.08);
    border: 1px solid rgba(255,70,70,.25);
    color: #ff7070;
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    color: #5d7185;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #182535;
}

td {
    padding: 13px 12px;
    border-bottom: 1px solid rgba(24,37,53,.65);
    color: #b8c8d7;
    font-size: 14px;
    vertical-align: top;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.badge-red {
    color: #ff7373;
    background: rgba(255,70,70,.10);
}

.badge-cyan {
    color: #39d5ff;
    background: rgba(57,213,255,.10);
}

.badge-yellow {
    color: #e5c05b;
    background: rgba(213,169,58,.10);
}

.muted {
    color: #536678;
}

.inline-form {
    margin: 0;
}

.inline-form input {
    margin-bottom: 8px;
}

.inline-form button {
    width: auto;
    padding: 7px 12px;
    font-size: 12px;
}

.log-details {
    max-width: 500px;
    white-space: normal;
    word-break: break-word;
}

@media(max-width: 1100px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 700px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .content {
        margin-left: 0;
        padding: 20px;
    }

    .stats {
        grid-template-columns: 1fr;
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

        <a href="punishments.php"
		class="active">
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

    <div class="header">

        <h1>Central de Punições</h1>

        <p>
            Controle de banimentos, retiradas e histórico administrativo.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <section class="stats">

        <div class="card">

            <div class="card-label">
                Chars banidos
            </div>

            <div class="card-value">
                <?= number_format($totalCharacterBans, 0, ',', '.') ?>
            </div>

        </div>

        <div class="card">

            <div class="card-label">
                Contas banidas
            </div>

            <div class="card-value">
                <?= number_format($totalAccountBans, 0, ',', '.') ?>
            </div>

        </div>

        <div class="card">

            <div class="card-label">
                Bans permanentes
            </div>

            <div class="card-value">
                <?= number_format($permanentCharacterBans, 0, ',', '.') ?>
            </div>

        </div>

        <div class="card">

            <div class="card-label">
                Bans temporários
            </div>

            <div class="card-value">
                <?= number_format($activeTimedCharacterBans, 0, ',', '.') ?>
            </div>

        </div>

    </section>

    <section class="grid">

        <!-- APLICAR -->

        <div class="panel">

            <h2>Aplicar punição</h2>

            <div class="operation-box">

                <div class="operation-title">
                    BANIMENTO
                </div>

                <form method="post">

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <label>Tipo</label>

                    <select name="action" required>

                        <option value="ban_character">
                            Banir personagem
                        </option>

                        <option value="ban_account">
                            Banir conta
                        </option>

                    </select>

                    <label>Personagem / Conta</label>

                    <input
                        type="text"
                        name="target"
                        maxlength="45"
                        placeholder="Nome do personagem ou login"
                        required
                    >

                    <label>Duração</label>

                    <select name="duration" required>

                        <option value="permanent">
                            Permanente
                        </option>

                        <option value="86400">
                            1 dia
                        </option>

                        <option value="259200">
                            3 dias
                        </option>

                        <option value="604800">
                            7 dias
                        </option>

                        <option value="1209600">
                            14 dias
                        </option>

                        <option value="2592000">
                            30 dias
                        </option>

                    </select>

                    <label>Motivo</label>

                    <textarea
                        name="reason"
                        maxlength="200"
                        placeholder="Informe o motivo..."
                        required
                    ></textarea>

                    <button type="submit">
                        APLICAR BANIMENTO
                    </button>

                </form>

            </div>

        </div>

        <!-- BANIMENTOS DE CHAR -->

        <div class="panel">

            <h2>Banimentos de personagens</h2>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>Personagem</th>
                            <th>Conta</th>
                            <th>Expiração</th>
                            <th>Staff</th>
                            <th>Motivo</th>
                            <th>Ação</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($bans as $ban): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?= htmlspecialchars(
                                        (string)($ban['char_name'] ?? 'N/A'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string)$ban['account_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <td>

                                <?php if ((int)$ban['endban'] === 0): ?>

                                    <span class="badge badge-red">
                                        PERMANENTE
                                    </span>

                                <?php else: ?>

                                    <?= htmlspecialchars(
                                        punishmentDate((int)$ban['endban']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string)$ban['GM'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    (string)$ban['reason'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                            <td>

                                <form
                                    method="post"
                                    class="inline-form"
                                    onsubmit="return confirm('Tem certeza que deseja retirar o banimento deste personagem?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="unban_character"
                                    >

                                    <input
                                        type="hidden"
                                        name="target"
                                        value="<?= htmlspecialchars(
                                            (string)($ban['char_name'] ?? ''),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                    <input
                                        type="text"
                                        name="reason"
                                        maxlength="200"
                                        placeholder="Motivo da retirada"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="danger"
                                    >
                                        DESBANIR
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$bans): ?>

                        <tr>

                            <td colspan="6" class="muted">
                                Nenhum banimento de personagem encontrado.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

    <!-- CONTAS -->

    <section class="panel">

        <h2>Contas banidas</h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Conta</th>
                        <th>Expiração</th>
                        <th>Último IP</th>
                        <th>Último acesso</th>
                        <th>Ação</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($accountBans as $account): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    (string)$account['login'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </td>

                        <td>

                            <?php if ((int)$account['ban_expire'] === 0): ?>

                                <span class="badge badge-red">
                                    PERMANENTE
                                </span>

                            <?php else: ?>

                                <?= date(
                                    'd/m/Y H:i',
                                    (int)$account['ban_expire']
                                ) ?>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                (string)($account['last_ip'] ?? '-'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>

                        <td>

                            <?= (int)$account['last_access'] > 0
                                ? date(
                                    'd/m/Y H:i',
                                    (int)$account['last_access']
                                )
                                : '-'
                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                class="inline-form"
                                onsubmit="return confirm('Tem certeza que deseja retirar o banimento desta conta?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="unban_account"
                                >

                                <input
                                    type="hidden"
                                    name="target"
                                    value="<?= htmlspecialchars(
                                        (string)$account['login'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                <input
                                    type="text"
                                    name="reason"
                                    maxlength="200"
                                    placeholder="Motivo da retirada"
                                    required
                                >

                                <button
                                    type="submit"
                                    class="danger"
                                >
                                    DESBANIR
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$accountBans): ?>

                    <tr>

                        <td colspan="5" class="muted">
                            Nenhuma conta banida encontrada.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- LOG -->

    <section class="panel">

        <h2>Histórico de punições</h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Data</th>
                        <th>Staff ID</th>
                        <th>Ação</th>
                        <th>Detalhes</th>
                        <th>IP</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($punishmentLogs as $log): ?>

                    <tr>

                        <td>

                            <?= htmlspecialchars(
                                (string)$log['created_at'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>

                        <td>

                            <?= (int)$log['admin_id'] ?>

                        </td>

                        <td>

                            <?php

                            $actionLabel = match ((string)$log['action']) {

                                'PUNISHMENT_BAN_CHARACTER'
                                    => 'BAN CHAR',

                                'PUNISHMENT_UNBAN_CHARACTER'
                                    => 'UNBAN CHAR',

                                'PUNISHMENT_BAN_ACCOUNT'
                                    => 'BAN CONTA',

                                'PUNISHMENT_UNBAN_ACCOUNT'
                                    => 'UNBAN CONTA',

                                default
                                    => (string)$log['action']
                            };

                            ?>

                            <span class="badge badge-cyan">
                                <?= htmlspecialchars(
                                    $actionLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </td>

                        <td class="log-details">

                            <?= htmlspecialchars(
                                (string)$log['details'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                (string)($log['ip_address'] ?? '-'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$punishmentLogs): ?>

                    <tr>

                        <td colspan="5" class="muted">
                            Nenhuma operação de punição registrada.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>

</html>