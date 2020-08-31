<?php


namespace Snowflake\Annotation;


use Closure;
use ReflectionClass;
use ReflectionException;
use Snowflake\Snowflake;

/**
 * Class Http
 * @package Snowflake\Annotation
 */
class Http extends Annotation
{

	const HTTP_EVENT = 'http:event:';

	/**
	 * @var string
	 * 拦截器
	 */
	private $Interceptor;


	/**
	 * @var string
	 * 限速
	 */
	private $Limits;


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $handler
	 * @return array
	 * @throws ReflectionException
	 */
	public function createLimits($controller, $methodName, $handler)
	{
		$namespace = 'App\Http\Interceptor\\' . $handler;
		$class = Snowflake::getDi()->getReflect($namespace);

		$object = $class->newInstance();


		$method = $class->getMethod('Interceptor');


		return [$object, 'Interceptor'];
	}


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $handler
	 * @return array
	 * @throws ReflectionException
	 */
	public function createInterceptor($controller, $methodName, $handler)
	{
		$namespace = 'App\Interceptor\\' . $handler;
		$class = Snowflake::getDi()->getReflect($namespace);

		$object = $class->newInstance();

		return [$object, 'Interceptor', [request(), [$controller, $methodName]]];
	}


	/**
	 * @param $name
	 * @param $events
	 * @return false|string
	 */
	public function getName($name, $events)
	{
		return self::HTTP_EVENT . $name . ':' . $events[1];
	}

}
