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


	/**
	 * Limits constructor.
	 * @param string|array $limits
	 * @throws
	 */
	public function __construct(public string|array $limits)
	{
		if (!is_string($this->limits)) {
			return;
		}
		$this->limits = [$this->limits];
	}


	/**
	 * @param array $handler
	 * @return array|string
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function execute(array $handler): array|string
	{
		// TODO: Implement execute() method.
		foreach ($this->limits as $key => $item) {
			$this->limits[$key] = [Snowflake::createObject($item), 'next'];
		}
		return $this->limits;
	}


}
