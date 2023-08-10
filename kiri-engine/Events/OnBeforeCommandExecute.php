<?php

namespace Kiri\Events;

use Symfony\Component\Console\Command\Command;

class OnBeforeCommandExecute
{


    /**
     * @param Command $command
     */
    public function __construct(public Command $command)
    {
    }

}
