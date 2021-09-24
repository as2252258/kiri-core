<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:03
 */
declare(strict_types=1);

namespace Kiri\Core;

use Exception;

/**
 * Class Xml
 * @package Kiri\Kiri\Core
 */
class Xml
{

	/**
	 * @param $data
	 * @param bool $asArray
	 * @return array|object
	 * @throws Exception
	 */
	public static function toArray($data, bool $asArray = true): object|array
	{
		$data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($data === false) {
			throw new Exception('Parameter format error.');
		}
		$array = get_object_vars($data);
		if (isset($array[0])) {
			$array[$data->getName()] = $array[0];
			unset($array[0]);
		}
		return $array;
	}

	/**
	 * @param $str
	 * @return array|bool|object
	 * @throws Exception
	 */
	public static function isXml($str): object|bool|array
	{
		$xml_parser = xml_parser_create();
		if (!xml_parse($xml_parser, $str, true)) {
			xml_parser_free($xml_parser);
			return false;
		} else {
			return self::toArray($str);
		}
	}

}
