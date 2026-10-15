<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

/*
|--------------------------------------------------------------------------
| Virtual Boost - Exclusão permanente de personagem
|--------------------------------------------------------------------------
|
| NÃO APAGA:
| - logs
| - character_logs
| - accounts
|
| APAGA:
| - dados funcionais relacionados ao personagem
| - itens
| - personagem da tabela characters
|
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';

$character = null;
$previewCounts = [];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['delete_character_csrf'])) {
    $_SESSION['delete_character_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['delete_character_csrf'];

/*
|--------------------------------------------------------------------------
| Tabelas relacionadas
|--------------------------------------------------------------------------
|
| Todas são fixas no código. Nenhum nome de tabela vindo do usuário.
|
*/

$relatedTables = [
    'admin_donate_log'              => 'character_id',
    'bans'                           => 'obj_Id',
    'bbs_buffs'                      => 'char_id',
    'bbs_pointsave'                  => 'charId',
    'bbs_skillsave'                  => 'charId',
    'bonus'                           => 'obj_id',
    'bot_reported_punish'            => 'charId',
    'character_blocklist'            => 'obj_Id',
    'character_bookmarks'            => 'char_Id',
    'character_counters'             => 'char_id',
    'character_friends'              => 'char_id',
    'character_instances'            => 'obj_id',
    'character_mail'                 => 'char_id',
    'character_offline_buffers'     => 'charId',
    'character_offline_buffer_buffs'=> 'charId',
    'character_premium_items'       => 'charId',
    'character_quests'              => 'char_id',
    'character_recipebook'           => 'char_id',
    'character_stats'               => 'charId',
    'character_variables'            => 'obj_id',
    'communitybuff_grp'              => 'charId',
    'communitybuff_grp_buffs'        => 'charId',
    'comteleport'                    => 'charId',
    'cursed_weapons'                 => 'player_id',
    'event_data'                     => 'charId',
    'heroes'                         => 'char_id',
    'heroes_diary'                   => 'charId',
    'items'                          => 'owner_id',
    'items_delayed'                  => 'owner_id',
    'monthly_character_stats'        => 'charId',
    'npcbuffer_scheme_list'          => 'player_id',
    'olympiad_nobles'                => 'char_id',
    'raidboss_points'                => 'owner_id',
    'report_data'                    => 'obj_id',
    'stress_auto_rb'                 => 'charId',
    'stress_referal'                 => 'charId',
];

/*
|--------------------------------------------------------------------------
| Função para buscar personagem
|--------------------------------------------------------------------------
*/

function findCharacter(PDO $pdo, string $name): ?array
{
    $sql = "
        SELECT
            obj_Id,
            char_name,
            account_name,
            online,
            accesslevel,
            clanid
        FROM characters
        WHERE char_name = :char_name
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':char_name' => $name
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
}

/*
|--------------------------------------------------------------------------
| Contagem dos registros relacionados
|--------------------------------------------------------------------------
*/

function getRelatedCounts(
    PDO $pdo,
    int $characterId,
    array $relatedTables
): array {
    $counts = [];

    foreach ($relatedTables as $table => $column) {
        try {
            $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :character_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':character_id' => $characterId
            ]);

            $counts[$table] = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            /*
             * Se uma tabela não existir no servidor atual, não derruba
             * a página durante a pesquisa.
             */
            $counts[$table] = -1;
        }
    }

    return $counts;
}

/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Validação CSRF
     */
    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($postedToken) ||
        !hash_equals($csrfToken, $postedToken)
    ) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {

        $action = $_POST['action'] ?? '';

        /*
         * ---------------------------------------------------------------
         * PESQUISA
         * ---------------------------------------------------------------
         */

        if ($action === 'search') {

            $charName = trim((string)($_POST['char_name'] ?? ''));

            if ($charName === '') {
                $message = 'Digite o nome do personagem.';
                $messageType = 'error';
            } else {

                $character = findCharacter($pdo, $charName);

                if (!$character) {
                    $message = 'Personagem não encontrado.';
                    $messageType = 'error';
                } else {

                    $previewCounts = getRelatedCounts(
                        $pdo,
                        (int)$character['obj_Id'],
                        $relatedTables
                    );

                    if ((int)$character['online'] === 1) {
                        $message = 'Este personagem está ONLINE. A exclusão foi bloqueada. Desconecte o personagem antes de continuar.';
                        $messageType = 'warning';
                    } else {
                        $message = 'Personagem encontrado. Confira os dados antes da exclusão.';
                        $messageType = 'success';
                    }
                }
            }
        }

        /*
         * ---------------------------------------------------------------
         * EXCLUSÃO
         * ---------------------------------------------------------------
         */

        elseif ($action === 'delete') {

            $charName = trim((string)($_POST['char_name'] ?? ''));
            $confirmation = trim((string)($_POST['confirmation'] ?? ''));

            if ($charName === '') {

                $message = 'Nome do personagem inválido.';
                $messageType = 'error';

            } elseif ($confirmation !== $charName) {

                $message = 'A confirmação não corresponde exatamente ao nome do personagem.';
                $messageType = 'error';

            } else {

                $character = findCharacter($pdo, $charName);

                if (!$character) {

                    $message = 'O personagem não existe mais no banco de dados.';
                    $messageType = 'error';

                } elseif ((int)$character['online'] === 1) {

                    $message = 'EXCLUSÃO BLOQUEADA: o personagem está ONLINE. Desconecte-o primeiro.';
                    $messageType = 'warning';

                } else {

                    $characterId = (int)$character['obj_Id'];

                    /*
                     * Fazemos uma nova contagem imediatamente antes
                     * da exclusão para mostrar o resultado.
                     */
                    $beforeCounts = getRelatedCounts(
                        $pdo,
                        $characterId,
                        $relatedTables
                    );

                    $deletedCounts = [];
                    $errors = [];

                    /*
                     * ---------------------------------------------------
                     * Transação para tabelas InnoDB
                     * ---------------------------------------------------
                     *
                     * Como o characters é MyISAM, ele não participa
                     * realmente de uma transação. Por isso o personagem
                     * é removido somente depois dos filhos.
                     */
                    try {

                        $pdo->beginTransaction();

                        /*
                         * Apaga primeiro todas as tabelas relacionadas.
                         *
                         * logs e character_logs NÃO estão nessa lista
                         * propositalmente.
                         */
                        foreach ($relatedTables as $table => $column) {

                            /*
                             * Não permite que uma tabela inexistente
                             * passe silenciosamente durante uma exclusão.
                             */
                            if (($beforeCounts[$table] ?? 0) === -1) {
                                throw new RuntimeException(
                                    "A tabela `{$table}` não pôde ser consultada."
                                );
                            }

                            $sql = "
                                DELETE FROM `{$table}`
                                WHERE `{$column}` = :character_id
                            ";

                            $stmt = $pdo->prepare($sql);

                            $stmt->execute([
                                ':character_id' => $characterId
                            ]);

                            $deletedCounts[$table] = $stmt->rowCount();
                        }

                        /*
                         * ------------------------------------------------
                         * Remove finalmente o personagem
                         * ------------------------------------------------
                         *
                         * characters é MyISAM.
                         */
                        $stmt = $pdo->prepare("
                            DELETE FROM characters
                            WHERE obj_Id = :obj_id
                              AND char_name = :char_name
                            LIMIT 1
                        ");

                        $stmt->execute([
                            ':obj_id' => $characterId,
                            ':char_name' => $charName
                        ]);

                        if ($stmt->rowCount() !== 1) {

                            throw new RuntimeException(
                                'O registro do personagem não pôde ser removido da tabela characters.'
                            );
                        }

                        /*
                         * Os DELETEs das tabelas InnoDB são confirmados.
                         */
                        if ($pdo->inTransaction()) {
                            $pdo->commit();
                        }

                        /*
                         * Limpa a tela.
                         */
                        $deletedCharacterName = $character['char_name'];
                        $deletedCharacterId = $characterId;
                        $deletedAccount = $character['account_name'];

                        $character = null;
                        $previewCounts = [];

                        $messageType = 'deleted';

                        /*
                         * Montamos um relatório especial abaixo.
                         */
                        $deletionReport = [
                            'name' => $deletedCharacterName,
                            'obj_id' => $deletedCharacterId,
                            'account' => $deletedAccount,
                            'counts' => $deletedCounts
                        ];

                    } catch (Throwable $e) {

                        /*
                         * Rollback das tabelas transacionais.
                         *
                         * Se characters ainda não foi apagado,
                         * o personagem permanece.
                         */
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        /*
                         * Não mostramos detalhes internos do banco
                         * para o administrador.
                         */
                        error_log(
                            '[Virtual Boost] Erro ao excluir personagem ' .
                            $charName .
                            ': ' .
                            $e->getMessage()
                        );

                        $message = 'A exclusão foi interrompida por um erro. Nenhuma alteração transacional restante foi confirmada. Verifique o log do servidor.';
                        $messageType = 'error';

                        $character = findCharacter($pdo, $charName);

                        if ($character) {
                            $previewCounts = getRelatedCounts(
                                $pdo,
                                (int)$character['obj_Id'],
                                $relatedTables
                            );
                        }
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Soma dos registros do preview
|--------------------------------------------------------------------------
*/

$totalPreview = 0;

foreach ($previewCounts as $count) {
    if ($count > 0) {
        $totalPreview += $count;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Excluir Personagem — Virtual Boost</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --bg: #070a10;
            --panel: #0d111a;
            --panel2: #111722;
            --border: rgba(56, 189, 248, .14);

            --blue: #38bdf8;
            --cyan: #22d3ee;
            --purple: #a855f7;
            --gold: #f5c451;

            --red: #ef4444;
            --red-dark: #7f1d1d;

            --green: #22c55e;
            --yellow: #f59e0b;

            --text: #e5edf7;
            --muted: #8290a3;

            --sidebar-width: 240px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(56,189,248,.07),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom left,
                    rgba(168,85,247,.06),
                    transparent 30%
                ),
                var(--bg);

            color: var(--text);
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
        }

        /* ==============================================================
           SIDEBAR
        ============================================================== */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    rgba(13,17,26,.98),
                    rgba(7,10,16,.98)
                );

            border-right: 1px solid var(--border);

            padding: 24px 14px;

            z-index: 1000;
        }

        .brand {
            padding: 0 12px 25px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            margin-bottom: 18px;
        }

        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 1.5px;

            color: #fff;

            text-shadow:
                0 0 15px rgba(56,189,248,.35);
        }

        .brand-sub {
            margin-top: 5px;

            color: var(--blue);

            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-title {
            color: #536175;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;

            padding: 8px 12px;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 11px 13px;

            color: #8d9bad;
            text-decoration: none;

            border: 1px solid transparent;
            border-radius: 8px;

            font-size: 14px;
            font-weight: 600;

            transition: .2s ease;
        }

        .nav a:hover {
            color: #fff;

            background: rgba(56,189,248,.06);
            border-color: rgba(56,189,248,.10);

            transform: translateX(2px);
        }

        .nav a.active {
            color: #fff;

            background:
                linear-gradient(
                    90deg,
                    rgba(56,189,248,.12),
                    rgba(168,85,247,.06)
                );

            border-color: rgba(56,189,248,.20);

            box-shadow:
                inset 3px 0 0 var(--blue),
                0 0 20px rgba(56,189,248,.04);
        }

        .nav-icon {
            width: 22px;
            text-align: center;
            font-size: 15px;
        }

        .logout {
            position: absolute;
            bottom: 20px;
            left: 14px;
            right: 14px;
        }

        .logout a {
            color: #ef7777;
        }

        .logout a:hover {
            color: #fff;
            background: rgba(239,68,68,.08);
            border-color: rgba(239,68,68,.15);
        }

        /* ==============================================================
           CONTENT
        ============================================================== */

        .content {
            margin-left: var(--sidebar-width);
            padding: 35px;
            max-width: 1500px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0;

            font-family: 'Orbitron', sans-serif;

            font-size: 25px;
            font-weight: 800;

            letter-spacing: 1px;

            color: #fff;
        }

        .page-header p {
            margin: 8px 0 0;

            color: var(--muted);
            font-size: 15px;
        }

        /* ==============================================================
           WARNING
        ============================================================== */

        .danger-banner {
            padding: 17px 20px;

            margin-bottom: 24px;

            border: 1px solid rgba(239,68,68,.25);
            border-left: 4px solid var(--red);

            border-radius: 10px;

            background:
                linear-gradient(
                    90deg,
                    rgba(127,29,29,.20),
                    rgba(239,68,68,.04)
                );
        }

        .danger-banner strong {
            display: block;

            color: #ff7b7b;

            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1px;

            margin-bottom: 5px;
        }

        .danger-banner span {
            color: #aeb9c8;
            font-size: 14px;
        }

        /* ==============================================================
           CARD
        ============================================================== */

        .card {
            background:
                linear-gradient(
                    145deg,
                    rgba(17,23,34,.96),
                    rgba(10,14,22,.96)
                );

            border: 1px solid var(--border);
            border-radius: 12px;

            box-shadow:
                0 15px 40px rgba(0,0,0,.20);

            overflow: hidden;

            margin-bottom: 24px;
        }

        .card-header {
            padding: 19px 22px;

            border-bottom: 1px solid rgba(255,255,255,.06);

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h2 {
            margin: 0;

            font-family: 'Orbitron', sans-serif;

            font-size: 14px;
            letter-spacing: .8px;
        }

        .card-body {
            padding: 24px;
        }

        /* ==============================================================
           FORM
        ============================================================== */

        .search-form {
            display: flex;
            gap: 12px;
        }

        .input {
            flex: 1;

            min-width: 0;

            padding: 13px 15px;

            background: #080c13;

            color: #fff;

            border: 1px solid #1e2a3b;
            border-radius: 8px;

            outline: none;

            font-family: 'Rajdhani', sans-serif;
            font-size: 15px;
            font-weight: 600;

            transition: .2s ease;
        }

        .input:focus {
            border-color: rgba(56,189,248,.5);

            box-shadow:
                0 0 0 3px rgba(56,189,248,.07);
        }

        .btn {
            border: 0;
            border-radius: 8px;

            padding: 12px 20px;

            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            font-weight: 700;

            letter-spacing: .5px;

            cursor: pointer;

            transition: .2s ease;
        }

        .btn-blue {
            color: #031018;

            background: linear-gradient(
                135deg,
                #38bdf8,
                #22d3ee
            );

            box-shadow:
                0 0 20px rgba(56,189,248,.12);
        }

        .btn-blue:hover {
            transform: translateY(-1px);

            box-shadow:
                0 0 25px rgba(56,189,248,.25);
        }

        .btn-danger {
            color: #fff;

            background: linear-gradient(
                135deg,
                #dc2626,
                #991b1b
            );

            border: 1px solid rgba(239,68,68,.35);
        }

        .btn-danger:hover {
            transform: translateY(-1px);

            box-shadow:
                0 0 25px rgba(239,68,68,.20);
        }

        .btn-danger:disabled {
            opacity: .45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* ==============================================================
           MESSAGE
        ============================================================== */

        .message {
            margin-bottom: 22px;

            padding: 14px 17px;

            border-radius: 8px;

            font-size: 14px;
            font-weight: 600;
        }

        .message.success {
            color: #8ff0b0;

            background: rgba(34,197,94,.07);
            border: 1px solid rgba(34,197,94,.18);
        }

        .message.warning {
            color: #ffd17a;

            background: rgba(245,158,11,.07);
            border: 1px solid rgba(245,158,11,.20);
        }

        .message.error {
            color: #ff9494;

            background: rgba(239,68,68,.07);
            border: 1px solid rgba(239,68,68,.20);
        }

        .message.deleted {
            color: #8ff0b0;

            background:
                linear-gradient(
                    90deg,
                    rgba(34,197,94,.10),
                    rgba(56,189,248,.04)
                );

            border: 1px solid rgba(34,197,94,.22);
        }

        /* ==============================================================
           CHARACTER
        ============================================================== */

        .character-info {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 24px;
        }

        .info-box {
            padding: 16px;

            background: rgba(5,8,13,.55);

            border: 1px solid rgba(255,255,255,.06);
            border-radius: 8px;
        }

        .info-label {
            color: #647286;

            text-transform: uppercase;

            font-size: 10px;
            letter-spacing: 1.5px;

            margin-bottom: 6px;
        }

        .info-value {
            color: #fff;

            font-family: 'Orbitron', sans-serif;

            font-size: 14px;
            font-weight: 700;

            word-break: break-word;
        }

        .status-offline {
            color: var(--green);
        }

        .status-online {
            color: var(--red);
        }

        /* ==============================================================
           TABLE
        ============================================================== */

        .records-table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 10px;
        }

        .records-table th {
            text-align: left;

            color: #657287;

            font-family: 'Orbitron', sans-serif;

            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;

            padding: 11px 12px;

            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .records-table td {
            padding: 10px 12px;

            color: #b7c2d1;

            font-size: 13px;

            border-bottom: 1px solid rgba(255,255,255,.04);
        }

        .records-table tr:hover td {
            background: rgba(56,189,248,.025);
        }

        .count {
            font-family: 'Orbitron', sans-serif;
            color: #fff;
            font-size: 12px;
        }

        .count.zero {
            color: #526073;
        }

        .count.error {
            color: var(--red);
        }

        /* ==============================================================
           CONFIRM
        ============================================================== */

        .confirm-box {
            padding: 20px;

            background:
                linear-gradient(
                    145deg,
                    rgba(127,29,29,.10),
                    rgba(5,8,13,.55)
                );

            border: 1px solid rgba(239,68,68,.16);
            border-radius: 10px;
        }

        .confirm-box h3 {
            margin: 0 0 7px;

            font-family: 'Orbitron', sans-serif;

            font-size: 13px;

            color: #ff8a8a;
        }

        .confirm-box p {
            margin: 0 0 17px;

            color: #9aa7b9;

            font-size: 14px;
        }

        .confirm-form {
            display: flex;
            gap: 12px;
        }

        .confirm-form .input {
            flex: 1;
        }

        .confirm-name {
            color: #fff;
            font-weight: 700;
        }

        /* ==============================================================
           REPORT
        ============================================================== */

        .success-title {
            color: #7df2a4;

            font-family: 'Orbitron', sans-serif;

            font-size: 16px;

            margin-bottom: 8px;
        }

        .success-sub {
            color: #8d9bad;

            font-size: 14px;

            margin-bottom: 22px;
        }

        .report-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 8px 25px;
        }

        .report-row {
            display: flex;
            justify-content: space-between;

            padding: 9px 0;

            border-bottom: 1px solid rgba(255,255,255,.05);
        }

        .report-row span:first-child {
            color: #8996a8;
            font-size: 13px;
        }

        .report-row span:last-child {
            color: #fff;

            font-family: 'Orbitron', sans-serif;

            font-size: 11px;
        }

        /* ==============================================================
           RESPONSIVE
        ============================================================== */

        @media (max-width: 1000px) {

            .character-info {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {

            .sidebar {
                position: relative;

                width: 100%;
                height: auto;

                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .logout {
                position: relative;

                left: auto;
                right: auto;
                bottom: auto;

                margin-top: 15px;
            }

            .content {
                margin-left: 0;
                padding: 20px;
            }

            .search-form,
            .confirm-form {
                flex-direction: column;
            }

            .character-info {
                grid-template-columns: 1fr;
            }

            .records-table {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<!-- ==============================================================
     SIDEBAR
================================================================ -->

<aside class="sidebar">

    <div class="brand">
        <div class="brand-title">VIRTUAL BOOST</div>
        <div class="brand-sub">Admin Panel</div>
    </div>

    <div class="nav-title">Administração</div>

    <nav class="nav">

        <a href="index.php">
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a href="players.php">
            <span class="nav-icon">♙</span>
            <span>Players</span>
        </a>

        <a href="donates.php">
            <span class="nav-icon">◆</span>
            <span>Donates</span>
        </a>

        <a href="anticheat.php">
            <span class="nav-icon">◈</span>
            <span>Anti-Cheat</span>
        </a>

        <a href="punishments.php">
            <span class="nav-icon">⚔</span>
            <span>Punishments</span>
        </a>

        <a href="economy.php">
            <span class="nav-icon">₳</span>
            <span>Economy</span>
        </a>

        <a href="delete_character.php" class="active">
            <span class="nav-icon">✕</span>
            <span>Excluir Personagem</span>
        </a>

        <a href="castles.php">
            <span class="nav-icon">♜</span>
            <span>Castles</span>
        </a>

        <a href="logs.php">
            <span class="nav-icon">▤</span>
            <span>Logs</span>
        </a>

        <a href="administrators.php">
            <span class="nav-icon">♟</span>
            <span>Administrators</span>
        </a>

        <a href="settings.php">
            <span class="nav-icon">⚙</span>
            <span>Settings</span>
        </a>

    </nav>

    <div class="logout">
        <nav class="nav">
            <a href="logout.php">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

</aside>


<!-- ==============================================================
     CONTENT
================================================================ -->

<main class="content">

    <div class="page-header">
        <h1>Excluir Personagem</h1>
        <p>Remoção permanente dos dados funcionais de um personagem.</p>
    </div>


    <div class="danger-banner">
        <strong>⚠ AÇÃO PERMANENTE</strong>

        <span>
            Esta ferramenta remove permanentemente o personagem e seus
            registros relacionados. Os registros de <b>logs</b> e
            <b>character_logs</b> são preservados para auditoria.
        </span>
    </div>


    <?php if ($message !== ''): ?>

        <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>


    <?php if (isset($deletionReport)): ?>

        <!-- ==========================================================
             RELATÓRIO DE EXCLUSÃO
        =========================================================== -->

        <section class="card">

            <div class="card-header">
                <h2>EXCLUSÃO CONCLUÍDA</h2>
            </div>

            <div class="card-body">

                <div class="success-title">
                    ✓ Personagem removido permanentemente
                </div>

                <div class="success-sub">
                    O personagem foi removido da tabela
                    <b>characters</b> e os registros funcionais
                    relacionados foram excluídos.
                </div>

                <div class="character-info">

                    <div class="info-box">
                        <div class="info-label">Personagem</div>

                        <div class="info-value">
                            <?= htmlspecialchars(
                                $deletionReport['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">Object ID</div>

                        <div class="info-value">
                            <?= (int)$deletionReport['obj_id'] ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">Conta</div>

                        <div class="info-value">
                            <?= htmlspecialchars(
                                $deletionReport['account'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">Logs</div>

                        <div class="info-value" style="color:#f5c451;">
                            PRESERVADOS
                        </div>
                    </div>

                </div>


                <div class="report-grid">

                    <?php foreach ($deletionReport['counts'] as $table => $count): ?>

                        <div class="report-row">

                            <span>
                                <?= htmlspecialchars(
                                    $table,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <span>
                                <?= (int)$count ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- ==============================================================
         PESQUISA
    =========================================================== -->

    <section class="card">

        <div class="card-header">
            <h2>LOCALIZAR PERSONAGEM</h2>
        </div>

        <div class="card-body">

            <form method="POST" class="search-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="search"
                >

                <input
                    type="text"
                    name="char_name"
                    class="input"
                    placeholder="Digite o nome exato do personagem..."
                    maxlength="35"
                    autocomplete="off"
                    required
                    value="<?= htmlspecialchars(
                        (string)($_POST['char_name'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-blue"
                >
                    PESQUISAR
                </button>

            </form>

        </div>

    </section>


    <?php if ($character): ?>

        <!-- ==========================================================
             INFORMAÇÕES DO PERSONAGEM
        =========================================================== -->

        <section class="card">

            <div class="card-header">
                <h2>PERSONAGEM ENCONTRADO</h2>
            </div>

            <div class="card-body">

                <div class="character-info">

                    <div class="info-box">

                        <div class="info-label">
                            Personagem
                        </div>

                        <div class="info-value">
                            <?= htmlspecialchars(
                                $character['char_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Object ID
                        </div>

                        <div class="info-value">
                            <?= (int)$character['obj_Id'] ?>
                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Conta
                        </div>

                        <div class="info-value">
                            <?= htmlspecialchars(
                                $character['account_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Status
                        </div>

                        <div class="info-value
                            <?= ((int)$character['online'] === 1)
                                ? 'status-online'
                                : 'status-offline'
                            ?>
                        ">

                            <?= ((int)$character['online'] === 1)
                                ? 'ONLINE'
                                : 'OFFLINE'
                            ?>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     REGISTROS
                =================================================== -->

                <h3 style="
                    font-family:'Orbitron',sans-serif;
                    font-size:12px;
                    letter-spacing:.8px;
                    margin:0 0 5px;
                ">
                    REGISTROS RELACIONADOS
                </h3>

                <p style="
                    color:#718096;
                    font-size:13px;
                    margin:0 0 10px;
                ">
                    Total localizado:
                    <strong style="color:#fff;">
                        <?= $totalPreview ?>
                    </strong>
                    registros funcionais.
                    <br>
                    <span style="color:#f5c451;">
                        logs e character_logs não entram nesta contagem
                        porque serão preservados.
                    </span>
                </p>


                <table class="records-table">

                    <thead>
                        <tr>
                            <th>Tabela</th>
                            <th>Coluna</th>
                            <th>Registros</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($relatedTables as $table => $column): ?>

                            <?php
                            $count = $previewCounts[$table] ?? 0;
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $table,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $column,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>

                                    <?php if ($count === -1): ?>

                                        <span class="count error">
                                            ERRO
                                        </span>

                                    <?php elseif ($count === 0): ?>

                                        <span class="count zero">
                                            0
                                        </span>

                                    <?php else: ?>

                                        <span class="count">
                                            <?= $count ?>
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ==========================================================
             CONFIRMAÇÃO
        =========================================================== -->

        <?php if ((int)$character['online'] !== 1): ?>

            <section class="card">

                <div class="card-header">
                    <h2>CONFIRMAÇÃO DE EXCLUSÃO</h2>
                </div>

                <div class="card-body">

                    <div class="confirm-box">

                        <h3>
                            ATENÇÃO: ESTA AÇÃO NÃO PODE SER DESFEITA
                        </h3>

                        <p>
                            Para confirmar, digite exatamente o nome
                            <span class="confirm-name">
                                <?= htmlspecialchars(
                                    $character['char_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                            no campo abaixo.
                        </p>


                        <form
                            method="POST"
                            class="confirm-form"
                            onsubmit="
                                return confirm(
                                    'ATENÇÃO!\\n\\n' +
                                    'O personagem será excluído permanentemente.\\n' +
                                    'Esta ação NÃO pode ser desfeita.\\n\\n' +
                                    'Deseja realmente continuar?'
                                );
                            "
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >

                            <input
                                type="hidden"
                                name="char_name"
                                value="<?= htmlspecialchars(
                                    $character['char_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="text"
                                name="confirmation"
                                class="input"
                                placeholder="Digite o nome exato para confirmar..."
                                autocomplete="off"
                                required
                            >

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                EXCLUIR PERMANENTEMENTE
                            </button>

                        </form>

                    </div>

                </div>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</main>

</body>
</html>
