<?php


namespace Kiri\Abstracts;


use Note\Note as SNote;
use Database\Connection;
use Database\DatabasesProviders;
use Http\Handler\Client\Client;
use Http\Handler\Client\Curl;
use Http\Handler\Router;
use Server\Server;
use Kiri\Crontab\Producer;
use Kiri\Async;
use Kiri\Error\Logger;
use Kiri\Jwt\JWTAuth;

/**
 * Trait TraitApplication
 * @package Kiri\Abstracts
 * @property Router $router
 * @property Server $server
 * @property DatabasesProviders $db
 * @property Async $async
 * @property Logger $logger
 * @property JWTAuth $jwt
 * @property SNote $annotation
 * @property BaseGoto $goto
 * @property Client $client
 * @property Connection $databases
 * @property Curl $curl
 */
trait TraitApplication
{

}
