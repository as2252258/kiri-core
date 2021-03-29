<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Component;


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
		exec('ls -alh /.dockerenv', $output, $cod);
		if ($cod === 0 && !empty($output)) {
			return;
		}

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
	public function isRunning()
	{
		$master_pid = Server()->setting['pid_file'] ?? PID_PATH;

		if (!file_exists($master_pid)) {
			return false;
		}

		return $this->pidIsExists($master_pid);
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
		$shell = 'ps -eo pid,cmd,state | grep %d | grep -v grep';
		exec(sprintf($shell, intval($content)), $output, $code);
		var_dump($content, $output, $code);
		if (empty($output)) {
			return false;
		}
		return true;
	}


	/**
	 * @param string $path
	 * @return bool
	 */
	public function directoryCheck(string $path): bool
	{
		$dir = new \DirectoryIterator($path);
		if ($dir->getSize() < 1) {
			return true;
		}
		foreach ($dir as $value) {
			/** @var \DirectoryIterator $value */
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
		$resource = fopen($value, 'r');
		$content = fgets($resource);
		fclose($resource);

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
