<?php


namespace Annotation\Route;


/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware
{


	/**
	 * Interceptor constructor.
	 * @param string|array $middleware
	 */
	public function __construct(public string|array $middleware)
	{
	}

}
