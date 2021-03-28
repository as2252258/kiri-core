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
        [$class, $method] = array_shift($get_args);

        $aopName = get_class($class) . '::' . $method;
        if (!isset($this->_aop[$aopName])) {
            return call_user_func($get_args, ...$get_args);
        }

        $reflect = new \ReflectionClass($this->_aop[$aopName]);
        if (!$reflect->hasMethod('invoke')) {
            throw new \Exception(ASPECT_ERROR . IAspect::class);
        }
        $method = $reflect->getMethod('invoke');

        $data = $method->invokeArgs($reflect->newInstance([$class, $method]), $get_args);
        if ($method->getReturnType() !== null) {
            return $data;
        }
    }


}
