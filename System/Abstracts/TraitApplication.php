<?php


namespace Kiri\Abstracts;


use Annotation\Annotation as SAnnotation;
use Database\DatabasesProviders;
use HttpServer\Client\Help\Client;
use HttpServer\Client\Help\Curl;
use HttpServer\Client\Http2;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\HttpFilter;
use HttpServer\Route\Router;
use HttpServer\Server;
use HttpServer\Shutdown;
use Rpc\Producer as RPCProducer;
use Kiri\Async;
use Kiri\Cache\Redis;
use Kiri\Error\Logger;
use Kiri\Event;
use Kiri\Jwt\Jwt;
use Kiri\Pool\Connection;
use Kiri\Pool\Pool;

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
 * @property Http2 $http2
 * @property BaseGoto $goto
 * @property Client $client
 * @property \Database\Connection $databases
 * @property Curl $curl
 * @property \Kiri\Crontab\Producer $crontab
 * @property HttpFilter $filter
 * @property RPCProducer $rpc
 * @property Shutdown $shutdown
 */
trait TraitApplication
{

}
