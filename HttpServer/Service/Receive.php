<?php
declare(strict_types=1);

namespace HttpServer\Service;


use HttpServer\Service\Abstracts\Tcp;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;

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
