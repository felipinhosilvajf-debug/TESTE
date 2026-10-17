<?php
declare(strict_types=1);

/*
 * VBoost — Webhook de pagamentos do Mercado Pago.
 * Configure MP_ACCESS_TOKEN e SITE_URL no servidor.
 * Recomendado: configure também MP_WEBHOOK_SECRET com a chave secreta
 * de assinatura fornecida na configuração de Webhooks do Mercado Pago.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function donateWebhookRespond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function donateWebhookEnv(string $name): string
{
    $value = getenv($name);
    return $value === false ? '' : trim((string)$value);
}

function donateWebhookMpGet(string $url, string $token): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão cURL não está habilitada.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        throw new RuntimeException('Falha de comunicação com a API do Mercado Pago.');
    }

    $data = json_decode((string)$response, true);
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        throw new RuntimeException('A API do Mercado Pago não confirmou os dados do pagamento.');
    }

    return $data;
}

function donateWebhookVerifySignature(string $paymentId): bool
{
    $secret = donateWebhookEnv('MP_WEBHOOK_SECRET');

    // Sem segredo configurado, a confirmação ainda exige consulta autenticada
    // à API do Mercado Pago e validação de pedido, valor e moeda.
    if ($secret === '') {
        return true;
    }

    $signatureHeader = (string)($_SERVER['HTTP_X_SIGNATURE'] ?? '');
    $requestId = (string)($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
    if ($signatureHeader === '' || $requestId === '') {
        return false;
    }

    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        $pair = explode('=', trim($part), 2);
        if (count($pair) === 2) {
            $parts[trim($pair[0])] = trim($pair[1]);
        }
    }

    $ts = $parts['ts'] ?? '';
    $v1 = $parts['v1'] ?? '';
    if ($ts === '' || $v1 === '') {
        return false;
    }

    $manifest = 'id:' . strtolower($paymentId) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
    $expected = hash_hmac('sha256', $manifest, $secret);
    return hash_equals($expected, $v1);
}

function donateWebhookEnsureTable(PDO $pdo): void
{
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
}

function donateWebhookDeliver(PDO $pdo, int $characterId, int $coins): void
{
    $stmt = $pdo->prepare("
        SELECT obj_Id
        FROM characters
        WHERE obj_Id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$characterId]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Personagem do pedido não encontrado no banco do jogo.');
    }

    $stmt = $pdo->prepare("
        SELECT object_id, count
        FROM items
        WHERE owner_id = ? AND item_id = 37000 AND loc = 'INVENTORY' AND count > 0
        ORDER BY object_id ASC
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$characterId]);
    $item = $stmt->fetch();

    if ($item) {
        $newCount = (int)$item['count'] + $coins;
        if ($newCount > 2147483647) {
            throw new RuntimeException('O saldo de Donate Coins excederia o limite permitido.');
        }
        $update = $pdo->prepare('UPDATE items SET count = ? WHERE object_id = ? LIMIT 1');
        $update->execute([$newCount, (int)$item['object_id']]);
        return;
    }

    // O lock nomeado serializa a geração do próximo object_id entre webhooks.
    $objectId = (int)$pdo->query('SELECT COALESCE(MAX(object_id), 0) + 1 FROM items')->fetchColumn();
    if ($objectId <= 0) {
        throw new RuntimeException('Não foi possível gerar o identificador do item Donate.');
    }

    $insert = $pdo->prepare("
        INSERT INTO items (
            object_id, owner_id, item_id, count, enchant_level, loc, loc_data,
            life_time, augmentation_id, attribute_fire, attribute_water,
            attribute_wind, attribute_earth, attribute_holy, attribute_unholy,
            custom_type1, custom_type2, custom_flags, agathion_energy, visual_item_id
        ) VALUES (?, ?, 37000, ?, 0, 'INVENTORY', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
    ");
    $insert->execute([$objectId, $characterId, $coins]);
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('A conexão PDO não está disponível.');
    }

    $token = donateWebhookEnv('MP_ACCESS_TOKEN');
    if ($token === '') {
        throw new RuntimeException('MP_ACCESS_TOKEN não está configurado no servidor.');
    }

    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) {
        $body = [];
    }

    $topic = (string)($body['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
    if ($topic !== '' && $topic !== 'payment' && $topic !== 'merchant_order') {
        donateWebhookRespond(200, ['received' => true, 'ignored' => true]);
    }

    $paymentId = (string)($body['data']['id'] ?? $_GET['data.id'] ?? $_GET['id'] ?? '');
    if ($paymentId === '' || !ctype_digit($paymentId)) {
        // Notificações de outros recursos não precisam ser reprocessadas.
        donateWebhookRespond(200, ['received' => true, 'ignored' => true]);
    }

    if (!donateWebhookVerifySignature($paymentId)) {
        error_log('[VBoost Donate Webhook] Assinatura inválida.');
        donateWebhookRespond(401, ['received' => false]);
    }

    $payment = donateWebhookMpGet(
        'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId),
        $token
    );

    if (($payment['status'] ?? '') !== 'approved') {
        // Pagamentos pendentes/recusados não geram entrega; o Mercado Pago
        // pode notificar novamente quando o estado mudar.
        donateWebhookRespond(200, ['received' => true, 'delivered' => false]);
    }

    $externalReference = (string)($payment['external_reference'] ?? '');
    $currency = (string)($payment['currency_id'] ?? '');
    $amount = isset($payment['transaction_amount']) ? (float)$payment['transaction_amount'] : -1.0;

    if (!ctype_digit($externalReference) || (int)$externalReference < 1 ||
        $currency !== 'BRL' || $amount <= 0) {
        error_log('[VBoost Donate Webhook] Pagamento aprovado com referência/valor/moeda inválidos.');
        donateWebhookRespond(200, ['received' => true, 'delivered' => false]);
    }

    donateWebhookEnsureTable($pdo);

    // Serializa as entregas e a geração do object_id para evitar duplicidade
    // quando o Mercado Pago envia notificações simultâneas.
    $lockResult = (int)$pdo->query("SELECT GET_LOCK('vboost_donate_delivery', 15)")->fetchColumn();
    if ($lockResult !== 1) {
        throw new RuntimeException('Não foi possível obter o bloqueio de entrega.');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM donate_purchase_orders WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([(int)$externalReference]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            donateWebhookRespond(200, ['received' => true, 'delivered' => false]);
        }

        $expectedCents = (int)round(((float)$order['amount']) * 100);
        $actualCents = (int)round($amount * 100);
        if ($expectedCents !== $actualCents) {
            $pdo->rollBack();
            error_log('[VBoost Donate Webhook] Valor do pagamento não corresponde ao pedido ' . (int)$order['id'] . '.');
            donateWebhookRespond(200, ['received' => true, 'delivered' => false]);
        }

        if (!empty($order['delivered_at']) || $order['status'] === 'delivered') {
            // Notificação repetida: entrega já concluída.
            $pdo->commit();
            donateWebhookRespond(200, ['received' => true, 'delivered' => true, 'duplicate' => true]);
        }

        if (!empty($order['mp_payment_id']) && (string)$order['mp_payment_id'] !== $paymentId) {
            throw new RuntimeException('O pedido já está associado a outro pagamento.');
        }

        $stmt = $pdo->prepare("
            UPDATE donate_purchase_orders
            SET mp_payment_id = ?, paid_at = COALESCE(paid_at, NOW()), status = 'paid'
            WHERE id = ?
        ");
        $stmt->execute([$paymentId, (int)$order['id']]);

        donateWebhookDeliver($pdo, (int)$order['character_id'], (int)$order['coins']);

        $stmt = $pdo->prepare("
            UPDATE donate_purchase_orders
            SET status = 'delivered', delivered_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([(int)$order['id']]);

        $pdo->commit();
        donateWebhookRespond(200, ['received' => true, 'delivered' => true]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('vboost_donate_delivery')");
    }
} catch (Throwable $e) {
    error_log('[VBoost Donate Webhook] ' . $e->getMessage());
    donateWebhookRespond(500, ['received' => false]);
}
