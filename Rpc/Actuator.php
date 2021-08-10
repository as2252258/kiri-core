<?php


namespace Rpc;


use Exception;
use HttpServer\Route\Router;
use Kiri\Kiri;


/**
 * Class Actuator
 * @package Rpc
 */
class Actuator
{


    private Router $router;


    /**
     * Actuator constructor.
     * @param int $port
     * @throws Exception
     */
    public function __construct(public int $port)
    {
        $this->router = Kiri::getApp('router');
    }


    /**
     * @param string $path
     * @param string|callable $callback
     * @throws Exception
     */
    public function addListener(string $path, string|callable $callback): void
    {
        $this->router->addRoute('rpc/p' . $this->port . '/' . ltrim($path, '/'), $callback);
    }


}
