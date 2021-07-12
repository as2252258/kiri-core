<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\HttpFilter;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Filter
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Filter extends Attribute
{

	/**
	 * Filter constructor.
	 * @param array $rules
	 */
	public function __construct(public array $rules)
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 */
    public function execute(mixed $class, mixed $method = null): bool
	{
		return true;
	}


}
