<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Error\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Note\Inject;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Timer;
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
	 */
	protected function configure()
	{
		$this->setName('sw:wather')->setDescription('server start');
	}


	/**
	 * @throws ConfigException
	 * @throws \ReflectionException
	 */
	protected function initCore()
	{
		$this->dirs = Config::get('inotify', [APP_PATH . 'app']);
		swoole_async_set(['enable_coroutine' => false]);
		if (!extension_loaded('inotify')) {
			$this->driver = Kiri::getDi()->make(Scaner::class, [$this->dirs, $this]);
		} else {
			$this->driver = Kiri::getDi()->make(Inotify::class, [$this->dirs, $this]);
		}
		if (Kiri::getPlatform()->isLinux()) {
			swoole_set_process_name('[' . Config::get('id', 'sw service.') . '].sw:wather');
		}
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
		$this->initCore();
		$this->trigger_reload();

		Timer::tick(1000, fn() => $this->healthCheck());

		Process::signal(SIGTERM, [$this, 'onSignal']);
		Process::signal(SIGKILL, [$this, 'onSignal']);

		$this->driver->start();
		return 0;
	}


	/**
	 * @throws Exception
	 */
	public function healthCheck()
	{
		$pid = (int)file_get_contents(storage('.swoole.pid'));
		if (empty($pid)) {
			$this->logger->warning('service is shutdown you need reload.');
			$this->trigger_reload();
		} else if (!Process::kill($pid, 0)) {
			$this->logger->warning('service is shutdown you need reload.');
			$this->trigger_reload();
		}
	}


	/**
	 * @param $data
	 * @throws Exception
	 */
	public function onSignal($data)
	{
		if (!$data) {
			return;
		}
		$this->driver->clear();
		$pid = file_get_contents(storage('.swoole.pid'));
		if (!empty($pid) && Process::kill($pid, 0)) {
			Process::kill($pid, SIGTERM);
		}
		if ($this->process && Process::kill($this->process->pid, 0)) {
			Process::kill($this->process->pid) && Process::wait(true);
		}
		while ($ret = Process::wait(true)) {
			echo "PID={$ret['pid']}\n";
			sleep(1);
		}
	}


	/**
	 * 重启
	 *
	 * @throws Exception
	 */
	public function trigger_reload()
	{
		$this->logger->warning('change reload');
		$pid = $this->process?->pid;
		if ($pid && Process::kill($pid, 0)) {
			Process::kill($pid) && Process::wait(true);
		}
		$this->process = null;
		$process = new Process(function (Process $process) {
			$process->exec(PHP_BINARY, [APP_PATH . "kiri.php", "sw:server", "restart"]);
		});
		$process->start();
		$this->process = $process;
	}


}
