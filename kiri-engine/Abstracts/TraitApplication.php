<?php


namespace Kiri\Abstracts;


use Kiri\Annotation\Annotation as SAnnotation;
use Database\Connection;
use Database\DatabasesProviders;
use Http\Handler\Router;
use Server\Server;
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
 * @property SAnnotation $annotation
 * @property BaseGoto $goto
 * @property Connection $databases
 */
trait TraitApplication
{

}
