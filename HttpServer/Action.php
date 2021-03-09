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
		exec("ps -ef $content | grep $content", $output);
		if (!empty($output)) {
			exec("kill -15 $content");
		}
		unset($content);
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
			sleep(1);
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
			clearstatcache(true, $file->getFilename());

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
	 * @throws Exception
	 */
	private function isUse($port): bool|array
	{
		if (empty($port)) {
			return false;
		}
		if (Snowflake::getPlatform()->isLinux()) {
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
