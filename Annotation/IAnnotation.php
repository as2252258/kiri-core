<?php


namespace Annotation;


use Closure;

interface IAnnotation
{


	/**
	 * @param array|Closure $closure
	 * @return mixed
	 */
	public function setHandler(array|Closure $closure): mixed;


}
