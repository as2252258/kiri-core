<?php


namespace HttpServer\Service;


use Exception;
use HttpServer\Service\Abstracts\Tcp;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPacket
 * @package HttpServer\Events
 */
class Packet extends Tcp
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
		$this->on('connect', $this->createHandler('connect'));
		$this->on('packet', $this->createHandler('packet'));
		$this->on('close', $this->createHandler('close'));
	}


}
