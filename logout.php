<?php
// Encerra a sessão do jogador e retorna à página inicial pública.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];

// Invalida o cookie de sessão, se estiver sendo utilizado.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Sempre volta para https://virtualboost.online/, fora da pasta /site.
header('Location: https://virtualboost.online/', true, 302);
exit;
