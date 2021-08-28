<?php

namespace Server\Worker;

use Annotation\Annotation;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Kiri\Runtime;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Server\Events\OnWorkerStart as WorkerStart;

class OnWorkerStart implements EventDispatcherInterface
{


    public Annotation $annotation;


    /**
     * @param \Server\Events\OnWorkerStart $onWorkerStart
     * @throws \Exception
     */
    public function __construct()
    {
        $this->annotation = Kiri::app()->getAnnotation();
    }


    /**
     * @param object $event
     * @return object|void
     * @throws \Kiri\Exception\ConfigException
     * @throws \ReflectionException
     */
    public function dispatch(object $event)
    {
        putenv('state=start');
        putenv('worker=' . $event->workerId);
        $serialize = file_get_contents(storage(Runtime::CONFIG_NAME));
        if (!empty($serialize)) {
            Config::sets(unserialize($serialize));
        }
        if ($event->workerId < $event->server->setting['worker_num']) {
            $this->onWorkerInit($event);
        } else {
            $this->onTaskInit($event);
        }
        $this->interpretDirectory();
    }


    /**
     * @param $event
     * @throws \Kiri\Exception\ConfigException
     */
    public function onTaskInit($event)
    {
        $this->annotation->read(APP_PATH . 'app', 'App', [CONTROLLER_PATH]);

        putenv('environmental=' . Kiri::TASK);

        echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Tasker[%d].%d start.", $event->server->worker_pid, $event->workerId) . PHP_EOL;

        $this->setProcessName(sprintf('Tasker[%d].%d', $event->server->worker_pid, $event->workerId));
    }


    /**
     * @param $event
     * @throws \Kiri\Exception\ConfigException
     * @throws \ReflectionException
     */
    public function onWorkerInit($event)
    {
        $this->annotation->read(APP_PATH . 'app');
        putenv('environmental=' . Kiri::WORKER);
        echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Worker[%d].%d start.", $event->server->worker_pid, $event->workerId) . PHP_EOL;
        $this->setProcessName(sprintf('Worker[%d].%d', $event->server->worker_pid, $event->workerId));
        if (is_enable_file_modification_listening()) {
            $loader = Kiri::app()->getRouter();
            $loader->_loader();
        }
    }


    /**
     * @param $prefix
     * @throws ConfigException
     */
    protected function setProcessName($prefix)
    {
        if (Kiri::getPlatform()->isMac()) {
            return;
        }
        $name = Config::get('id', 'system-service');
        if (!empty($prefix)) {
            $name .= '.' . $prefix;
        }
        swoole_set_process_name($name);
    }


    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function interpretDirectory()
    {
        $fileLists = $this->annotation->runtime(APP_PATH . 'app');
        $di = Kiri::getDi();
        foreach ($fileLists as $class) {
            foreach ($di->getTargetNote($class) as $value) {
                $value->execute($class);
            }
            $methods = $di->getMethodAttribute($class);
            foreach ($methods as $method => $attribute) {
                if (empty($attribute)) {
                    continue;
                }
                foreach ($attribute as $item) {
                    $item->execute($class, $method);
                }
            }
        }
    }

}
