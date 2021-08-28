<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use Http\Route\Router;
use Kiri\Kiri;

#[\Attribute(\Attribute::TARGET_METHOD)] class Route extends Attribute
{

    /**
     * Route constructor.
     * @param string $uri
     * @param string $method
     * @param string $version
     */
    public function __construct(string $uri, string $method, string $version = 'v.1.0')
    {
    }


    /**
     * @param static $params
     * @param mixed $class
     * @param mixed|null $method
     * @return Router
     * @throws \Kiri\Exception\NotFindClassException
     * @throws \ReflectionException
     */
    public static function execute(mixed $params, mixed $class, mixed $method = null): Router
    {
        // TODO: Implement setHandler() method.
        $router = Kiri::app()->getRouter();
        if (is_string($class)) {
            $class = di($class);
        }
        $router->addRoute($params->uri, [$class, $method], strtoupper($params->method));
        return $router;
    }


}
