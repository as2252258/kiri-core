<?php


namespace Kiri\Error;


use Exception;
use Kiri\Core\Json;
use Kiri\Exception\ComponentException;
use Kiri\Kiri;
use Kiri\Server\Abstracts\BaseProcess;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Process;

/**
 * Class LoggerProcess
 * @package Kiri\Error
 */
class LoggerProcess extends BaseProcess
{


	public string $name = 'logger process';


	/**
	 * @param Process $process
	 * @throws ComponentException
	 */
	public function process(Process $process): void
	{
		// TODO: Implement onHandler() method.
		$this->message($process);
	}



	/**
	 * @param $message
	 * @return void
	 */
	public function onBroadcast($message)
	{
		$logger = Kiri::getDi()->get(LoggerInterface::class);
		$logger->debug($message->data . '::' . static::class);
	}


	/**
	 * @param Process $process
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function message(Process $process)
	{
		if ($this->isStop()) {
			return;
		}
		$message = Json::decode($process->read());
		if (!empty($message)) {
			Kiri::writeFile($this->getDirName($message), $message[0], FILE_APPEND);

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
		$files = new \DirectoryIterator(storage(null, $dirName));
		if ($files->getSize() < 15) {
			return;
		}
		Coroutine\System::exec('find ' . storage(null, $dirName) . '/ -mtime +15 -name "*.log" -exec rm -rf {} \;');
	}

}
