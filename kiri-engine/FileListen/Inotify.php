<?php

namespace Kiri\FileListen;

use Exception;
use Swoole\Event;
use Swoole\Timer;

class Inotify
{

	private mixed $inotify;
	private mixed $events;

	private array $watchFiles = [];


	public bool $isReloading = FALSE;


	protected int $cid;

	const IG_DIR = [APP_PATH . 'commands', APP_PATH . '.git', APP_PATH . '.gitee'];


	/**
	 * @param array $dirs
	 * @param HotReload $process
	 */
	public function __construct(protected array $dirs, public HotReload $process)
	{
		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'error']);
	}


	/**
	 * @return void
	 */
	public function error(): void
	{

	}


	/**
	 * @throws Exception
	 */
	public function start()
	{
		$this->inotify = inotify_init();
		$this->events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;
		foreach ($this->dirs as $dir) {
			if (!is_dir($dir)) continue;
			$this->watch($dir);
		}
		Event::add($this->inotify, [$this, 'check']);
		Event::wait();
	}


	public function clear()
	{
		Event::del($this->inotify);
		Event::exit();
	}


	/**
	 * 开始监听
	 * @throws Exception
	 */
	public function check()
	{
		if (!($events = inotify_read($this->inotify))) {
			return;
		}
		if ($this->isReloading) {
			return;
		}

		$LISTEN_TYPE = [IN_CREATE, IN_DELETE, IN_MODIFY, IN_MOVED_TO, IN_MOVED_FROM];
		foreach ($events as $ev) {
			if (!in_array($ev['mask'], $LISTEN_TYPE)) {
				continue;
			}
			//非重启类型
			if (str_ends_with($ev['name'], '.php')) {
				Timer::after(3000, fn() => $this->reload($ev['name']));
				$this->isReloading = TRUE;
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function reload($path)
	{
		$this->process->trigger_reload($path);
		$this->clearWatch();
		foreach ($this->dirs as $root) {
			$this->watch($root);
		}
		$this->process->int = -1;
		$this->isReloading = FALSE;
	}


	/**
	 * @throws Exception
	 */
	public function clearWatch()
	{
		foreach ($this->watchFiles as $wd) {
			@inotify_rm_watch($this->inotify, $wd);
		}
		$this->watchFiles = [];
	}


	/**
	 * @param $dir
	 * @return bool
	 * @throws Exception
	 */
	public function watch($dir): bool
	{
		//目录不存在
		if (!is_dir($dir)) {
			return logger()->addError("[$dir] is not a directory.");
		}
		//避免重复监听
		if (isset($this->watchFiles[$dir])) {
			return FALSE;
		}

		if (in_array($dir, self::IG_DIR)) {
			return FALSE;
		}

		$wd = @inotify_add_watch($this->inotify, $dir, $this->events);
		$this->watchFiles[$dir] = $wd;

		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f == '.' || $f == '..') {
				continue;
			}
			$path = $dir . '/' . $f;
			//递归目录
			if (is_dir($path)) {
				$this->watch($path);
			} else if (!str_ends_with($f, '.php')) {
				continue;
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
