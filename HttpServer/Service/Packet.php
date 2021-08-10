<?php
declare(strict_types=1);

namespace HttpServer\Service;


use HttpServer\Service\Abstracts\Tcp;
use ReflectionException;
use Kiri\Exception\NotFindClassException;

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
		$this->on('packet', $this->createHandler('packet'));
	}


}
