<?php

declare(strict_types=1);

namespace Console;

use Snowflake\Abstracts\Input;

/**
 * Interface CommandInterface
 * @package Console
 */
interface CommandInterface
{

	public function onHandler(Input $dtl);

}
