<?php

namespace Kiri;

class Coordinator
{

	const WORKER_START = 'worker:start';

	private bool $waite = true;


	private static array $_waite = [];


	/**
	 * @return bool
	 */
	public function isWaite(): bool
	{
		return $this->waite;
	}


	/**
	 * @return void
	 */
	public function yield(): void
	{
		if ($this->waite === false) {
			return;
		}
		$this->yield();
	}


	/**
	 * @return void
	 */
	public function waite(): void
	{
		$this->waite = true;
	}


	/**
	 * @return void
	 */
	public function done(): void
	{
		$this->waite = false;
	}


}
