<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kiri\Event;
use Kiri\Kiri;
use Swoole\Server;

/**
 * Class OnFinish
 * @package HttpServer\Events
 */
class OnFinish extends Callback
{


    /**
     * @param Server $server
     * @param $task_id
     * @param $data
     * @throws Exception
     */
    public function onHandler(Server $server, $task_id, $data)
    {
        try {
            defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

            fire(Event::TASK_FINISH, [$task_id, $data]);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'task');
        }
    }

}
