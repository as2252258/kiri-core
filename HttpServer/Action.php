<?php
declare(strict_types=1);

namespace HttpServer;


use Exception;

use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
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
	public function restart(\HttpServer\Server $socket): mixed
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
		$content = file_get_contents($this->getPidFile());
		if (!file_exists($content)) {
			return;
		}
		if (empty($content)) {
			$this->close($server);
		} else {
			exec("ps -ef $content | grep $content", $output);
			if (!empty($output)) {
				exec("kill -15 $content");
			}
			$this->close($server);
		}
	}


	/**
	 * @return mixed
	 * @throws ConfigException
	 */
	private function getPidFile(): string
	{
		$settings = Config::get('settings', false, []);
		if (!isset($settings['pid_file'])) {
			return PID_PATH;
		}
		return $settings['pid_file'];
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
	#[Pure] private function getWorkerPath(): string
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
		exec("ps -ef | grep $pid | grep -v grep | grep -v kill
if [ $? -eq 0 ];then
	kill -9 `ps -ef | grep $pid  | grep -v grep | grep -v kill | awk '{print $2}'`
else
	echo $pid' No Found Process'
fi");
	}


}
