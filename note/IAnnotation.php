<?php


namespace Annotation;


use Closure;

interface IAnnotation
{

    /**
     * @param static $params
     * @param mixed $class
     * @param mixed $method
     * @return mixed
     */
    public static function execute(mixed $params, mixed $class, mixed $method = ''): mixed;


}
