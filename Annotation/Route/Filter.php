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
	 * @param array $handler
	 * @return array
	 * @throws Exception
	 */
	public function execute(array $handler): array
	{
		[$class, $method] = $handler;

		/** @var HttpFilter $filter */
		$filter = Snowflake::app()->get('filter');
		$filter->register($class::class, $method, $this->rules);

		return $handler;
	}


}
