<?php

namespace Protocol\Message;

use Kiri\Core\Xml;

class Parse
{


	/**
	 * @param $content
	 * @param $contentType
	 * @return mixed
	 */
	public static function data($content, $contentType): mixed
	{
		var_dump($contentType, $content);
		if (str_contains($contentType, 'json')) {
			return json_encode($content,true);
		}
		if (str_contains($contentType, 'xml')) {
			return Xml::toArray($content);
		}
		if (str_contains($contentType, 'x-www-form-urlencoded')) {
			parse_str($content, $array);
			return $array;
		}
		if (str_contains($contentType, 'serialize')) {
			return unserialize($content);
		}
		return $content;
	}

}
