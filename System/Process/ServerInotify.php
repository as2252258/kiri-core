<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */

namespace Snowflake\Process;


use Exception;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Error;
use Swoole\Event;
use Swoole\Server;
use Swoole\Timer;
use swoole_process;

/**
 * Class ServerInotify
 * @package Snowflake\Snowflake\Server
 */
class ServerInotify extends Process
{
	private $inotify;
	private $isReloading = false;
	private $isReloadingOut = false;
	private $watchFiles = [];
	private $dirs = [];
	private $events;

	private $int = -1;

	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process)
	{
		set_error_handler([$this, 'onErrorHandler']);
		$this->dirs = Config::get('inotify', false, [APP_PATH]);
		if (extension_loaded('inotify')) {
			$this->inotify = inotify_init();
			$this->events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;
			$process->name('event: file change.');
			foreach ($this->dirs as $dir) {
				$this->watch($dir);
			}
			Event::add($this->inotify, [$this, 'check']);
			Event::wait();
		} else {
			foreach ($this->dirs as $dir) {
				$this->loadByDir($dir);
			}
			Timer::tick(2000, [$this, 'tick']);
		}
	}


	private $md5Map = [];


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		foreach ($this->dirs as $dir) {
			$this->loadByDir($dir, true);
		}
	}


	/**
	 * @param $path
	 * @param bool $isReload
	 * @return void
	 * @throws Exception
	 */
	private function loadByDir($path, $isReload = false)
	{
		$path = rtrim($path, '/');
		if ($this->isReloading) {
			return;
		}
		foreach (glob($path . '/*') as $value) {
			if (is_dir($value)) {
				$this->loadByDir($value, $isReload);
				continue;
			}
			$md5 = md5($value);
			$mTime = filectime($value);
			if (!isset($this->md5Map[$md5])) {
				if ($isReload) {
					$this->isReloading = true;
					return Timer::after(2000, [$this, 'reload']);
				}
				$this->md5Map[$md5] = $mTime;
			} else {
				if ($this->md5Map[$md5] != $mTime) {
					if ($isReload) {
						$this->isReloading = true;
						return Timer::after(2000, [$this, 'reload']);
					}
					$this->md5Map[$md5] = $mTime;
				}
			}
		}
	}


	/**
	 * 开始监听
	 */
	public function check()
	{
		if (!($events = inotify_read($this->inotify))) {
			return;
		}
		if ($this->isReloading) {
			if (!$this->isReloadingOut) {
				$this->isReloadingOut = true;
			}
			return;
		}

		$eventList = [IN_CREATE, IN_DELETE, IN_MODIFY, IN_MOVED_TO, IN_MOVED_FROM];
		foreach ($events as $ev) {
			if (empty($ev['name'])) {
				continue;
			}
			if ($ev['mask'] == IN_IGNORED) {
				continue;
			}
			if (!in_array($ev['mask'], $eventList)) {
				continue;
			}
			$fileType = strstr($ev['name'], '.');
			//非重启类型
			if ($fileType !== '.php') {
				continue;
			}
			if ($this->int !== -1) {
				return;
			}
			$this->int = @swoole_timer_after(2000, [$this, 'reload']);

			$this->isReloading = true;
		}
	}

	/**
	 * @throws Exception
	 */
	public function reload()
	{
		$this->isReloading = true;

		//清理所有监听
		$this->trigger_reload();
		$this->clearWatch();

		//重新监听
		foreach ($this->dirs as $root) {
			$this->watch($root);
		}

		$this->int = -1;
		$this->isReloading = FALSE;
		$this->isReloadingOut = FALSE;

		$this->loadByDir(APP_PATH . 'app');
		$this->loadByDir(APP_PATH . 'routes');
	}

	/**
	 * 重启
	 */
	public function trigger_reload()
	{
		Snowflake::reload();
	}


	/**
	 * 清理所有inotify监听
	 */
	public function clearWatch()
	{
		foreach ($this->watchFiles as $wd) {
			try {
				inotify_rm_watch($this->inotify, $wd);
			} catch (\Throwable $exception) {
				$this->application->debug($exception->getMessage());
			} finally {
				$this->watchFiles = [];
			}
		}
	}

	/**
	 */
	protected function onErrorHandler()
	{
		[$code, $message, $file, $line, $args] = func_get_args();
		$this->application->debug('Error:' . $message);
		$this->application->debug($file . ':' . $line);
	}


	/**
	 * @param $dir
	 * @return bool
	 * @throws Exception
	 */
	public function watch($dir)
	{
		//目录不存在
		if (!is_dir($dir)) {
			throw new Exception("[$dir] is not a directory.");
		}
		//避免重复监听
		if (isset($this->watchFiles[$dir])) {
			return FALSE;
		}

		if (in_array($dir, [APP_PATH . '/config', APP_PATH . '/commands', APP_PATH . '/.git', APP_PATH . '/.gitee'])) {
			return FALSE;
		}

		$wd = @inotify_add_watch($this->inotify, $dir, $this->events);
		$this->watchFiles[$dir] = $wd;

		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f == '.' or $f == '..' or $f == 'runtime' or preg_match('/\.txt/', $f) or preg_match('/\.sql/', $f) or preg_match('/\.log/', $f)) {
				continue;
			}
			$path = $dir . '/' . $f;
			//递归目录
			if (is_dir($path)) {
				$this->watch($path);
			}

			//检测文件类型
			if (strstr($f, '.') == '.php') {
				$wd = @inotify_add_watch($this->inotify, $path, $this->events);
				$this->watchFiles[$path] = $wd;
			}
		}
		return TRUE;
	}
}
