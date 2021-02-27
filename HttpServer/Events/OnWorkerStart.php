<?php
declare(strict_types=1);

namespace HttpServer\Events;


use co;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{


    private int $_taskTable = 0;

    private int $signal = SIGTERM | SIGKILL | SIGUSR2 | SIGUSR1;


    /**
     * @param Server $server
     * @param int $worker_id
     *
     * @return mixed
     * @throws ConfigException
     * @throws Exception
     */
    public function onHandler(Server $server, int $worker_id): void
    {
        Coroutine::set(['enable_deadlock_check' => false]);
        putenv('workerId=' . $worker_id);

        $get_name = $this->get_process_name($server, $worker_id);
        if (!empty($get_name) && !Snowflake::isMac()) {
            swoole_set_process_name($get_name);
        }

        $this->onSignal($server, $worker_id);
        if ($worker_id >= $server->setting['worker_num']) {
            fire(Event::SERVER_TASK_START);

            putenv('environmental=' . Snowflake::TASK);
        } else {
            putenv('environmental=' . Snowflake::WORKER);

            Snowflake::setWorkerId($server->worker_pid);
            $this->setWorkerAction($worker_id);
        }
    }

    /**
     * @param $server
     * @param $worker_id
     */
    public function onSignal($server, $worker_id)
    {
        $this->debug(sprintf(workerName($worker_id) . ' #%d is start.....', $worker_id));
        Coroutine\go(function (Server $server) {
            $sigkill = Coroutine::waitSignal($this->signal);
            if ($sigkill === false) {
                return $server->stop();
            }
            while (Co::stats()['coroutine_num'] > 0) {
                Coroutine::sleep(0.01);
            }
            return $server->stop();
        }, $server);
    }


    /**
     * @param $worker_id
     * @throws Exception
     */
    private function setWorkerAction($worker_id)
    {
        $event = Snowflake::app()->getEvent();
        try {
            $event->trigger(Event::SERVER_WORKER_START, [$worker_id]);
        } catch (\Throwable $exception) {
            $this->addError($exception);
            write($exception->getMessage(), 'worker');
        }
        try {
            $event->trigger(Event::SERVER_AFTER_WORKER_START, [$worker_id]);
        } catch (\Throwable $exception) {
            $this->addError($exception);
            write($exception->getMessage(), 'worker');
        }
    }

    /**
     * @param $socket
     * @param $worker_id
     * @return string
     * @throws ConfigException
     */
    private function get_process_name($socket, $worker_id): string
    {
        $prefix = rtrim(Config::get('id', false, 'system:'), ':');
        if ($worker_id >= $socket->setting['worker_num']) {
            return $prefix . ': Task: No.' . $worker_id;
        } else {
            return $prefix . ': worker: No.' . $worker_id;
        }
    }


}
