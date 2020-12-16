<?php


namespace Annotation\Route;


/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class After
{


	/**
	 * Interceptor constructor.
	 * @param string|array $after
	 */
	public function __construct(public string|array $after)
	{
	}

}
