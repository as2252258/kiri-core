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
use Swoole\Coroutine;
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
		$pid_file = $this->getPidFile();
		if (!file_exists($pid_file)) {
			return;
		}
		$content = file_get_contents($pid_file);
		$output = Coroutine\System::exec("ps -ef $content | grep $content");
		if (!empty($output)) {
			Coroutine\System::exec("kill -15 $content");
		}
		$this->close($server);
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
			$output = Coroutine\System::exec("ps -ax | awk '{ print $1 }' | grep -e '^{$content}$'");
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
			$output = Coroutine\System::exec('netstat -tunlp | grep ' . $port);
		} else {
			$output = Coroutine\System::exec('lsof -i :' . $port . ' | grep -i "LISTEN"');
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
		Coroutine\System::exec("ps -ef | grep $pid | grep -v grep | grep -v kill
if [ $? -eq 0 ];then
	kill -9 `ps -ef | grep $pid  | grep -v grep | grep -v kill | awk '{print $2}'`
else
	echo $pid' No Found Process'
fi");
	}


}
