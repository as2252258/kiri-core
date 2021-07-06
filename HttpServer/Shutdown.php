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
		$output = shell_exec('[ -f /.dockerenv ] && echo yes || echo no');
		if (trim($output) === 'yes') {
			return;
		}

		$master_pid = Server()->setting['pid_file'] ?? PID_PATH;
		if (file_exists($master_pid)) {
			$this->close(file_get_contents($master_pid));
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
	 */
	public function directoryCheck(string $path): bool
	{
		$values = $this->getProcessPidS($path);
		if (empty($values)) return false;

		$diff = array_diff($values, $this->getPidS());
		foreach ($diff as $value) {
			$this->pidIsExists($value);
		}
		return false;
	}


	/**
	 * @param $path
	 * @return array|bool
	 */
	private function getProcessPidS($path): bool|array
	{
		$values = [];
		$dir = new \DirectoryIterator($path);
		if ($dir->getSize() < 1) {
			return $values;
		}
		foreach ($dir as $value) {
			if ($value->isDot()) continue;

			if (!$value->valid()) continue;

			$_value = file_get_contents($value->getRealPath());
			if (empty($_value)) {
				continue;
			}
			$values[] = intval($_value);
		}
		return $values;
	}


	/**
	 * @return array
	 */
	private function getPidS(): array
	{
		exec('ps -eo pid', $output);
		return array_filter($output, function ($value) {
			return intval($value);
		});
	}


	/**
	 * @param string $value
	 */
	public function close(mixed $value)
	{
		while ($this->pidIsExists($value)) {
			exec('kill -15 ' . $value);
			usleep(100);
		}
		clearstatcache($value);
		if (file_exists($value)) {
			@unlink($value);
		}
	}


}
