<?php

namespace Kiri\Annotation\Route;

use Kiri\Annotation\AbstractAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)] class RequestMapping extends AbstractAttribute
{


	/**
	 * @param RequestMethod $method
	 * @param string $path
	 * @param string|null $version
	 */
	public function __construct(RequestMethod $method, string $path, string $version = null)
	{
	}


}
