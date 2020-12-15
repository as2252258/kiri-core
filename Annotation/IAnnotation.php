<?php


namespace Annotation;


use Closure;

interface IAnnotation
{


	/**
	 * @param array|Closure $handler
	 * @return mixed
	 */
	public function setHandler(array|Closure $handler): mixed;


}
