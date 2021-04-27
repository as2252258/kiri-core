<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
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
        $channel = Snowflake::app()->getChannel();
		$channel->cleanAll();

        Event::trigger(Event::SERVER_WORKER_EXIT);

        logger()->insert();
	}

}
