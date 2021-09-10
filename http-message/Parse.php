<?php

namespace Http\Message;

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
        if (str_contains($contentType, 'json')) {
            return json_encode($contentType);
        }
        if (str_contains($contentType, 'xml')) {
            return Xml::toArray($contentType);
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
