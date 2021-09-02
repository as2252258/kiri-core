<?php
declare(strict_types=1);

namespace Gii;


use Exception;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Input;
use Kiri\Exception\ComponentException;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package Http
 */
class GiiCommand extends Command
{

	public string $command = 'sw:gii';


	public string $description = './snowflake sw:gii make=model|controller|task|interceptor|limits|middleware name=xxxx';


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return array
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function execute(InputInterface $input, OutputInterface $output): array
	{
		/** @var Gii $gii */
		$gii = Kiri::app()->get('gii');

		$connections = Kiri::app()->get('db');
		if ($input->getArgument('databases')) {
			return $gii->run($connections->get($input->getArgument('databases')), $input);
		}

		$array = [];
		foreach (Config::get('databases') as $key => $connection) {
			$array[$key] = $gii->run($connections->get($key), $input);
		}
		return $array;
	}

}
