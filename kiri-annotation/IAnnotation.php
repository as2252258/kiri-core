<?php


namespace Kiri\Annotation;



interface IAnnotation
{

    /**
     * @param mixed $class
     * @param mixed $method
     * @return mixed
     */
    public function execute(mixed $class, mixed $method = ''): mixed;


}
