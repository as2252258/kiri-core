<?php


namespace Annotation\Route;


/**
 * Class Filter
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Filter
{

	/**
	 * Filter constructor.
	 * @param string $uri
	 */
	public function __construct(public string $uri)
	{
	}


}
