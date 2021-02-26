<?php
declare(strict_types=1);

namespace HttpServer\Events;


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

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{


    private int $_taskTable = 0;


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
//        Coroutine::set([
//            'enable_deadlock_check' => false,
//            'exit_condition'        => function () {
//                return Coroutine::stats()['coroutine_num'] === 0;
//            }
//        ]);
        $get_name = $this->get_process_name($server, $worker_id);
        if (!empty($get_name) && !Snowflake::isMac()) {
            swoole_set_process_name($get_name);
        }

        Coroutine\go(function (Server $server, $worker_id) {
            $sigkill = Coroutine::waitSignal(SIGTERM | SIGKILL | SIGUSR2 | SIGUSR1);
            if ($sigkill !== false) {
                return Process::kill($server->worker_pid);
            }
            do {
                $number = Coroutine::stats()['coroutine_num'];
                if ($number === 0) {
                    break;
                }
                Coroutine::sleep(0.01);
            } while (true);
            return Process::kill($server->worker_pid);
        }, $server, $worker_id);

        $name = ($worker_id >= $server->setting['worker_num'] ? 'Task' : 'Worker');
        $this->debug(sprintf($name . ' #%d is start.....', $worker_id));
        putenv('workerId=' . $name . '.' . $worker_id);
        if ($worker_id >= $server->setting['worker_num']) {
            fire(Event::SERVER_TASK_START);
        } else {
            Snowflake::setWorkerId($server->worker_pid);
            $this->setWorkerAction($worker_id);
        }
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
