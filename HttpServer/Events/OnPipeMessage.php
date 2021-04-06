<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Loader;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPipeMessage
 * @package HttpServer\Events
 */
class OnPipeMessage extends Callback
{

    /**
     * @param Server $server
     * @param int $src_worker_id
     * @param $message
     * @throws ComponentException
     * @throws Exception
     */
    public function onHandler(Server $server, int $src_worker_id, $message)
    {
        // TODO: Implement onHandler() method.
        var_dump($message);
        if (is_array($message) && ($message[1] ?? null) instanceof Loader) {
            if ($src_worker_id > $server->setting['worker_num']) {
                $message[1]->loadByDirectory(MODEL_PATH);
            } else {
                $message[1]->loadByDirectory(APP_PATH);
            }
        } else {
            $events = Snowflake::app()->getEvent();
            $events->trigger(Event::PIPE_MESSAGE, [$server, $src_worker_id, $message]);
        }
    }

}
