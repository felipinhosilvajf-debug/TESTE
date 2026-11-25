<?php
require __DIR__ . '/leitor.php';
require __DIR__ . '/mp_lib.php';

if (!isset($_SESSION['jogador_nick'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['loja_csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('Solicitação inválida.');
}
$nick = mpSafeNick($_SESSION['jogador_nick']);
$pacotes = [
    5000 => 2.00,
    10000 => 4.00,
    15000 => 6.00,
    20000 => 8.00,
    30000 => 12.00,
];
$coins = (int)($_POST['pacote'] ?? 0);
if (!isset($pacotes[$coins])) {
    http_response_code(400);
    exit('Pacote inválido.');
}
try {
    $config = mpConfig();
    mpEnsureDir($config['private_dir']);
    mpEnsureDir(rtrim($config['private_dir'], '\\/') . DIRECTORY_SEPARATOR . 'orders');

    $reference = bin2hex(random_bytes(16));
    $amount = $pacotes[$coins];
    $order = [
        'reference' => $reference,
        'nick' => $nick,
        'coins' => $coins,
        'amount' => $amount,
        'currency' => 'BRL',
        'status' => 'pending',
        'created_at' => date(DATE_ATOM),
    ];
    $orderPath = rtrim($config['private_dir'], '\\/') . DIRECTORY_SEPARATOR . 'orders' . DIRECTORY_SEPARATOR . $reference . '.json';
    mpWriteJsonAtomic($orderPath, $order);

    $base = rtrim($config['base_url'], '/');
    $preference = mpApi('POST', 'https://api.mercadopago.com/checkout/preferences', $config['access_token'], [
        'items' => [[
            'id' => 'coins-' . $coins,
            'title' => number_format($coins, 0, ',', '.') . ' Coins - Virtual Boost RP',
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => $amount,
        ]],
        'external_reference' => $reference,
        'notification_url' => $base . '/site/webhook_mercadopago.php',
        'back_urls' => [
            'success' => $base . '/site/retorno_pagamento.php?status=success',
            'pending' => $base . '/site/retorno_pagamento.php?status=pending',
            'failure' => $base . '/site/retorno_pagamento.php?status=failure',
        ],
        'auto_return' => 'approved',
        'statement_descriptor' => 'VIRTUAL BOOST RP',
    ]);
    if (empty($preference['init_point'])) {
        throw new RuntimeException('Mercado Pago não retornou o endereço de pagamento.');
    }
    header('Location: ' . $preference['init_point'], true, 303);
    exit;
} catch (Throwable $e) {
    error_log('[VirtualBoost MercadoPago] ' . $e->getMessage());
    http_response_code(500);
    echo 'Não foi possível iniciar o pagamento. Tente novamente ou contate a administração.';
}
