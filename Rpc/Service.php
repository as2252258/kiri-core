<?php

namespace Rpc;


use Annotation\Inject;
use Exception;
use HttpServer\Http\Context;
use HttpServer\Route\Router;
use Server\Constant;
use Server\Events\OnAfterRequest;
use Snowflake\Core\Json;
use Snowflake\Events\EventDispatch;
use Snowflake\Events\EventProvider;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Server;
use function Swoole\Coroutine\defer;


/**
 * Class Service
 * @package Rpc
 */
class Service extends \Server\Abstracts\Server
{

	const RPC_CONNECT = 'RPC::CONNECT';
	const RPC_CLOSE = 'RPC::CLOSE';

	private Router $router;


	/** @var EventProvider */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


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
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::CONNECT, null, [$server, $fd, $reactorId]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * on tcp client close
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::CLOSE, null, [$server, $fd]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * on tcp client close
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::DISCONNECT, null, [$server, $fd]);
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
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));
		try {
			$client = $server->getClientInfo($fd, $reID);

			$request = $this->requestSpl((int)$client['server_port'], $data, $fd);

			$result = $this->router->find_path($request)?->dispatch();

			$server->send($fd, $result);
		} catch (\Throwable $exception) {
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
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));
		try {
			$request = $this->requestSpl((int)$client['server_port'], $data);

			$result = $this->router->find_path($request)?->dispatch();

			$server->sendto($client['address'], $client['port'], $result);
		} catch (\Throwable $exception) {
			$server->sendto($client['address'], $client['port'], $exception->getMessage());
		}
	}


	/**
	 * @param int $server_port
	 * @param string $data
	 * @param int $fd
	 * @return mixed
	 * @throws Exception
	 */
	public function requestSpl(int $server_port, string $data, int $fd = 0): \HttpServer\Http\Request
	{
		$sRequest = new Request();

		[$cmd, $repeat, $body] = explode("\n", $data);
		if (is_null($body) || is_null($cmd) || !empty($repeat)) {
			throw new Exception('Protocol format error.');
		}

		if (is_string($body) && is_null($data = Json::decode($body))) {
			throw new Exception('Protocol format error.');
		}

		$sRequest->fd = $fd;
		$sRequest->post = $data;
		$sRequest->header['request_uri'] = 'rpc/p' . $server_port . '/' . ltrim($cmd, '/');
		$sRequest->header['request_method'] = 'rpc';

		Context::setContext(Request::class, $sRequest);

		return \request();
	}


}
