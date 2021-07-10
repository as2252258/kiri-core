<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
use Rpc\Actuator;
use Snowflake\Snowflake;


/**
 * Class RpcProducer
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class RpcProducer extends Attribute
{

    const PROTOCOL_JSON = 'json';
    const PROTOCOL_SERIALIZE = 'serialize';


    /**
     * Route constructor.
     * @param string $cmd
     * @param string $protocol
     * @param int $port
     */
    #[Pure] public function __construct(public string $cmd, public string $protocol = self::PROTOCOL_SERIALIZE, public int $port = 443)
    {
    }



    /**
     * @param array $handler
     * @return Router
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): Router
    {
        // TODO: Implement setHandler() method.
        $router = Snowflake::app()->getRouter();
        $cmd = $this->cmd;
        $callback = function (Actuator $actuator) use ($cmd, $class, $method) {
            $actuator->addListener($cmd, $class . '@' . $method);
        };
        $router->addRpcService($this->port, $callback);
        return $router;
    }


}
