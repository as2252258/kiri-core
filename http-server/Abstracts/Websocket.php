<?php

namespace Server\Abstracts;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constrict\ResponseEmitter;
use Server\Constrict\WebSocketEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnClose;
use Server\SInterface\OnHandshake;
use Server\SInterface\OnMessage;
use Swoole\Http\Request;
use Swoole\Http\Response;


/**
 *
 */
abstract class Websocket extends Server implements OnHandshake, OnMessage, OnClose
{

	use EventDispatchHelper;
	use ResponseHelper;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$exceptionHandler = Config::get('exception.websocket', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
		$this->responseEmitter = Kiri::getDi()->get(WebSocketEmitter::class);
	}



	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		// TODO: Implement OnHandshake() method.
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
			$explode = explode(',',$request->header['sec-websocket-protocol']);
			$headers['Sec-websocket-Protocol'] = $explode[0];
		}
		foreach ($headers as $key => $val) {
			$response->setHeader($key, $val);
		}
	}


}
