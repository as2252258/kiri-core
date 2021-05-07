<?php


namespace Snowflake;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\NotFindClassException;

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
        if (in_array($aspect, static::$_aop[$alias])) {
            return;
        }
	    static::$_aop[$alias][] = $aspect;
    }


	/**
	 * @param $handler
	 * @param $params
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
    final public function dispatch($handler, $params): mixed
    {
        if ($handler instanceof \Closure) {
            return call_user_func($handler, ...$params);
        }
        $aopName = $handler[0]::class . '::' . $handler[1];
        if (!isset(static::$_aop[$aopName])) {
            return $this->notFound($handler, $params);
        }
        return $this->invoke($handler, $params, $aopName);
    }


	/**
	 * @param $handler
	 * @param $params
	 * @param $aopName
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
    private function invoke($handler, $params, $aopName): mixed
    {
        $reflect = Snowflake::getDi()->getReflect(current(static::$_aop[$aopName]));
        if (!$reflect->isInstantiable() || !$reflect->hasMethod('invoke')) {
            throw new Exception(ASPECT_ERROR . IAspect::class);
        }
        $method = $reflect->getMethod('invoke');

        return $method->invokeArgs($reflect->newInstance($handler), $params);
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
