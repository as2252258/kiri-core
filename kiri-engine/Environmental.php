<?php


declare(strict_types=1);


namespace Kiri;


use JetBrains\PhpStorm\Pure;
use Kiri;

/**
 * Class Environmental
 * @package Kiri
 */
class Environmental
{


	/**
	 * @return bool
	 */
	public function isMac(): bool
	{
		$output = strtolower(PHP_OS | PHP_OS_FAMILY);
		if (str_contains('mac', $output)) {
			return true;
		} else if (str_contains('darwin', $output)) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * @return bool
	 */
	#[Pure] public function isLinux(): bool
	{
		if (!static::isMac()) {
			return true;
		} else {
			return false;
		}
	}

}
