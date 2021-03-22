<?php


namespace Annotation\Route;


use Annotation\Attribute;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Cmd extends Attribute
{

    /**
     * Route constructor.
     * @param string $uri
     * @param string $method
     * @param string $version
     */
    public function __construct(
        public string $cmd,
    )
    {
    }


    /**
     * @param array $handler
     * @return Router
     * @throws ComponentException
     * @throws ConfigException
     * @throws ReflectionException
     * @throws NotFindClassException
     */
    public function execute(array $handler): Router
    {
        // TODO: Implement setHandler() method.
        $router = Snowflake::app()->getRouter();

        $router->addRoute($this->cmd, $handler, Request::HTTP_CMD);

        return $router;
    }


}
