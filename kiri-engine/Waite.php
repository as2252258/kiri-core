<?php

namespace Kiri;

use Swoole\Coroutine;

class Waite
{


    /**
     * @param float $time
     * @return void
     */
    public static function sleep(float $time): void
    {
        if (!class_exists(Coroutine::class) || Coroutine::getCid() > -1) {
            usleep($time * 1000);
        } else {
            Coroutine::sleep($time / 1000);
        }
    }

}