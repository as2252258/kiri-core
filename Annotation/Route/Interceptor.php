<?php


namespace Annotation\Route;


/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor
{


	/**
	 * Interceptor constructor.
	 * @param string|array $interceptor
	 */
	public function __construct(public string|array $interceptor)
	{
	}

}
