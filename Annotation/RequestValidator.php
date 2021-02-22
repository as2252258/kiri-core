<?php


namespace Annotation;


use Exception;
use validator\Validator;

/**
 * Class RequestValidator
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class RequestValidator implements IAnnotation
{

	/**
	 * RequestValidator constructor.
	 * @param array $validators
	 * @throws Exception
	 */
	public function __construct(public array $validators)
	{
	}


	/**
	 * @param array $handler
	 * @return bool
	 */
	public function execute(array $handler): bool
	{
		return true;
	}


}
