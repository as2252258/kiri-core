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
    public function __construct(string|array $middleware)
    {
    }


    /**
     * @param static $params
     * @param mixed $class
     * @param mixed|null $method
     * @return $this
     * @throws ReflectionException
     */
    public static function execute(mixed $params, mixed $class, mixed $method = null): mixed
    {
        if (is_string($params->middleware)) {
            $params->middleware = [$params->middleware];
        }
        $array = [];
        foreach ($params->middleware as $value) {
            $sn = di($value);
            if (!($sn instanceof MiddlewareInterface)) {
                continue;
            }
            $array[] = [$sn, 'onHandler'];
        }
        MiddlewareManager::addMiddlewares($class, $method, $array);

        return parent::execute($params, $class, $method);
    }


}
