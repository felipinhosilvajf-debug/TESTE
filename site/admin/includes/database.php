<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONEXÃO DO PAINEL ADMIN
|--------------------------------------------------------------------------
| Reutiliza o mesmo db.php do site principal.
|--------------------------------------------------------------------------
*/

$rootDb = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db.php';

if (!file_exists($rootDb)) {
    die('Erro: db.php não encontrado em: ' . $rootDb);
}

require_once $rootDb;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Erro: conexão PDO não foi criada pelo db.php.');
}