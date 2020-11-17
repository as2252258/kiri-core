<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 19:09
 */
declare(strict_types=1);

namespace Snowflake\Process;


use Exception;
use Snowflake\Abstracts\Config;
use Snowflake\Snowflake;
use Swoole\Event;
use Swoole\Timer;

/**
 * Class ServerInotify
 * @package Snowflake\Snowflake\Server
 */
class ServerInotify extends Process
{
	private $inotify;
	private bool $isReloading = false;
	private bool $isReloadingOut = false;
	private array $watchFiles = [];
	private ?array $dirs = [];
	private int $events;

	private int $int = -1;

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
			foreach ($this->dirs as $dir) {
				$this->watch($dir);
			}
			Event::add($this->inotify, [$this, 'check']);
			Event::wait();
		} else {
			$this->loadByDir(APP_PATH . 'app');
			$this->loadByDir(APP_PATH . 'routes');
			$this->loadByDir(__DIR__ . '/../../');
			Timer::after(2000, [$this, 'tick']);
		}
	}


	private array $md5Map = [];


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		$this->loadByDir(APP_PATH . 'app', true);
		$this->loadByDir(APP_PATH . 'routes', true);
		$this->loadByDir(__DIR__ . '/../../', true);
		if ($this->isReloading) {
			return;
		}
		Timer::after(2000, [$this, 'tick']);
	}


	/**
	 * @param $path
	 * @param bool $isReload
	 * @return void|mixed
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
			}
			if (is_file($value)) {
				if (!$this->checkFile($value, $isReload)) {
					continue;
				}
				$this->timerReload();
				break;
			}
		}
		Timer::after(2000, [$this, 'tick']);
	}


	/**
	 * @param $value
	 * @param $isReload
	 * @return bool
	 */
	private function checkFile($value, $isReload)
	{
		$md5 = md5($value);
		$mTime = filectime($value);
		if (!isset($this->md5Map[$md5])) {
			if ($isReload) {
				return true;
			}
			$this->md5Map[$md5] = $mTime;
		} else {
			if ($this->md5Map[$md5] != $mTime) {
				if ($isReload) {
					return true;
				}
				$this->md5Map[$md5] = $mTime;
			}
		}
		return false;
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
		$this->trigger_reload();
		$this->clearWatch();
		foreach ($this->dirs as $root) {
			$this->watch($root);
		}
		$this->int = -1;
		$this->isReloading = FALSE;
		$this->isReloadingOut = FALSE;
		$this->md5Map = [];
	}

	/**
	 * @throws Exception
	 */
	public function timerReload()
	{
		if ($this->isReloading) {
			return;
		}
		$this->isReloading = true;
		$this->trigger_reload();
		$this->int = -1;
		$this->md5Map = [];

		$this->loadByDir(APP_PATH . 'app');
		$this->loadByDir(APP_PATH . 'routes');
		$this->loadByDir(__DIR__ . '/../../');

		$this->isReloading = FALSE;
		$this->isReloadingOut = FALSE;

		Timer::after(2000, [$this, 'tick']);
	}

	/**
	 * 重启
	 * @throws Exception
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
