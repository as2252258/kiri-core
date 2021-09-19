<?php

namespace Server\Worker;

use Kiri\Abstracts\Config;
use Kiri\Kiri;
use Psr\EventDispatcher\EventDispatcherInterface;
use Server\ServerManager;

class OnTaskerStart extends WorkerStart implements EventDispatcherInterface
{


    /**
     * @throws \Kiri\Exception\ConfigException
     * @throws \ReflectionException
     */
    public function dispatch(object $event)
    {
        $isWorker = $event->workerId < $event->server->setting['worker_num'];

        $time = microtime(true);

        ServerManager::setEnv('environmental', Kiri::TASK);
        if (!is_enable_file_modification_listening()) {
            $this->interpretDirectory();
        }

        $this->mixed($event, $isWorker, $time);
    }


}
