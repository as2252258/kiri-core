<?php


namespace Annotation\Route;


use Annotation\Attribute;

/**
 * Class Filter
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Filter extends Attribute
{

	/**
	 * Filter constructor.
	 * @param string $uri
	 */
	public function __construct(public string $uri)
	{
	}


	/**
	 * @param array $handler
	 * @return array
	 */
	public function execute(array $handler): array
	{
		// TODO: Implement execute() method.
		return $handler;
	}


}
