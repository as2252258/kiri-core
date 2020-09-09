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
use Snowflake\Snowflake;
use Swoole\Coroutine;

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
		return $node->callback = Reduce::reduce($last, $this->annotation($node));
	}


	/**
	 * @param Node $node
	 * @return array
	 */
	protected function annotation($node)
	{
		$middleWares = $this->middleWares;
		$this->middleWares = [];
		if (!$node->hasInterceptor()) {
			return $this->annotation_limit($node, $middleWares);
		}
		foreach ($node->getInterceptor() as $item) {
			$middleWares[] = $item;
		}
		return $this->annotation_limit($node, $middleWares);
	}


	/**
	 * @param Node $node
	 * @param $middleWares
	 * @return array
	 */
	protected function annotation_limit($node, $middleWares)
	{
		if (!$node->hasLimits()) {
			return $middleWares;
		}
		foreach ($node->getLimits() as $item) {
			$middleWares[] = $item;
		}
		return $middleWares;
	}


}
