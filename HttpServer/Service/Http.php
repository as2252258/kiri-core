<?php


namespace HttpServer\Service;


use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use ReflectionException;
use Snowflake\Core\JSON;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
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
