<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

global $pdo;

$success = '';
$error = '';

/*
 * Todas as operações administrativas de Donate exigem a senha do servidor.
 * Configure DONATE_ACTION_PASSWORD no ambiente da hospedagem; a senha não fica
 * gravada no repositório público.
 */
$donateAction = strtoupper(trim((string)($_POST['action'] ?? '')));
$donateActions = ['CLEAR_DONATE_HISTORY', 'CLEAR_APPROVED_PURCHASE_HISTORY', 'APPROVE_ORDER', 'REJECT_ORDER', 'DELETE_ORDER', 'DELIVERY', 'WITHDRAW'];
$donateActionAuthorized = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($donateAction, $donateActions, true)) {
    $configuredPassword = getenv('DONATE_ACTION_PASSWORD');
    $providedPassword = (string)($_POST['donate_password'] ?? '');
    if ($configuredPassword === false || $configuredPassword === '') {
        $donateActionAuthorized = false;
        $error = 'A senha de segurança do Donate ainda não foi configurada na hospedagem.';
    } elseif (!hash_equals((string)$configuredPassword, $providedPassword)) {
        $donateActionAuthorized = false;
        $error = 'Senha incorreta. Nenhuma operação de Donate foi executada.';
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$perPage = 20;
$offset = ($page - 1) * $perPage;

$DONATE_ITEM_ID = 37000;

/* Pedidos feitos pela página de compra. A aprovação e a entrega são manuais. */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS donate_purchase_orders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        account_name VARCHAR(45) NOT NULL,
        character_id INT NOT NULL,
        character_name VARCHAR(35) NOT NULL,
        package_id VARCHAR(40) NOT NULL,
        coins INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        mp_preference_id VARCHAR(100) NULL,
        mp_payment_id VARCHAR(100) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME NULL,
        delivered_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_mp_payment_id (mp_payment_id),
        KEY idx_account_status (account_name, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");


/*
|--------------------------------------------------------------------------
| Operações com Donate
|--------------------------------------------------------------------------
*/

/* Limpar o histórico de entregas/retiradas exige senha configurada no servidor e CSRF válido. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $donateActionAuthorized && strtoupper(trim((string)($_POST['action'] ?? ''))) === 'CLEAR_DONATE_HISTORY') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido.';
    } else {
        try {
                $pdo->beginTransaction();
                $deletedRows = (int)$pdo->query('SELECT COUNT(*) FROM admin_donate_log')->fetchColumn();
                $pdo->exec('DELETE FROM admin_donate_log');
                $pdo->commit();

                audit('DONATE_HISTORY_CLEARED', 'Histórico de entregas/retiradas do Donate limpo pela administração. Registros removidos: ' . $deletedRows);
                $success = 'Histórico de Donate limpo. Registros removidos: ' . $deletedRows . '. Os saldos dos personagens e os pedidos de compra não foram alterados.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Não foi possível limpar o histórico. Tente novamente.';
        }
    }
}


/* Apagar o histórico de pedidos aprovados/entregues exige senha do servidor e CSRF válido. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $donateActionAuthorized && $donateAction === 'CLEAR_APPROVED_PURCHASE_HISTORY') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido.';
    } else {
        try {
            $pdo->beginTransaction();
            $deletedRows = (int)$pdo->query("
                SELECT COUNT(*)
                FROM donate_purchase_orders
                WHERE status IN ('paid', 'delivered', 'approved') OR delivered_at IS NOT NULL
            ")->fetchColumn();
            $pdo->exec("
                DELETE FROM donate_purchase_orders
                WHERE status IN ('paid', 'delivered', 'approved') OR delivered_at IS NOT NULL
            ");
            $pdo->commit();

            audit('APPROVED_DONATE_PURCHASE_HISTORY_CLEARED', 'Histórico de pedidos aprovados/entregues removido pela administração. Registros removidos: ' . $deletedRows . '. Saldos dos personagens não foram alterados.');
            $success = 'Histórico de pedidos aprovados/entregues apagado. Registros removidos: ' . $deletedRows . '. Os saldos dos personagens não foram alterados.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Não foi possível apagar o histórico de pedidos aprovados/entregues. Tente novamente.';
        }
    }
}

/* Aprovação manual de pedido: a staff deve conferir o pagamento no Mercado Pago antes de clicar. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $donateActionAuthorized && strtoupper(trim((string)($_POST['action'] ?? ''))) === 'APPROVE_ORDER') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido.';
    } else {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId < 1) {
            $error = 'Pedido inválido.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT * FROM donate_purchase_orders WHERE id = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([(int)$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$order) {
                    throw new RuntimeException('Pedido não encontrado.');
                }
                if ((string)$order['status'] !== 'pending') {
                    throw new RuntimeException('Este pedido já foi processado.');
                }

                $stmt = $pdo->prepare('SELECT obj_Id, char_name, account_name FROM characters WHERE obj_Id = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([(int)$order['character_id']]);
                $character = $stmt->fetch(PDO::FETCH_ASSOC);
                if (
                    !$character
                    || strcasecmp(trim((string)$character['account_name']), trim((string)$order['account_name'])) !== 0
                ) {
                    throw new RuntimeException('O personagem não corresponde à conta do pedido. Confira a conta vinculada ao personagem e ao pedido.');
                }

                $coins = (int)$order['coins'];
                if ($coins < 1 || $coins > 1000000000) {
                    throw new RuntimeException('Quantidade de Donate Coins inválida no pedido.');
                }

                $stmt = $pdo->prepare("
                    SELECT object_id, count
                    FROM items
                    WHERE owner_id = ? AND item_id = ? AND loc = 'INVENTORY' AND count > 0
                    ORDER BY object_id ASC LIMIT 1 FOR UPDATE
                ");
                $stmt->execute([(int)$character['obj_Id'], $DONATE_ITEM_ID]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $newCount = (int)$item['count'] + $coins;
                    if ($newCount > 2147483647) {
                        throw new RuntimeException('O saldo ultrapassaria o limite permitido.');
                    }
                    $stmt = $pdo->prepare('UPDATE items SET count = ? WHERE object_id = ? LIMIT 1');
                    $stmt->execute([$newCount, (int)$item['object_id']]);
                } else {
                    $objectId = (int)$pdo->query('SELECT COALESCE(MAX(object_id), 0) + 1 FROM items')->fetchColumn();
                    if ($objectId <= 0) {
                        throw new RuntimeException('Não foi possível gerar o object_id.');
                    }
                    $stmt = $pdo->prepare("
                        INSERT INTO items (
                            object_id, owner_id, item_id, count, enchant_level, loc, loc_data,
                            life_time, augmentation_id, attribute_fire, attribute_water,
                            attribute_wind, attribute_earth, attribute_holy, attribute_unholy,
                            custom_type1, custom_type2, custom_flags, agathion_energy, visual_item_id
                        ) VALUES (?, ?, ?, ?, 0, 'INVENTORY', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
                    ");
                    $stmt->execute([$objectId, (int)$character['obj_Id'], $DONATE_ITEM_ID, $coins]);
                }

                $admin = current_admin();
                $adminId = $admin ? (int)$admin['id'] : (int)($_SESSION['admin_id'] ?? 0);
                $adminUsername = $admin ? (string)$admin['username'] : (string)($_SESSION['admin_username'] ?? 'Admin');
                $reason = 'Pedido web #' . (int)$order['id'] . ' aprovado manualmente após conferência do pagamento.';
                $stmt = $pdo->prepare("
                    INSERT INTO admin_donate_log
                    (admin_id, admin_username, action, character_id, character_name, account_name, amount, reason, ip_address, created_at)
                    VALUES (?, ?, 'DELIVERY', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $adminId, $adminUsername, (int)$character['obj_Id'],
                    (string)$character['char_name'], (string)$character['account_name'],
                    $coins, $reason, $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                audit(
                    'DONATE_ORDER_APPROVED',
                    'Pedido #' . (int)$order['id'] . ' | Personagem: ' . $character['char_name'] .
                    ' | Quantidade: ' . $coins . ' | Valor: R$ ' . number_format((float)$order['amount'], 2, ',', '.')
                );

                $stmt = $pdo->prepare("UPDATE donate_purchase_orders SET status = 'delivered', paid_at = NOW(), delivered_at = NOW() WHERE id = ? AND status = 'pending'");
                $stmt->execute([(int)$order['id']]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('O pedido foi alterado por outra operação. Atualize a página.');
                }
                $pdo->commit();
                $success = 'Pedido #' . (int)$order['id'] . ' aprovado e ' . number_format($coins, 0, ',', '.') . ' Donate Coins entregues para ' . $character['char_name'] . '.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            }
        }
    }
}


/* Recusar ou excluir pedidos sem tocar nas moedas dos jogadores. */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $donateActionAuthorized
    && in_array(strtoupper(trim((string)($_POST['action'] ?? ''))), ['REJECT_ORDER', 'DELETE_ORDER'], true)
) {
    $orderAction = strtoupper(trim((string)($_POST['action'] ?? '')));
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido.';
    } else {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId < 1) {
            $error = 'Pedido inválido.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id, status FROM donate_purchase_orders WHERE id = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([(int)$orderId]);
                $targetOrder = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$targetOrder) {
                    throw new RuntimeException('Pedido não encontrado.');
                }
                $status = (string)$targetOrder['status'];
                if ($orderAction === 'REJECT_ORDER') {
                    if ($status !== 'pending') {
                        throw new RuntimeException('Só é possível recusar pedidos pendentes.');
                    }
                    $stmt = $pdo->prepare("UPDATE donate_purchase_orders SET status = 'rejected' WHERE id = ? AND status = 'pending'");
                    $stmt->execute([(int)$orderId]);
                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException('Não foi possível recusar o pedido. Atualize a página.');
                    }
                    $pdo->commit();
                    audit('DONATE_ORDER_REJECTED', 'Pedido web #' . (int)$orderId . ' recusado pela administração.');
                    $success = 'Pedido #' . (int)$orderId . ' recusado. Nenhuma Donate Coin foi entregue.';
                } else {
                    if (!in_array($status, ['pending', 'rejected'], true)) {
                        throw new RuntimeException('Pedidos já entregues não podem ser excluídos.');
                    }
                    $stmt = $pdo->prepare("DELETE FROM donate_purchase_orders WHERE id = ? AND status IN ('pending', 'rejected')");
                    $stmt->execute([(int)$orderId]);
                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException('Não foi possível excluir o pedido. Atualize a página.');
                    }
                    $pdo->commit();
                    audit('DONATE_ORDER_DELETED', 'Pedido web #' . (int)$orderId . ' excluído pela administração.');
                    $success = 'Pedido #' . (int)$orderId . ' excluído.';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $donateActionAuthorized && !in_array(strtoupper(trim((string)($_POST['action'] ?? ''))), ['APPROVE_ORDER', 'REJECT_ORDER', 'DELETE_ORDER', 'CLEAR_DONATE_HISTORY'], true)) {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {

        $error = 'Token de segurança inválido.';

    } else {

        $action = strtoupper(
            trim((string)($_POST['action'] ?? ''))
        );

        $characterName = trim(
            (string)($_POST['character'] ?? '')
        );

        $amount = (int)($_POST['amount'] ?? 0);

        $reason = trim(
            (string)($_POST['reason'] ?? '')
        );


        /*
        |--------------------------------------------------------------------------
        | Validações
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $action,
            ['DELIVERY', 'WITHDRAW'],
            true
        )) {

            $error = 'Operação inválida.';

        } elseif ($characterName === '') {

            $error = 'Informe o personagem.';

        } elseif ($amount <= 0) {

            $error = 'A quantidade deve ser maior que zero.';

        } elseif ($amount > 1000000000) {

            $error = 'Quantidade de Donate inválida.';

        } elseif ($reason === '') {

            $error = 'O motivo é obrigatório.';

        } else {

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Localiza e trava o personagem
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT
                        obj_Id,
                        char_name,
                        account_name
                    FROM characters
                    WHERE char_name = ?
                    LIMIT 1
                    FOR UPDATE
                ");

                $stmt->execute([
                    $characterName
                ]);

                $character = $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


                if (!$character) {

                    throw new RuntimeException(
                        'Personagem não encontrado.'
                    );
                }


                $ownerId = (int)$character['obj_Id'];


                /*
                |--------------------------------------------------------------------------
                | Localiza o Donate no inventário
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT
                        object_id,
                        count
                    FROM items
                    WHERE owner_id = ?
                      AND item_id = ?
                      AND loc = 'INVENTORY'
                      AND count > 0
                    ORDER BY object_id ASC
                    LIMIT 1
                    FOR UPDATE
                ");

                $stmt->execute([
                    $ownerId,
                    $DONATE_ITEM_ID
                ]);

                $donateItem = $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


                /*
                |--------------------------------------------------------------------------
                | ENTREGA
                |--------------------------------------------------------------------------
                */

                if ($action === 'DELIVERY') {

                    if ($donateItem) {

                        $newCount =
                            (int)$donateItem['count']
                            + $amount;


                        $stmt = $pdo->prepare("
                            UPDATE items
                            SET count = ?
                            WHERE object_id = ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            $newCount,
                            (int)$donateItem['object_id']
                        ]);

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Cria o item caso o personagem ainda não tenha Donate
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $pdo->query("
                            SELECT
                                COALESCE(
                                    MAX(object_id),
                                    0
                                ) + 1
                            FROM items
                        ");

                        $objectId = (int)$stmt->fetchColumn();


                        if ($objectId <= 0) {

                            throw new RuntimeException(
                                'Não foi possível gerar o object_id.'
                            );
                        }


                        $stmt = $pdo->prepare("
                            INSERT INTO items
                            (
                                object_id,
                                owner_id,
                                item_id,
                                count,
                                enchant_level,
                                loc,
                                loc_data,
                                life_time,
                                augmentation_id,
                                attribute_fire,
                                attribute_water,
                                attribute_wind,
                                attribute_earth,
                                attribute_holy,
                                attribute_unholy,
                                custom_type1,
                                custom_type2,
                                custom_flags,
                                agathion_energy,
                                visual_item_id
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                0,
                                'INVENTORY',
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0
                            )
                        ");

                        $stmt->execute([
                            $objectId,
                            $ownerId,
                            $DONATE_ITEM_ID,
                            $amount
                        ]);
                    }


                    $operationText = 'ENTREGA';

                    $success =
                        'Foram entregues '
                        . number_format(
                            $amount,
                            0,
                            ',',
                            '.'
                        )
                        . ' Donate Coins para '
                        . $character['char_name']
                        . '.';


                /*
                |--------------------------------------------------------------------------
                | RETIRADA
                |--------------------------------------------------------------------------
                */

                } else {

                    if (!$donateItem) {

                        throw new RuntimeException(
                            'Este personagem não possui Donate Coins.'
                        );
                    }


                    $currentCount =
                        (int)$donateItem['count'];


                    if ($currentCount < $amount) {

                        throw new RuntimeException(
                            'Saldo insuficiente. '
                            . 'O personagem possui '
                            . number_format(
                                $currentCount,
                                0,
                                ',',
                                '.'
                            )
                            . ' Donate Coins.'
                        );
                    }


                    $newCount =
                        $currentCount - $amount;


                    if ($newCount > 0) {

                        /*
                        |--------------------------------------------------------------------------
                        | Apenas reduz o saldo
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $pdo->prepare("
                            UPDATE items
                            SET count = ?
                            WHERE object_id = ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            $newCount,
                            (int)$donateItem['object_id']
                        ]);

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Saldo chegou a zero.
                        | Remove o item do inventário.
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $pdo->prepare("
                            DELETE FROM items
                            WHERE object_id = ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            (int)$donateItem['object_id']
                        ]);
                    }


                    $operationText = 'RETIRADA';

                    $success =
                        'Foram retirados '
                        . number_format(
                            $amount,
                            0,
                            ',',
                            '.'
                        )
                        . ' Donate Coins de '
                        . $character['char_name']
                        . '.';
                }


                /*
                |--------------------------------------------------------------------------
                | Administrador atual
                |--------------------------------------------------------------------------
                */

                $admin = current_admin();

                $adminId = $admin
                    ? (int)$admin['id']
                    : (int)(
                        $_SESSION['admin_id'] ?? 0
                    );

                $adminUsername = $admin
                    ? (string)$admin['username']
                    : (string)(
                        $_SESSION['admin_username']
                        ?? 'Admin'
                    );


                /*
                |--------------------------------------------------------------------------
                | Histórico específico do Donate
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO admin_donate_log
                    (
                        admin_id,
                        admin_username,
                        action,
                        character_id,
                        character_name,
                        account_name,
                        amount,
                        reason,
                        ip_address,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

                $stmt->execute([
                    $adminId,
                    $adminUsername,
                    $action,
                    $ownerId,
                    $character['char_name'],
                    $character['account_name'],
                    $amount,
                    $reason,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);


                /*
                |--------------------------------------------------------------------------
                | Auditoria geral
                |--------------------------------------------------------------------------
                */

                audit(
                    'DONATE_' . $action,
                    'Personagem: '
                    . $character['char_name']
                    . ' | Conta: '
                    . $character['account_name']
                    . ' | Quantidade: '
                    . $amount
                    . ' | Motivo: '
                    . $reason
                );


                $pdo->commit();

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = $e->getMessage();
            }
        }
    }
}


$pendingPurchaseOrders = $pdo->query("
    SELECT id, account_name, character_name, coins, amount, created_at, mp_preference_id, status
    FROM donate_purchase_orders
    WHERE status = 'pending'
    ORDER BY created_at ASC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$approvedPurchaseOrders = $pdo->query("
    SELECT id, account_name, character_name, coins, amount, created_at, paid_at, delivered_at, status, mp_payment_id
    FROM donate_purchase_orders
    WHERE status IN ('paid', 'delivered', 'approved') OR delivered_at IS NOT NULL
    ORDER BY COALESCE(delivered_at, paid_at, created_at) DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$rejectedPurchaseOrders = $pdo->query("
    SELECT id, account_name, character_name, coins, amount, created_at
    FROM donate_purchase_orders
    WHERE status = 'rejected'
    ORDER BY id DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Estatísticas reais da moeda
|--------------------------------------------------------------------------
*/

$totalDonate = (int)$pdo->query("
    SELECT COALESCE(SUM(count), 0)
    FROM items
    WHERE item_id = 37000
      AND loc = 'INVENTORY'
")->fetchColumn();


$totalOwners = (int)$pdo->query("
    SELECT COUNT(*)
    FROM
    (
        SELECT owner_id
        FROM items
        WHERE item_id = 37000
          AND loc = 'INVENTORY'
          AND count > 0
        GROUP BY owner_id
    ) x
")->fetchColumn();


$largestBalance = (int)$pdo->query("
    SELECT COALESCE(MAX(count), 0)
    FROM items
    WHERE item_id = 37000
      AND loc = 'INVENTORY'
")->fetchColumn();


$averageBalance = $totalOwners > 0
    ? (int)floor(
        $totalDonate / $totalOwners
    )
    : 0;


/*
|--------------------------------------------------------------------------
| Total de entradas e saídas pelo painel
|--------------------------------------------------------------------------
*/

$totalDelivered = (int)$pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM admin_donate_log
    WHERE action = 'DELIVERY'
")->fetchColumn();


$totalWithdrawn = (int)$pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM admin_donate_log
    WHERE action = 'WITHDRAW'
")->fetchColumn();


$totalDeliveries = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admin_donate_log
    WHERE action = 'DELIVERY'
")->fetchColumn();


$totalWithdrawals = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admin_donate_log
    WHERE action = 'WITHDRAW'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| Pesquisa
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "
        (
            c.char_name LIKE ?
            OR c.account_name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
}


$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE '
        . implode(' AND ', $where);
}


/*
|--------------------------------------------------------------------------
| Total de jogadores
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM items i

    INNER JOIN characters c
        ON c.obj_Id = i.owner_id

    $whereSql

    " . (
        $whereSql !== ''
            ? 'AND'
            : 'WHERE'
    ) . "

    i.item_id = 37000
    AND i.loc = 'INVENTORY'
    AND i.count > 0
";

$countStmt = $pdo->prepare(
    $countSql
);

$countStmt->execute($params);

$totalFiltered =
    (int)$countStmt->fetchColumn();


$totalPages = max(
    1,
    (int)ceil(
        $totalFiltered / $perPage
    )
);


if ($page > $totalPages) {

    $page = $totalPages;

    $offset =
        ($page - 1) * $perPage;
}


/*
|--------------------------------------------------------------------------
| Jogadores
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        i.object_id,
        i.owner_id,
        i.count,
        c.char_name,
        c.account_name,
        c.online
    FROM items i

    INNER JOIN characters c
        ON c.obj_Id = i.owner_id

    $whereSql

    " . (
        $whereSql !== ''
            ? 'AND'
            : 'WHERE'
    ) . "

    i.item_id = 37000
    AND i.loc = 'INVENTORY'
    AND i.count > 0

    ORDER BY
        i.count DESC,
        c.char_name ASC

    LIMIT $perPage
    OFFSET $offset
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$donatePlayers =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Histórico
|--------------------------------------------------------------------------
*/

$historyStmt = $pdo->query("
    SELECT
        id,
        admin_username,
        action,
        character_name,
        account_name,
        amount,
        reason,
        ip_address,
        created_at
    FROM admin_donate_log
    ORDER BY id DESC
    LIMIT 50
");

$history =
    $historyStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function donateNumber($value): string
{
    return number_format(
        (float)$value,
        0,
        ',',
        '.'
    );
}


function donatePaginationUrl(
    int $page
): string {

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

<title>Donate — L2 Virtual Boost</title>

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
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap"
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

.content {
    margin-left: 230px;
    padding: 30px;
    min-height: 100vh;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    margin: 0;
    font-family: 'Orbitron', sans-serif;
    font-size: 24px;
    color: #fff;
}

.header p {
    margin: 6px 0 0;
    color: #687989;
    font-size: 14px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.stats.second {
    grid-template-columns: repeat(4, 1fr);
    margin-bottom: 22px;
}

.card {
    background: #0b121c;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 10px;
    padding: 20px;
}

.card.gold {
    border-color: rgba(213,169,58,.15);
}

.card.red {
    border-color: rgba(255,90,90,.15);
}

.card-title {
    color: #687989;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 1.5px;
}

.card-value {
    font-family: 'Orbitron', sans-serif;
    color: #39d5ff;
    font-size: 24px;
    margin-top: 8px;
}

.card.gold .card-value {
    color: #d5a93a;
}

.card.green .card-value {
    color: #6ee7a8;
}

.card.red .card-value {
    color: #ff7070;
}

.alert {
    border-radius: 6px;
    padding: 12px 15px;
    margin-bottom: 15px;
    font-size: 14px;
}

.alert.success {
    color: #6ee7a8;
    background: rgba(110,231,168,.07);
    border: 1px solid rgba(110,231,168,.15);
}

.alert.error {
    color: #ff7f7f;
    background: rgba(255,127,127,.07);
    border: 1px solid rgba(255,127,127,.15);
}

.operations {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 22px;
}

.operation {
    background: #0b121c;
    border-radius: 10px;
    padding: 22px;
}

.operation.delivery {
    border: 1px solid rgba(213,169,58,.18);
}

.operation.withdraw {
    border: 1px solid rgba(255,90,90,.18);
}

.operation-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 14px;
    margin-bottom: 17px;
}

.operation.delivery .operation-title {
    color: #d5a93a;
}

.operation.withdraw .operation-title {
    color: #ff7070;
}

.operation-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.operation-form .full {
    grid-column: 1 / -1;
}

.field label {
    display: block;
    color: #647688;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.field input {
    width: 100%;
    background: #070b12;
    border: 1px solid #1c2b3a;
    border-radius: 6px;
    padding: 11px 12px;
    color: #fff;
    font-family: 'Rajdhani', sans-serif;
    outline: none;
}

.field input:focus {
    border-color: #39d5ff;
}

.operation .btn {
    width: 100%;
    border: 0;
    border-radius: 6px;
    padding: 11px 20px;
    color: #080b10;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 800;
    cursor: pointer;
    grid-column: 1 / -1;
}

.delivery .btn {
    background: #d5a93a;
}

.withdraw .btn {
    background: #ff7070;
}

.panel {
    background: #0b121c;
    border: 1px solid rgba(57,213,255,.10);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 22px;
}

.panel-header {
    padding: 17px 20px;
    background: #0d1723;
    border-bottom: 1px solid rgba(255,255,255,.04);
}

.panel-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 13px;
    color: #fff;
}

.panel-subtitle {
    margin-top: 4px;
    color: #627487;
    font-size: 12px;
}

.filters {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
}

.filters input {
    width: 350px;
    background: #0b121c;
    color: #fff;
    border: 1px solid #1c2b3a;
    border-radius: 6px;
    padding: 10px 12px;
    font-family: 'Rajdhani', sans-serif;
    outline: none;
}

.search-btn {
    background: #39d5ff;
    color: #061018;
    border: 0;
    border-radius: 6px;
    padding: 10px 18px;
    font-weight: 700;
    cursor: pointer;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    color: #627487;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 10px;
    padding: 14px 12px;
    text-align: left;
    background: #0d1723;
}

td {
    padding: 13px 12px;
    border-top: 1px solid rgba(255,255,255,.035);
    color: #aebdca;
    font-size: 13px;
}

.player-name {
    color: #fff;
    font-weight: 700;
}

.account {
    color: #687989;
}

.amount {
    color: #d5a93a;
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
}

.amount.withdraw {
    color: #ff7070;
}

.reason {
    color: #8b9aaa;
    max-width: 260px;
}

.ip {
    color: #596a7a;
    font-size: 11px;
}

.badge {
    display: inline-flex;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}

.badge.delivery {
    color: #d5a93a;
    background: rgba(213,169,58,.08);
}

.badge.withdraw {
    color: #ff7070;
    background: rgba(255,112,112,.08);
}

.online {
    color: #6ee7a8;
    background: rgba(110,231,168,.08);
}

.offline {
    color: #738392;
    background: rgba(115,131,146,.08);
}

.empty {
    text-align: center;
    padding: 40px;
    color: #607182;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 18px;
}

.pagination-info {
    color: #637485;
    font-size: 13px;
}

.pagination-links {
    display: flex;
    gap: 5px;
}

.pagination-links a,
.pagination-links span {
    min-width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    text-decoration: none;
    background: #0b121c;
    border: 1px solid #182636;
    color: #718395;
    font-size: 12px;
}

.pagination-links a:hover,
.pagination-links .current {
    border-color: #39d5ff;
    color: #39d5ff;
}

@media (max-width: 1200px) {

    .stats.second {
        grid-template-columns: repeat(2, 1fr);
    }

    .operations {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        border-right: 0;
        border-bottom: 1px solid rgba(57,213,255,.12);
    }

    .content {
        margin-left: 0;
        padding: 20px;
    }
}

@media (max-width: 650px) {

    .stats,
    .stats.second {
        grid-template-columns: 1fr;
    }

    .operation-form {
        grid-template-columns: 1fr;
    }

    .operation-form .full,
    .operation .btn {
        grid-column: auto;
    }

    .filters {
        flex-direction: column;
    }

    .filters input {
        width: 100%;
    }

    .pagination {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
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

        <a href="donates.php"
		class="active">
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

    <div class="header">

        <h1>Donate</h1>

        <p>
            Controle, circulação e distribuição da Donate Coin.
        </p>

    </div>


    <?php if ($success !== ''): ?>

        <div class="alert success">

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <!-- ESTATÍSTICAS -->

    <section class="stats">

        <div class="card gold">

            <div class="card-title">
                Donate Coins em circulação
            </div>

            <div class="card-value">
                <?= donateNumber($totalDonate) ?>
            </div>

        </div>


        <div class="card green">

            <div class="card-title">
                Jogadores com Donate
            </div>

            <div class="card-value">
                <?= donateNumber($totalOwners) ?>
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Maior saldo individual
            </div>

            <div class="card-value">
                <?= donateNumber($largestBalance) ?>
            </div>

        </div>

    </section>


    <section class="stats second">

        <div class="card">

            <div class="card-title">
                Média por jogador
            </div>

            <div class="card-value">
                <?= donateNumber($averageBalance) ?>
            </div>

        </div>


        <div class="card gold">

            <div class="card-title">
                Total entregue
            </div>

            <div class="card-value">
                <?= donateNumber($totalDelivered) ?>
            </div>

        </div>


        <div class="card red">

            <div class="card-title">
                Total retirado
            </div>

            <div class="card-value">
                <?= donateNumber($totalWithdrawn) ?>
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Operações realizadas
            </div>

            <div class="card-value">
                <?= donateNumber(
                    $totalDeliveries
                    + $totalWithdrawals
                ) ?>
            </div>

        </div>

    </section>


    <section class="operations" style="margin-bottom:24px;">
        <div class="operation delivery">
            <div class="operation-title">🔔 Pedidos de Donate pendentes (<?= count($pendingPurchaseOrders) ?>)</div>
            <p style="color:#aab8c8;line-height:1.6;">
                Confira o pagamento no painel do Mercado Pago antes de aprovar. O retorno do jogador ao site não comprova pagamento.
                Use o número do pedido como referência e só libere após confirmar o recebimento.
            </p>
            <?php if (empty($pendingPurchaseOrders)): ?>
                <p>Não há pedidos pendentes no momento.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead><tr>
                            <th style="text-align:left;padding:10px;">Pedido</th>
                            <th style="text-align:left;padding:10px;">Conta / personagem</th>
                            <th style="text-align:left;padding:10px;">Donate</th>
                            <th style="text-align:left;padding:10px;">Valor</th>
                            <th style="text-align:left;padding:10px;">Data</th>
                            <th style="text-align:left;padding:10px;">Status</th>
                            <th style="text-align:left;padding:10px;">Ação</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($pendingPurchaseOrders as $order): ?>
                            <tr>
                                <td style="padding:10px;">#<?= (int)$order['id'] ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars((string)$order['account_name'], ENT_QUOTES, 'UTF-8') ?><br><strong><?= htmlspecialchars((string)$order['character_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td style="padding:10px;"><?= donateNumber((int)$order['coins']) ?></td>
                                <td style="padding:10px;">R$ <?= number_format((float)$order['amount'], 2, ',', '.') ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars((string)$order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding:10px;">
                                    <?php if ((string)$order['status'] === 'pending'): ?>
                                        <span class="badge offline">PENDENTE</span>
                                    <?php else: ?>
                                        <span class="badge withdraw">RECUSADO</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px;display:flex;gap:8px;flex-wrap:wrap;">
                                    <?php if ((string)$order['status'] === 'pending'): ?>
                                        <form method="post" onsubmit="return confirm('Confirma que verificou o pagamento deste pedido no Mercado Pago? As moedas serão entregues imediatamente.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="APPROVE_ORDER">
                                            <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password" style="margin:8px 0;padding:9px 10px;border:1px solid #465064;border-radius:7px;background:#080b12;color:#e7edf5;max-width:220px;">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                            <button type="submit" class="submit">Confirmar pagamento e entregar</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Recusar este pedido? Nenhuma Donate Coin será entregue.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="REJECT_ORDER">
                                            <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password" style="margin:8px 0;padding:9px 10px;border:1px solid #465064;border-radius:7px;background:#080b12;color:#e7edf5;max-width:220px;">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                            <button type="submit" class="submit" style="background:#a83d4a;">Recusar</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" onsubmit="return confirm('Excluir definitivamente o registro deste pedido? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="action" value="DELETE_ORDER">
                                            <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password" style="margin:8px 0;padding:9px 10px;border:1px solid #465064;border-radius:7px;background:#080b12;color:#e7edf5;max-width:220px;">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                        <button type="submit" class="submit" style="background:#555;">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>


    <section class="panel" style="margin:0 0 24px;">
        <div class="panel-header">
            <div class="panel-title">✅ Histórico de Donate aprovados/entregues (<?= count($approvedPurchaseOrders) ?>)</div>
            <div class="panel-subtitle">Pagamentos confirmados e moedas entregues pelo webhook ou pela administração.</div>
        </div>
        <?php if (empty($approvedPurchaseOrders)): ?>
            <p style="padding:0 20px 18px;color:#aab8c8;">Nenhum pedido aprovado ou entregue ainda.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr>
                        <th style="text-align:left;padding:10px;">Pedido</th><th style="text-align:left;padding:10px;">Conta / personagem</th>
                        <th style="text-align:left;padding:10px;">Donate</th><th style="text-align:left;padding:10px;">Valor</th>
                        <th style="text-align:left;padding:10px;">Pagamento</th><th style="text-align:left;padding:10px;">Entrega</th><th style="text-align:left;padding:10px;">Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($approvedPurchaseOrders as $order): ?>
                        <tr>
                            <td style="padding:10px;">#<?= (int)$order['id'] ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars((string)$order['account_name'], ENT_QUOTES, 'UTF-8') ?><br><strong><?= htmlspecialchars((string)$order['character_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td style="padding:10px;"><?= donateNumber((int)$order['coins']) ?></td>
                            <td style="padding:10px;">R$ <?= number_format((float)$order['amount'], 2, ',', '.') ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars((string)($order['paid_at'] ?: $order['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars((string)($order['delivered_at'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding:10px;"><span class="badge online">APROVADO / ENTREGUE</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div style="padding:0 20px 20px;">
            <form method="post"
                  onsubmit="return confirm('ATENÇÃO: apagar permanentemente todos os pedidos aprovados/entregues desta lista? Os registros de pagamento serão removidos, mas os saldos/moedas dos personagens não serão alterados. Esta ação não pode ser desfeita.');"
                  style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="CLEAR_APPROVED_PURCHASE_HISTORY">
                <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password"
                       style="max-width:260px;padding:10px 12px;border:1px solid rgba(217,106,106,.55);border-radius:8px;background:#080b12;color:#e7edf5;">
                <button type="submit" class="btn" style="background:#a83d4a;border-color:#a83d4a;">APAGAR HISTÓRICO APROVADO</button>
            </form>
            <p style="color:#aab8c8;font-size:12px;margin-top:8px;">A senha de segurança é configurada no servidor pela variável DONATE_ACTION_PASSWORD. Esta ação apaga os registros dos pedidos desta lista, não remove moedas do inventário.</p>
        </div>
    </section>

    <section class="panel" style="margin:0 0 24px;">
        <div class="panel-header">
            <div class="panel-title">📜 Histórico de pedidos de Donate recusados (<?= count($rejectedPurchaseOrders) ?>)</div>
            <div class="panel-subtitle">Pedidos recusados ficam registrados aqui e não entram na contagem de pendentes.</div>
        </div>
        <?php if (empty($rejectedPurchaseOrders)): ?>
            <p style="padding:0 20px 18px;color:#aab8c8;">Nenhum pedido recusado no histórico.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr>
                        <th style="text-align:left;padding:10px;">Pedido</th>
                        <th style="text-align:left;padding:10px;">Conta / personagem</th>
                        <th style="text-align:left;padding:10px;">Donate</th>
                        <th style="text-align:left;padding:10px;">Valor</th>
                        <th style="text-align:left;padding:10px;">Data</th>
                        <th style="text-align:left;padding:10px;">Status</th>
                        <th style="text-align:left;padding:10px;">Ação</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rejectedPurchaseOrders as $order): ?>
                        <tr>
                            <td style="padding:10px;">#<?= (int)$order['id'] ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars((string)$order['account_name'], ENT_QUOTES, 'UTF-8') ?><br><strong><?= htmlspecialchars((string)$order['character_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td style="padding:10px;"><?= donateNumber((int)$order['coins']) ?></td>
                            <td style="padding:10px;">R$ <?= number_format((float)$order['amount'], 2, ',', '.') ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars((string)$order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding:10px;"><span class="badge withdraw">RECUSADO</span></td>
                            <td style="padding:10px;">
                                <form method="post" onsubmit="return confirm('Excluir definitivamente este pedido do histórico? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="DELETE_ORDER">
                                    <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password" style="margin:8px 0;padding:9px 10px;border:1px solid #465064;border-radius:7px;background:#080b12;color:#e7edf5;max-width:220px;">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <button type="submit" class="submit" style="background:#555;">Excluir do histórico</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- OPERAÇÕES -->

    <section class="operations">


        <!-- ENTREGA -->

        <div class="operation delivery">

            <div class="operation-title">
                🎁 Entregar Donate Coin
            </div>


            <form
                method="post"
                class="operation-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrf_token(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="DELIVERY"
                >


                <div class="field">

                    <label>
                        Personagem
                    </label>

                    <input
                        type="text"
                        name="character"
                        placeholder="Nome do personagem"
                        required
                        autocomplete="off"
                    >

                </div>


                <div class="field">

                    <label>
                        Quantidade
                    </label>

                    <input
                        type="number"
                        name="amount"
                        min="1"
                        max="1000000000"
                        placeholder="Ex.: 100"
                        required
                    >

                </div>


                <div class="field full">

                    <label>
                        Motivo obrigatório
                    </label>

                    <input
                        type="text"
                        name="reason"
                        maxlength="255"
                        placeholder="Informe o motivo da entrega"
                        required
                    >

                </div>


                <div class="field full">
                    <label>Senha de segurança</label>
                    <input type="password" name="donate_password" placeholder="Digite a senha de autorização" required autocomplete="current-password">
                </div>

                <button
                    type="submit"
                    class="btn"
                >
                    ENTREGAR DONATE
                </button>

            </form>

        </div>


        <!-- RETIRADA -->

        <div class="operation withdraw">

            <div class="operation-title">
                🔻 Retirar Donate Coin
            </div>


            <form
                method="post"
                class="operation-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrf_token(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="WITHDRAW"
                >


                <div class="field">

                    <label>
                        Personagem
                    </label>

                    <input
                        type="text"
                        name="character"
                        placeholder="Nome do personagem"
                        required
                        autocomplete="off"
                    >

                </div>


                <div class="field">

                    <label>
                        Quantidade
                    </label>

                    <input
                        type="number"
                        name="amount"
                        min="1"
                        max="1000000000"
                        placeholder="Ex.: 100"
                        required
                    >

                </div>


                <div class="field full">

                    <label>
                        Motivo obrigatório
                    </label>

                    <input
                        type="text"
                        name="reason"
                        maxlength="255"
                        placeholder="Informe o motivo da retirada"
                        required
                    >

                </div>


                <div class="field full">
                    <label>Senha de segurança</label>
                    <input type="password" name="donate_password" placeholder="Digite a senha de autorização" required autocomplete="current-password">
                </div>

                <button
                    type="submit"
                    class="btn"
                >
                    RETIRAR DONATE
                </button>

            </form>

        </div>

    </section>


    <!-- CIRCULAÇÃO -->

    <section class="panel">

        <div class="panel-header">

            <div class="panel-title">
                🪙 Circulação da Donate Coin
            </div>

            <div class="panel-subtitle">
                Saldo real existente nos inventários dos jogadores.
            </div>

        </div>


        <form
            method="get"
            class="filters"
            style="padding:18px 20px 0;"
        >

            <input
                type="text"
                name="search"
                placeholder="Buscar personagem ou conta..."
                value="<?= htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <button
                type="submit"
                class="search-btn"
            >
                Buscar
            </button>

        </form>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Personagem
                        </th>

                        <th>
                            Conta
                        </th>

                        <th>
                            Donate Coins
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (
                    empty($donatePlayers)
                ): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >
                            Nenhum Donate Coin encontrado.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach (
                        $donatePlayers
                        as $index => $player
                    ): ?>

                        <tr>

                            <td>
                                <?= $offset
                                    + $index
                                    + 1 ?>
                            </td>


                            <td>

                                <div class="player-name">

                                    <?= htmlspecialchars(
                                        (string)$player['char_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            </td>


                            <td class="account">

                                <?= htmlspecialchars(
                                    (string)$player['account_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <span class="amount">

                                    <?= donateNumber(
                                        $player['count']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    (int)$player['online']
                                    === 1
                                ): ?>

                                    <span class="badge online">
                                        ONLINE
                                    </span>

                                <?php else: ?>

                                    <span class="badge offline">
                                        OFFLINE
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <?php if ($totalFiltered > 0): ?>

        <div class="pagination">

            <div class="pagination-info">

                Mostrando
                <?= min(
                    $offset + 1,
                    $totalFiltered
                ) ?>

                -

                <?= min(
                    $offset
                    + count($donatePlayers),
                    $totalFiltered
                ) ?>

                de

                <?= donateNumber(
                    $totalFiltered
                ) ?>

            </div>


            <div class="pagination-links">

                <?php if ($page > 1): ?>

                    <a
                        href="<?= htmlspecialchars(
                            donatePaginationUrl(
                                $page - 1
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ‹
                    </a>

                <?php endif; ?>


                <?php

                $startPage =
                    max(1, $page - 2);

                $endPage =
                    min(
                        $totalPages,
                        $page + 2
                    );

                for (
                    $i = $startPage;
                    $i <= $endPage;
                    $i++
                ):
                ?>

                    <?php if (
                        $i === $page
                    ): ?>

                        <span class="current">
                            <?= $i ?>
                        </span>

                    <?php else: ?>

                        <a
                            href="<?= htmlspecialchars(
                                donatePaginationUrl(
                                    $i
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endif; ?>

                <?php endfor; ?>


                <?php if (
                    $page < $totalPages
                ): ?>

                    <a
                        href="<?= htmlspecialchars(
                            donatePaginationUrl(
                                $page + 1
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        ›
                    </a>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- HISTÓRICO -->

    <section
        class="panel"
        style="margin-top:22px;"
    >

        <div class="panel-header">

            <div class="panel-title">
                📜 Histórico de Donate
            </div>

            <div class="panel-subtitle">
                Entregas e retiradas realizadas pela administração.
            </div>

            <form method="post" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px;"
                  onsubmit="return confirm('ATENÇÃO: apagar todos os registros de entregas e retiradas? Esta ação não pode ser desfeita. Os saldos dos personagens não serão alterados.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="CLEAR_DONATE_HISTORY">
                <input type="password" name="donate_password" placeholder="Senha de segurança" required autocomplete="current-password"
                       style="max-width:260px;padding:10px 12px;border:1px solid rgba(217,106,106,.55);border-radius:8px;background:#080b12;color:#e7edf5;">
                <button type="submit" class="btn" style="background:#a83d4a;border-color:#a83d4a;">LIMPAR HISTÓRICO</button>
            </form>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Data
                        </th>

                        <th>
                            Operação
                        </th>

                        <th>
                            Administrador
                        </th>

                        <th>
                            Personagem
                        </th>

                        <th>
                            Conta
                        </th>

                        <th>
                            Quantidade
                        </th>

                        <th>
                            Motivo
                        </th>

                        <th>
                            IP
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (
                    empty($history)
                ): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >
                            Nenhuma operação registrada ainda.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach (
                        $history
                        as $entry
                    ): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    (string)$entry['created_at'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>


                            <td>

                                <?php if (
                                    $entry['action']
                                    === 'WITHDRAW'
                                ): ?>

                                    <span class="badge withdraw">
                                        RETIRADA
                                    </span>

                                <?php else: ?>

                                    <span class="badge delivery">
                                        ENTREGA
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    (string)$entry['admin_username'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <div class="player-name">

                                    <?= htmlspecialchars(
                                        (string)$entry['character_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            </td>


                            <td class="account">

                                <?= htmlspecialchars(
                                    (string)$entry['account_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <?php if (
                                    $entry['action']
                                    === 'WITHDRAW'
                                ): ?>

                                    <span class="amount withdraw">
                                        -
                                        <?= donateNumber(
                                            $entry['amount']
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="amount">
                                        +
                                        <?= donateNumber(
                                            $entry['amount']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="reason">

                                <?= htmlspecialchars(
                                    (string)$entry['reason'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td class="ip">

                                <?= htmlspecialchars(
                                    (string)$entry['ip_address'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>

</html>
