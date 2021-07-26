<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Route\Router;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Route extends Attribute
{

    /**
     * Route constructor.
     * @param string $uri
     * @param string $method
     * @param string $version
     */
    public function __construct(
        public string $uri,
        public string $method,
        public string $version = 'v.1.0'
    )
    {
    }


    /**
     * @param mixed $class
     * @param mixed|null $method
     * @return Router
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): Router
    {
        // TODO: Implement setHandler() method.
        $router = Snowflake::app()->getRouter();
        $node = $router->addRoute($this->uri, [$class, $method], $this->method);
        if ($node !== null) {
            $attribute = Snowflake::getDi()->getMethodAttribute($class::class, $method);
            foreach ($attribute as $item) {
                if ($item instanceof Route) {
                    continue;
                }
                $item->execute($class, $method);
            }
        }
        return $router;
    }


}
