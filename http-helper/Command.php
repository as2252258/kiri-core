<?php
declare(strict_types=1);

namespace Http;


use Annotation\Inject;
use Exception;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Events\OnBeforeWorkerStart;
use Server\Events\OnWorkerStart;
use Server\Worker\OnServerWorker;
use Server\Worker\OnWorkerStart as WorkerDispatch;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package Http
 */
class Command extends \Symfony\Component\Console\Command\Command
{


	const ACTIONS = ['start', 'stop', 'restart'];


	/**
	 * @var EventProvider
	 */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	/**
	 *
	 */
	protected function configure()
	{
		$this->setName('sw:server')
			->setDescription('server start|stop|reload|restart')
			->addArgument('action', InputArgument::REQUIRED)
			->addArgument('daemon', InputArgument::OPTIONAL, '', 0);
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return string
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$manager = Kiri::app()->getServer();
		$manager->setDaemon($input->getArgument('daemon'));
		if (!in_array($input->getArgument('action'), self::ACTIONS)) {
			$output->write('I don\'t know what I want to do.');
			return 1;
		}
		if ($manager->isRunner() && $input->getArgument('action') == 'start') {
			$output->write('Service is running. Please use restart.');
			return 1;
		}
		$manager->shutdown();
		if ($input->getArgument('action') == 'stop') {
			$output->write('shutdown success');
			return 1;
		}
		return $this->generate_runtime_builder($manager);
	}


	/**
	 * @param $manager
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function generate_runtime_builder($manager): mixed
	{
		exec(PHP_BINARY . ' ' . APP_PATH . 'kiri.php runtime:builder');

		$this->eventProvider->on(OnBeforeWorkerStart::class, [di(OnServerWorker::class), 'setConfigure']);
		$this->eventProvider->on(OnWorkerStart::class, [di(WorkerDispatch::class), 'dispatch']);

		$manager->start();

		return 0;
	}

}
