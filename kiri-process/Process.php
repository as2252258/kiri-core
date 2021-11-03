<?php

namespace Kiri\Process;

abstract class Process implements OnProcessInterface
{


	/**
	 * @var \Swoole\Process
	 */
	protected \Swoole\Process $process;


	/**
	 * @var mixed
	 */
	protected mixed $redirect_stdin_and_stdout = null;


	/**
	 * @var int
	 */
	protected int $pipe_type = SOCK_DGRAM;


	/**
	 * @var bool
	 */
	protected bool $enable_coroutine = true;


	/**
	 * @var string
	 */
	protected string $name = '';

	/**
	 * @return \Swoole\Process
	 */
	public function getProcess(): \Swoole\Process
	{
		return $this->process;
	}

	/**
	 * @return mixed
	 */
	public function getRedirectStdinAndStdout(): mixed
	{
		return $this->redirect_stdin_and_stdout;
	}

	/**
	 * @return int
	 */
	public function getPipeType(): int
	{
		return $this->pipe_type;
	}

	/**
	 * @return bool
	 */
	public function isEnableCoroutine(): bool
	{
		return $this->enable_coroutine;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @param \Swoole\Process $process
	 */
	public function start(\Swoole\Process $process)
	{
		$this->process = $process;
	}


}
