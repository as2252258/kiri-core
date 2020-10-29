<?php
declare(strict_types=1);

namespace HttpServer\Service;


use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use HttpServer\Service\Abstracts\Http as AHttp;

class Http extends AHttp
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
		$this->on('request', $this->createHandler('request'));
	}


}
