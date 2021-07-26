<?php


namespace Annotation\Route;


use Annotation\Attribute;
use HttpServer\Route\Middlewares;
use Snowflake\Snowflake;
use HttpServer\IInterface\Middleware as IMiddleware;

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
            $sn = Snowflake::createObject($value);
            if (!($sn instanceof IMiddleware)) {
                continue;
            }
            $array[] = [$sn, 'onHandler'];
        }
        $this->middleware = $array;
    }


    /**
     * @param mixed $class
     * @param mixed|null $method
     * @return Middleware
     */
    public function execute(mixed $class, mixed $method = null): static
    {
        $middleware = Snowflake::getDi()->get(Middlewares::class);
        $middleware->addMiddlewares($class, $method, $this->middleware);
        return $this;
    }


}
