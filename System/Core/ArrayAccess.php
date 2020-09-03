<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 14:57
 */

namespace Snowflake\Core;


use Exception;

/**
 * Class ArrayAccess
 * @package Snowflake\Core
 */
class ArrayAccess
{

	/**
	 * @param $data
	 * @return array
	 * @throws Exception
	 */
	public static function toArray($data)
	{
		if (!is_object($data) && !is_array($data)) {
			return $data;
		}
		if (is_object($data)) {
			$data = self::objToArray($data);
		}
		$tmp = [];
		if (!is_array($data)) {
			return $data;
		}
		foreach ($data as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$tmp[$key] = self::toArray($val);
			} else {
				$tmp[$key] = $val;
			}
		}
		return $tmp;
	}


	/**
	 * @param $data
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function objToArray($data)
	{
		if (!is_object($data)) {
			return $data;
		}
		if (method_exists($data, 'get')) {
			$data = $data->get();
			if (is_array($data)) {
				return $data;
			}
		}
		if (method_exists($data, 'toArray')) {
			$data = $data->toArray();
		} else {
			$data = get_object_vars((object)$data);
		}
		return $data;
	}


	/**
	 * @param array $oldArray
	 * @param array $newArray
	 * @return array
	 */
	public static function merge(array $oldArray, array $newArray)
	{
		if (empty($oldArray)) {
			return $newArray;
		} else if (empty($newArray)) {
			return $oldArray;
		}
		foreach ($newArray as $item => $value) {
			if (!isset($oldArray[$item])) {
				$oldArray[$item] = $value;
			}
			if (is_array($value)) {
				$oldArray[$item] = self::merge($oldArray[$item], $value);
			} else {
				$oldArray[$item] = $value;
			}
		}
		return $oldArray;
	}

}
