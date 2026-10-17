<?php
// Encerra a sessão do jogador e retorna à página inicial do site.
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

// Retorna à raiz do site, sem depender de um index.php específico.
header('Location: ./');
exit;
