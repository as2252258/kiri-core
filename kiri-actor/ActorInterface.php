<?php
declare(strict_types=1);

namespace Kiri\Actor;

interface ActorInterface
{

	/**
	 * @param ActorMessage $message
	 * @return void
	 */
	public function process(ActorMessage $message): void;

}
