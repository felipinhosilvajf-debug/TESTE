<?php
require __DIR__ . '/leitor.php';
if (!isset($_SESSION['jogador_nick'])) {
    header('Location: login.php');
    exit;
}
$status = $_GET['status'] ?? 'pending';
$mensagens = [
    'success' => ['Pagamento recebido', 'O Mercado Pago está finalizando a confirmação. Suas Coins serão agendadas assim que o servidor confirmar o pagamento.'],
    'pending' => ['Pagamento pendente', 'Assim que o pagamento for aprovado, suas Coins serão agendadas automaticamente.'],
    'failure' => ['Pagamento não concluído', 'Nenhuma moeda foi agendada. Você pode voltar à loja e tentar novamente.'],
];
[$titulo, $mensagem] = $mensagens[$status] ?? $mensagens['pending'];
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#07131d;color:#edf8fb;font:14px Arial}.box{width:min(520px,88%);padding:32px;background:#102936;border:1px solid #245363;border-radius:9px;text-align:center}h1{color:#8af4ff}p{color:#a9c0c7;line-height:1.7}a{display:inline-block;margin:10px;padding:12px 18px;background:#28d9ef;color:#06202a;text-decoration:none;font-weight:bold;border-radius:5px}</style></head><body><main class="box"><h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p><a href="loja.php">VOLTAR À LOJA</a><a href="dashboard.php">MINHA CONTA</a></main></body></html>
