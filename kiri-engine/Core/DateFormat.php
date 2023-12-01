<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/1/14 0014
 * Time: 13:50
 */
declare(strict_types=1);

namespace Kiri\Core;


/**
 * Class DateFormat
 * @package Kiri\Core
 */
class DateFormat
{


    /**
     * @return int
     */
    public static function DaySecond(): int
    {
        $time = strtotime(date('Y-m-d', strtotime('+1days')));

        return $time - time();
    }


    /**
     * @param $time
     * @return bool|false|int|string
     */
    private static function check($time): bool|int|string
    {
        if ($time === null) {
            $time = time();
        } else if (is_numeric($time)) {
            $length = strlen((string)$time);
            if ($length != 10 && $length != 13) {
                return false;
            }
        } else if (is_string($time)) {
            $time = strtotime($time);
        }

        if (date('Y-m-d', $time)) {
            return $time;
        }
        return false;
    }


    /**
     * @param null $time
     * @return bool|false|int
     *
     * 获取指定日期当周第一天的时间
     */
    public static function getWeekCurrentDay($time = null): bool|int
    {
        if (!($time = static::check($time))) {
            return false;
        }

        $time = strtotime('-' . (date('N') - 1) . 'days', $time);

        return strtotime(date('Y-m-d'), $time);
    }


    /**
     * @param null $time
     * @return bool|false|int
     *
     * 获取指定日期当月第一天的时间
     */
    public static function getMonthCurrentDay($time = null): bool|int
    {
        if (!($time = static::check($time))) {
            return false;
        }

        return strtotime(date('Y-m', $time) . '-01');
    }

    /**
     * @param $time
     * @return bool|int|string 指定的月份有几天
     * 指定的月份有几天
     */
    public static function getMonthTotalDay($time): bool|int|string
    {
        if (!($time = static::check($time))) {
            return false;
        }

        return date('t', $time);
    }

    /**
     * @param $startTime
     * @param null $endTime
     * @return string
     */
    public static function mtime($startTime, $endTime = null): string
    {
        if ($endTime === null) {
            $endTime = microtime(true);
        }
        return sprintf('%.7f', $endTime - $startTime);
    }
}
