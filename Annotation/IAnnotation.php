<?php


namespace Annotation;


use Closure;

interface IAnnotation
{

	/**
	 * @param array $handler
	 * @return mixed
	 */
	public function execute(array $handler): mixed;


}
