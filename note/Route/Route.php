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
    public function __construct(public string $uri,public string $method,public string $version = 'v.1.0')
    {
    }


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return Router
	 */
    public function execute(mixed $class, mixed $method = null): Router
    {
        // TODO: Implement setHandler() method.
        $router = Kiri::getDi()->get(Router::class);
        if (is_string($class)) {
            $class = di($class);
        }
        $router->addRoute($this->uri, [$class, $method], strtoupper($this->method));
        return $router;
    }


}
