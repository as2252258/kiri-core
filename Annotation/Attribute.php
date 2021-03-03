<?php


namespace Annotation;


use Snowflake\Snowflake;

/**
 * Class Attribute
 * @package Annotation
 */
abstract class Attribute implements IAnnotation
{


	/**
	 * @param array $handler
	 * @return mixed
	 */
	public function execute(array $handler): mixed
	{
		return $this;
	}

}
