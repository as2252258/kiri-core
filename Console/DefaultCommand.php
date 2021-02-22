<?php

declare(strict_types=1);

namespace Console;

use Snowflake\Abstracts\Input;

/**
 * Class DefaultCommand
 * @package Console
 */
class DefaultCommand extends Command
{
	public string $command = 'list';

	public string $description = 'help';

	/**
	 * @param Input $dtl
	 * @return string
	 */
	public function onHandler(Input $dtl): string
	{
		$param = $dtl->get('commandList');

		$last = '';
		$lists = [str_pad('Commands', 24, ' ', STR_PAD_RIGHT) . '注释'];
		foreach ($param as $key => $val) {
			$split = explode(':', $key);
			if (empty($last) && isset($split[0])) {
				$lists[] = "\033[32;40;1;1m" . $split[0] . " \033[0m\v";
			} else if (isset($split[0]) && $last != $split[0]) {
				$lists[] = "\033[32;40;1;1m" . $split[0] . " \033[0m\v";
			}

			$last = $split[0] ?? '';

			list($method, $ts) = $val;
			$lists[] = "\033[32;40;1;1m  " . $key . " \033[0m\v" . $method;
		}
		return implode("\t", $lists);
	}

}
