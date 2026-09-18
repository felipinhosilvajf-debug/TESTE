<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (!verify_csrf($_POST['csrf'] ?? null)) {
    $_SESSION['admin_error'] = 'Sessão inválida. Recarregue a página.';
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['admin_error'] = 'Informe usuário e senha.';
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        password_hash,
        role,
        active
    FROM admin_users
    WHERE username = ?
    LIMIT 1
");

$stmt->execute([$username]);

$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['admin_error'] = 'Usuário ou senha inválidos.';
    header('Location: login.php');
    exit;
}

if ((int)$admin['active'] !== 1) {
    $_SESSION['admin_error'] = 'Este administrador está desativado.';
    header('Location: login.php');
    exit;
}

if (!password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_error'] = 'Usuário ou senha inválidos.';
    header('Location: login.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_role'] = $admin['role'];

$stmt = $pdo->prepare("
    UPDATE admin_users
    SET last_login = NOW()
    WHERE id = ?
");

$stmt->execute([
    (int)$admin['id']
]);

audit(
    'LOGIN',
    'Administrador entrou no painel.'
);

header('Location: index.php');
exit;