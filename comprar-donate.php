<?php
declare(strict_types=1);

/*
 * Mythras — Compra de Donate Coins
 *
 * CONFIGURAÇÃO NO SERVIDOR:
 * MP_ACCESS_TOKEN e SITE_URL precisam ser configurados no servidor.
 * A conexão com o banco é reutilizada de includes/auth.php.
 * Pacotes fixos a R$ 2,00 por Donate Coin; entrega manual pela staff.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin/includes/auth.php';
global $pdo;


const DONATE_ITEM_ID = 37000;

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : trim((string)$value);
}

function jsonResponse(int $status, array $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function dbConnection(): PDO
{
    global $pdo;
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('A conexão com o banco de dados do site não está disponível.');
    }
    return $pdo;
}

function donatePackages(): array
{
    $quantities = [1, 5, 10, 25, 50, 100];
    $packages = [];
    foreach ($quantities as $coins) {
        $id = 'coins_' . $coins;
        $regularPrice = round($coins * 2, 2);
        // Promoção: o pacote de 100 Donate Coins cai de R$ 200 para R$ 180.
        $promoPrice = $coins === 100 ? 180.00 : $regularPrice;
        $packages[$id] = [
            'id' => $id,
            'coins' => $coins,
            'price' => $promoPrice,
            'regular_price' => $regularPrice,
            'on_promotion' => $promoPrice < $regularPrice,
            'label' => $coins === 1 ? 'Pacote Individual' : 'Pacote de ' . $coins . ' moedas',
        ];
    }
    return $packages;
}

function ensureOrderTable(PDO $pdo): void
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

function mpRequest(string $method, string $url, string $accessToken, ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão cURL do PHP precisa estar habilitada.');
    }

    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
    }

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
    error_log('[Mythras Donate cURL] ' . json_encode([
        'erro' => $curlError,
        'errno' => curl_errno($ch),
        'http_status' => $status,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    throw new RuntimeException('Não foi possível comunicar com o Mercado Pago.');
}

    $decoded = json_decode((string)$response, true);
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        throw new RuntimeException('O Mercado Pago recusou a solicitação. Confira a configuração da integração.');
    }
    return $decoded;
}

function deliverDonate(PDO $pdo, int $characterId, int $coins): void
{
    $stmt = $pdo->prepare("
        SELECT obj_Id, char_name
        FROM characters
        WHERE obj_Id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$characterId]);
    $character = $stmt->fetch();

    if (!$character) {
        throw new RuntimeException('Personagem do pedido não encontrado.');
    }

    $stmt = $pdo->prepare("
        SELECT object_id, count
        FROM items
        WHERE owner_id = ? AND item_id = ? AND loc = 'INVENTORY' AND count > 0
        ORDER BY object_id ASC
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$characterId, DONATE_ITEM_ID]);
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

    // Mantém o mesmo formato de item utilizado pelo painel donate.php do servidor.
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
        ) VALUES (?, ?, ?, ?, 0, 'INVENTORY', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
    ");
    $insert->execute([$objectId, $characterId, DONATE_ITEM_ID, $coins]);
}

if (!isset($_SESSION['user_login']) || trim((string)$_SESSION['user_login']) === '') {
    header('Location: login.php');
    exit;
}

$accountName = (string)$_SESSION['user_login'];
$error = '';
$successUrl = '';
$packages = donatePackages();
$siteUrl = rtrim(envValue('SITE_URL'), '/');
$accessToken = envValue('MP_ACCESS_TOKEN');


try {
    $pdo = dbConnection();
    ensureOrderTable($pdo);

    $stmt = $pdo->prepare('SELECT obj_Id, char_name FROM characters WHERE account_name = ? ORDER BY char_name ASC');
    $stmt->execute([$accountName]);
    $characters = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals((string)($_SESSION['donate_csrf'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Sessão de segurança expirada. Atualize a página e tente novamente.');
        }
        if ($packages === []) {
            throw new RuntimeException('Os pacotes de Donate ainda não foram configurados pela administração.');
        }
        if ($accessToken === '' || $siteUrl === '' || !filter_var($siteUrl, FILTER_VALIDATE_URL) || !str_starts_with($siteUrl, 'https://')) {
    error_log('[Mythras Donate Config] ' . json_encode([
        'token_vazio' => $accessToken === '',
        'site_url_vazio' => $siteUrl === '',
        'site_url_valida' => (bool) filter_var($siteUrl, FILTER_VALIDATE_URL),
        'site_url_https' => str_starts_with($siteUrl, 'https://'),
    ]));
    throw new RuntimeException('O checkout ainda não está configurado. A administração precisa configurar Mercado Pago e SITE_URL HTTPS.');
}

        $packageId = (string)($_POST['package_id'] ?? '');
        $characterId = filter_var($_POST['character_id'] ?? null, FILTER_VALIDATE_INT);
        $package = $packages[$packageId] ?? null;

        if ($packageId === 'custom') {
            $customCoins = filter_var($_POST['custom_coins'] ?? null, FILTER_VALIDATE_INT);
            if ($customCoins === false || $customCoins < 1 || $customCoins > 5000) {
                throw new RuntimeException('Informe uma quantidade inteira entre 1 e 5.000 Donate Coins.');
            }
            $package = [
                'id' => 'custom_' . $customCoins,
                'coins' => $customCoins,
                'price' => round($customCoins * 2, 2),
                'label' => 'Quantidade personalizada',
            ];
        }

        if (!$package || $characterId === false || $characterId < 1) {
            throw new RuntimeException('Selecione um pacote ou informe uma quantidade personalizada válida, além de um personagem.');
        }

        $characterStmt = $pdo->prepare('SELECT obj_Id, char_name, account_name FROM characters WHERE obj_Id = ? AND account_name = ? LIMIT 1');
        $characterStmt->execute([(int)$characterId, $accountName]);
        $character = $characterStmt->fetch();
        if (!$character) {
            throw new RuntimeException('O personagem selecionado não pertence à sua conta.');
        }

        $pdo->beginTransaction();
        $insert = $pdo->prepare("
            INSERT INTO donate_purchase_orders
            (account_name, character_id, character_name, package_id, coins, amount, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $insert->execute([
            $accountName,
            (int)$character['obj_Id'],
            (string)$character['char_name'],
            $package['id'],
            $package['coins'],
            number_format($package['price'], 2, '.', ''),
        ]);
        $orderId = (int)$pdo->lastInsertId();
        $pdo->commit();

        $preference = [
            'items' => [[
                'id' => $package['id'],
                'title' => $package['label'] . ' — Mythras',
                'description' => $package['coins'] . ' Donate Coins para ' . $character['char_name'],
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => $package['price'],
            ]],
            'external_reference' => (string)$orderId,
            'back_urls' => [
                'success' => $siteUrl . '/comprar-donate.php?status=success',
                'pending' => $siteUrl . '/comprar-donate.php?status=pending',
                'failure' => $siteUrl . '/comprar-donate.php?status=failure',
            ],
            'statement_descriptor' => 'MYTHRAS DONATE',
            'metadata' => [
                'order_id' => $orderId,
                'account_name' => $accountName,
                'character_id' => (int)$character['obj_Id'],
            ],
        ];

        try {
            $mpPreference = mpRequest(
                'POST',
                'https://api.mercadopago.com/checkout/preferences',
                $accessToken,
                $preference
            );
            $update = $pdo->prepare('UPDATE donate_purchase_orders SET mp_preference_id = ? WHERE id = ?');
            $update->execute([(string)($mpPreference['id'] ?? ''), $orderId]);

            $checkoutUrl = (string)($mpPreference['init_point'] ?? '');
            if (!filter_var($checkoutUrl, FILTER_VALIDATE_URL) || !str_starts_with($checkoutUrl, 'https://')) {
                throw new RuntimeException('O Mercado Pago não retornou um link de pagamento válido.');
            }
            header('Location: ' . $checkoutUrl, true, 303);
            exit;
        } catch (Throwable $e) {
            error_log('[Mythras Donate Checkout] ' . $e->getMessage());
            $failed = $pdo->prepare("UPDATE donate_purchase_orders SET status = 'checkout_error' WHERE id = ? AND status = 'pending'");
            $failed->execute([$orderId]);
            $error = 'Não foi possível iniciar o pagamento. Nenhuma moeda foi entregue. Tente novamente ou contate a staff.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

if (!isset($_SESSION['donate_csrf']) || !is_string($_SESSION['donate_csrf'])) {
    $_SESSION['donate_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['donate_csrf'];

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Comprar Donate Coins | Lineage 2 Mythras</title>
<style>
:root{color-scheme:dark;--bg:#080e18;--panel:#101b2a;--line:#26394e;--gold:#e9b94f;--text:#edf3fa;--muted:#9eb0c5;--green:#5bd6a1}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at top,#17263b 0,#080e18 62%);color:var(--text);font-family:Arial,Helvetica,sans-serif;min-height:100vh}
.wrap{width:min(1060px,calc(100% - 32px));margin:0 auto;padding:28px 0 54px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:35px}.brand{font-size:14px;letter-spacing:2px;font-weight:800;color:var(--gold)}.back{color:var(--text);text-decoration:none;border:1px solid var(--line);padding:10px 14px;border-radius:8px}.hero{text-align:center;margin:24px auto 30px;max-width:680px}.eyebrow{color:var(--gold);font-size:12px;letter-spacing:3px;font-weight:bold}.hero h1{font-size:clamp(30px,5vw,48px);margin:12px 0}.hero p{color:var(--muted);line-height:1.65;margin:0}.notice{border:1px solid #745b2c;background:#2b2415;color:#f7d98c;border-radius:10px;padding:14px 16px;margin:18px 0;line-height:1.5}.error{border:1px solid #8e3d49;background:#31171d;color:#ffc3cb;border-radius:10px;padding:14px 16px;margin:18px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:24px}.package{position:relative;background:linear-gradient(160deg,#152338,#0d1725);border:1px solid var(--line);border-radius:15px;padding:23px;display:flex;flex-direction:column;min-height:240px}.package:hover{border-color:#a98742;transform:translateY(-2px)}.coin{font-size:30px}.package h2{font-size:19px;margin:13px 0 8px}.coins{font-size:28px;color:var(--gold);font-weight:800}.coins small{font-size:13px;color:var(--muted);font-weight:normal}.price{font-size:20px;margin:15px 0 20px}.old-price{display:block;color:var(--muted);font-size:14px;text-decoration:line-through;margin-bottom:4px}.promo-tag{display:inline-block;align-self:flex-start;background:#3b1d1d;color:#ff9b9b;border:1px solid #8f3e3e;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800;letter-spacing:.5px;margin-bottom:10px}.package button,.submit{margin-top:auto;border:0;border-radius:9px;background:linear-gradient(100deg,#f0c75e,#d99b2d);color:#1c1608;font-weight:800;padding:13px 15px;cursor:pointer;font-size:14px}.package button:disabled,.submit:disabled{opacity:.5;cursor:not-allowed}.select-area{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:22px;margin-top:25px}.select-area label{display:block;font-size:13px;color:var(--muted);margin-bottom:9px}.select-area select{width:100%;padding:13px;background:#09121e;border:1px solid var(--line);border-radius:8px;color:var(--text);font-size:15px}.foot{margin-top:28px;color:var(--muted);font-size:12px;line-height:1.7;text-align:center}.empty{background:var(--panel);border:1px dashed var(--line);border-radius:14px;padding:25px;color:var(--muted);line-height:1.6;text-align:center}
@media(max-width:520px){.top{align-items:flex-start}.wrap{width:calc(100% - 22px);padding-top:18px}.package{min-height:0}.hero{margin-top:14px}}
</style>
</head>
<body>
<div class="wrap">
  <header class="top">
    <div class="brand">LINEAGE 2 · MYTHRAS</div>
    <a class="back" href="dashboard.php">← Voltar à conta</a>
  </header>
  <section class="hero">
    <div class="eyebrow">LOJA OFICIAL</div>
    <h1>Comprar Donate Coins</h1>
    <p>Escolha seu pacote, selecione o personagem e pague pelo checkout seguro do Mercado Pago. Depois de conferir o pagamento, a staff libera as Donate Coins manualmente.</p>
  </section>

  <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
  <?php if (($_GET['status'] ?? '') === 'success'): ?>
    <div class="notice">Você voltou do Mercado Pago. Seu pedido ficará pendente até a staff conferir o pagamento e liberar as moedas. Não pague novamente se já concluiu a compra.</div>
  <?php elseif (($_GET['status'] ?? '') === 'pending'): ?>
    <div class="notice">Seu pedido está aguardando conferência manual da staff. As Donate Coins não são liberadas automaticamente.</div>
  <?php elseif (($_GET['status'] ?? '') === 'failure'): ?>
    <div class="notice">O pagamento não foi concluído. Você pode tentar novamente quando quiser.</div>
  <?php endif; ?>

  <?php if ($packages === []): ?>
    <div class="empty"><strong>Pacotes ainda não configurados.</strong><br>Os pacotes serão exibidos assim que o checkout estiver configurado pela administração.</div>
  <?php elseif (empty($characters)): ?>
    <div class="empty">Não encontramos personagens nesta conta. Crie um personagem no servidor e volte para comprar Donate Coins.</div>
  <?php else: ?>
    <form method="post" action="comprar-donate.php">
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
      <div class="select-area">
        <label for="character_id">Personagem que receberá as Donate Coins</label>
        <select id="character_id" name="character_id" required>
          <option value="">Selecione seu personagem</option>
          <?php foreach ($characters as $character): ?>
            <option value="<?= (int)$character['obj_Id'] ?>"><?= h((string)$character['char_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid">
        <?php foreach ($packages as $package): ?>
          <article class="package">
            <div class="coin" aria-hidden="true">🪙</div>
            <h2><?= h($package['label']) ?></h2>
            <?php if (!empty($package['on_promotion'])): ?><span class="promo-tag">PROMOÇÃO</span><?php endif; ?>
            <div class="coins"><?= number_format($package['coins'], 0, ',', '.') ?> <small>Donate Coins</small></div>
            <div class="price">
              <?php if (!empty($package['on_promotion'])): ?><span class="old-price">De R$ <?= number_format($package['regular_price'], 2, ',', '.') ?></span><?php endif; ?>
              <?= !empty($package['on_promotion']) ? 'Por ' : '' ?>R$ <?= number_format($package['price'], 2, ',', '.') ?>
            </div>
            <button type="submit" name="package_id" value="<?= h($package['id']) ?>">COMPRAR PACOTE</button>
          </article>
        <?php endforeach; ?>
        <article class="package custom-package">
          <div class="coin" aria-hidden="true">🪙</div>
          <h2>Quantidade personalizada</h2>
          <label for="custom_coins" style="color:var(--muted);font-size:13px;margin:8px 0">Digite de 1 a 5.000 Donate Coins</label>
          <input id="custom_coins" name="custom_coins" type="number" min="1" max="5000" step="1" inputmode="numeric" placeholder="Ex.: 250" style="width:100%;padding:12px;background:#09121e;border:1px solid var(--line);border-radius:8px;color:var(--text);font-size:16px">
          <div class="price">Total: <strong id="custom_total">R$ 2,00</strong></div>
          <button type="submit" name="package_id" value="custom" id="custom_buy">COMPRAR QUANTIDADE</button>
        </article>
      </div>
    </form>
    <script>
    (() => {
      const amount = document.getElementById('custom_coins');
      const total = document.getElementById('custom_total');
      const button = document.getElementById('custom_buy');
      const formatBRL = value => value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      const updateCustom = () => {
        const coins = Number.parseInt(amount.value, 10);
        const valid = Number.isInteger(coins) && coins >= 1 && coins <= 5000;
        total.textContent = formatBRL(valid ? coins * 2 : 0);
        button.disabled = !valid;
      };
      amount.addEventListener('input', updateCustom);
      button.addEventListener('click', event => {
        const coins = Number.parseInt(amount.value, 10);
        if (!Number.isInteger(coins) || coins < 1 || coins > 5000) {
          event.preventDefault();
          amount.setCustomValidity('Informe uma quantidade inteira entre 1 e 5.000.');
          amount.reportValidity();
        } else {
          amount.setCustomValidity('');
        }
      });
      updateCustom();
    })();
    </script>
  <?php endif; ?>

  <footer class="foot">
    Pagamento realizado pelo checkout do Mercado Pago. Nunca informe sua senha do jogo durante a compra.<br>
    A volta para esta página não confirma o pagamento. A staff confere o recebimento e libera as moedas manualmente.
  </footer>
</div>
</body>
</html>
