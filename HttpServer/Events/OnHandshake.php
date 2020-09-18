<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
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
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return bool|string
	 * @throws Exception
	 */
	public function onHandler(SRequest $request, SResponse $response)
	{
		/** @var Server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			return false;
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

		/** @var AWebsocket $manager */
		$manager = Snowflake::app()->getAnnotation()->get('websocket');
		if ($manager->has($manager->getName(AWebsocket::HANDSHAKE))) {
			$manager->runWith($manager->getName(AWebsocket::HANDSHAKE), [$request, $response]);
		} else {
			$response->status(502);
			$response->end();
		}
		return true;
	}


}
