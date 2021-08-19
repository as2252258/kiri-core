<?php

namespace Http\Route;

use Http\IInterface\MiddlewareInterface;


/**
 *
 */
abstract class MiddlewareAbstracts implements MiddlewareInterface
{

	/** @var int */
	protected int $priority = 0;


	/**
	 * @return int
	 */
	public function getPriority(): int
	{
		return $this->priority;
	}
}
