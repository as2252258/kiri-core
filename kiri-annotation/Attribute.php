<?php


namespace Kiri\Annotation;


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
    #[\ReturnTypeWillChange]
    public function execute(mixed $class, mixed $method = ''): mixed
    {
        // TODO: Implement execute() method.
        return true;
    }

}
