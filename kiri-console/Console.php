<?php

declare(strict_types=1);

namespace Console;

use Kiri\Kiri;

/**
 * Class Console
 * @package Console
 */
class Console extends AbstractConsole
{


	/**
	 * @param $class
	 * @throws
	 */
	public function register($class)
	{
		if (is_string($class) || is_callable($class, true)) {
			$class = Kiri::createObject($class);
		}
		$this->signCommand($class);
	}

}
