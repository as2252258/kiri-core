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
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Core\ArrayAccess;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
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
	 * @return Router
	 * @throws Exception
	 */
	private function _protocol($request, $response): Router
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
		return Snowflake::app()->getRouter();
	}


	/**
	 * @param SResponse $response
	 * @param int $code
	 * @return void
	 */
	private function disconnect(SResponse $response, int $code = 500): void
	{
		$server = Snowflake::getWebSocket();
		if (!$server->isEstablished($response->fd)) {
			return;
		}
		$response->status($code);
		$response->end();
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
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

			$router = $this->_protocol($request, $response);

			[$sRequest, $sResponse] = $this->sRequest($request, $response);

			if (($node = $router->find_path($sRequest)) !== null) {
				$node->dispatch($sRequest, $sResponse);
			} else {
				$this->disconnect($response, 404);
			}
		} catch (\Throwable $exception) {
			$this->addError($exception, 'throwable');
			$response->status(500);
			$response->end($exception->getMessage());
		}
	}


	/**
	 * @param $request
	 * @param SResponse $response
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function sRequest($request, SResponse $response): array
	{
		/** @var Request $sRequest */
		$sRequest = Request::create($request);
		$sRequest->uri = '/' . Socket::HANDSHAKE . '::event';

		$sRequest->headers = new HttpHeaders(ArrayAccess::merge($request->server, $request->header));

		$sRequest->headers->replace('request_method', 'sw::socket');
		$sRequest->headers->replace('request_uri', $sRequest->uri);

		$sRequest->params = new HttpParams([], $request->get, []);

		$sRequest->parseUri();

		return [$sRequest, Response::create($response)];
	}


}
