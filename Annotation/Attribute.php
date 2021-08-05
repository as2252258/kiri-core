<?php


namespace Annotation;



/**
 * Class Attribute
 * @package Annotation
 */
abstract class Attribute implements IAnnotation
{


	/**
	 * @param mixed $class
	 * @param mixed|string $method
	 * @return mixed
	 */
    public function execute(mixed $class, mixed $method = ''): mixed
    {
        // TODO: Implement execute() method.
        return true;
    }

}
