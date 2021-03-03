<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
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
		$this->execute($request, $response);
		fire(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return mixed
	 * @throws Exception
	 */
	private function execute(SRequest $request, SResponse $response): mixed
	{
		try {
			$this->resolveParse($request, $response);
			if (isset($request->get['debug']) && $request->get['debug'] == 'test') {
				return $this->connect($response, 101);
			}
			$router = Snowflake::app()->getRouter();

			$sRequest = Request::create($request);
			$sRequest->uri = '/' . Socket::HANDSHAKE . '::event';
			$sRequest->headers->replace('request_method', 'sw::socket');
			$sRequest->headers->replace('request_uri', $sRequest->uri);
			$sRequest->parseUri();

			if (($node = $router->find_path($sRequest)) === null) {
				return $this->disconnect($response, 502);
			}
			return $node->dispatch($request, $response);
		} catch (\Throwable $exception) {
			$this->addError($exception);
			return $this->disconnect($response, 500);
		}
	}


}
