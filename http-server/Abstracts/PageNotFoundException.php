<?php

namespace Server\Abstracts;


/**
 *
 */
class PageNotFoundException extends \Exception
{


	/**
	 *
	 */
	public function __construct(int $code)
	{
		parent::__construct('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', $code);
	}

}
