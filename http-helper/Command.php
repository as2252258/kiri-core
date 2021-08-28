<?php
declare(strict_types=1);

namespace Http;


use Annotation\Inject;
use Exception;
use Kiri\Abstracts\Input;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Worker\OnWorkerStart as WorkerDispatch;
use Server\Events\OnWorkerStart;

/**
 * Class Command
 * @package Http
 */
class Command extends \Console\Command
{

    public string $command = 'sw:server';


    public string $description = 'server start|stop|reload|restart';


    const ACTIONS = ['start', 'stop', 'restart'];


    /**
     * @var \Kiri\Events\EventProvider
     */
    #[Inject(EventProvider::class)]
    public EventProvider $eventProvider;


    /**
     * @param Input $dtl
     * @return string
     * @throws Exception
     * @throws ConfigException
     */
    public function onHandler(Input $dtl): string
    {
        $manager = Kiri::app()->getServer();
        $manager->setDaemon($dtl->get('daemon', 0));
        if (!in_array($dtl->get('action'), self::ACTIONS)) {
            return 'I don\'t know what I want to do.';
        }
        if ($manager->isRunner() && $dtl->get('action') == 'start') {
            return 'Service is running. Please use restart.';
        }
        $manager->shutdown();
        if ($dtl->get('action') == 'stop') {
            return 'shutdown success.';
        }
        return $this->generate_runtime_builder($manager);
    }


    /**
     * @param $manager
     * @return mixed
     * @throws \Kiri\Exception\NotFindClassException
     * @throws \ReflectionException
     */
    private function generate_runtime_builder($manager)
    {
        exec(PHP_BINARY . ' ' . APP_PATH . 'kiri.php runtime:builder');

        $this->eventProvider->on(OnWorkerStart::class, [di(WorkerDispatch::class), 'dispatch']);

        return $manager->start();
    }

}
