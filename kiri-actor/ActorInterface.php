<?php

namespace Kiri\Actor;

interface ActorInterface
{

	/**
	 * @param mixed $message
	 * @return void
	 */
	public function process(mixed $message): void;

}
