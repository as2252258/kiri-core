<?php


namespace Snowflake;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Component;

defined('ASPECT_ERROR') or define('ASPECT_ERROR', 'Aspect annotation must implement ');


/**
 * Class Aop
 * @package Snowflake
 */
class Aop extends Component
{


	private static array $_aop = [];


	/**
	 * @param array $handler
	 * @param string $aspect
	 */
	public function aop_add(array $handler, string $aspect)
	{
		[$class, $method] = $handler;
		$alias = $class::class . '::' . $method;
		if (!isset(static::$_aop[$alias])) {
			static::$_aop[$alias] = [];
		}
		var_dump('add ' . $alias);
		if (in_array($aspect, static::$_aop[$alias])) {
			return;
		}
		static::$_aop[$alias][] = $aspect;
	}


	/**
	 * @param $handler
	 * @return bool
	 */
	public function hasAop($handler): bool
	{
		var_dump('check ' . $handler[0]::class . '::' . $handler[1]);
		return isset(static::$_aop[$handler[0]::class . '::' . $handler[1]]);
	}


	/**
	 * @param $handler
	 * @param $params
	 * @return mixed
	 * @throws ReflectionException
	 * @throws Exception
	 */
	final public function dispatch($handler, $params): mixed
	{
		$aopName = $handler[0]::class . '::' . $handler[1];

		$reflect = Snowflake::getDi()->getReflect(current(static::$_aop[$aopName]));
		if (!$reflect->isInstantiable() || !$reflect->hasMethod('invoke')) {
			throw new Exception(ASPECT_ERROR . IAspect::class);
		}
		$method = $reflect->getMethod('invoke');

		return $method->invokeArgs($reflect->newInstance($handler), $params);
	}


	/**
	 * @param array $handler
	 * @return IAspect
	 * @throws Exception
	 * @throws ReflectionException
	 */
	public function getAop(array $handler): IAspect
	{
		$aopName = $handler[0]::class . '::' . $handler[1];

		$reflect = Snowflake::getDi()->get(current(static::$_aop[$aopName]));
		if (!method_exists($reflect, 'invoke')) {
			throw new Exception(ASPECT_ERROR . IAspect::class);
		}
		return $reflect;
	}


	/**
	 * @param $handler
	 * @param $params
	 * @return mixed
	 * @throws Exception
	 */
	private function notFound($handler, $params): mixed
	{
		if (!method_exists($handler[0], $handler[1])) {
			return response()->close(404);
		}
		return call_user_func($handler, ...$params);
	}

}
