<?php

namespace Server\Service;


use Exception;
use Server\SInterface\OnRequest;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;

/**
 *
 */
class WebSocket extends \Server\Abstracts\Websocket
{


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		parent::onHandshake($request, $response); // TODO: Change the autogenerated stub

		$response->status(101);
		$response->end();
	}


	/**
	 * @param Server $server
	 * @param Frame $frame
	 */
	public function onMessage(Server $server, Frame $frame): void
	{
		// TODO: Implement OnMessage() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd): void
	{
		// TODO: Implement OnClose() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd): void
	{
		// TODO: Implement OnDisconnect() method.
	}
}
