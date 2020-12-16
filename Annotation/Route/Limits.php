<?php


namespace Annotation\Route;


/**
 * Class Limits
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Limits
{


	/**
	 * Limits constructor.
	 * @param string|array $limits
	 */
	public function __construct(public string|array $limits)
	{
	}


}
