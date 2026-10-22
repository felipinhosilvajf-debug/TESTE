<?php
header('Content-Type: text/plain; charset=utf-8');

$token = getenv('MP_ACCESS_TOKEN');
$url = getenv('SITE_URL');

echo 'MP_ACCESS_TOKEN configurado: '
    . (($token !== false && trim($token) !== '') ? 'SIM' : 'NAO')
    . PHP_EOL;

echo 'SITE_URL configurado: '
    . (($url !== false && trim($url) !== '') ? 'SIM' : 'NAO')
    . PHP_EOL;

echo 'SITE_URL HTTPS valido: '
    . (($url !== false
        && filter_var(trim($url), FILTER_VALIDATE_URL)
        && str_starts_with(trim($url), 'https://')) ? 'SIM' : 'NAO')
    . PHP_EOL;