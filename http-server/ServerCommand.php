<?php
declare(strict_types=1);

namespace Server;


use Annotation\Inject;
use Exception;
use Kiri\Events\EventProvider;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Events\OnBeforeWorkerStart;
use Server\Events\OnWorkerStart;
use Server\Worker\OnServerWorker;
use Server\Worker\OnWorkerStart as WorkerDispatch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package Http
 */
class ServerCommand extends Command
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
	 * @return int
	 * @throws Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output): int
	{
		try {
			$manager = Kiri::app()->getServer();
			$manager->setDaemon($input->getArgument('daemon'));
			if (!in_array($input->getArgument('action'), self::ACTIONS)) {
				throw new Exception('I don\'t know what I want to do.');
			}
			if ($manager->isRunner() && $input->getArgument('action') == 'start') {
				throw new Exception('Service is running. Please use restart.');
			}
			$manager->shutdown();
			if ($input->getArgument('action') == 'stop') {
				throw new Exception('shutdown success');
			}
			$this->generate_runtime_builder($manager);
		} catch (\Throwable $throwable) {
			$output->write($throwable->getMessage());
		} finally {
			return 1;
		}
	}


	/**
	 * @param $manager
	 * @return void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function generate_runtime_builder($manager): void
	{
		exec(PHP_BINARY . ' ' . APP_PATH . 'kiri.php runtime:builder');

		$this->eventProvider->on(OnBeforeWorkerStart::class, [di(OnServerWorker::class), 'setConfigure']);
		$this->eventProvider->on(OnWorkerStart::class, [di(WorkerDispatch::class), 'dispatch']);

		$manager->start();
	}

}
