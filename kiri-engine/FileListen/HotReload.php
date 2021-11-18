<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Constant;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 *
 */
class HotReload extends Command
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
		if (Kiri::getPlatform()->isLinux()) {
			swoole_set_process_name('[' . Config::get('id', 'sw service.') . '].sw:wather');
		}
		$this->trigger_reload();
		Coroutine::create(fn() => $this->onExit());
		Coroutine::create(function () use ($driver) {
			$driver->start();
		});
		return 0;
	}


	/**
	 * @throws Exception
	 */
	public function onExit()
	{
		$data = Coroutine::waitSignal(SIGTERM | SIGKILL, -1);
		if ($data) {
			$pid = file_get_contents(storage('.swoole.pid'));
			if (!empty($pid) && Process::kill($pid, 0)) {
				Process::kill($pid, SIGTERM);
			}
			$this->stop();
			$this->source = null;
		}
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
			$this->source = null;
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
		Kiri::getDi()->get(Logger::class)->warning('change reload');
		Coroutine::create(function () {
			$reusePort = Config::get('server.settings')[Constant::OPTION_ENABLE_REUSE_PORT] ?? false;
			if (!$reusePort) {
				$this->source = proc_open("php " . APP_PATH . "kiri.php sw:server", [], $pipes);
				return;
			}
			$pid = file_get_contents(storage('.swoole.pid'));
			$source = proc_open("php " . APP_PATH . "kiri.php sw:server start", [], $pipes);
			if (!empty($pid) && Process::kill($pid, 0)) {
				Process::kill($pid, SIGTERM);
			}
			$this->stop();
			$this->source = $source;
		});
	}


}
