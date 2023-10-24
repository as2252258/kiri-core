<?php

namespace Kiri\Events;

use Symfony\Component\Console\Command\Command;

class OnAfterCommandExecute
{


    /**
     * @param Command $command
     */
	public function __construct(public Command $command)
	{
	}

}
