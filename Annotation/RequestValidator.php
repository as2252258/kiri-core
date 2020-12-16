<?php


namespace Annotation;


use Exception;
use validator\Validator;

/**
 * Class RequestValidator
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class RequestValidator
{

	/**
	 * RequestValidator constructor.
	 * @param array $validators
	 * @throws Exception
	 */
	public function __construct(public array $validators)
	{
	}


}
