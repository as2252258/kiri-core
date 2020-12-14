<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:17
 */
declare(strict_types=1);

// declare(strict_types=1);

namespace HttpServer\Route;

use Exception;
use HttpServer\Route\Dispatch\Dispatch;

/**
 * Class Middleware
 * @package Snowflake\Snowflake\Route
 */
class Middleware
{

	/** @var array */
	private array $middleWares = [];

	/**
	 * @param $call
	 * @return $this
	 */
	public function set($call): static
	{
		$this->middleWares[] = $call;
		return $this;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function setMiddleWares(array $array): static
	{
		$this->middleWares = $array;
		return $this;
	}

	/**
	 * @param Node $node
	 * @return array
	 * @throws Exception
	 */
	public function getGenerate(Node $node): array
	{
		return $node->callback = Reduce::reduce(function ($passable) use ($node) {
			return Dispatch::create($node->handler, $passable)->dispatch();
		}, $this->annotation($node));
	}


	/**
	 * @param Node $node
	 * @return array
	 */
	protected function annotation(Node $node): array
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
	protected function annotation_limit(Node $node, $middleWares): array
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
