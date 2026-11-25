<?php
declare(strict_types=1);
require __DIR__ . '/mp_lib.php';

try {
    $config = mpConfig();
    $adminUser = (string)($config['admin_user'] ?? '');
    $passwordHash = (string)($config['admin_password_hash'] ?? '');
    if ($adminUser === '' || $passwordHash === '' || strpos($passwordHash, 'COLOQUE_AQUI') === 0) {
        http_response_code(503);
        exit('Acesso financeiro ainda não configurado no arquivo privado.');
    }

    $providedUser = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $providedPass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
    if (!hash_equals($adminUser, $providedUser) || !password_verify($providedPass, $passwordHash)) {
        header('WWW-Authenticate: Basic realm="Virtual Boost - Financeiro", charset="UTF-8"');
        http_response_code(401);
        exit('Autenticação necessária.');
    }

    $ordersDir = rtrim($config['private_dir'], '\\\\/') . DIRECTORY_SEPARATOR . 'orders';
    $orders = [];
    if (is_dir($ordersDir)) {
        foreach (glob($ordersDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (basename($file) === 'credits.lock' || strpos(basename($file), 'payment-') === 0) continue;
            $row = json_decode((string)file_get_contents($file), true);
            if (is_array($row) && !empty($row['reference'])) $orders[] = $row;
        }
    }
    usort($orders, static function(array $a, array $b): int {
        return strcmp((string)($b['last_payment_update'] ?? $b['created_at'] ?? ''), (string)($a['last_payment_update'] ?? $a['created_at'] ?? ''));
    });

    $totalApproved = 0.0;
    $countApproved = 0;
    foreach ($orders as $row) {
        if (($row['status'] ?? '') === 'credited' || ($row['status'] ?? '') === 'approved') {
            $totalApproved += (float)($row['amount'] ?? 0);
            $countApproved++;
        }
    }
    function eh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    function statusLabel(string $status): string {
        $map = ['credited'=>'APROVADO · COINS AGENDADAS','approved'=>'APROVADO','pending'=>'PENDENTE','in_process'=>'EM ANÁLISE','rejected'=>'RECUSADO','cancelled'=>'CANCELADO','refunded'=>'ESTORNADO','charged_back'=>'CONTESTADO'];
        return $map[$status] ?? strtoupper($status ?: 'DESCONHECIDO');
    }
} catch (Throwable $e) {
    error_log('[VirtualBoost Financeiro] ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o histórico financeiro.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Financeiro · Virtual Boost RP</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#07131d;--panel:#102936;--line:rgba(112,207,222,.18);--cyan:#28d9ef;--gold:#e9c879;--text:#edf8fb;--muted:#91aab4;--green:#70e0b0;--red:#ff8585}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 15% 0%,rgba(28,169,190,.13),transparent 35%),var(--bg);color:var(--text);font-family:Inter,Arial,sans-serif}.shell{width:min(1200px,94%);margin:auto}.top{padding:25px 0;border-bottom:1px solid var(--line);font:900 24px 'Barlow Condensed';letter-spacing:1px}.top span{color:var(--cyan)}main{padding:35px 0 70px}.eyebrow{font-size:10px;letter-spacing:2px;color:var(--gold);font-weight:800}h1{font:900 clamp(34px,5vw,48px) 'Barlow Condensed';margin:8px 0 22px;text-transform:uppercase}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.stat,.table-wrap{background:linear-gradient(145deg,#102936,#0b202c);border:1px solid var(--line);border-radius:8px}.stat{padding:20px}.stat small{display:block;color:var(--muted);font-size:9px;letter-spacing:1.5px;text-transform:uppercase}.stat strong{display:block;margin-top:8px;font:800 30px 'Barlow Condensed';color:var(--gold)}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:900px}th,td{text-align:left;padding:14px 15px;border-bottom:1px solid rgba(112,207,222,.1);font-size:11px}th{font-size:9px;letter-spacing:1.2px;color:var(--cyan);text-transform:uppercase;background:rgba(4,16,24,.35)}td{color:#d8e8ec}td small{display:block;color:var(--muted);margin-top:4px}.status{font-weight:800;font-size:9px;letter-spacing:.3px}.approved,.credited{color:var(--green)}.rejected,.cancelled,.refunded,.charged_back{color:var(--red)}.pending,.in_process{color:var(--gold)}.empty{padding:28px;text-align:center;color:var(--muted)}.foot{margin-top:18px;color:#688590;font-size:10px}@media(max-width:650px){.stats{grid-template-columns:1fr}.stat{padding:15px}}
</style></head><body><header class="top"><div class="shell">BRASIL <span>VIRTUAL BOOST</span> · FINANCEIRO</div></header><main class="shell">
<div class="eyebrow">PAINEL PRIVADO · REGISTRO DE TRANSAÇÕES</div><h1>Histórico de pagamentos</h1>
<section class="stats"><article class="stat"><small>Pedidos registrados</small><strong><?= count($orders) ?></strong></article><article class="stat"><small>Pagamentos aprovados</small><strong><?= $countApproved ?></strong></article><article class="stat"><small>Total aprovado</small><strong>R$ <?= number_format($totalApproved,2,',','.') ?></strong></article></section>
<div class="table-wrap"><?php if (!$orders): ?><div class="empty">Nenhum pedido registrado até o momento.</div><?php else: ?><table><thead><tr><th>Data do pedido</th><th>Jogador</th><th>Coins</th><th>Valor</th><th>Status</th><th>Pagamento</th><th>Atualização</th></tr></thead><tbody>
<?php foreach ($orders as $row): $st=(string)($row['status']??'unknown'); ?><tr>
<td><?= eh(isset($row['created_at']) ? date('d/m/Y H:i', strtotime((string)$row['created_at'])) : '—') ?></td>
<td><?= eh($row['nick']??'—') ?><small><?= eh($row['reference']??'') ?></small></td>
<td><?= number_format((int)($row['coins']??0),0,',','.') ?></td><td>R$ <?= number_format((float)($row['amount']??0),2,',','.') ?></td>
<td><span class="status <?= eh($st) ?>"><?= eh(statusLabel($st)) ?></span><?php if (!empty($row['status_detail'])): ?><small><?= eh($row['status_detail']) ?></small><?php endif; ?></td>
<td><?= eh($row['payment_method_id']??'—') ?><small>ID: <?= eh($row['payment_id']??'—') ?></small></td>
<td><?= eh(isset($row['last_payment_update']) ? date('d/m/Y H:i', strtotime((string)$row['last_payment_update'])) : '—') ?></td>
</tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<p class="foot">Dados registrados pelo checkout e pelas notificações do Mercado Pago. Horários conforme o servidor.</p>
</main></body></html>
