<?php

namespace Kiri\FileListen;

use Exception;

class Scaner
{

	private array $md5Map = [];

	/**
	 * @param array $dirs
	 * @param FileChangeCustomProcess $process
	 */
	public function __construct(protected array $dirs, public FileChangeCustomProcess $process)
	{
	}


	/**
	 * @throws Exception
	 */
	public function start(): void
	{
		$this->loadDirs();
		$this->tick();
	}


	/**
	 * @param bool $isReload
	 * @throws Exception
	 */
	private function loadDirs(bool $isReload = false)
	{
		foreach ($this->dirs as $value) {
			if (is_bool($path = realpath($value))) {
				continue;
			}

			if (!is_dir($path)) continue;

			$this->loadByDir($path, $isReload);
		}
	}


	/**
	 * @param $path
	 * @param bool $isReload
	 * @return void
	 * @throws Exception
	 */
	private function loadByDir($path, bool $isReload = false): void
	{
		if (!is_string($path)) {
			return;
		}
		$path = rtrim($path, '/');
		foreach (glob(realpath($path) . '/*') as $value) {
			if (is_dir($value)) {
				$this->loadByDir($value, $isReload);
			}
			if (is_file($value)) {
				if ($this->checkFile($value, $isReload)) {
					$this->timerReload();
					break;
				}
			}
		}
	}


	/**
	 * @param $value
	 * @param $isReload
	 * @return bool
	 */
	private function checkFile($value, $isReload): bool
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
	 * @throws Exception
	 */
	public function timerReload()
	{
		$this->process->isReloading = true;
		$this->process->trigger_reload();

		$this->process->int = -1;

		$this->loadDirs();

		$this->process->isReloading = FALSE;
		$this->process->isReloadingOut = FALSE;

		$this->tick();
	}


	private bool $isStop = false;

	public function clear()
	{
		$this->isStop = true;
	}


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		if ($this->process->isReloading || $this->isStop) {
			return;
		}

		$this->loadDirs(true);

		sleep(2);

		$this->tick();
	}

}
