<?php


use HttpServer\Server;
use Snowflake\Snowflake;

/** @var \HttpServer\Route\Router $router */
$router = Snowflake::getApp('router');
$router->addRpcService(9527, function (\Rpc\Actuator $actuator) {
    $actuator->addListener('', '');
    $actuator->addListener('', '');
});

return [
    'rpc' => [
        'type'     => Server::TCP,
        'host'     => '0.0.0.0',
        'mode'     => SWOOLE_SOCK_TCP,
        'port'     => 5377,
        'setting'  => [
            'open_tcp_keepalive'      => true,
            'tcp_keepidle'            => 30,
            'tcp_keepinterval'        => 10,
            'tcp_keepcount'           => 10,
            'open_http_protocol'      => false,
            'open_websocket_protocol' => false,
        ],
        'events'   => [
            Server::SERVER_ON_CONNECT => [],
            Server::SERVER_ON_CLOSE   => [],
        ],
        'registry' => [
            'protocol' => 'consul',
            'address'  => [
                'host' => '47.14.25.45',
                'port' => 5527,
                'path' => ''
            ],
        ],
    ]

];
