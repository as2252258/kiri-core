<?php

namespace Rpc;


use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Server;
use function Swoole\Coroutine\defer;


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


    const RPC_CONNECT = 'RPC::CONNECT';
    const RPC_CLOSE = 'RPC::CLOSE';

    private Router $router;


    /**
     * @throws Exception
     */
    public function init()
    {
        $this->router = Snowflake::getApp('router');
    }


    /**
     * @param Packet|Websocket|Receive|Http|null $server
     * @throws ConfigException
     * @throws Exception
     */
    public function instance(Packet|Websocket|Receive|null|Http $server): void
    {
        $service = Config::get('rpc');
        if (!is_array($service) || empty($service)) {
            return;
        }

        $listen_type = $service['mode'] ?? SWOOLE_SOCK_TCP6;
        $rpcServer = $server->addlistener($service['host'], $service['port'], $listen_type);
        if ($rpcServer === false) {
            throw new Exception('Listen rpc service fail.');
        }
        $this->debug(Snowflake::listen($service));

        $rpcServer->set($service['setting'] ?? self::defaultConfig);
        $this->addCallback($rpcServer, $service, $listen_type);
    }


	/**
	 * @param $rpcServer
	 * @param $config
	 * @param $mode
	 * @throws Exception
	 */
    private function addCallback($rpcServer, $config, $mode)
    {
        $tcp = [SWOOLE_SOCK_TCP, SWOOLE_TCP, SWOOLE_TCP6, SWOOLE_SOCK_TCP6];
        $server = Snowflake::app()->getServer();
        if (in_array($mode, $tcp)) {
            $connectCallback = $config['events'][Event::SERVER_ON_CONNECT] ?? [$this, 'onConnect'];
            $server->onBindCallback($rpcServer, 'connect', $connectCallback);

            $connectCallback = $config['events'][Event::SERVER_ON_CLOSE] ?? [$this, 'onClose'];
            $server->onBindCallback($rpcServer, 'close', $connectCallback);

            $connectCallback = $config['events'][Event::SERVER_ON_CONNECT] ?? [$this, 'onReceive'];
            $server->onBindCallback($rpcServer, 'receive', $connectCallback);
        } else {
            $connectCallback = $config['events'][Event::SERVER_ON_PACKET] ?? [$this, 'onPacket'];
            $server->onBindCallback($rpcServer, 'packet', $connectCallback);
        }
    }


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactorId
	 * @throws Exception
	 */
    private function onConnect(Server $server, int $fd, int $reactorId)
    {
        defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

        $config = $server->setting['enable_delay_receive'] ?? null;
        if ($config === true) {
            $server->confirm($fd);
        }
        Event::trigger(Service::RPC_CONNECT, [$server, $fd, $reactorId]);
    }


	/**
	 * @param Server $server
	 * @param int $fd
	 * on tcp client close
	 * @throws Exception
	 */
    private function onClose(Server $server, int $fd)
    {
        defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

        Event::trigger(Service::RPC_CLOSE, [$server, $fd]);
    }


    /**
     * @param Server $server
     * @param int $fd
     * @param int $reID
     * @param string $data
     * @throws Exception
     */
    private function onReceive(Server $server, int $fd, int $reID, string $data)
    {
        defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
        try {
            $client = $server->getClientInfo($fd, $reID);

            $request = $this->requestSpl((int)$client['server_port'], $data);

            $result = $this->router->find_path($request)?->dispatch();

            $server->send($fd, $result);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'rpc-service');
            $server->send($fd, $exception->getMessage());
        }
    }


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $client
	 * @throws Exception
	 */
    private function onPacket(Server $server, string $data, array $client)
    {
        defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
        try {
            $request = $this->requestSpl((int)$client['server_port'], $data);

            $result = $this->router->find_path($request)?->dispatch();

            $server->sendto($client['address'], $client['port'], $result);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'rpc-service');
            $server->sendto($client['address'], $client['port'], $exception->getMessage());
        }
    }


	/**
	 * @param int $server_port
	 * @param string $data
	 * @return mixed
	 * @throws Exception
	 */
    private function requestSpl(int $server_port, string $data): mixed
    {
        $sRequest = new Request();

        [$cmd, $repeat, $body] = explode("\n", $data);
        if (is_null($body) || is_null($cmd) || !empty($repeat)) {
            throw new Exception('Protocol format error.');
        }

        if (is_string($body) && is_null($data = Json::decode($body))) {
            throw new Exception('Protocol format error.');
        }

        $sRequest->params->setPosts($data);
        $sRequest->headers->setRequestUri('rpc/p' . $server_port . '/' . ltrim($cmd, '/'));
        $sRequest->headers->setRequestMethod(Request::HTTP_CMD);

        return Context::setContext('request', $sRequest);
    }


}
