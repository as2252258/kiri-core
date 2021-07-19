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
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactorId
	 * @throws Exception
	 */
    public function onConnect(Server $server, int $fd, int $reactorId)
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
    public function onClose(Server $server, int $fd)
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
    public function onReceive(Server $server, int $fd, int $reID, string $data)
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
    public function onPacket(Server $server, string $data, array $client)
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
    public function requestSpl(int $server_port, string $data): mixed
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
