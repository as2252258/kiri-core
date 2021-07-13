<?php


namespace Snowflake\Abstracts;


use Annotation\Annotation as SAnnotation;
use Database\DatabasesProviders;
use HttpServer\Client\Client;
use HttpServer\Client\Curl;
use HttpServer\Client\Http2;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\HttpFilter;
use HttpServer\Route\Router;
use HttpServer\Server;
use HttpServer\Shutdown;
use Kafka\Producer;
use Snowflake\Async;
use Snowflake\Cache\Redis;
use Snowflake\Channel;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Jwt\Jwt;
use Snowflake\Pool\Pool;
use Snowflake\Pool\Connection;
use Rpc\Producer as RPCProducer;

/**
 * Trait TraitApplication
 * @package Snowflake\Abstracts
 * @property Event $event
 * @property Router $router
 * @property \Redis|Redis $redis
 * @property Server $server
 * @property Response $response
 * @property Request $request
 * @property DatabasesProviders $db
 * @property Async $async
 * @property Connection $connections
 * @property Logger $logger
 * @property Jwt $jwt
 * @property SAnnotation $annotation
 * @property Http2 $http2
 * @property BaseGoto $goto
 * @property Producer $kafka
 * @property Client $client
 * @property Curl $curl
 * @property \Snowflake\Crontab\Producer $crontab
 * @property HttpFilter $filter
 * @property RPCProducer $rpc
 * @property Channel $channel
 * @property Shutdown $shutdown
 * @property Pool $clientsPool
 */
trait TraitApplication
{

}
