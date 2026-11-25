<?php
session_start();

function carregarConta($nick) {
    // Limpa caracteres inválidos para segurança
    $nickLimpo = preg_replace('/[^a-zA-Z0-9_\[\]\(\)\.\-\@]/', '', $nick);
    if (empty($nickLimpo)) return false;

    $pasta = __DIR__ . '/../scriptfiles/Players/';
    
    // Verifica se o GM salva como "Nick.ini" ou apenas "Nick"
    if (file_exists($pasta . $nickLimpo . '.ini')) {
        $caminho = $pasta . $nickLimpo . '.ini';
    } elseif (file_exists($pasta . $nickLimpo)) {
        $caminho = $pasta . $nickLimpo;
    } else {
        return false;
    }

    $dados = [];
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        if (strpos($linha, '=') !== false) {
            list($chave, $valor) = explode('=', $linha, 2);
            $dados[trim($chave)] = trim($valor);
        }
    }

    return $dados;
}
?>
