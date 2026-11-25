<?php
require __DIR__ . '/mp_lib.php';

http_response_code(200);
try {
    $config = mpConfig();
    $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
    $type = $_GET['type'] ?? $body['type'] ?? '';
    if ($type !== 'payment') exit;

    $paymentId = (string)($_GET['data.id'] ?? $_GET['id'] ?? $body['data']['id'] ?? '');
    if (!preg_match('/^[0-9]{1,24}$/', $paymentId)) exit;

    // Confirma os dados diretamente na API do Mercado Pago; não confia no corpo do webhook.
    $payment = mpApi('GET', 'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId), $config['access_token']);
    if (($payment['status'] ?? '') !== 'approved') exit;

    $reference = (string)($payment['external_reference'] ?? '');
    $order = mpLoadOrder($config['private_dir'], $reference);
    if (!$order || ($order['status'] ?? '') === 'credited') exit;

    $amount = (float)($payment['transaction_amount'] ?? 0);
    $currency = (string)($payment['currency_id'] ?? '');
    if ($currency !== 'BRL' || abs($amount - (float)$order['amount']) > 0.001) exit;
    if ((int)($order['coins'] ?? 0) < 1 || !isset($order['nick'])) exit;

    $ordersDir = rtrim($config['private_dir'], '\\/') . DIRECTORY_SEPARATOR . 'orders';
    $lock = fopen($ordersDir . DIRECTORY_SEPARATOR . 'credits.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível bloquear a fila de créditos.');
    try {
        // Releitura sob lock evita que duas notificações iguais creditem em paralelo.
        $order = mpLoadOrder($config['private_dir'], $reference);
        if (!$order || ($order['status'] ?? '') === 'credited') exit;
        $paymentFile = $ordersDir . DIRECTORY_SEPARATOR . 'payment-' . $paymentId . '.json';
        if (is_file($paymentFile)) exit;

        mpCreditAgendamento($config, (string)$order['nick'], (int)$order['coins']);
        $order['status'] = 'credited';
        $order['payment_id'] = $paymentId;
        $order['credited_at'] = date(DATE_ATOM);
        mpWriteJsonAtomic($ordersDir . DIRECTORY_SEPARATOR . $reference . '.json', $order);
        mpWriteJsonAtomic($paymentFile, [
            'payment_id' => $paymentId,
            'reference' => $reference,
            'nick' => $order['nick'],
            'coins' => (int)$order['coins'],
            'credited_at' => date(DATE_ATOM),
        ]);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
} catch (Throwable $e) {
    error_log('[VirtualBoost Webhook] ' . $e->getMessage());
    http_response_code(500);
}
