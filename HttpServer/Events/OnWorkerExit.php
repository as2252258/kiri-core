<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kiri\Event;
use Kiri\Kiri;
use Swoole\Timer;

/**
 * Class OnWorkerExit
 * @package HttpServer\Events
 */
class OnWorkerExit extends Callback
{

    /**
     * @param $server
     * @param $worker_id
     * @throws Exception
     */
    public function onHandler($server, $worker_id)
    {
        putenv('state=exit');

        Event::trigger(Event::SERVER_WORKER_EXIT, [$server, $worker_id]);

        Kiri::getApp('logger')->insert();
    }

}
