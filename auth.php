<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

function admin_logged_in(): bool
{
    return isset($_SESSION['admin_id'])
        && (int) $_SESSION['admin_id'] > 0;
}


/*
|--------------------------------------------------------------------------
| PROTEGER PÁGINA
|--------------------------------------------------------------------------
*/

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

function csrf_token(): string
{
    if (
        empty($_SESSION['admin_csrf'])
        || !is_string($_SESSION['admin_csrf'])
    ) {
        $_SESSION['admin_csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf'];
}


function verify_csrf(?string $token): bool
{
    if (
        empty($token)
        || empty($_SESSION['admin_csrf'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['admin_csrf'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN ATUAL
|--------------------------------------------------------------------------
*/

function current_admin(): ?array
{
    global $pdo;

    if (!admin_logged_in()) {
        return null;
    }

    static $adminLoaded = false;
    static $admin = null;

    if ($adminLoaded) {
        return $admin;
    }

    $adminLoaded = true;

    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            role,
            active,
            created_at,
            last_login
        FROM admin_users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        (int) $_SESSION['admin_id']
    ]);

    $result = $stmt->fetch();

    if (!$result) {

        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_username'],
            $_SESSION['admin_role']
        );

        return null;
    }

    if ((int) $result['active'] !== 1) {

        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_username'],
            $_SESSION['admin_role']
        );

        return null;
    }

    $admin = $result;

    return $admin;
}


/*
|--------------------------------------------------------------------------
| AUDITORIA
|--------------------------------------------------------------------------
*/

function audit(
    string $action,
    string $details = ''
): void {

    global $pdo;

    if (!admin_logged_in()) {
        return;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO admin_audit_log
            (
                admin_id,
                action,
                details,
                ip_address,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ");

        $stmt->execute([
            (int) $_SESSION['admin_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

    } catch (Throwable $e) {

        /*
         * Não derrubar o painel por falha de auditoria.
         */
    }
}