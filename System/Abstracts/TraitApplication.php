<?php


namespace Kiri\Abstracts;


use Annotation\Annotation as SAnnotation;
use Database\DatabasesProviders;
use Http\Client\Client;
use Http\Client\Curl;
use Http\Http\Request;
use Http\Http\Response;
use Http\HttpFilter;
use Http\Route\Router;
use Http\Server;
use Http\Shutdown;
use Kiri\Crontab\Producer;
use Kiri\Async;
use Kiri\Cache\Redis;
use Kiri\Error\Logger;
use Kiri\Jwt\Jwt;

/**
 * Trait TraitApplication
 * @package Kiri\Abstracts
 * @property Router $router
 * @property \Redis|Redis $redis
 * @property Server $server
 * @property Response $response
 * @property Request $request
 * @property DatabasesProviders $db
 * @property Async $async
 * @property Logger $logger
 * @property Jwt $jwt
 * @property SAnnotation $annotation
 * @property BaseGoto $goto
 * @property Client $client
 * @property \Database\Connection $databases
 * @property Curl $curl
 * @property Producer $crontab
 * @property HttpFilter $filter
 * @property Shutdown $shutdown
 */
trait TraitApplication
{

}
