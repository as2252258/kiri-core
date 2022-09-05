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
use Swoole\Timer;

class Scanner extends BaseProcess
{

	private array $md5Map = [];

	public bool $isReloading = FALSE;

	public string $name = 'hot reload';

	protected bool $enable_coroutine = false;

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
		Timer::tick(3000, fn() => $this->loadDirs());
	}


	/**
	 * @param bool $isReload
	 * @throws Exception
	 */
	private function loadDirs(bool $isReload = FALSE)
	{
		try {
			if ($this->isReloading) {
				return;
			}
			foreach ($this->dirs as $value) {
				if ($this->isReloading) {
					break;
				}
				$value = new DirectoryIterator($value);
				if ($value->isDir()) {
					$this->loadByDir($value, $isReload);
				}
			}
		} catch (\Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [$throwable]);
		} finally {
			$this->loadDirs($isReload);
		}
	}


	/**
	 * @param DirectoryIterator $iterator
	 * @param bool $isReload
	 * @return void
	 * @throws Exception
	 */
	private function loadByDir(DirectoryIterator $iterator, bool $isReload = FALSE): void
	{
		foreach ($iterator as $path) {
			if ($this->isReloading) {
				return;
			}
			if (!$this->isNeedCheck($path)) {
				continue;
			}
			if ($path->isDir()) {
				$this->loadByDir(new DirectoryIterator($path->getRealPath()), $isReload);
			}
			if ($this->checkFile($path, $isReload)) {
				$this->isReloading = TRUE;

				Timer::after(3000, fn() => $this->timerReload());
				break;
			}
		}
	}


	private function isNeedCheck(DirectoryIterator $path): bool
	{
		if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
			return false;
		}

		if ($path->getExtension() !== 'php') {
			return false;
		}
		return true;
	}


	/**
	 * @param DirectoryIterator $value
	 * @param $isReload
	 * @return bool
	 */
	private function checkFile(DirectoryIterator $value, $isReload): bool
	{
		$md5 = md5($value->getRealPath());
		$mTime = filectime($value->getRealPath());
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
	public function timerReload()
	{
		if ($this->isReloading) {
			return;
		}
		$this->logger->warning('file change');
		$swow = \Kiri::getDi()->get(ServerInterface::class);
		$swow->reload();

		$this->loadDirs();
		$this->isReloading = FALSE;
	}

}
