<?php


namespace Annotation\Route;


use Snowflake\Snowflake;

/**
 * Class Limits
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Limits
{


	/**
	 * Limits constructor.
	 * @param string|array $limits
	 * @throws
	 */
	public function __construct(public string|array $limits)
	{
		if (is_string($this->limits)) {
			$this->limits = [$this->limits];
		}
		foreach ($this->limits as $key => $item) {
			$this->limits[$key] = [Snowflake::createObject($item), 'next'];
		}
	}


}
