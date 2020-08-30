<?php


namespace BeReborn\Console;

/**
 * Class DefaultCommand
 * @package BeReborn\Console
 */
class DefaultCommand extends Command
{
	public $command = 'list';

	public $description = 'help';

	public function handler(Dtl $dtl)
	{
		$param = $dtl->get('commandList');

		$last = '';
		$lists = [str_pad('Commands', 24, ' ', STR_PAD_RIGHT) . '注释'];
		foreach ($param as $key => $val) {
			$split = explode(':', $key);
			if (empty($last) && isset($split[0])) {
				$lists[] = str_pad("\033[32;40;1;1m" . $split[0] . " \033[0m", 40, ' ', STR_PAD_RIGHT);
			} else if (isset($split[0]) && $last != $split[0]) {
				$lists[] = str_pad("\033[32;40;1;1m" . $split[0] . " \033[0m", 40, ' ', STR_PAD_RIGHT);
			}

			$last = $split[0] ?? '';

			list($method, $ts) = $val;
			$lists[] = str_pad("\033[32;40;1;1m  " . $key . " \033[0m", 40, ' ', STR_PAD_RIGHT) . $method;
		}
		return implode(PHP_EOL, $lists);
	}

}
