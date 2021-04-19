<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use Snowflake\Core\ArrayAccess;
use Snowflake\Event;
use Snowflake\Snowflake;
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
		$key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
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
	 * @param SResponse $response
	 * @param int $code
	 * @return false
	 */
	private function disconnect(SResponse $response, $code = 500): bool
	{
		$server = Snowflake::getWebSocket();
		if (!$server->exist($response->fd)) {
			return false;
		}
		$response->status($code);
		$response->end();
		return false;
	}


	/**
	 * @param $response
	 * @param int $code
	 * @return false
	 */
	private function connect($response, $code = 101): bool
	{
		$response->status($code);
		$response->end();
		return false;
	}


	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return void
	 * @throws Exception
	 */
	public function onHandler(SRequest $request, SResponse $response): void
	{
		try {
			$this->execute($request, $response);

			$clientInfo = Snowflake::getWebSocket()->getClientInfo($request->fd);

			$event = Snowflake::app()->getEvent();

			$eventName = 'listen ' . $clientInfo['server_port'] . ' ' . Event::SERVER_HANDSHAKE;
			$event->trigger($eventName, [$request, $response]);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'throwable');
			$this->disconnect($response, 401);
		} finally {
			fire(Event::SYSTEM_RESOURCE_CLEAN);
			logger_insert();
		}
	}


	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return mixed
	 * @throws Exception
	 */
	private function execute(SRequest $request, SResponse $response): mixed
	{
		$this->resolveParse($request, $response);

		$router = Snowflake::app()->getRouter();

		/** @var Request $sRequest */
		$sRequest = Request::create($request);
		$sRequest->uri = '/' . Socket::HANDSHAKE . '::event';

		$sRequest->headers = new HttpHeaders(ArrayAccess::merge($request->server, $request->header));

		$sRequest->headers->replace('request_method', 'sw::socket');
		$sRequest->headers->replace('request_uri', $sRequest->uri);

		$sRequest->params = new HttpParams([], $request->get, []);

		$sRequest->parseUri();

		if (($node = $router->find_path($sRequest)) === null) {
			return $this->disconnect($response, 404);
		}
		Response::create($response);

		return $node->dispatch($sRequest, \response());
	}


}
