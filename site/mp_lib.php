<?php
declare(strict_types=1);

function mpConfig(): array {
    $path = 'C:\\inetpub\\private\\virtualboost-mp.php';
    if (!is_file($path)) {
        throw new RuntimeException('Configuração privada do Mercado Pago não instalada.');
    }
    $config = require $path;
    if (!is_array($config) || empty($config['access_token']) || empty($config['webhook_secret']) || empty($config['private_dir']) || empty($config['coins_dir']) || empty($config['base_url'])) {
        throw new RuntimeException('Configuração do Mercado Pago incompleta.');
    }
    return $config;
}

function mpApi(string $method, string $url, string $token, ?array $body = null): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão cURL do PHP não está habilitada no IIS.');
    }
    $ch = curl_init($url);
    $headers = ['Authorization: Bearer ' . $token, 'Content-Type: application/json', 'Accept: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Falha na API Mercado Pago (HTTP ' . $status . '). ' . $error);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Resposta inválida da API Mercado Pago.');
    }
    return $data;
}

function mpValidateWebhookSignature(array $config, string $dataId): bool {
    $secret = (string)($config['webhook_secret'] ?? '');
    $signature = (string)($_SERVER['HTTP_X_SIGNATURE'] ?? '');
    $requestId = (string)($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
    if ($secret === '' || $signature === '' || $requestId === '' || $dataId === '') return false;

    $parts = [];
    foreach (explode(',', $signature) as $part) {
        $pair = explode('=', trim($part), 2);
        if (count($pair) === 2) $parts[trim($pair[0])] = trim($pair[1]);
    }
    if (empty($parts['ts']) || empty($parts['v1'])) return false;

    $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $parts['ts'] . ';';
    $expected = hash_hmac('sha256', $manifest, $secret);
    return hash_equals($expected, $parts['v1']);
}

function mpSafeNick(string $nick): string {
    $nick = trim($nick);
    if (!preg_match('/^[a-zA-Z0-9_\[\]\(\)\.\-@]{1,24}$/', $nick)) {
        throw new RuntimeException('Nick inválido.');
    }
    return $nick;
}

function mpEnsureDir(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar a pasta privada.');
    }
}

function mpWriteJsonAtomic(string $path, array $data): void {
    $tmp = $path . '.' . bin2hex(random_bytes(5)) . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Não foi possível gravar o registro do pedido.');
    }
}

function mpCreditAgendamento(array $config, string $nick, int $coins): void {
    $nick = mpSafeNick($nick);
    if ($coins < 1 || $coins > 1000000) {
        throw new RuntimeException('Quantidade de Coins inválida.');
    }
    $dir = rtrim($config['coins_dir'], '\\/');
    if (!is_dir($dir)) {
        throw new RuntimeException('Pasta Moedas Agendadas não encontrada.');
    }
    $file = $dir . DIRECTORY_SEPARATOR . $nick . '.ini';
    $lockPath = $file . '.lock';
    $lock = fopen($lockPath, 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Não foi possível bloquear o agendamento.');
    }
    try {
        $total = $coins;
        $admin = 'APOIO PELO SITE';
        if (is_file($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                if (strpos($line, '=') === false) continue;
                [$key, $value] = explode('=', $line, 2);
                if (trim($key) === 'Coins') $total += max(0, (int)trim($value));
                if (trim($key) === 'Admin' && trim($value) !== '') $admin = trim($value);
            }
        }
        $content = 'Admin=' . $admin . PHP_EOL . 'Coins=' . $total . PHP_EOL;
        $tmp = $file . '.' . bin2hex(random_bytes(5)) . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Falha ao gravar o arquivo de moedas.');
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
        @unlink($lockPath);
    }
}

function mpLoadOrder(string $privateDir, string $reference): ?array {
    if (!preg_match('/^[a-f0-9-]{30,40}$/i', $reference)) return null;
    $path = rtrim($privateDir, '\\/') . DIRECTORY_SEPARATOR . 'orders' . DIRECTORY_SEPARATOR . $reference . '.json';
    if (!is_file($path)) return null;
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? $data : null;
}
