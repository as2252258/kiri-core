<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Snowflake\Snowflake;

/**
 * Class Limits
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Limits extends Attribute
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

		foreach ($this->limits as $key => $value) {
			$sn = Snowflake::createObject($value);

			if (!($sn instanceof \HttpServer\IInterface\Limits)) {
				continue;
			}

			$this->limits[$key] = [$sn, 'next'];
		}
	}


	/**
	 * @param array $handler
	 * @return Limits
	 */
    public function execute(mixed $class, mixed $method = null): static
	{
		return $this;
	}


}
