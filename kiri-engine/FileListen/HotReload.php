<?php

namespace Kiri\FileListen;

use Annotation\Inject;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 *
 */
class HotReload extends Command
{


    public bool $isReloading = FALSE;
    public bool $isReloadingOut = FALSE;
    public ?array $dirs = [];

    public int $events;

    public int $int = -1;


    #[Inject(Logger::class)]
    public Logger $logger;


    private Scaner|Inotify $driver;

    private ?Process $process = NULL;


    protected mixed $source = NULL;

    protected mixed $pipes = [];

    protected ?Coroutine\Channel $channel = NULL;


    /**
     *
     */
    protected function configure()
    {
        $this->setName('sw:wather')
            ->setDescription('server start');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConfigException
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: Implement onHandler() method.
        set_error_handler([$this, 'onErrorHandler']);

        $this->dirs = Config::get('inotify', [APP_PATH . 'app']);
        if (!extension_loaded('inotify')) {
            $this->driver = Kiri::getDi()->get(Scaner::class, [$this->dirs, $this]);
        } else {
            $this->driver = Kiri::getDi()->get(Inotify::class, [$this->dirs, $this]);
        }
        if (Kiri::getPlatform()->isLinux()) {
            swoole_set_process_name('[' . Config::get('id', 'sw service.') . '].sw:wather');
        }
        $this->trigger_reload();
        Coroutine::create(function () {
            Coroutine::create(function () {
                $this->onSignal(Coroutine::waitSignal(SIGKILL | SIGTERM, -1));
            });
            $this->driver->start();
        });
        return 0;
    }


    /**
     * @throws Exception
     */
    #[NoReturn] public function onSignal($data)
    {
        $this->driver->clear();
        $pid = file_get_contents(storage('.swoole.pid'));
        if (!empty($pid) && Process::kill($pid, 0)) {
            while (Process::kill($pid, 0)) {
                Process::kill($pid, SIGTERM);
            }
        }
        $this->logger->notice('over');
    }


    /**
     * @throws Exception
     */
    private function stop(): void
    {
        if (is_resource($this->source)) {
            proc_terminate($this->source);
            while (proc_get_status($this->source)['running']) {
                Coroutine::sleep(1);
            }
            proc_close($this->source);
            $this->source = NULL;
        }
    }


    /**
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     * @throws Exception
     */
    public function onErrorHandler($code, $message, $file, $line)
    {
        if (str_contains($message, 'The file descriptor is not an inotify instance')) {
            return;
        }
        debug('Error:' . $message . ' at ' . $file . ':' . $line);
    }


    /**
     * 重启
     *
     * @throws Exception
     */
    public function trigger_reload()
    {
        $this->logger->notice('change reload');
        $pid = file_get_contents(storage('.swoole.pid'));
        if (!empty($pid) && Process::kill($pid, 0)) {
            Process::kill($pid, SIGTERM);
        }
        $this->stop();
        Coroutine::create(function () {
            $this->source = proc_open('php ' . APP_PATH . '/kiri.php sw:server restart', [], $pipes);
        });
    }


}
