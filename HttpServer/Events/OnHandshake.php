<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Http\Request as SRequest;
use Swoole\Http\Response as SResponse;
use Swoole\WebSocket\Server;


/**
 * Class OnHandshake
 * @package HttpServer\Events
 */
class OnHandshake extends Callback
{


	/**
	 * @param $request
	 * @param $response
	 * @throws Exception
	 */
	private function resolveParse($request, $response)
	{
		/** @var Server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			throw new Exception('protocol error.', 500);
		}
		$key = base64_encode(sha1(
			$request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
			TRUE
		));
		$headers = [
			'Upgrade'               => 'websocket',
			'Connection'            => 'Upgrade',
			'Sec-websocket-Accept'  => $key,
			'Sec-websocket-Version' => '13',
		];
		if (isset($request->header['sec-websocket-protocol'])) {
			$headers['Sec-websocket-Protocol'] = $request->header['sec-websocket-protocol'];
		}
		foreach ($headers as $key => $val) {
			$response->header($key, $val);
		}
	}


	/**
	 * @param $response
	 * @param int $code
	 * @return false
	 */
	private function disconnect($response, $code = 500): bool
	{
		$response->status($code);
		$response->end();
		return false;
	}


	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return void
	 * @throws ComponentException
	 */
	public function onHandler(SRequest $request, SResponse $response): void
	{
		Coroutine::defer(function () {
			fire(Event::EVENT_AFTER_REQUEST);
		});
		$this->execute($request, $response);
	}


	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return mixed
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function execute(SRequest $request, SResponse $response): mixed
	{
		try {
			$this->resolveParse($request, $response);
			if (isset($request->get['debug']) && $request->get['debug'] == 'test') {
				$response->status(101);
				$response->end();
				return true;
			}

			$router = Snowflake::app()->getRouter();
			$node = $router->search('/' . Socket::HANDSHAKE . '::event', 'sw::socket');
			if ($node === null) {
				return $this->disconnect($response, 502);
			}
			return $node->dispatch($request, $response);
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine());
			return $this->disconnect($response, 500);
		} finally {
			$this->eventTrigger($request, $response);
		}
	}


	/**
	 * @param $request
	 * @param $response
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function eventTrigger($request, $response)
	{
		go(function () use ($request, $response) {
			$manager = Snowflake::app()->getEvent();
			$manager->trigger(Event::SERVER_HANDSHAKE, [$request, $response]);
		});
	}


}
