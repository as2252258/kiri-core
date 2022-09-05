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
		$this->tick();
	}


	/**
	 * @param bool $isReload
	 * @throws Exception
	 */
	private function loadDirs(bool $isReload = FALSE)
	{
		try {
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
			/** @var DirectoryIterator $path */

			if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
				continue;
			}

			if ($path->getExtension() !== 'php') {
				continue;
			}

			if ($path->isDir()) {
				$this->loadByDir(new DirectoryIterator($path->getRealPath()), $isReload);
			}

			if ($this->checkFile($path, $isReload)) {
				$this->isReloading = TRUE;
				break;
			}
		}
		if ($this->isReloading) {
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
		$this->isReloading = TRUE;

		$this->logger->warning('file change');

		$swow = \Kiri::getDi()->get(ServerInterface::class);

		$swow->reload();

		$this->isReloading = FALSE;

		$this->loadDirs();
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
