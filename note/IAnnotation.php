<?php


namespace Annotation;


use Closure;

interface IAnnotation
{

	/**
	 * @param mixed $class
	 * @param mixed $method
	 * @return mixed
	 */
    public function execute(mixed $class, mixed $method = ''): mixed;


}
