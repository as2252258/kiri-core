<?php


namespace HttpServer\Service;


use Exception;
use ReflectionException;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request as SRequest;
use Swoole\Http\Response as SResponse;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use HttpServer\Service\Abstracts\Websocket as HAWebsocket;
use HttpServer\Route\Annotation\Websocket as AWebsocket;

class Websocket extends HAWebsocket
{

	/**
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function onInit()
	{
		$this->onHandlerListener();
		$this->onBaseListener();
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function onBaseListener()
	{
		$this->on('handshake', $this->createHandler('handshake'));
		$this->on('message', $this->createHandler('message'));
		$this->on('close', $this->createHandler('close'));
	}

}
