<?php

namespace Server\Worker;

use Annotation\Annotation;
use Annotation\Inject;
use Exception;
use Http\Route\Router;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Kiri\Runtime;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;

class OnWorkerStart implements EventDispatcherInterface
{


    #[Inject(Annotation::class)]
    public Annotation $annotation;


    #[Inject(Router::class)]
    public Router $router;


    /**
     * @param object $event
     * @return object|void
     * @throws \Kiri\Exception\ConfigException
     * @throws \ReflectionException
     */
    public function dispatch(object $event)
    {
        $isWorker = $event->workerId < $event->server->setting['worker_num'];

        $this->annotation->read(APP_PATH . 'app','App', $isWorker ? [] : [CONTROLLER_PATH]);
        $this->interpretDirectory();
        if ($isWorker) {
            putenv('environmental=' . Kiri::WORKER);
            $this->router->_loader();

            echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Worker[%d].%d start.", $event->server->worker_pid, $event->workerId) . PHP_EOL;

            $this->setProcessName(sprintf('Worker[%d].%d', $event->server->worker_pid, $event->workerId));
        } else {
            putenv('environmental=' . Kiri::TASK);

            echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Tasker[%d].%d start.", $event->server->worker_pid, $event->workerId) . PHP_EOL;

            $this->setProcessName(sprintf('Tasker[%d].%d', $event->server->worker_pid, $event->workerId));
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
                $value['class']::execute((object)$value['params'], $class);
            }
            $methods = $di->getMethodAttribute($class);
            foreach ($methods as $method => $attribute) {
                if (empty($attribute)) {
                    continue;
                }
                foreach ($attribute as $item) {
                    $item['class']::execute((object)$item['params'], $class, $method);
                }
            }
        }
    }

}
