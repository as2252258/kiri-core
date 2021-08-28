<?php


namespace Annotation;


/**
 * Class Attribute
 * @package Annotation
 */
abstract class Attribute implements IAnnotation
{


    /**
     * @param static $class
     * @param mixed|string $method
     * @return mixed
     */
    public static function execute(mixed $params, mixed $class, mixed $method = ''): mixed
    {
        // TODO: Implement execute() method.
        return true;
    }

}
