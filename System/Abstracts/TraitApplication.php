<?php


namespace Kiri\Abstracts;


use Annotation\Annotation as SAnnotation;
use Database\DatabasesProviders;
use HttpServer\Client\Client;
use HttpServer\Client\Curl;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\HttpFilter;
use HttpServer\Route\Router;
use HttpServer\Server;
use HttpServer\Shutdown;
use Kiri\Crontab\Producer;
use Kiri\Async;
use Kiri\Cache\Redis;
use Kiri\Error\Logger;
use Kiri\Event;
use Kiri\Jwt\Jwt;

/**
 * Trait TraitApplication
 * @package Kiri\Abstracts
 * @property Event $event
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
