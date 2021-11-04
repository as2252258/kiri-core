<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Coroutine;
use Swoole\Coroutine\Barrier;
use Swoole\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Co\run;


/**
 *
 */
class FileChangeCustomProcess extends Command
{


	public bool $isReloading = false;
	public bool $isReloadingOut = false;
	public ?array $dirs = [];
	public int $events;

	public int $int = -1;


	protected mixed $source = null;
	protected mixed $pipes = [];

    protected ?Coroutine\Channel $channel = null;


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
			$driver = Kiri::getDi()->get(Scaner::class, [$this->dirs, $this]);
		} else {
			$driver = Kiri::getDi()->get(Inotify::class, [$this->dirs, $this]);
		}

        $this->trigger_reload();
        Coroutine::create(function () use ($driver) {
            $driver->start();
        });
        return 0;
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
        Kiri::getDi()->get(Logger::class)->warning('change reload');

        if (file_exists(storage('.swoole.pid'))) {
            $content = (int)file_get_contents(storage('.swoole.pid'));
            if ($content > 0 && Process::kill($content,0)){
                var_dump(Process::kill($content,15));
            }
            @unlink(storage('.swoole.pid'));
        }


        Coroutine::create(function () {
            $descriptorspec = [0 => STDIN, 1 => STDOUT, 2 => STDERR];

            proc_open("php " . APP_PATH . "kiri.php", $descriptorspec, $pipes);
        });
	}






}
