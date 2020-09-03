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
	 * @param Node $node
	 * @return mixed
	 * @throws Exception
	 */
	public function getGenerate($node)
	{
		$last = function ($passable) use ($node) {
			return Dispatch::create($node->handler, $passable)->dispatch();
		};
		$middleWares = $this->annotation($node);
		$data = array_reduce(array_reverse($middleWares), $this->core(), $last);
		$this->middleWares = [];
		return $node->callback = $data;
	}


	/**
	 * @param Node $node
	 * @return array
	 */
	public function annotation($node)
	{
		$middleWares = $this->middleWares;
		if (!$node->hasInterceptor()) {
			return $middleWares;
		}
		foreach ($node->getInterceptor() as $item) {
			$middleWares[] = $item[0];
		}
		return $middleWares;
	}


	/**
	 * @return Closure
	 */
	public function core()
	{
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof \HttpServer\IInterface\Middleware) {
					return $pipe->handler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
