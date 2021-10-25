<?php

namespace Http\Abstracts;


use JetBrains\PhpStorm\Pure;

/**
 *
 */
class PageNotFoundException extends \Exception
{


	/**
	 *
	 */
	#[Pure] public function __construct(int $code)
	{
		parent::__construct('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', $code);
	}

}
