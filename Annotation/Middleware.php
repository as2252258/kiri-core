<?php


namespace Annotation;


use Closure;

#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware
{


	/**
	 * Middleware constructor.
	 * @param string|array $handler
	 */
	public function __construct(
		private string|array $handler
	)
	{
	}


	/**
	 * @return array|string
	 */
	public function getMiddleware(): array|string
	{
		return $this->handler;
	}


}
