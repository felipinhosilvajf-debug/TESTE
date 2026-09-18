<?php
// Configurações do Banco de Dados do Lineage II
$host = '127.0.0.1';     // IP do seu servidor MySQL
$port = '3306';          // Porta padrão do MySQL
$db   = 'l2mythras';         // Nome do banco de dados do L2
$user = 'root';          // Usuário do MySQL
$pass = 'mas3510';// Senha do MySQL

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Em ambiente de produção, não exiba $e->getMessage() para evitar expor dados
    die("Erro ao conectar com o Reino: " . $e->getMessage());
}