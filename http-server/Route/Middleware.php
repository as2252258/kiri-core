<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:17
 */

namespace HttpServer\Route;

use Closure;
use Exception;
use HttpServer\IInterface\IMiddleware;
use HttpServer\Route\Dispatch\Dispatch;

/**
 * Class Middleware
 * @package Snowflake\Snowflake\Route
 */
class Middleware
{

	/** @var array */
	private $middleWares = [];

	/**
	 * @param $call
	 * @return $this
	 */
	public function set($call)
	{
		$this->middleWares[] = $call;
		return $this;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function setMiddleWares(array $array)
	{
		$this->middleWares = $array;
		return $this;
	}

	/**
	 * @param $dispatch
	 * @return mixed
	 * @throws Exception
	 */
	public function getGenerate($dispatch)
	{
		$last = function ($passable) use ($dispatch) {
			return Dispatch::create($dispatch, $passable)->dispatch();
		};
		$data = array_reduce(array_reverse($this->middleWares), $this->core(), $last);
		$this->middleWares = [];
		return $data;
	}

	/**
	 * @return Closure
	 */
	public function core()
	{
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof IMiddleware) {
					return $pipe->handler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
