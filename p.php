<?php

use function Swoole\Coroutine\run;

run(function () {

    $channel = new \Swoole\Coroutine\Channel(100);
    for ($i = 0; $i < 90; $i++) {
        $channel->push(100);
    }
    $channel->close();
    $channel = null;
    var_dump($channel);
});