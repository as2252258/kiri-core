<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Limits
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Limits implements IAnnotation
{

	use Node;

	/**
	 * Limits constructor.
	 * @param string|array $limits
	 * @throws
	 */
	public function __construct(public string|array $limits)
	{
		if (is_string($this->limits)) {
			$this->limits = [];
		}
	}


	/**
	 * @param array $handler
	 * @return Limits
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
