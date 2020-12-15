<?php


namespace Annotation;


/**
 * Class RequestValidator
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class RequestValidator
{

	/**
	 * RequestValidator constructor.
	 * @param array $validators
	 */
	public function __construct(public array $validators)
	{
	}


}
