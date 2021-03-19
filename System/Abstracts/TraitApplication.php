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
use Kafka\Producer;
use Snowflake\Async;
use Snowflake\Cache\Redis;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Jwt\Jwt;
use Snowflake\Pool\Connection;
use Snowflake\Pool\Pool as SPool;

/**
 * Trait TraitApplication
 * @package Snowflake\Abstracts
 * @property Event $event
 * @property Router $router
 * @property SPool $pool
 * @property \Redis|Redis $redis
 * @property Server $server
 * @property Response $response
 * @property Request $request
 * @property DatabasesProviders $db
 * @property Async $async
 * @property Connection $connections
 * @property Logger $logger
 * @property Jwt $jwt
 * @property SAnnotation $attributes
 * @property Http2 $http2
 * @property BaseGoto $goto
 * @property Producer $kafka
 * @property Client $client
 * @property Curl $curl
 * @property Crontab $crontab
 * @property HttpFilter $filter
 */
trait TraitApplication
{

}
