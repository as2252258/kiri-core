<?php

namespace Rpc;


use Exception;
use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Server;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class Service
 * @package Rpc
 */
class Service extends Component
{

    const defaultConfig = [
        'open_tcp_keepalive'      => true,
        'tcp_keepidle'            => 30,
        'tcp_keepinterval'        => 10,
        'tcp_keepcount'           => 10,
        'open_http_protocol'      => false,
        'open_websocket_protocol' => false,
    ];

    /**
     * @param Packet|Websocket|Receive|Http|null $server
     * @throws ConfigException
     * @throws Exception
     */
    public function instance(Packet|Websocket|Receive|null|Http $server): void
    {
        $service = Config::get('rpc');
        if (empty($service) || !is_array($service)) {
            return;
        }
        $mode = $service['mode'] ?? SWOOLE_SOCK_TCP6;

        if (Snowflake::port_already($service['port'])) {
            throw new Exception($this->already($service));
        }
        $this->debug(Snowflake::listen($service));

        if (!isset($service['setting'])) {
            $service['setting'] = self::defaultConfig;
        }

        $rpcServer = $server->addlistener($service['host'], $service['port'], $mode);
        $rpcServer->set($service['setting']);
        $this->addCallback($rpcServer, $mode);
    }


    /**
     * @param $service
     * @return string
     */
    #[Pure] private function already($service): string
    {
        return sprintf('Port %s::%d is already.', $service['host'], $service['port']);
    }


    /**
     * @param $mode
     * @throws Exception
     */
    private function addCallback($rpcServer, $mode)
    {
        $tcp = [SWOOLE_SOCK_TCP, SWOOLE_TCP, SWOOLE_TCP6, SWOOLE_SOCK_TCP6];
        $server = Snowflake::app()->getServer();
        if (in_array($mode, $tcp)) {
            $server->onBindCallback($rpcServer, 'connect', [make(OnConnect::class), 'onHandler']);
            $server->onBindCallback($rpcServer, 'close', [make(OnClose::class), 'onHandler']);
            $server->onBindCallback($rpcServer, 'receive', [make(OnReceive::class), 'onHandler']);
        } else {
            $server->onBindCallback($rpcServer, 'packet', [make(OnReceive::class), 'onHandler']);
        }
    }


}
