<?php
declare(strict_types=1);

namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\After;
use HttpServer\IInterface\Middleware;
use Snowflake\Core\Json;

class Reduce
{


    /**
     * @param $last
     * @param $middleWares
     * @return mixed
     */
    public static function reduce($last, $middleWares): mixed
    {
        return array_reduce(array_reverse($middleWares), function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Middleware) {
                    return $pipe->onHandler($passable, $stack);
                }
                return call_user_func($pipe, $passable, $stack);
            };
        }, $last);
    }


}
