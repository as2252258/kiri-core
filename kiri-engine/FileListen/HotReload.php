<?php

namespace Kiri\FileListen;

use Exception;
use Kiri;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;
use Kiri\Error\StdoutLoggerInterface;
use Kiri\Exception\ConfigException;
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


	public bool $isReloading = FALSE;
	public bool $isReloadingOut = FALSE;
	public ?array $dirs = [];

	public int $events;

	public int $int = -1;


	private ?Process $process = NULL;


	public Inotify|Scaner $driver;


	public StdoutLoggerInterface $logger;


	protected mixed $source = NULL;

	protected mixed $pipes = [];

	protected ?Coroutine\Channel $channel = NULL;


	/**
	 */
	protected function configure()
	{
		$this->setName('sw:wather')->setDescription('server start');

		$this->logger = Kiri::getDi()->get(StdoutLoggerInterface::class);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	protected function initCore()
	{
		set_error_handler([$this, 'errorHandler']);
		$this->dirs = Config::get('inotify', [APP_PATH . 'app']);

		$container = Kiri::getDi();
		if (!extension_loaded('inotify')) {
			$this->driver = $container->make(Scaner::class, [$this->dirs, $this, $this->logger]);
		} else {
			$this->driver = $container->make(Inotify::class, [$this->dirs, $this, $this->logger]);
		}
		$this->clearOtherService();
		$this->setProcessName();
	}


	/**
	 * @throws ConfigException
	 */
	public function setProcessName()
	{
		swoole_async_set(['enable_coroutine' => FALSE]);
		if (Kiri::getPlatform()->isLinux()) {
			swoole_set_process_name('[' . Config::get('id', 'sw service.') . '].sw:wather');
		}
	}


	/**
	 * @throws Exception
	 */
	public function clearOtherService()
	{
		if (file_exists(storage('.manager.pid'))) {
			$pid = (int)file_get_contents(storage('.manager.pid'));
			if ($pid > 0 && Process::kill($pid, 0)) {
				Process::kill($pid, 15) && Process::wait(TRUE);
			}
		}
		file_put_contents(storage('.manager.pid'), getmypid());
	}


	/**
	 * @throws Exception
	 */
	public function errorHandler()
	{
		$error = func_get_args();

		$path = ['file' => $error[2], 'line' => $error[3]];

		if ($error[0] === 0) {
			$error[0] = 500;
		}
		$data = Json::to(500, $error[1], $path);

		$this->logger->error('error', [$data]);
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
		if ($this->int == 1) {
			return;
		}
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
		Timer::clearAll();
		$this->driver->clear();
		$this->stopServer();
		while ($ret = Process::wait(TRUE)) {
			echo "PID={$ret['pid']}\n";
			sleep(1);
		}
	}


	/**
	 * @throws Exception
	 */
	protected function stopServer()
	{
		$pid = file_get_contents(storage('.swoole.pid'));
		if (!empty($pid) && Process::kill($pid, 0)) {
			Process::kill($pid, SIGTERM);
		}
		if ($this->process && Process::kill($this->process->pid, 0)) {
			Process::kill($this->process->pid) && Process::wait(TRUE);
		}
	}


	/**
	 * 重启
	 *
	 * @throws Exception
	 */
	public function trigger_reload(string $path = '')
	{
		if ($this->int == 1) {
			return;
		}
		$this->int = 1;
		$this->logger->warning('restart');

		$this->stopServer();
		$this->process = new Process(function (Process $process) {
			$config = Config::get('scanner', []);
			scan_directory(MODEL_PATH, 'app\Model');
			if (is_array($config)) foreach ($config as $key => $value) {
				scan_directory($value, $key);
			}
			Kiri::app()->getServer()->start();
		});
		$this->process->start();

		sleep(3);

		$this->int = -1;
	}


}
