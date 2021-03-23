<?php


namespace Snowflake\Core;


class No
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

}
