<?php

namespace Protocol\Message;

use Kiri\Core\Xml;

class Parse
{


	/**
	 * @param $content
	 * @param $contentType
	 * @return mixed
	 * @throws \Exception
	 */
	public static function data($content, $contentType): mixed
	{
		if (empty($content)) {
			return null;
		}
		if (str_starts_with($content, '<') || str_contains($contentType, 'xml')) {
			return Xml::toArray($content);
		}
		if (str_contains($contentType, 'x-www-form-urlencoded')) {
			parse_str($content, $array);
			return $array;
		}
		if (str_contains($contentType, 'serialize')) {
			return unserialize($content);
		}
		return json_decode($content, true);
	}

}
