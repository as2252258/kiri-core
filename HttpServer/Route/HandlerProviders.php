<?php

namespace HttpServer\Route;

use Snowflake\Abstracts\BaseObject;


/**
 *
 */
class HandlerProviders extends BaseObject
{

	private array $handlers = [];


	/**
	 * @param $path
	 * @param $method
	 * @return mixed
	 */
	public function get($path, $method): mixed
	{
		return $this->handlers[$method][$path] ?? null;
	}


	/**
	 * @param $method
	 * @param $path
	 * @param $handler
	 */
	public function add($method, $path, $handler)
	{
		$this->handlers[$method][$path] = $handler;
	}

}
