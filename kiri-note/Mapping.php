<?php

namespace Annotation;

use Kiri\Kiri;

#[\Attribute(\Attribute::TARGET_CLASS)] class Mapping extends Attribute
{


	/**
	 * @param string $class
	 */
	public function __construct(public string $class)
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|string $method
	 * @return mixed
	 */
	public function execute(mixed $class, mixed $method = ''): mixed
	{
		Kiri::getDi()->mapping($class, $this->class);

		return parent::execute($class, $method);
	}

}
