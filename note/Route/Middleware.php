<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Http\Route\MiddlewareManager;
use ReflectionException;
use Http\IInterface\MiddlewareInterface;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware extends Attribute
{


    /**
     * Interceptor constructor.
     * @param string|array $middleware
     * @throws
     */
    public function __construct(public string|array $middleware)
    {
        if (is_string($this->middleware)) {
            $this->middleware = [$this->middleware];
        }
        $array = [];
        foreach ($this->middleware as $value) {
            $sn = di($value);
            if (!($sn instanceof MiddlewareInterface)) {
                continue;
            }
            $array[] = [$sn, 'onHandler'];
        }
        $this->middleware = $array;
    }


    /**
     * @param static $params
     * @param mixed $class
     * @param mixed|null $method
     * @return $this
     * @throws ReflectionException
     */
    public function execute(mixed $class, mixed $method = null): mixed
    {
        MiddlewareManager::addMiddlewares($class, $method, $this->middleware);
        return parent::execute($class, $method);
    }


}
