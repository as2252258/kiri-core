<?php

namespace Server\Service;


use Exception;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Abstracts\Utility\EventDispatchHelper;
use Server\Abstracts\Utility\ResponseHelper;
use Server\Constrict\WebSocketEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnCloseInterface;
use Server\SInterface\OnHandshakeInterface;
use Server\SInterface\OnMessageInterface;
use Server\SInterface\OnRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;

/**
 *
 */
class WebSocket implements OnHandshakeInterface, OnMessageInterface, OnCloseInterface
{


    use EventDispatchHelper;
    use ResponseHelper;


    /**
     * @var ExceptionHandlerInterface
     */
    public ExceptionHandlerInterface $exceptionHandler;


    /**
     * @throws ConfigException
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
        // TODO: Implement OnHandshakeInterface() method.
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
		$response->status(101);
		$response->end();
	}


	/**
	 * @param Server $server
	 * @param Frame $frame
	 */
	public function onMessage(Server $server, Frame $frame): void
	{
		// TODO: Implement OnMessageInterface() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd): void
	{
		// TODO: Implement OnCloseInterface() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd): void
	{
		// TODO: Implement OnDisconnectInterface() method.
	}
}
