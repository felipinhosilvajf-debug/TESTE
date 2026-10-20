<?php
declare(strict_types=1);

// A URL /site/election.php usa a implementação única da raiz do projeto.
// Isso mantém a sessão do jogador e a conexão PDO compartilhadas com o restante do site.
$electionPage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'election.php';
if (!is_file($electionPage)) {
    http_response_code(500);
    exit('Página de eleição indisponível.');
}
require $electionPage;
