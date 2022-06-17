<?php

namespace Kiri\Abstracts;

use Kiri\Coordinator;

class CoordinatorManager
{



	private static array $_waite = [];


	/**
	 * @param string $category
	 * @return Coordinator
	 */
	public static function utility(string $category): Coordinator
	{
		if (!((static::$_waite[$category] ?? null) instanceof Coordinator)) {
			static::$_waite[$category] = new Coordinator();
		}
		return static::$_waite[$category];
	}

}
