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
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	final public function dispatch(): mixed
	{
		$get_args = func_get_args();
		if (($close = array_shift($get_args)) instanceof \Closure) {
			return call_user_func($close, ...$get_args);
		}

		$aopName = get_class($close[0]) . '::' . $close[1];
		if (!isset($this->_aop[$aopName])) {
			return call_user_func($close, ...$get_args);
		}

		$reflect = Snowflake::getDi()->getReflect(current($this->_aop[$aopName]));
		if (!$reflect->isInstantiable() || !$reflect->hasMethod('invoke')) {
			throw new Exception(ASPECT_ERROR . IAspect::class);
		}
		$method = $reflect->getMethod('invoke');

		return $method->invokeArgs($reflect->newInstance($close), $get_args);
	}


}
