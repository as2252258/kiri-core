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
		$array = [];
		foreach ($classes as $class) {
			$object = Snowflake::getDi()->get($class);
			if ($object instanceof Interceptor) {
				$array[] = [$object, 'Interceptor'];
			}
			if ($object instanceof Limits) {
				$array[] = [$object, 'next'];
			}
			if ($object instanceof After) {
				$array[] = [$object, 'onHandler'];
			}
			if ($object instanceof Middleware) {
				$array[] = [$object, 'onHandler'];
			}
		}
		return $array;
	}

}
