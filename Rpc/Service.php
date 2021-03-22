<?php

namespace Rpc;


use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;


/**
 * Class Service
 * @package Rpc
 */
class Service extends Component
{


    /**
     * @throws \Snowflake\Exception\ConfigException
     */
    public function instance(Packet|Websocket|Receive|null|Http $server): void
    {
        $services = Config::get('rpc.service', false, []);
        if (empty($services)) {
            return;
        }
        foreach ($services as $service) {
            $mode = $service['mode'] ?? SWOOLE_SOCK_TCP6;
            $rpcServer = $server->addlistener($service['host'], $service['port'], $mode);
            $rpcServer->set([
                'open_tcp_keepalive'      => true,
                'tcp_keepidle'            => 30,
                'tcp_keepinterval'        => 10,
                'tcp_keepcount'           => 10,
                'open_http_protocol'      => false,
                'open_websocket_protocol' => false,
            ]);
        }
    }


}
