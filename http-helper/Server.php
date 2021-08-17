<?php


namespace Http;

use Annotation\Inject;
use Exception;
use Http\Abstracts\HttpService;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Config;
use Kiri\Error\LoggerProcess;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Process\Biomonitoring;
use ReflectionException;
use Rpc\Service;
use Server\Constant;
use Server\Events\OnShutdown;
use Server\ServerManager;
use Swoole\Runtime;


defined('PID_PATH') or define('PID_PATH', APP_PATH . 'storage/server.pid');

/**
 * Class Server
 * @package Http
 */
class Server extends HttpService
{

	private array $process = [
		Biomonitoring::class,
		LoggerProcess::class
	];


	private ServerManager $manager;
	private mixed $daemon = 0;


	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	/**
	 *
	 */
	public function init()
	{
		$this->manager = ServerManager::getContext();
	}


	/**
	 * @param $process
	 */
	public function addProcess($process)
	{
		$this->process[] = $process;
	}


	/**
	 * @return string start server
	 *
	 * start server
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function start(): string
	{
		$this->manager->initBaseServer(Config::get('server', [], true), $this->daemon);

		$rpcService = Config::get('rpc', []);
		if (!empty($rpcService)) {
			$this->rpcListener($rpcService);
		}

		$processes = array_merge($this->process, Config::get('processes', []));
		foreach ($processes as $process) {
			$this->manager->addProcess($process);
		}
		Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_BLOCKING_FUNCTION);

		return $this->manager->getServer()->start();
	}


	/**
	 * @param $rpcService
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws ConfigException
	 */
	private function rpcListener($rpcService)
	{
		if (in_array($rpcService['mode'], [SWOOLE_SOCK_UDP, SWOOLE_UDP, SWOOLE_UDP6, SWOOLE_SOCK_UDP6])) {
			$rpcService['events'][Constant::PACKET] = [Service::class, 'onPacket'];
		} else {
			$rpcService['events'][Constant::RECEIVE] = [Service::class, 'onReceive'];
			$rpcService['events'][Constant::CONNECT] = [Service::class, 'onConnect'];
			$rpcService['events'][Constant::DISCONNECT] = [Service::class, 'onDisconnect'];
			$rpcService['events'][Constant::CLOSE] = [Service::class, 'onClose'];
		}
		$rpcService['settings']['enable_unsafe_event'] = true;
		$this->addRpcListener($rpcService);
	}


	/**
	 * @param $rpcService
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function addRpcListener($rpcService)
	{
		$this->manager->addListener($rpcService['type'], $rpcService['host'], $rpcService['port'], $rpcService['mode'], $rpcService);
	}


	/**
	 * @return void
	 *
	 * start server
	 * @throws Exception
	 */
	public function shutdown()
	{
		$configs = Config::get('server', [], true);
		foreach ($this->manager->sortService($configs['ports'] ?? []) as $config) {
			$this->manager->stopServer($config['port']);
		}
		$this->eventDispatch->dispatch(new OnShutdown());
	}


	/**
	 * @return bool
	 * @throws ConfigException
	 */
	public function isRunner(): bool
	{
		return $this->manager->isRunner();
	}


	/**
	 * @param $daemon
	 * @return Server
	 */
	public function setDaemon($daemon): static
	{
		if (!in_array($daemon, [0, 1])) {
			return $this;
		}
		$this->daemon = $daemon;
		return $this;
	}


	/**
	 * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	 */
	#[Pure] public function getServer(): \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	{
		return $this->manager->getServer();
	}

}
