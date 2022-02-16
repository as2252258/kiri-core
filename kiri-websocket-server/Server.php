<?php

namespace Kiri\Websocket;

use Exception;
use Kiri\Abstracts\AbstractServer;
use Kiri\Message\Handler\DataGrip;
use Kiri\Message\Handler\RouterCollector;
use Kiri\Server\Contract\OnCloseInterface;
use Kiri\Server\Contract\OnHandshakeInterface;
use Kiri\Server\Contract\OnMessageInterface;
use Kiri\Server\Contract\OnOpenInterface;
use Kiri\Server\SwooleServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;


/**
 * websocket server
 */
class Server extends AbstractServer
{

	public RouterCollector $router;


	const SHA1_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';


	public mixed $callback = null;


	public Sender $sender;


	public FdCollector $collector;


	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function init()
	{
		$container = $this->getContainer();

		$this->router = $container->get(DataGrip::class)->get('ws');
		$handler = $this->router->find('/', 'GET');

		$this->collector = $container->get(FdCollector::class);

		$this->sender = $container->get(Sender::class);
		if ($container->has(SwooleServerInterface::class)) {
			$this->sender->setServer($container->get(SwooleServerInterface::class));
		}
		if (is_int($handler) || is_null($handler)) {
			return;
		}
		$this->callback = $handler->callback[0];
	}


	/**
	 * @param int $fd
	 */
	public function onClose(int $fd): void
	{
		$this->collector->remove($fd);
		if (!$this->sender->isEstablished($fd)) {
			return;
		}
		if ($this->callback instanceof OnCloseInterface) {
			$this->callback->onClose($fd);
		}
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		try {
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

			if ($this->callback instanceof OnHandshakeInterface) {
				$this->callback->onHandshake($request, $response);
			} else {
				$response->setStatusCode(101, 'connection success.');
				$response->end();

				if ($this->callback instanceof OnOpenInterface) {
					$this->callback->onOpen($request);
				}

			}
		} catch (\Throwable $throwable) {
			$response->status(4000 + $throwable->getCode(), $throwable->getMessage());
			$response->end();
		}
	}


	/**
	 * @param Frame $frame
	 */
	public function onMessage(Frame $frame): void
	{
		if ($frame->opcode == 0x08) {
			$this->collector->remove($frame->fd);
		} else {
			if (!($this->callback instanceof OnMessageInterface)) {
				return;
			}
			$this->callback->onMessage($frame);
		}
	}
}
