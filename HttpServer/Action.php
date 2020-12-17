<?php
declare(strict_types=1);

namespace HttpServer;


use Exception;

use Snowflake\Abstracts\Input;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\WebSocket\Server;

/**
 * Class Action
 * @package HttpServer
 */
trait Action
{

	/**
	 * @param \HttpServer\Server $socket
	 * @return mixed
	 * @throws Exception
	 */
	public function restart(\HttpServer\Server $socket)
	{
		$this->_shutdown($socket);

		return $this->start();
	}


	/**
	 * @param \HttpServer\Server $socket
	 * @throws Exception
	 */
	public function stop(\HttpServer\Server $socket)
	{
		$this->_shutdown($socket);
	}

	/**
	 * @param $server
	 * @return void
	 * @throws Exception
	 */
	private function _shutdown($server)
	{
		$socket = storage('socket.sock');
		if (!file_exists($socket)) {
			$this->close($server);
		} else {
			$pathId = file_get_contents($socket);
			@unlink($socket);
			if (empty($pathId)) {
				$this->close($server);
			} else {
				exec("ps -ef $pathId | grep $pathId", $output);
				if (!empty($output)) {
					exec("kill -TERM $pathId");
				}
				$this->close($server);
			}
		}
		Snowflake::clearWorkerId();
	}


	/**
	 * @param \HttpServer\Server $server
	 * @return void
	 * @throws Exception
	 */
	private function close(\HttpServer\Server $server)
	{
		echo 'waite.';
		while ($server->isRunner()) {
			if (!$this->masterIdCheck()) {
				break;
			}
			usleep(100);
		}
		echo PHP_EOL;
	}


	/**
	 * WorkerId Iterator
	 */
	private function masterIdCheck(): bool
	{
		echo '.';
		$files = new \DirectoryIterator($this->getWorkerPath());
		if ($files->getSize() < 1) {
			return false;
		}
		foreach ($files as $file) {
			$content = file_get_contents($file->getRealPath());
			exec("ps -ax | awk '{ print $1 }' | grep -e '^{$content}$'", $output);
			if (count($output) > 0) {
				$this->closeByPid($content);
			} else {
				@unlink($file->getRealPath());
			}
		}
		return true;
	}


	/**
	 * @return string
	 */
	private function getWorkerPath(): string
	{
		return "glob://" . ltrim(APP_PATH, '/') . '/storage/worker/*.sock';
	}


	/**
	 * @param $port
	 * @return bool|array
	 */
	private function isUse($port): bool|array
	{
		if (empty($port)) {
			return false;
		}
		if (Snowflake::isLinux()) {
			exec('netstat -tunlp | grep ' . $port, $output);
		} else {
			exec('lsof -i :' . $port . ' | grep -i "LISTEN"', $output);
		}
		if (empty($output)) {
			return false;
		}
		$this->error(implode(PHP_EOL, $output));
		return $output;
	}

	/**
	 * @param $pid
	 */
	private function closeByPid($pid)
	{
		exec("ps -ef $pid | grep $pid", $output);
		if (!empty($output)) {
			exec("kill -TERM $pid");
		}
	}


}
