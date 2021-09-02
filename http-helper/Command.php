<?php
declare(strict_types=1);

namespace Http;


use Annotation\Inject;
use Exception;
use Kiri\Abstracts\Input;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Events\OnBeforeWorkerStart;
use Server\Events\OnWorkerStart;
use Server\Worker\OnServerWorker;
use Server\Worker\OnWorkerStart as WorkerDispatch;

/**
 * Class Command
 * @package Http
 */
class Command extends \Symfony\Component\Console\Command\Command
{

	public string $command = 'sw:server';


	public string $description = 'server start|stop|reload|restart';


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
		$this->setName('swoole')
			->setDescription('server start|stop|reload|restart');
	}


	/**
	 * @param Input $dtl
	 * @return string
	 * @throws Exception
	 * @throws ConfigException
	 */
	public function onHandler(Input $dtl): string
	{
		$manager = Kiri::app()->getServer();
		$manager->setDaemon($dtl->get('daemon', 0));
		if (!in_array($dtl->get('action'), self::ACTIONS)) {
			return 'I don\'t know what I want to do.';
		}
		if ($manager->isRunner() && $dtl->get('action') == 'start') {
			return 'Service is running. Please use restart.';
		}
		$manager->shutdown();
		if ($dtl->get('action') == 'stop') {
			return 'shutdown success.';
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

		return $manager->start();
	}

}
