<?php


namespace Kiri\Abstracts;


use Annotation\Annotation as SAnnotation;
use Database\Connection;
use Database\DatabasesProviders;
use Http\Client\Client;
use Http\Client\Curl;
use Http\Handler\Router;
use Server\Server;
use Kiri\Crontab\Producer;
use Kiri\Async;
use Kiri\Error\Logger;
use Kiri\Jwt\Jwt;

/**
 * Trait TraitApplication
 * @package Kiri\Abstracts
 * @property Router $router
 * @property Server $server
 * @property DatabasesProviders $db
 * @property Async $async
 * @property Logger $logger
 * @property Jwt $jwt
 * @property SAnnotation $annotation
 * @property BaseGoto $goto
 * @property Client $client
 * @property Connection $databases
 * @property Curl $curl
 */
trait TraitApplication
{

}
