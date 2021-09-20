<?php

namespace Server\Abstracts;

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
        $time = microtime(true);

        ServerManager::setEnv('environmental', Kiri::TASK);
        if (!is_enable_file_modification_listening()) {
            $this->interpretDirectory();
        }

        $this->mixed($event, false, $time);
    }


}
