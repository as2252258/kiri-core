<?php


namespace Annotation;


use Closure;

interface IAnnotation
{


	/**
	 * @param array|Closure $handler
	 * @param array $attributes
	 * @return mixed
	 */
	public function setHandler(array|Closure $handler, array $attributes): mixed;


}
