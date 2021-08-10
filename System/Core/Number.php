<?php


namespace Kiri\Core;


class Number
{


	/**
	 * @param int $userId
	 * @return string
	 */
	public static function order(int $userId): string
	{
		$explode = current(explode(' ', str_replace('0.', '', microtime())));

		return 'No.' . sprintf('%09d', $userId) . '.' . date('Ymd.His') . '.' . $explode;
	}

	/**
	 * @param int $userId
	 * @return string
	 */
	public static function create(int $userId): string
	{
		return static::order($userId);
	}

}
