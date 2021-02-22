<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class After implements IAnnotation
{


	/**
	 * Interceptor constructor.
	 * @param \HttpServer\IInterface\After|\HttpServer\IInterface\After[] $after
	 * @throws
	 */
	public function __construct(public string|array $after)
	{
		if (!is_string($this->after)) {
			return;
		}
		$this->after = [$this->after];
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
		foreach ($this->after as $key => $item) {
			$this->after[$key] = [Snowflake::createObject($item), 'onHandler'];
		}
		return $this->after;
	}


}
