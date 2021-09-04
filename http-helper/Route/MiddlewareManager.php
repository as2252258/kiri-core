<?php


namespace Http\Route;


use Closure;
use Http\IInterface\MiddlewareInterface;
use Kiri\Abstracts\BaseObject;


/**
 * Class MiddlewareManager
 * @package Http\Route
 */
class MiddlewareManager extends BaseObject
{

    private static array $_middlewares = [];


    /**
     * @param $class
     * @param $method
     * @param array|string $middlewares
     */
    public static function addMiddlewares($class, $method, array|string $middlewares)
    {
        if (is_object($class)) {
            $class = $class::class;
        }
        if (!isset(static::$_middlewares[$class . '::' . $method])) {
            static::$_middlewares[$class . '::' . $method] = [];
        }

        if (is_string($middlewares) && !in_array($middlewares, static::$_middlewares[$class . '::' . $method])) {
            static::$_middlewares[$class . '::' . $method][] = $middlewares;
            return;
        }
        foreach ($middlewares as $middleware) {
            if (in_array($middleware, static::$_middlewares[$class . '::' . $method])) {
                continue;
            }
            static::$_middlewares[$class . '::' . $method][] = $middleware;
        }
    }


    /**
     * @param $handler
     * @return mixed|null
     */
    public static function get($handler)
    {
        if ($handler instanceof Closure) {
            return null;
        }
        [$class, $method] = [$handler[0]::class, $handler[1]];
        if (!static::hasMiddleware($class, $method)) {
            return null;
        }
        return static::$_middlewares[$class . '::' . $method];
    }


    /**
     * @param $class
     * @param $method
     * @return bool
     */
    public static function hasMiddleware($class, $method): bool
    {
        if (is_object($class)) {
            $class = $class::class;
        }
        return isset(static::$_middlewares[$class . '::' . $method]);
    }


    /**
     * @param $class
     * @param $method
     * @param $caller
     * @return mixed
     */
    public static function callerMiddlewares($class, $method, $caller): mixed
    {
        if (is_object($class)) {
            $class = $class::class;
        }
        $middlewares = static::$_middlewares[$class . '::' . $method] ?? [];
        if (empty($middlewares)) {
            return $caller;
        }
        return array_reduce(array_reverse($middlewares), function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof MiddlewareInterface) {
                    $pipe = [$pipe, 'onHandler'];
                }
                return call_user_func($pipe, $passable, $stack);
            };
        }, $caller);
    }


    /**
     * @param $middlewares
     * @param Closure $caller
     * @return Closure
     */
    public static function closureMiddlewares($middlewares, Closure $caller): Closure
    {
        if (empty($middlewares)) {
            return $caller;
        }
        return array_reduce(array_reverse($middlewares), function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof MiddlewareInterface) {
                    $pipe = [$pipe, 'onHandler'];
                }
                return call_user_func($pipe, $passable, $stack);
            };
        }, $caller);
    }


}
