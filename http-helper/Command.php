<?php
declare(strict_types=1);

namespace Http;


use Exception;
use Kiri\Abstracts\Input;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;

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
        if (is_enable_file_modification_listening()) {
            scan_directory(directory('app'), 'App');
            $loader = Kiri::app()->getRouter();
            $loader->_loader();
        }
        return $manager->start();
    }

}
