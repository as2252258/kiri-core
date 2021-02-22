<?php


namespace Gii;

use Annotation\Inject;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MakeGiiProviders
 * @package Gii
 */
class GiiConsole extends Command
{

	#[Inject(Gii::class)]
	public ?Gii $gii = null;


	public function configure()
	{
		$this->setName('sw:gii')
			->setDescription('create default file.')
			->setHelp('make=model|controller|task|interceptor|limits|middleware name=xxxx');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->gii = Snowflake::createObject(Gii::class);
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('start generate. Please waite...');
		$connections = Snowflake::app()->get('db');
		if ($input->getArgument('databases')) {
			$array = $this->gii->run($connections->get($input->getArgument('databases')), $input);
		} else {
			$array = $this->batchCreate($input, $this->gii, $connections);
		}
		$output->writeln('create file [' . Json::encode($array) . ']');
		return 0;
	}


	/**
	 * @param InputInterface $input
	 * @param $gii
	 * @param $connections
	 * @return array
	 * @throws ConfigException
	 */
	private function batchCreate(InputInterface $input, $gii, $connections): array
	{
		$array = [];
		foreach (Config::get('databases') as $key => $connection) {
			$array[$key] = $gii->run($connections->get($key), $input);
		}
		return $array;
	}


}
