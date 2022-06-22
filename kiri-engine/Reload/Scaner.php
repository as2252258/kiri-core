<?php

namespace Kiri\Reload;

use DirectoryIterator;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Inject;
use Kiri\Server\Abstracts\BaseProcess;
use Kiri\Server\ServerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Process;

class Scaner extends BaseProcess
{

	private array $md5Map = [];

	public bool $isReloading = FALSE;


	private array $dirs = [];


	/**
	 * @var LoggerInterface
	 */
	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;


	/**
	 * @throws Exception
	 */
	public function process(Process $process): void
	{
		$this->dirs = Config::get('reload.inotify', []);

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
			$value = new DirectoryIterator($value);
			if ($value->isDot() || str_starts_with($value->getFilename(), '.')) {
				continue;
			}
			if ($value->isDir()) {
				$this->loadByDir($value, $isReload);
			}
		}
	}


	/**
	 * @param DirectoryIterator $path
	 * @param bool $isReload
	 * @return void
	 * @throws Exception
	 */
	private function loadByDir(DirectoryIterator $path, bool $isReload = FALSE): void
	{
		if ($path->isDir()) {
			$this->loadByDir(new DirectoryIterator($path->getRealPath()), $isReload);
		}
		if (!str_ends_with($path->getFilename(), '.php')) {
			return;
		}
		if ($this->checkFile($path, $isReload)) {
			if ($this->isReloading) {
				return;
			}
			$this->isReloading = TRUE;
			sleep(2);
			$this->timerReload();
		}
	}


	/**
	 * @param DirectoryIterator $value
	 * @param $isReload
	 * @return bool
	 */
	private function checkFile(DirectoryIterator $value, $isReload): bool
	{
		$md5 = md5_file($value->getRealPath());
		$mTime = $value->getCTime();
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
	public function timerReload()
	{
		$this->isReloading = TRUE;

		$this->logger->warning('file change');

		$swow = \Kiri::getDi()->get(ServerInterface::class);

		$swow->reload();

		$this->loadDirs();

		$this->isReloading = FALSE;

		$this->tick();
	}


	/**
	 * @return $this
	 */
	public function onSigterm(): static
	{
		pcntl_signal(SIGTERM, function () {
			$this->onProcessStop();
		});
		return $this;
	}


	/**
	 * @throws Exception
	 */
	public function tick()
	{
		if ($this->isStop) {
			return;
		}

		$this->loadDirs(TRUE);

		sleep(2);

		$this->tick();
	}

}
