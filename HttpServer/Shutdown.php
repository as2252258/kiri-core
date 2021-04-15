<?php


namespace HttpServer;


use Annotation\Aspect;
use Database\InjectProperty;
use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;


/**
 * Class Shutdown
 * @package HttpServer
 */
class Shutdown extends Component
{


	private string $taskDirectory;
	private string $workerDirectory;
	private string $managerDirectory;
	private string $processDirectory;


	private array $_pids = [];


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->taskDirectory = storage(null, 'pid/task');
		$this->workerDirectory = storage(null, 'pid/worker');
		$this->managerDirectory = storage(null, 'pid/manager');
		$this->processDirectory = storage(null, 'pid/process');
	}


	/**
	 * @throws Exception
	 */
	public function shutdown(): void
	{
		clearstatcache(storage());
//		exec('ls -alh /.dockerenv', $output, $cod);
//		if ($cod === 0 && !empty($output)) {
//			return;
//		}

		$master_pid = Server()->setting['pid_file'] ?? PID_PATH;
		if (file_exists($master_pid)) {
			$this->close($master_pid);
		}
		$this->closeOther();
	}


	/**
	 * 关闭其他进程
	 */
	private function closeOther(): void
	{
		$this->directoryCheck($this->managerDirectory);
		$this->directoryCheck($this->taskDirectory);
		$this->directoryCheck($this->workerDirectory);
		$this->directoryCheck($this->processDirectory);
	}


	/**
	 * @return bool
	 * @throws Exception
	 * check server is running.
	 */
	public function isRunning(): bool
	{
		$master_pid = Server()->setting['pid_file'] ?? PID_PATH;

		if (!file_exists($master_pid)) {
			return false;
		}

		return $this->pidIsExists(file_get_contents($master_pid));
	}


	/**
	 * @param $content
	 * @return bool
	 */
	public function pidIsExists($content): bool
	{
		if (intval($content) < 1) {
			return false;
		}
		exec('ps -eo pid', $output);
		$output = array_filter($output, function ($value) {
			return intval($value);
		});
		return in_array(intval($content), $output);
	}


	/**
	 * @param string $path
	 * @return bool
	 * @throws ConfigException
	 */
	public function directoryCheck(string $path): bool
	{
		$dir = new \DirectoryIterator($path);
		if ($dir->getSize() < 1) {
			return true;
		}
		foreach ($dir as $value) {
			if ($value->isDot()) continue;

			if (!$value->valid()) continue;

			$this->close($value->getRealPath());
		}
		return false;
	}


	/**
	 * @param string $value
	 */
	public function close(string $value)
	{
		$content = file_get_contents($value);

		while ($this->pidIsExists($content)) {
			exec('kill -15 ' . $content);
			sleep(1);
		}

		clearstatcache($value);
		if (file_exists($value)) {
			@unlink($value);
		}
	}


}
