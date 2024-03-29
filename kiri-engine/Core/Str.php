<?php
declare(strict_types=1);

namespace Kiri\Core;


use Exception;


/**
 * Class Str
 * @package Kiri\Core
 */
class Str
{

    const string STRING = 'abcdefghijklmnopqrstuvwxyz';

    const string NUMBER = '01234567890';

    /**
     * @param int $length
     *
     * @return string
     * 获取随机字符串
     */
    public static function rand(int $length = 20): string
    {
        $string = '';
        if ($length < 1) $length = 20;
        $default = self::STRING . strtoupper(self::STRING) . self::NUMBER;
        $default = str_split($default);
        for ($i = 0; $i < $length; $i++) {
            shuffle($default);
            $string .= $default[array_rand($default)];
        }
        return $string;
    }

    /**
     * @param int $length
     *
     * @return int|string 获取随机数字
     * 获取随机数字
     */
    public static function random(int $length = 20): int|string
    {
        $number  = '';
        $default = str_split(self::NUMBER);
        if ($length < 1) $length = 1;
        for ($i = 0; $i < $length; $i++) {
            shuffle($default);
            $number .= $default[array_rand($default)];
        }
        return $number;
    }

    /**
     * @param        $string
     * @param        $sullen
     * @param bool $strip_tags
     * @param string $append
     *
     * @return string
     */
    public static function cut_str_utf8($string, $sullen, bool $strip_tags = TRUE, string $append = '...'): string
    {
        if ($strip_tags) {
            $string = strip_tags($string);
        }//去掉签标
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);
        $str = "";
        for ($i = 0; $i < count($t_string[0]); $i++) {
            $str .= $t_string[0][$i];
            //转为gbk，一个汉字长度为2
            if (strlen(@iconv('utf-8', 'gbk', $str)) >= $sullen) {
                if ($i != count($t_string[0]) - 1) $str .= $append;
                break;
            }
        }
        return $str;
    }

    /**
     * @param $data
     *
     * @param null $callback
     * @return bool
     * 判断是否为json字符串
     */
    public static function isJson($data, $callback = NULL): bool
    {
        $json = !is_null(json_decode($data)) && !is_numeric($data);
        if ($json && is_callable($callback, TRUE)) {
            return call_user_func($callback, $data);
        }
        return $json;
    }

    /**
     * @param $data
     *
     * @param null $callBack
     * @return bool
     * 判断是否序列化字符串
     */
    public static function isSerialize($data, $callBack = NULL): bool
    {
        $false = !empty($data) && unserialize($data) !== FALSE;
        if ($false && is_callable($callBack, TRUE)) {
            return call_user_func($callBack, $data);
        }
        return $false;
    }

    /**
     * @param     $string
     * @param int $length
     *
     * @param string $append
     * @return string
     */
    public static function cut($string, int $length = 20, string $append = '...'): string
    {
        if (empty($string)) {
            return '';
        }
        if ($length < 1) {
            $length = 1;
        }
        $array = str_split($string);
        if (count($array) <= $length) {
            return implode('', $array);
        }
        $string = implode('', array_slice($array, 0, $length));
        if (!empty($append)) {
            $string .= $append;
        }
        return $string;
    }

    /**
     * @param        $str
     * @param int $number
     * @param string $key
     *
     * @return string
     */
    public static function encrypt($str, int $number = 10, string $key = 'xshucai.com'): string
    {
        $res = [];
        $add = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($key) + 5 > strlen($add) ? strlen($add) - 5 : strlen($key);
        if ($number < 1) $number = 10;
        $array = str_split($str);
        asort($array);
        $str = implode('', $array);
        for ($i = 0; $i < $number; $i++) {
            $_tmp  = md5($key) . md5($str) . mb_substr($add, $len, $len + 5, 'utf-8');
            $res[] = md5($_tmp);
        }
        sort($res, SORT_STRING);
        return hash('sha384', implode('', $res));
    }

    /**
     * @param $file
     * @param $type
     * @return string
     */
    public static function filename($file, $type): string
    {
        return match ($type) {
            'image/png'               => md5_file($file) . '.png',
            'image/jpeg', 'image/jpg' => md5_file($file) . '.jpg',
            'image/gif'               => md5_file($file) . '.gif',
            default                   => md5_file($file),
        };
    }

    /**
     * @param $endTime
     * @param int|null $startTime
     * @return array
     * 剩余天，带分秒
     */
    public static function timeout($endTime, int $startTime = NULL): array
    {
        $endTime = $endTime - (!empty($startTime) ? $startTime : time());

        $day = intval($endTime / (3600 * 24));

        $hours = intval(($endTime - ($day * (3600 * 24))) / 3600);

        $minute = intval(($endTime - ($day * (3600 * 24) + $hours * 3600)) / 60);

        $scrod = intval(($endTime - ($day * (3600 * 24) + $hours * 3600 + $minute * 60)));

        return [$day, $hours, $minute, $scrod];
    }


    /**
     * @return false|int
     */
    public static function get_sy_time(): bool|int
    {
        $time = strtotime('+1days', strtotime(date('Y-m-d')));

        return $time - time();
    }

    /**
     * @param string $string
     * @return string
     */
    public static function encode(string $string): string
    {
        return addslashes($string);
    }

    /**
     * @param string $string
     * @return string|string[]|null
     * 清除标点符号
     */
    public static function clear(string $string): array|string|null
    {
        $char = '。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔¡¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）';
        return preg_replace(["/[[:punct:]]/i", '/[' . $char . ']/u', '/ {2,}/'], '', $string);
    }


    /**
     * @param int $user
     * @param array $param
     * @param null $requestTime
     *
     * @return string
     * @throws Exception
     */
    public static function token(int $user, array $param = [], $requestTime = NULL): string
    {
        $str = '';
        if (!$requestTime) {
            $requestTime = microtime(TRUE);
        }
        $_user = str_split(md5($user . md5((string)$user)));
        ksort($_user);
        foreach ($_user as $key => $val) {
            $str .= md5(sha1($key . $val . 'www.xshucai.com'));
        }
        if (is_array($param)) {
            foreach ($param as $key => $val) {
                $str .= md5($str . sha1($key . md5($val)));
            }
        }
        $str .= sha1(base64_encode((string)$requestTime));

        $md5 = md5($str . $user);

        return preg_replace('/(\w{10})(\w{3})(\w{4})(\w{9})(\w{6})/', '$1-$2-$3-$4-$5', $md5);
    }


    /**
     * @param string $str
     * @param bool $unfairest
     * @return string
     */
    public static function convertUnderline(string $str, bool $unfairest = TRUE): string
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', lcfirst($str));
        return $unfairest ? ucfirst($str) : $str;
    }
}
