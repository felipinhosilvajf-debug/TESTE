<?php
require __DIR__ . '/mp_lib.php';

http_response_code(200);
try {
    $config = mpConfig();
    $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
    $type = $_GET['type'] ?? $body['type'] ?? '';
    if ($type !== 'payment') exit;

    $paymentId = (string)($_GET['data.id'] ?? $_GET['data_id'] ?? $_GET['id'] ?? $body['data']['id'] ?? '');
    if (!preg_match('/^[0-9]{1,24}$/', $paymentId)) exit;
    if (!mpValidateWebhookSignature($config, $paymentId)) {
        http_response_code(401);
        exit;
    }

    // Sempre consulta o pagamento na API: o body do webhook não é fonte confiável.
    $payment = mpApi('GET', 'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId), $config['access_token']);
    $reference = (string)($payment['external_reference'] ?? '');
    $order = mpLoadOrder($config['private_dir'], $reference);
    if (!$order) exit;

    $amount = (float)($payment['transaction_amount'] ?? 0);
    $currency = (string)($payment['currency_id'] ?? '');
    if ($currency !== 'BRL' || abs($amount - (float)($order['amount'] ?? 0)) > 0.001) exit;
    if ((int)($order['coins'] ?? 0) < 1 || empty($order['nick'])) exit;

    $ordersDir = rtrim($config['private_dir'], '\\\\/') . DIRECTORY_SEPARATOR . 'orders';
    $lock = fopen($ordersDir . DIRECTORY_SEPARATOR . 'credits.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível bloquear a fila de créditos.');
    try {
        $order = mpLoadOrder($config['private_dir'], $reference);
        if (!$order) exit;

        $paymentStatus = (string)($payment['status'] ?? 'unknown');
        // Não regride um pedido já creditado por causa de notificações atrasadas.
        if (($order['status'] ?? '') !== 'credited') {
            $order['status'] = $paymentStatus;
        }
        $order['payment_id'] = $paymentId;
        $order['status_detail'] = (string)($payment['status_detail'] ?? '');
        $order['payment_method_id'] = (string)($payment['payment_method_id'] ?? '');
        $order['payment_type_id'] = (string)($payment['payment_type_id'] ?? '');
        $order['transaction_amount'] = $amount;
        $order['currency'] = $currency;
        $order['last_payment_update'] = date(DATE_ATOM);

        if ($paymentStatus === 'approved' && ($order['status'] ?? '') !== 'credited') {
            $paymentFile = $ordersDir . DIRECTORY_SEPARATOR . 'payment-' . $paymentId . '.json';
            if (!is_file($paymentFile)) {
                mpCreditAgendamento($config, (string)$order['nick'], (int)$order['coins']);
                $order['status'] = 'credited';
                $order['credited_at'] = date(DATE_ATOM);
                mpWriteJsonAtomic($paymentFile, [
                    'payment_id' => $paymentId,
                    'reference' => $reference,
                    'nick' => $order['nick'],
                    'coins' => (int)$order['coins'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'approved',
                    'credited_at' => $order['credited_at'],
                ]);
            } else {
                $order['status'] = 'credited';
            }
        }
        mpWriteJsonAtomic($ordersDir . DIRECTORY_SEPARATOR . $reference . '.json', $order);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
} catch (Throwable $e) {
    error_log('[VirtualBoost Webhook] ' . $e->getMessage());
    http_response_code(500);
}
