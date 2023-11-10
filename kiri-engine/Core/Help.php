<?php
declare(strict_types=1);


namespace Kiri\Core;


use Exception;
use Swift_Message;
use Swift_SmtpTransport;


/**
 * Class Help
 * @package Kiri\Core
 */
class Help
{

    /**
     * @param array $data
     * @return string
     */
    public static function toXml(array $data): string
    {
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . static::xmlChild($val) . "</" . $key . ">";
            } else if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    /**
     * @param array $array
     * @return string
     */
    private static function xmlChild(array $array): string
    {
        $string = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $string .= static::xmlChild($value);
            } else if (is_numeric($value)) {
                $string .= "<" . $key . ">" . $value . "</" . $key . ">";
            } else {
                $string .= "<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">";
            }
        }
        return $string;
    }


    /**
     * @param $xml
     * @return mixed
     * @throws Exception
     */
    public static function toArray($xml): mixed
    {
        if (empty($xml)) {
            return [];
        } else if (is_array($xml)) {
            return $xml;
        }
        if (!($_xml = Xml::isXml($xml))) {
            return static::jsonToArray($xml);
        }
        return $_xml;
    }


    /**
     * @param $xml
     * @return mixed
     */
    public static function jsonToArray($xml): mixed
    {
        $_xml = json_decode($xml, true);
        if (is_null($_xml)) {
            return [];
        }
        return $_xml;
    }

    /**
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml): mixed
    {
        if (is_array($xml)) {
            return $xml;
        }
        if (($data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)) !== false) {
            return json_decode(json_encode($data), TRUE);
        }
        if (!is_null($json = json_decode($xml, TRUE))) {
            return $json;
        }
        return $xml;
    }

    /**
     * @param $parameter
     * @return array|false|string
     * @throws Exception
     */
    public static function toString($parameter): bool|array|string
    {
        if (!is_string($parameter)) {
            $parameter = Json::encode(ArrayAccess::toArray($parameter));
        }
        return $parameter;
    }

    /**
     * @param mixed $json
     * @return bool|string
     */
    public static function toJson(mixed $json): bool|string
    {
        if (is_object($json)) {
            $json = get_object_vars($json);
        }
        if (is_array($json)) {
            return json_encode($json, JSON_UNESCAPED_UNICODE);
        }
        $matchQuote = '/(<\?xml.*?\?>)?<([a-zA-Z_]+)>(<([a-zA-Z_]+)><!.*?><\/\4>)+<\/\2>/';
        if (preg_match($matchQuote, $json)) {
            $json = self::xmlToArray($json);
        } else {
            $json = json_decode($json, true);
        }
        if (!is_array($json)) {
            $json = [];
        }
        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param int $length
     * @return string
     *
     * 随机字符串
     */
    public static function random(int $length = 20): string
    {
        $res = [];
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $str .= strtoupper($str) . '1234567890';
        for ($i = 0; $i < $length; $i++) {
            $rand = substr($str, rand(0, strlen($str) - 2), 1);
            if (empty($rand)) {
                $rand = substr($str, strlen($str) - 3, 1);
            }
            $res[] = $rand;
        }

        return implode($res);
    }

    /**
     * @param array $array
     * @param $key
     * @param string $type
     * @return string
     */
    public static function sign(array $array, $key, string $type = 'MD5'): string
    {
        ksort($array, SORT_ASC);
        $string = [];
        foreach ($array as $hashKey => $val) {
            if (empty($val)) {
                continue;
            }
            $string[] = $hashKey . '=' . $val;
        }
        $string[] = 'key=' . $key;
        $string   = implode('&', $string);
        if ($type == 'MD5') {
            return strtoupper(md5($string));
        } else {
            return hash('sha256', $string);
        }
    }


    /**
     * @param $email
     * @param string $Subject
     * @param $messageContent
     */
    public static function sendEmail($email, string $Subject, $messageContent): void
    {
        if (!class_exists('\Swift_Mailer')) {
            return;
        }
        $mailer  = new \Swift_Mailer((new Swift_SmtpTransport($email['host'], $email['port']))
            ->setUsername($email['username'])->setPassword($email['password']));
        $message = (new Swift_Message($Subject))
            ->setFrom([$email['send']['address'] => $email['send']['nickname']])
            ->setBody('Here is the message itself');

        foreach ($email['receive'] as $item) {
            $message->setTo([$item['address'], $item['address'] => $item['nickname']]);
        }
        $mailer->send($messageContent);
    }


    /**
     * @param int $year
     * @return int
     */
    public static function age(int $year): int
    {
        return date('Y') - $year;
    }


    /**
     * @param int $year
     * @return string
     */
    public static function zodiac(int $year): string
    {
        $zodiac = "-1";
        $start  = 1901;
        $x      = ($start - $year) % 12;
        if ($x == 1 || $x == -11) {
            $zodiac = "鼠";
        }
        if ($x == 0) {
            $zodiac = "牛";
        }
        if ($x == 11 || $x == -1) {
            $zodiac = "虎";
        }
        if ($x == 10 || $x == -2) {
            $zodiac = "兔";
        }
        if ($x == 9 || $x == -3) {
            $zodiac = "龙";
        }
        if ($x == 8 || $x == -4) {
            $zodiac = "蛇";
        }
        if ($x == 7 || $x == -5) {
            $zodiac = "马";
        }
        if ($x == 6 || $x == -6) {
            $zodiac = "羊";
        }
        if ($x == 5 || $x == -7) {
            $zodiac = "猴";
        }
        if ($x == 4 || $x == -8) {
            $zodiac = "鸡";
        }
        if ($x == 3 || $x == -9) {
            $zodiac = "狗";
        }
        if ($x == 2 || $x == -10) {
            $zodiac = "猪";
        }
        return $zodiac;
    }


    /**
     * @param int $month
     * @param int $day
     * @return string
     */
    public static function constellation(int $month, int $day): string
    {
        $star = "-1";
        if (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
            $star = "水瓶座";
        }
        if (($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
            $star = "双鱼座";
        }
        if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
            $star = "白羊座";
        }
        if (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
            $star = "金牛座";
        }
        if (($month == 5 && $day >= 21) || ($month == 6 && $day <= 21)) {
            $star = "双子座";
        }
        if (($month == 6 && $day >= 22) || ($month == 7 && $day <= 22)) {
            $star = "巨蟹座";
        }
        if (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
            $star = "狮子座";
        }
        if (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
            $star = "处女座";
        }
        if (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) {
            $star = "天秤座";
        }
        if (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) {
            $star = "天蝎座";
        }
        if (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) {
            $star = "射手座";
        }
        if (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
            $star = "魔蝎座";
        }
        return $star;
    }

}
