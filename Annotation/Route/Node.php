<?php


namespace Annotation\Route;

use HttpServer\IInterface\After;
use HttpServer\IInterface\Interceptor;
use HttpServer\IInterface\Limits;
use HttpServer\IInterface\Middleware;
use HttpServer\Route\Node as RNode;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

trait Node
{


	/**
	 * @param RNode $node
	 * @return RNode
	 * @throws
	 */
	public function add(RNode $node): RNode
	{
		if (!empty($this->middleware)) {
			$node->addMiddleware($this->reflectClass($this->middleware));
		}
		if (!empty($this->interceptor)) {
			$node->addInterceptor($this->reflectClass($this->interceptor));
		}
		if (!empty($this->limits)) {
			$node->addLimits($this->reflectClass($this->limits));
		}
		if (!empty($this->after)) {
			$node->addAfter($this->reflectClass($this->after));
		}
		return $node;
	}


	/**
	 * @param array $classes
	 * @return array
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	private function reflectClass(array $classes): array
	{
		$di = Snowflake::getDi();
		foreach ($classes as $key => $class) {
			$object = $di->get($class);
			if ($object instanceof Interceptor) {
				$classes[$key] = [$object, 'Interceptor'];
			}
			if ($object instanceof Limits) {
				$classes[$key] = [$object, 'next'];
			}
			if ($object instanceof After) {
				$classes[$key] = [$object, 'onHandler'];
			}
			if ($object instanceof Middleware) {
				$classes[$key] = [$object, 'onHandler'];
			}
		}
		return $classes;
	}

}
