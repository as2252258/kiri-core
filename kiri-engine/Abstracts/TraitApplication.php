<?php


namespace Kiri\Abstracts;


use Kiri\Annotation\Annotation as SAnnotation;
use Database\Connection;
use Database\DatabasesProviders;
use Kiri\Message\Handler\Router;
use Kiri\Server\Server;
use Kiri\Jwt\JWTAuth;

/**
 * Trait TraitApplication
 * @package Kiri\Abstracts
 * @property Router $router
 * @property Server $server
 * @property DatabasesProviders $db
 * @property JWTAuth $jwt
 * @property SAnnotation $annotation
 * @property Connection $databases
 */
trait TraitApplication
{

}
