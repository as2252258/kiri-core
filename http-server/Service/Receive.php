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
 * Class Receive
 * @package HttpServer\Events
 */
class Receive extends Tcp
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
		$this->on('receive', $this->createHandler('receive'));
		$this->on('close', $this->createHandler('close'));
	}


}
