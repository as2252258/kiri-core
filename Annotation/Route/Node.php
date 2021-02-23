<?php


namespace Annotation\Route;

use HttpServer\IInterface\After;
use HttpServer\IInterface\Interceptor;
use HttpServer\IInterface\Limits;
use HttpServer\IInterface\Middleware;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

trait Node
{


	/**
	 * @param array $classes
	 * @return array
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function reflectClass(array $classes): array
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
