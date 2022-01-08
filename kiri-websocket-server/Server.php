<?php

namespace Kiri\Websocket;

use Exception;
use Http\Handler\DataGrip;
use Http\Handler\Router;
use Kiri\Abstracts\AbstractServer;
use Kiri\Annotation\Inject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Server\Contract\OnCloseInterface;
use Server\Contract\OnHandshakeInterface;
use Server\Contract\OnMessageInterface;
use Server\Contract\OnOpenInterface;
use Server\SwooleServerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;


/**
 * websocket server
 */
class Server extends AbstractServer
{

	public Router $router;


	const SHA1_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';


	public mixed $callback = null;


	/**
	 * @var WebSocketInterface
	 */
	#[Inject(WebSocketInterface::class)]
	public WebSocketInterface $server;



	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function init()
	{
		$this->router = $this->container->get(DataGrip::class)->get('ws');
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
		$clientInfo = $server->getClientInfo($fd);
		if (!isset($clientInfo['websocket_status'])) {
			return;
		}
		if ($this->callback instanceof OnCloseInterface) {
			$this->callback->onClose($server, $fd);
		}
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception
	 */
	protected function setWebSocketProtocol(Request $request, Response $response)
	{
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (preg_match($patten, $secWebSocketKey) === 0 || strlen(base64_decode($secWebSocketKey)) !== 16) {
			throw new Exception('protocol error.', 500);
		}
		$key = base64_encode(sha1($request->header['sec-websocket-key'] . self::SHA1_KEY, true));
		$headers = [
			'Upgrade'               => 'websocket',
			'Connection'            => 'Upgrade',
			'Sec-Websocket-Accept'  => $key,
			'Sec-Websocket-Version' => '13',
		];
		if (isset($request->header['sec-websocket-protocol'])) {
			$headers['Sec-Websocket-Protocol'] = $request->header['sec-websocket-protocol'];
		}
		foreach ($headers as $key => $val) {
			$response->header($key, $val);
		}
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		try {
			$this->setWebSocketProtocol($request, $response);
			if ($this->callback instanceof OnHandshakeInterface) {
				$this->callback->onHandshake($request, $response);
			} else {
				$response->setStatusCode(101, 'connection success.');
				$response->end();
			}
//			if ($this->server instanceof \Swoole\Coroutine\Http\Server) {
//				$response->upgrade();
//				$this->deferOpen($request);
//				while (true) {
//					$receive = $response->recv();
//					if ($receive === '' || $receive instanceof CloseFrame) {
//						$response->close();
//						if ($this->callback instanceof OnCloseInterface) {
//							$this->callback->onClose($this->server, $response->fd);
//						}
//						break;
//					}
//					$this->callback->onMessage($this->server, $receive);
//				}
//			} else {
//				$this->deferOpen($request);
//			}
			if ($response->isWritable()) {
				$this->deferOpen($request);
			}
		} catch (\Throwable $throwable) {
			$response->status(4000 + $throwable->getCode(), $throwable->getMessage());
			$response->end();
		}
	}


	private function deferOpen($request)
	{
		if ($this->callback instanceof OnOpenInterface) {
			$this->callback->onOpen($request);
		}
	}


	/**
	 * @param $server
	 * @param Frame $frame
	 */
	public function onMessage($server, Frame $frame): void
	{
		if ($this->callback instanceof OnMessageInterface) {
			$this->callback->onMessage($frame);
		}
	}
}
