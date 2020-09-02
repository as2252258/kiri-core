<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */

namespace Snowflake\Error;

use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Core\JSON;
use Snowflake\Snowflake;
use Swoole\Process;

/**
 * Class Logger
 * @package Snowflake\Snowflake\Error
 */
class Logger extends Component
{

	private $logs = [];

	public $worker_id;

	/**
	 * @param $message
	 * @param string $category
	 * @param null $_
	 * @throws Exception
	 */
	public function debug($message, $category = 'app', $_ = null)
	{
		parent::debug($message);
		$this->writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	public function trance($message, $category = 'app')
	{
		$this->writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @param null $_
	 * @throws Exception
	 */
	public function error($message, $category = 'error', $_ = null)
	{
		parent::error($message);
		$this->writer($message, $category);
	}

	/**
	 * @param $message
	 * @param string $category
	 * @param null $_
	 * @throws Exception
	 */
	public function success($message, $category = 'app', $_ = null)
	{
		parent::success($message);
		$this->writer($message, $category);
	}

	/**
	 * @param $message
	 * @param string $category
	 * @return string
	 * @throws Exception
	 */
	private function writer($message, $category = 'app')
	{
		if ($message instanceof \Throwable) {
			$message = $message->getMessage();
		} else {
			if (is_array($message) || is_object($message)) {
				$message = $this->arrayFormat($message);
			}
		}
		if (is_array($message)) {
			$message = $this->arrayFormat($message);
		}
		if (!empty($message)) {
			if (!is_array($this->$logs)) {
				$this->$logs = [];
			}
			$this->$logs[] = [$category, $message];
		}
		return $message;
	}


	/**
	 * @param $message
	 * @param $category
	 * @throws Exception
	 */
	public function print_r($message, $category = '')
	{
		/** @var Process $logger */
		$logger = Snowflake::get()->logger;
		$logger->write(JSON::encode([$message, $category]));
	}


	/**
	 * @param string $application
	 * @return mixed
	 */
	public function getLastError($application = 'app')
	{
		$_tmp = [];
		foreach ($this->logs as $key => $val) {
			if ($val[0] != $application) {
				continue;
			}
			$_tmp[] = $val[1];
		}
		if (empty($_tmp)) {
			return 'Unknown error.';
		}
		return end($_tmp);
	}

	/**
	 * @param $messages
	 * @param string $category
	 * @throws Exception
	 */
	public function write(string $messages, $category = 'app')
	{
		if (empty($messages)) {
			return;
		}
		$fileName = 'server-' . date('Y-m-d') . '.log';
		$dirName = 'log/' . (empty($category) ? 'app' : $category);
		$logFile = '[' . date('Y-m-d H:i:s') . ']' . $messages . PHP_EOL;
		Snowflake::writeFile(storage($fileName, $dirName), $logFile, FILE_APPEND);
	}

	/**
	 * @param $logFile
	 * @return false|string
	 */
	private function getSource($logFile)
	{
		if (!file_exists($logFile)) {
			shell_exec('echo 3 > /proc/sys/vm/drop_caches');
			touch($logFile);
		}
		if (is_writeable($logFile)) {
			$logFile = realpath($logFile);
		}
		return $logFile;
	}

	/**
	 * @throws Exception
	 * 写入日志
	 */
	public function insert()
	{
		if (empty($this->logs)) {
			return;
		}
		foreach ($this->logs as $log) {
			[$category, $message] = $log;
			$this->write($message, $category);
		}
		$this->logs = [];
	}

	/**
	 * @return array
	 */
	public function clear()
	{
		return $this->logs = [];
	}

	/**
	 * @param $data
	 * @return string
	 */
	private function arrayFormat($data)
	{
		if (is_string($data)) {
			return $data;
		}
		if ($data instanceof Exception) {
			$data = $this->getException($data);
		} else if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$_tmp = [];
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$_tmp[] = $this->arrayFormat($val);
			} else {
				$_tmp[] = (is_string($key) ? $key . ' : ' : '') . $val;
			}
		}
		return implode(PHP_EOL, $_tmp);
	}

	/**
	 * @param Exception $exception
	 * @return array
	 */
	private function getException($exception)
	{
		$_tmp = [$exception->getMessage()];
		$_tmp[] = $exception->getFile() . ' on line ' . $exception->getLine();
		$_tmp[] = $exception->getTrace();
		return $_tmp;
	}

}
