<?php
declare(strict_types=1);

/*
 * Conexão compartilhada do painel administrativo.
 * O arquivo db.php fica na raiz do site e não deve ser versionado com credenciais.
 */
$rootDb = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'db.php';
if (!is_file($rootDb)) {
    http_response_code(500);
    exit('Conexão indisponível: o arquivo db.php da raiz não foi encontrado.');
}
require_once $rootDb;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Conexão indisponível: db.php não inicializou o PDO.');
}
