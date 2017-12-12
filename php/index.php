<?php
session_start();

require_once __DIR__.'/vendor/autoload.php';

$serverConfig = [
    'host' => getenv('REDIS_HOST'),
    'port' => getenv('REDIS_PORT'),
];


$_SESSION['count'] = !isset($_SESSION['count']) ? 1 : $_SESSION['count'] + 1;

echo json_encode(
    [
        'ip' => getUserIP(),
        'date' => date('Y-m-d'),
    ]
);

(new Predis\Client($serverConfig))->set('session', json_encode($_SESSION));

function getUserIP()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    $ip = ($ip === false) ? '0.0.0.0' : $ip;

    return $ip;
}
