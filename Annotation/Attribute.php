<?php


namespace Annotation;


use Annotation\Route\After;
use Annotation\Route\Interceptor;
use Annotation\Route\Limits;
use Annotation\Route\Middleware as RMiddleware;
use HttpServer\Route\Node;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Attribute
 * @package Annotation
 */
abstract class Attribute implements IAnnotation
{



    public function execute(mixed $class, mixed $method = ''): mixed
    {
        // TODO: Implement execute() method.
        return true;
    }

}
