<?php


namespace Snowflake\Error;


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
	 * @throws \Exception
	 */
	public function message(\Swoole\Process $process)
	{
		$message = Json::decode($process->read());
		if (!empty($message)) {
			$fileName = 'server-' . date('Y-m-d') . '.log';
			$dirName = 'log/' . (empty($method) ? 'app' : $method);

			Snowflake::writeFile(storage($fileName, $dirName), $message[0], FILE_APPEND);

			$files = new \DirectoryIterator(storage(null, $dirName) . '/*.log');
			if ($files->getSize() >= 15) {
				$command = 'find ' . storage(null, $dirName) . '/ -mtime +15 -name "*.log" -exec rm -rf {} \;';
				if (Context::inCoroutine())
					Coroutine\System::exec($command);
				else
					\shell_exec($command);
			}
			var_dump($message);
		}

		Coroutine\System::sleep(1);

		$this->message($process);
	}

}
