<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Kiri;
use Swoole\Coroutine\Barrier;
use Swoole\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


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


	/**
	 *
	 */
	protected function configure()
	{
		$this->setName('sw:wather')
			->setDescription('server start|stop|reload|restart')
			->addArgument('action', InputArgument::REQUIRED);
	}


	/**
	 * @param Process $process
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
		$make = Barrier::make();
		go(function () {
			$this->trigger_reload();
		});
		go(function () use ($driver) {
			$driver->start();
		});
		Barrier::wait($make);
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
	 * @throws Exception
	 */
	public function trigger_reload()
	{
		proc_open("php " . APP_PATH . "kiri.php sw:server restart", [], $pipes);

//		exec(PHP_BINARY . ' ' . APP_PATH . 'kiri.php runtime:builder', $output);
//
//		print_r(implode(PHP_EOL, $output));

//		Kiri::reload();
	}
}
