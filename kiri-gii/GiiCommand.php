<?php
declare(strict_types=1);

namespace Gii;


use Exception;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
	 *
	 */
	protected function configure()
	{
		$this->setName('sw:gii')
			->addArgument('action', InputArgument::REQUIRED)
			->addArgument('name', InputArgument::OPTIONAL)
			->setDescription('./snowflake sw:gii make=model|controller|task|interceptor|limits|middleware name=xxxx');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws ConfigException
	 */
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		/** @var Gii $gii */
		$gii = Kiri::app()->get('gii');

		$connections = Kiri::app()->get('db');
//		if ($input->getArgument('databases')) {
//			return $gii->run($connections->get($input->getArgument('databases')), $input);
//		}

		$array = [];
		foreach (Config::get('databases.connections') as $key => $connection) {
			$array[$key] = $gii->run($connections->get($key), $input);
		}

		$output->writeln(json_encode($array, JSON_UNESCAPED_UNICODE));

		return 1;
	}

}
