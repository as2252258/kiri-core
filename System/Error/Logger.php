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
use Snowflake\Abstracts\Config;
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
		$this->print_r($message, $category);
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
			if (!is_array($this->logs)) {
				$this->logs = [];
			}
			$this->logs[] = [$category, $message];
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
		$debug = Config::get('debug', false, ['enable' => false]);
		if ((bool)$debug['enable'] === true) {
			if (!is_callable($debug['callback'] ?? null, true)) {
				return;
			}
			call_user_func($debug['callback'], $message, $category);
		}
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
	 * @throws
	 */
	public function write(string $messages, $category = 'app')
	{
		if (empty($messages)) {
			return;
		}
		$fileName = 'server-' . date('Y-m-d') . '.log';
		$dirName = 'log/' . (empty($category) ? 'app' : $category);
		$logFile = '[' . date('Y-m-d H:i:s') . ']:' . PHP_EOL . $messages . PHP_EOL;
		Snowflake::writeFile(storage($fileName, $dirName), $logFile, FILE_APPEND);

		$files = glob(storage(null, $dirName) . '/*');
		if (count($files) >= 5) {
			$time = strtotime(date('Y-m-d', strtotime('-10days')));
			foreach ($files as $file) {
				if (filectime($file) < $time) {
					continue;
				}
				@unlink($file);
			}
		}
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
	 * @return false|int|mixed|string
	 * @throws Exception
	 */
	public function exception(Exception $exception)
	{
		$errorInfo = [
			'message' => $exception->getMessage(),
			'file'    => $exception->getFile(),
			'line'    => $exception->getLine()
		];
		$this->error(var_export($errorInfo, true));

		$code = $exception->getCode() ?? 500;

		$logger = Snowflake::app()->logger;

		$string = 'Exception: ' . PHP_EOL;
		$string .= '#.  message: ' . $errorInfo['message'] . PHP_EOL;
		$string .= '#.  file: ' . $errorInfo['file'] . PHP_EOL;
		$string .= '#.  line: ' . $errorInfo['line'] . PHP_EOL;

		$logger->write($string . $exception->getTraceAsString(), 'trace');
		$logger->write(jTraceEx($exception), 'exception');

		return JSON::to($code, $errorInfo['message']);
	}


	/**
	 * @param Exception $exception
	 * @return array
	 */
	private function getException(Exception $exception)
	{
		$_tmp = [$exception->getMessage()];
		$_tmp[] = $exception->getFile() . ' on line ' . $exception->getLine();
		$_tmp[] = $exception->getTrace();
		return $_tmp;
	}

}
