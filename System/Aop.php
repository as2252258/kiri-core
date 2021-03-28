<?php


namespace Snowflake;


use Snowflake\Abstracts\Component;

defined('ASPECT_ERROR') or define('ASPECT_ERROR', 'Aspect annotation must implement ');


/**
 * Class Aop
 * @package Snowflake
 */
class Aop extends Component
{


    private array $_aop = [];


    /**
     * @param $className
     * @param null $method
     */
    public function aop_add(array $handler, string $aspect)
    {
        [$class, $method] = $handler;
        if (!isset($this->_aop[$aspect])) {
            $this->_aop[$aspect] = [];
        }

        $this->_aop[get_class($class) . '::' . $method][] = $aspect;
    }


    /**
     * @return mixed|void
     * @throws \ReflectionException
     */
    final public function dispatch()
    {
        $get_args = func_get_args();
        if (($close = array_shift($get_args)) instanceof \Closure) {
            return call_user_func($close, ...$get_args);
        }

        $aopName = get_class($close[0]) . '::' . $close[1];
        if (!isset($this->_aop[$aopName])) {
            return call_user_func($close, ...$get_args);
        }

        $reflect = Snowflake::getDi()->getReflect($this->_aop[$aopName]);
        if (!$reflect->isInstantiable() || !$reflect->hasMethod('invoke')) {
            throw new \Exception(ASPECT_ERROR . IAspect::class);
        }
        $method = $reflect->getMethod('invoke');

        return $method->invokeArgs($reflect->newInstance($close), $get_args);
    }


}
