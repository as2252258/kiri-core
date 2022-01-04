<?php

namespace Kiri\Websocket;

use Http\Handler\DataGrip;
use Http\Handler\Router;
use Kiri\Abstracts\Component;
use Kiri\Server\Contract\OnOpenInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Server\Contract\OnCloseInterface;
use Server\Contract\OnHandshakeInterface;
use Server\Contract\OnMessageInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame;


/**
 *
 */
class Server extends Component implements OnHandshakeInterface, OnMessageInterface, OnCloseInterface
{

	public Router $router;


	public string $serverName = 'ws';


	public mixed $callback = null;


	public mixed $server;



	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function init()
	{
		$this->router = $this->container->get(DataGrip::class)->get($this->serverName);
		$handler = $this->router->find('/', 'GET');
		if (is_int($handler) || is_null($handler)) {
			return;
		}
		$this->callback = $handler->callback[0];
	}


	/**
	 * @param \Swoole\Server $server
	 * @param int $fd
	 */
	public function onClose(\Swoole\Server $server, int $fd): void
	{
		if ($this->callback instanceof OnCloseInterface) {
			$this->callback->onClose($server, $fd);
		}
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		try {
			if (!$this->callback instanceof OnHandshakeInterface) {
				throw new \Exception('Page not found.');
			}
			$this->callback->onHandshake($request, $response);

			$this->afterHandshake($request);
			if ($this->server instanceof \Swoole\Coroutine\Http\Server) {
				while (true) {
					$data = $response->recv();
					if ($data === '' || $data === false || $data instanceof CloseFrame) {
						$this->onClose($this->server, $response->fd);
						break;
					}
					$this->onMessage($this->server, $data);
				}
			}
		} catch (\Throwable $throwable) {
			$response->status(500, $throwable->getMessage());
			$response->end();
		}
	}


	/**
	 * @param $request
	 */
	public function afterHandshake($request)
	{
		if (!($this->callback instanceof OnOpenInterface)) {
			return;
		}
		$this->callback->onOpen($this->server, $request);
	}


	/**
	 * @param \Swoole\Server $server
	 * @param Frame $frame
	 */
	public function onMessage(\Swoole\Server $server, Frame $frame): void
	{
		if ($this->callback instanceof OnMessageInterface) {
			$this->callback->onMessage($server, $frame);
		}
	}
}
