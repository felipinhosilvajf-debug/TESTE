<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['user_login'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

$accountName = (string)$_SESSION['user_login'];
$orders = [];
$pageError = '';

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('Conexão indisponível.');
    }
    $stmt = $pdo->prepare("
        SELECT id, character_name, coins, amount, status, created_at, paid_at, delivered_at
        FROM donate_purchase_orders
        WHERE account_name = ?
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$accountName]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[VBoost histórico Donate] ' . $e->getMessage());
    $pageError = 'Não foi possível carregar o histórico agora. Atualize a página em instantes.';
}

function donateHistoryStatus(array $order): array
{
    if (!empty($order['delivered_at']) || (string)$order['status'] === 'delivered') {
        return ['Entregue', 'success', 'Pagamento confirmado; Donate Coins entregues.'];
    }
    if ((string)$order['status'] === 'paid' || (string)$order['status'] === 'approved') {
        return ['Pagamento aprovado', 'success', 'Pagamento confirmado; aguardando a conclusão da entrega.'];
    }
    if ((string)$order['status'] === 'rejected') {
        return ['Recusado', 'danger', 'Este pedido foi recusado.'];
    }
    return ['Aguardando pagamento', 'pending', 'Assim que o Mercado Pago confirmar o pagamento, a entrega será processada.'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#070910">
<title>L2 Virtual Boost — Histórico de Donate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{color-scheme:dark;--bg:#070910;--panel:#101521;--line:rgba(205,170,91,.28);--gold:#d9b66b;--gold2:#f4dda0;--text:#e8e5dc;--muted:#9ca5b6;--green:#62dfa0;--red:#ff6875}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 70% 0%,#17243a 0,transparent 42%),var(--bg);color:var(--text);font:17px Rajdhani,Arial,sans-serif}.wrap{max-width:1150px;margin:auto;padding:30px 20px 60px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:1px solid var(--line);padding-bottom:22px;margin-bottom:25px}.brand{font:700 20px Cinzel,serif}.brand span{color:var(--gold)}.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 15px;border:1px solid var(--line);border-radius:8px;color:var(--gold2);text-decoration:none;background:#101521}.heading{font:600 27px Cinzel,serif;margin:0 0 8px}.muted{color:var(--muted)}.panel{background:rgba(16,21,33,.92);border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-top:20px}.panel-head{padding:18px 20px;border-bottom:1px solid var(--line)}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:760px}th,td{text-align:left;padding:13px 15px;border-bottom:1px solid rgba(205,170,91,.12)}th{color:var(--gold2);font-size:13px;text-transform:uppercase;letter-spacing:.08em}td{font-size:16px}.badge{display:inline-block;padding:5px 9px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em}.success{color:#8bf0b8;background:rgba(42,156,98,.16)}.pending{color:#f4dda0;background:rgba(217,182,107,.15)}.danger{color:#ff9aa4;background:rgba(255,104,117,.15)}.alert{padding:16px 20px;color:#ffb2ba}.empty{padding:25px 20px;color:var(--muted)}.footnote{padding:15px 20px;color:var(--muted);font-size:14px}@media(max-width:600px){.wrap{padding:20px 12px}.heading{font-size:22px}th,td{padding:10px}}
</style>
</head>
<body>
<main class="wrap">
  <header class="top">
    <div><div class="brand">L2 <span>VIRTUAL BOOST</span></div><div class="muted">Conta: <?= htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8') ?></div></div>
    <nav class="actions"><a class="btn" href="dashboard.php">← Voltar ao Dashboard</a><a class="btn" href="comprar-donate.php">💎 Comprar Donate</a></nav>
  </header>
  <h1 class="heading">📜 Histórico de transações Donate</h1>
  <p class="muted">Aqui você acompanha seus pedidos, a confirmação do Mercado Pago e a entrega das Donate Coins.</p>
  <?php if ($pageError !== ''): ?>
    <section class="panel alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></section>
  <?php else: ?>
    <section class="panel">
      <div class="panel-head"><strong><?= count($orders) ?> transação(ões) recente(s)</strong><div class="muted">Mostrando até 100 pedidos da sua conta.</div></div>
      <?php if (empty($orders)): ?>
        <div class="empty">Você ainda não tem transações de Donate. Quando fizer um pedido, ele aparecerá aqui.</div>
      <?php else: ?>
        <div class="table-wrap"><table>
          <thead><tr><th>Pedido</th><th>Personagem</th><th>Donate Coins</th><th>Valor</th><th>Data do pedido</th><th>Status</th><th>Pagamento / entrega</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $order): [$statusLabel, $statusClass, $statusDetail] = donateHistoryStatus($order); ?>
            <tr>
              <td>#<?= (int)$order['id'] ?></td>
              <td><?= htmlspecialchars((string)$order['character_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= number_format((int)$order['coins'], 0, ',', '.') ?></td>
              <td>R$ <?= number_format((float)$order['amount'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars((string)$order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span><div class="muted"><?= htmlspecialchars($statusDetail, ENT_QUOTES, 'UTF-8') ?></div></td>
              <td><div>Pagamento: <?= htmlspecialchars((string)($order['paid_at'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div><div>Entrega: <?= htmlspecialchars((string)($order['delivered_at'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
      <div class="footnote">O status é atualizado quando o Mercado Pago notifica o site. Se o pagamento foi aprovado e as moedas não apareceram, entre em contato com a administração e informe o número do pedido.</div>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
