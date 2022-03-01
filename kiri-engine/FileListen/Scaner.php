<?php

namespace Kiri\FileListen;

use Exception;
use Kiri\Error\StdoutLoggerInterface;

class Scaner
{

	private array $md5Map = [];

	public bool $isReloading = FALSE;


	/**
	 * @param array $dirs
	 * @param HotReload $process
	 */
	public function __construct(protected array $dirs, public HotReload $process,  public StdoutLoggerInterface $logger)
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
	private function loadDirs(bool $isReload = FALSE)
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
	private function loadByDir($path, bool $isReload = FALSE): void
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
					if ($this->isReloading) {
						break;
					}
					$this->isReloading = TRUE;

					sleep(2);

					$this->timerReload($value);
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
				return TRUE;
			}
			$this->md5Map[$md5] = $mTime;
		} else {
			if ($this->md5Map[$md5] != $mTime) {
				if ($isReload) {
					return TRUE;
				}
				$this->md5Map[$md5] = $mTime;
			}
		}
		return FALSE;
	}


	/**
	 * @throws Exception
	 */
	public function timerReload($path)
	{
		$this->isReloading = TRUE;

		$this->logger->warning('file change');

		$this->process->trigger_reload($path);

		$this->loadDirs();

		$this->process->int = -1;

		$this->isReloading = FALSE;
		$this->process->isReloadingOut = FALSE;

		$this->tick();
	}


	private bool $isStop = FALSE;

	public function clear()
	{
		$this->isStop = TRUE;
	}


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		if ($this->isReloading || $this->isStop) {
			return;
		}

		$this->loadDirs(TRUE);

		sleep(2);

		$this->tick();
	}

}
