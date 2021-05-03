<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerError
 * @package HttpServer\Events
 */
class OnWorkerError extends Callback
{


    /**
     * @param Server $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @param int $signal
     * @throws Exception
     */
    public function onHandler(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        Event::trigger(Event::SERVER_WORKER_ERROR);

        $message = sprintf('Worker#%d::%d error stop. signal %d, exit_code %d, msg %s',
            $worker_id, $worker_pid, $signal, $exit_code, swoole_strerror(swoole_last_error(), 9)
        );

        write($message, 'worker-exit');
    }

}
