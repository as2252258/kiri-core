<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */

namespace Snowflake\Process;


use Exception;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
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
		Snowflake::setProcessId($process->pid);
		if (extension_loaded('inotify')) {
			$this->inotify = inotify_init();
			$this->events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;
			$process->name('event: file change.');

			$this->watch(APP_PATH);
			Event::add($this->inotify, [$this, 'check']);
			Event::wait();
		} else {
			$this->loadByDir(APP_PATH . 'app');
			$this->loadByDir(APP_PATH . 'routes');
			Timer::tick(2000, [$this, 'tick']);
		}
	}


	private $md5Map = [];


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		$this->loadByDir(APP_PATH . 'app', true);
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
					return Timer::after(2000, [$this, 'reload']);
				}
				$this->md5Map[$md5] = $mTime;
			} else {
				if ($this->md5Map[$md5] != $mTime) {
					if ($isReload) {
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
	}

	/**
	 * 重启
	 * @throws ComponentException
	 */
	public function trigger_reload()
	{
		/** @var Server $server */
		$server = Snowflake::app()->get('server')->getServer();
		$server->reload();
	}


	/**
	 * 清理所有inotify监听
	 */
	public function clearWatch()
	{
		foreach ($this->watchFiles as $wd) {
			try {
				@inotify_rm_watch($this->inotify, $wd);
			} catch (\Error|Exception $exception) {
				$this->debug($exception->getMessage());
			} finally {
				$this->watchFiles = [];
			}
		}
	}


	/**
	 * @param $dir
	 * @param bool $root
	 * @return bool
	 * @throws Exception
	 */
	public function watch($dir, $root = TRUE)
	{
		//目录不存在
		if (!is_dir($dir)) {
			throw new Exception("[$dir] is not a directory.");
		}
		//避免重复监听
		if (isset($this->watchFiles[$dir])) {
			return FALSE;
		}
		//根目录
		if ($root) {
			$this->dirs[] = $dir;
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
				$this->watch($path, FALSE);
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
