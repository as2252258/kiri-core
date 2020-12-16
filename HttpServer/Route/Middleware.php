<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 02:17
 */
declare(strict_types=1);

namespace HttpServer\Route;

use Annotation\Route\After;
use Annotation\Route\Interceptor;
use Annotation\Route\Middleware as RMiddleware;
use Exception;
use Annotation\Route\Limits;
use HttpServer\Route\Dispatch\Dispatch;
use Snowflake\Core\JSON;
use Snowflake\Snowflake;

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
	 * @return mixed
	 * @throws Exception
	 */
	public function getGenerate(Node $node): mixed
	{
		try {
			if (is_array($node->handler) && is_object($node->handler[0])) {
				$this->set_attributes($node);
			}
			return $node->callback = Reduce::reduce(function () use ($node) {
				if (!request()->isOption) {
					var_dump($node);
				}
				return Dispatch::create($node->handler, func_get_args())->dispatch();
			}, $this->annotation($node));
		} catch (\Throwable $exception) {
			return $node->callback = function () use ($exception) {
				return JSON::to(500, $exception->getMessage(), [
					'file' => $exception->getFile(),
					'line' => $exception->getLine()
				]);
			};
		}
	}


	/**
	 * @param $node
	 * @throws Exception
	 */
	private function set_attributes(Node $node)
	{
		[$controller, $action] = $node->handler;
		$attributes = Snowflake::app()->getAttributes();
		$annotation = $attributes->getByClass(get_class($controller), $action);

		var_dump($annotation);
		foreach ($annotation as $item) {
			if ($item instanceof Interceptor) {
				$node->addInterceptor($item->interceptor);
			}
			if ($item instanceof After) {
				$node->addAfter($item->after);
			}
			if ($item instanceof RMiddleware) {
				$node->addMiddleware($item->middleware);
			}
			if ($item instanceof Limits) {
				$node->addLimits($item->limits);
			}
		}
	}


	/**
	 * @param Node $node
	 * @return array
	 */
	protected function annotation(Node $node): array
	{
		$middleWares = $this->annotation_limit($node);
		$middleWares = $this->annotation_interceptor($node, $middleWares);
		foreach ($this->middleWares as $middleWare) {
			$middleWares[] = $middleWare;
		}
		$this->middleWares = [];
		return $middleWares;
	}


	/**
	 * @param Node $node
	 * @param $middleWares
	 * @return array
	 */
	protected function annotation_interceptor(Node $node, $middleWares = []): array
	{
		if (!$node->hasInterceptor()) {
			return $middleWares;
		}
		foreach ($node->getInterceptor() as $item) {
			$middleWares[] = $item;
		}
		return $middleWares;
	}


	/**
	 * @param Node $node
	 * @param $middleWares
	 * @return array
	 */
	protected function annotation_limit(Node $node, $middleWares = []): array
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
