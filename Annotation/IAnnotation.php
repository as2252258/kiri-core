<?php


namespace Annotation;


use Closure;

interface IAnnotation
{

    /**
     * @param array $handler
     * @return mixed
     */
    public function execute(mixed $class, mixed $method = ''): mixed;


}
