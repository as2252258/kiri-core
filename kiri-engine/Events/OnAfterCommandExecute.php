<?php

namespace Kiri\Events;

use Symfony\Component\Console\Command\Command;

class OnAfterCommandExecute
{


	/**
	 *
	 */
	public function __construct(public Command $command)
	{
	}

}
