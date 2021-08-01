<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:03
 */
declare(strict_types=1);

namespace Snowflake\Core;

/**
 * Class Xml
 * @package Snowflake\Snowflake\Core
 */
class Xml
{

	/**
	 * @param $data
	 * @param bool $asArray
	 * @return array|object
	 */
	public static function toArray($data, bool $asArray = true): object|array
	{
		$data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($asArray) {
			return json_decode(json_encode($data), TRUE);
		}
		return json_decode(json_encode($data));
	}

	/**
	 * @param $str
	 * @return array|bool|object
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
