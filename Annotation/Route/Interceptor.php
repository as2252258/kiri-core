<?php


namespace Annotation\Route;


use Annotation\Attribute;
use JetBrains\PhpStorm\Pure;
use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor extends Attribute
{


	/**
	 * Interceptor constructor.
	 * @param string|array $interceptor
	 * @throws
	 */
	public function __construct(public string|array $interceptor)
	{
		if (is_string($this->interceptor)) {
			$this->interceptor = [$this->interceptor];
		}

		foreach ($this->interceptor as $key => $value) {
			$sn = Snowflake::createObject($value);

			if (!($sn instanceof \HttpServer\IInterface\Interceptor)) {
				continue;
			}

			$this->interceptor[$key] = [$sn, 'Interceptor'];
		}
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return Interceptor
	 */
    public function execute(mixed $class, mixed $method = null): static
	{
		return $this;
	}

}
