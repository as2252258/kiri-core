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
		$this->set_attributes($node);
		return $node->callback = Reduce::reduce(function () use ($node) {
			return Dispatch::create($node->handler, func_get_args())->dispatch();
		}, $this->annotation($node));
	}


	/**
	 * @param $node
	 * @throws Exception
	 */
	private function set_attributes(Node $node)
	{
		if (!is_array($node->handler)) {
			return;
		}
		[$controller, $action] = $node->handler;
		$attributes = Snowflake::app()->getAttributes();
		$annotation = $attributes->getAnnotationByMethod($controller, $action);
		if (count($annotation) < 1) {
			return;
		}
		foreach ($annotation as $attribute) {
			var_dump($attribute);
			if ($attribute instanceof Interceptor) {
				var_dump($attribute);
				$node->addInterceptor($attribute->interceptor);
			}
			if ($attribute instanceof After) {
				var_dump($attribute);
				$node->addAfter($attribute->after);
			}
			if ($attribute instanceof RMiddleware) {
				var_dump($attribute);
				$node->addMiddleware($attribute->middleware);
			}
			if ($attribute instanceof Limits) {
				var_dump($attribute);
				$node->addLimits($attribute->limits);
			}
		}
	}


	/**
	 * @param Node $node
	 * @return array
	 */
	protected function annotation(Node $node): array
	{
		$middleWares = $node->getMiddleWares();
		$middleWares = $this->annotation_limit($node, $middleWares);
		$middleWares = $this->annotation_interceptor($node, $middleWares);
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
