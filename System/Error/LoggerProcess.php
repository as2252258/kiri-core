<?php


namespace Snowflake\Error;


use Exception;
use HttpServer\Http\Context;
use Snowflake\Core\Json;
use Snowflake\Exception\ComponentException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;


/**
 * Class LoggerProcess
 * @package Snowflake\Error
 */
class LoggerProcess extends Process
{


	/**
	 * @param \Swoole\Process $process
	 * @throws ComponentException
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		// TODO: Implement onHandler() method.
		$this->message($process);
	}


	/**
	 * @param \Swoole\Process $process
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function message(\Swoole\Process $process)
	{
		$message = Json::decode($process->read());
		if (!empty($message)) {
			Snowflake::writeFile($this->getDirName($message), $message[0], FILE_APPEND);

			$this->checkLogFile($message[1]);
		}

		Coroutine\System::sleep(1);

		$this->message($process);
	}


	/**
	 * @param $message
	 * @return string
	 * @throws Exception
	 */
	private function getDirName($message): string
	{
		return storage('server-' . date('Y-m-d') . '.log', $message[1]);
	}


	/**
	 * @param $dirName
	 * @throws Exception
	 */
	private function checkLogFile($dirName)
	{
		$files = new \DirectoryIterator(storage(null, $dirName) . '/*.log');
		if ($files->getSize() < 15) {
			return;
		}
		Coroutine\System::exec('find ' . storage(null, $dirName) . '/ -mtime +15 -name "*.log" -exec rm -rf {} \;');
	}

}
