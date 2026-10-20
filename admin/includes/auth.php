<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function admin_logged_in(): bool {
    return isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] > 0;
}
function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
function csrf_token(): string {
    if (empty($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}
function verify_csrf(?string $token): bool {
    return is_string($token) && $token !== '' && !empty($_SESSION['admin_csrf'])
        && hash_equals((string) $_SESSION['admin_csrf'], $token);
}
function audit(string $action, string $details = ''): void {
    global $pdo;
    if (!admin_logged_in()) return;
    try {
        $stmt = $pdo->prepare('INSERT INTO admin_audit_log (admin_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([(int) $_SESSION['admin_id'], $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { /* Auditoria não deve derrubar o painel. */ }
}
