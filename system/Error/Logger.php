<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */

namespace Snowflake\Error;

use Exception;
use Snowflake\Snowflake;
use Swoole\Coroutine\System;
use Swoole\Process;

/**
 * Class Logger
 * @package BeReborn\Error
 */
class Logger
{

	private static $logs = [];

	public static $worker_id;

	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	public static function debug($message, $category = 'app')
	{
		static::writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	public static function trance($message, $category = 'app')
	{
		static::writer($message, $category);
	}


	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	public static function error($message, $category = 'app')
	{
		static::writer($message, $category);
	}

	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	public static function success($message, $category = 'app')
	{
		static::writer($message, $category);
	}

	/**
	 * @param $message
	 * @param string $category
	 * @return string
	 * @throws Exception
	 */
	private static function writer($message, $category = 'app')
	{
		if ($message instanceof \Throwable) {
			$message = $message->getMessage();
		} else {
			if (is_array($message) || is_object($message)) {
				$message = static::arrayFormat($message);
			}
		}
		if (is_array($message)) {
			$message = static::arrayFormat($message);
		}
		if (!empty($message)) {
			if (!is_array(static::$logs)) {
				static::$logs = [];
			}
			static::$logs[] = [$category, $message];
		}
		return $message;
	}


	/**
	 * @param $message
	 * @param $category
	 * @throws Exception
	 */
	public static function print_r($message, $category = '')
	{
		/** @var Process $logger */
		$logger = \BeReborn::getApp('logger');
		$logger->write(JSON::encode([$message, $category]));
	}


	/**
	 * @param string $application
	 * @return mixed
	 */
	public static function getLastError($application = 'app')
	{
		$_tmp = [];
		foreach (static::$logs as $key => $val) {
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
	public static function write(string $messages, $category = 'app')
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
	private static function getSource($logFile)
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
	public static function insert()
	{
		if (empty(static::$logs)) {
			return;
		}
		foreach (static::$logs as $log) {
			[$category, $message] = $log;
			static::write($message, $category);
		}
		static::$logs = [];
	}

	/**
	 * @return array
	 */
	public static function clear()
	{
		return static::$logs = [];
	}

	/**
	 * @param $data
	 * @return string
	 */
	private static function arrayFormat($data)
	{
		if (is_string($data)) {
			return $data;
		}
		if ($data instanceof Exception) {
			$data = static::getException($data);
		} else if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$_tmp = [];
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$_tmp[] = static::arrayFormat($val);
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
	private static function getException($exception)
	{
		$_tmp = [$exception->getMessage()];
		$_tmp[] = $exception->getFile() . ' on line ' . $exception->getLine();
		$_tmp[] = $exception->getTrace();
		return $_tmp;
	}

}
