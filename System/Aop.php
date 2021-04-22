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


    private array $_aop = [];


    /**
     * @param array $handler
     * @param string $aspect
     */
    public function aop_add(array $handler, string $aspect)
    {
        [$class, $method] = $handler;
        $alias = get_class($class) . '::' . $method;
        if (!isset($this->_aop[$alias])) {
            $this->_aop[$alias] = [];
        }
        if (in_array($aspect, $this->_aop[$alias])) {
            return;
        }
        $this->_aop[$alias][] = $aspect;
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
        $aopName = get_class($handler[0]) . '::' . $handler[1];
        if (!isset($this->_aop[$aopName])) {
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
        $reflect = Snowflake::getDi()->getReflect(current($this->_aop[$aopName]));
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
