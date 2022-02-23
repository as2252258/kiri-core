<?php

namespace Kiri\Annotation\Route;

use Kiri\Annotation\AbstractAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)] class RequestMapping extends AbstractAttribute
{


	/**
	 * @param Method $method
	 * @param string $path
	 * @param string|null $version
	 */
	public function __construct(Method $method, string $path, string $version = null)
	{
	}


}
