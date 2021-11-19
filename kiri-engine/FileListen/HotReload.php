<?php

namespace Kiri\FileListen;

use Annotation\Inject;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Error\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Runtime;
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


	private ?Process $process = null;


	public Inotify|Scaner $driver;


	#[Inject(Logger::class)]
	public Logger $logger;


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
		swoole_async_set(['enable_coroutine' => false]);
		if (!extension_loaded('inotify')) {
			$this->driver = Kiri::getDi()->get(Scaner::class, [$this->dirs, $this]);
		} else {
			$this->driver = Kiri::getDi()->get(Inotify::class, [$this->dirs, $this]);
		}
		if (Kiri::getPlatform()->isLinux()) {
			swoole_set_process_name('[' . Config::get('id', 'sw service.') . '].sw:wather');
		}
		$this->trigger_reload();

		var_dump(getmypid());

		Process::signal(SIGTERM, [$this, 'onSignal']);
		Process::signal(SIGKILL, [$this, 'onSignal']);

		$this->driver->start();
		return 0;
	}


	/**
	 * @param $data
	 * @throws Exception
	 */
	public function onSignal($data)
	{
		$this->driver->clear();
		$pid = file_get_contents(storage('.swoole.pid'));
		if (!empty($pid) && Process::kill($pid, 0)) {
			Process::kill($pid, SIGTERM);
		}
		if ($this->process && Process::kill($this->process->pid, 0)) {
			Process::kill($this->process->pid, 15);
		}
		while ($ret = Process::wait(true)) {
			echo "PID={$ret['pid']}\n";
			sleep(1);
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
		$this->logger->warning('change reload');
		$pid = file_get_contents(storage('.swoole.pid'));
		if (!empty($pid) && Process::kill($pid, 0)) {
			Process::kill($pid, SIGTERM);
		}
		if ($this->process && Process::kill($this->process->pid, 0)) {
			Process::kill($this->process->pid, 15);
		}
		Process::wait(true);
		$this->process = new Process(function (Process $process) {
			$process->exec(PHP_BINARY, [APP_PATH . "kiri.php", "sw:server", "restart"]);
		});
		$this->process->start();
	}


}
