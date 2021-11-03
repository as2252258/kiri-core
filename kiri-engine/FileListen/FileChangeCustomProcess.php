<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Abstracts\Config;
use Kiri\Kiri;
use ReflectionException;
use Server\Abstracts\BaseProcess;
use Swoole\Process;


/**
 *
 */
class FileChangeCustomProcess extends BaseProcess
{


	public bool $isReloading = false;
	public bool $isReloadingOut = false;
	public ?array $dirs = [];
	public int $events;

	public int $int = -1;


	/**
	 * @param Process $process
	 * @return string
	 */
	public function getProcessName(Process $process): string
	{
		// TODO: Implement getProcessName() method.
		return 'file change listener.';
	}


	/**
	 * @param Process $process
	 * @throws Exception
	 */
	public function onHandler(Process $process): void
	{
		// TODO: Implement onHandler() method.
		set_error_handler([$this, 'onErrorHandler']);
		$this->dirs = Config::get('inotify', [APP_PATH . 'app']);
		if (!extension_loaded('inotify')) {
			$driver = Kiri::getDi()->get(Scaner::class, [$this->dirs, $this]);
		} else {
			$driver = Kiri::getDi()->get(Inotify::class, [$this->dirs, $this]);
		}
		$driver->start();
	}


	/**
	 * @param Process $process
	 */
	public function signListen(Process $process): void
	{

	}


	/**
	 * @param $code
	 * @param $message
	 * @param $file
	 * @param $line
	 * @throws Exception
	 */
	public function onErrorHandler($code, $message, $file, $line)
	{
		if (str_contains($message, 'The file descriptor is not an inotify instance')) {
			return;
		}
		debug('Error:' . $message . ' at ' . $file . ':' . $line);
	}


	/**
	 * 重启
	 * @throws Exception
	 */
	public function trigger_reload()
	{
//		exec(PHP_BINARY . ' ' . APP_PATH . 'kiri.php runtime:builder', $output);
//
//		print_r(implode(PHP_EOL, $output));

		Kiri::reload();
	}
}
