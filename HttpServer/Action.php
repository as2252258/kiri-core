<?php


namespace HttpServer;


use Exception;
use Snowflake\Snowflake;
use Swoole\WebSocket\Server;

/**
 * Class Action
 * @package HttpServer
 */
trait Action
{

	/**
	 * @param $argv
	 * @return Server
	 * @throws
	 */
	public function getSwooleServer($argv)
	{
		/** @var \HttpServer\Server $socket */
		$socket = Snowflake::app()->get('server');
//		if (isset($argv[2])) {
//			$this->modify($argv, $socket);
//		}

		if (!isset($argv[1])) $argv[1] = 'start';

		return $this->checkAction($argv, $socket);
	}

	/**
	 * @param $argv
	 * @param $socket
	 * @return Server
	 * @throws Exception
	 */
	private function checkAction($argv, $socket)
	{
		if (!in_array($argv[1], ['stop', 'start', 'restart'])) {
			exit($this->error('action not exists.'));
		}
		return $this->{$argv[1]}($socket);
	}

	/**
	 * @param \HttpServer\Server $socket
	 * @return mixed
	 * @throws Exception
	 */
	public function restart($socket)
	{
		$this->_shutdown($socket);

		return $this->start();
	}


	/**
	 * @param \HttpServer\Server $socket
	 * @throws Exception
	 */
	public function stop($socket)
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
				exec("kill -TERM $pathId");
				$this->close($server);
			}
		}
	}

	/**
	 * @param \HttpServer\Server $server
	 * @return void
	 * @throws Exception
	 */
	private function close($server)
	{
		echo 'waite.';
		while ($server->isRunner()) {
			echo '.';
			$pods = glob(storage('workerIds') . '/*');
			if (count($pods) < 1) {
				break;
			}
			foreach ($pods as $pid) {
				if (!file_exists($pid)) {
					continue;
				}
				$content = file_get_contents($pid);
				exec("ps -ax | awk '{ print $1 }' | grep -e '^{$content}$'", $output);
				if (count($output) > 0) {
					$this->closeByPid($content);
				} else {
					file_exists($pid) && @unlink($pid);
				}
			}
			usleep(100);
		}
		echo PHP_EOL;
	}


	/**
	 * @param $port
	 * @return bool|array
	 */
	private function isUse($port)
	{
		if (empty($port)) {
			return false;
		}
		exec('netstat -tunlp tcp | grep ' . $port, $output);
		if (empty($output)) {
			return false;
		}
		return $output;
	}

	/**
	 * @param $pid
	 */
	private function closeByPid($pid)
	{
		shell_exec('kill -TERM ' . $pid);
	}


}
