<?php
declare(strict_types=1);


namespace Kiri;

class Coordinator
{

	const string WORKER_START = 'worker:start';

	private bool $waite = false;


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
