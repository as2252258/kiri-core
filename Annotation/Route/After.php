<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class After extends Attribute
{

	/**
	 * Interceptor constructor.
	 * @param \HttpServer\IInterface\After|\HttpServer\IInterface\After[] $after
	 * @throws
	 */
	public function __construct(public string|array $after)
	{
		if (is_string($this->after)) {
			$this->after = [$this->after];
		}
		foreach ($this->after as $key => $value) {
			$sn = Snowflake::createObject($value);
			if (!($sn instanceof \HttpServer\IInterface\After)) {
				continue;
			}
			$this->after[$key] = [$sn, 'onHandler'];
		}
	}


	/**
	 * @param array $handler
	 * @return After
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
