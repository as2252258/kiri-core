<?php

namespace Kiri\Server;

use Note\Inject;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Error\Logger;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Kiri\Server\Abstracts\BaseProcess;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server as HServer;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process;
use function Swoole\Coroutine\run;


/**
 *
 */
class CoroutineServer implements SwooleServerInterface
{

	use TraitServer;


	/**
	 * @var HServer[]|Server[]
	 */
	private array $servers = [];


	#[Inject(Logger::class)]
	public Logger $logger;


	const SERVER_CLASS = [
		Constant::SERVER_TYPE_BASE      => Server::class,
		Constant::SERVER_TYPE_TCP       => Server::class,
		Constant::SERVER_TYPE_UDP       => Server::class,
		Constant::SERVER_TYPE_HTTP      => HServer::class,
		Constant::SERVER_TYPE_WEBSOCKET => HServer::class,
	];


	/**
	 * @param array $configs
	 * @param bool $daemon
	 * @throws Exception
	 */
	public function initBaseServer(array $configs, bool $daemon)
	{
		$configs['ports'] = $this->sortService($configs['ports']);
		foreach ($configs['ports'] as $n => $config) {
			$this->servers[$config['name'] ?? $n] = $this->addListener($config);
		}
	}


	/**
	 * @param array $config
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function addListener(array $config): mixed
	{
		/** @var HServer|Server $port */
		$class = self::SERVER_CLASS[$config['type']];
		$port = new $class($config['host'], $config['port'], $config['isSsl'] ?? false, $config['reuse_port'] ?? true);
		$port->set($config['settings'] ?? []);
		if ($config['type'] == Constant::SERVER_TYPE_HTTP) {
			$port->handle('/', fn($request, $response) => $this->onRequestHandle($request, $response, $config));
		} else if ($config['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
			$port->handle('/', fn($request, $response) => $this->onWebsocketHandle($request, $response, $config));
		} else {
			$port->handle(fn(Connection $connection) => $this->onConnectionHandle($connection, $config));
		}
		return $this->eventListener($port, $config);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $config
	 */
	protected function onRequestHandle(Request $request, Response $response, $config)
	{
		if (isset($config[Constant::REQUEST])) {
			call_user_func($config[Constant::REQUEST], $request, $response);
			return;
		}
		$response->status(505);
		$response->end();
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $config
	 */
	protected function onWebsocketHandle(Request $request, Response $response, $config)
	{
		$handshake = $config[Constant::HANDSHAKE] ?? null;
		if (!is_null($handshake)) {
			call_user_func($handshake, $request, $response);
		} else {
			$response->upgrade();
			$open = $config[Constant::OPEN] ?? null;
			if (!is_null($open)) {
				call_user_func($open, $request);
			}
		}
		$close = $config[Constant::CLOSE] ?? null;
		$message = $config[Constant::MESSAGE] ?? null;
		while (true) {
			$data = $response->recv();
			if ($data === '' || $data === false) {
				$response->close();
				call_user_func($close, $response->fd);
			} else {
				call_user_func($message, $data);
			}
		}
	}


	/**
	 * @param Connection $connection
	 * @param $config
	 */
	protected function onConnectionHandle(Connection $connection, $config)
	{
		call_user_func($config[Constant::RECEIVE] ?? null, $connection);
	}


	/**
	 * @throws ConfigException
	 * @throws ReflectionException
	 */
	public function start(): void
	{
		$this->startProcess();
		run(function () {
			$this->startServers();
		});
	}


	/**
	 * @return array
	 * @throws ConfigException|ReflectionException
	 */
	private function startProcess(): array
	{
		$processes = [];
		$system = sprintf('[%s].process', Config::get('id', 'system-service'));
		foreach ($this->process as $process) {
			/** @var BaseProcess $process */
			if (is_string($process)) {
				$process = Kiri::getDi()->get($process);
			}
			$swowProcess = new Process([$process, 'onHandler'], $process->getRedirectStdinAndStdout(),
				$process->getPipeType(), $process->isEnableCoroutine());
			if (Kiri::getPlatform()->isLinux()) {
				$swowProcess->name($system . '(' . $process->getName() . ')');
			}
			$swowProcess->start();
			array_push($processes, $swowProcess);
		}
		return $processes;
	}


	private function startServers()
	{
		foreach ($this->servers as $server) {
			Coroutine::create(fn() => $server->start());
		}
	}


	/**
	 * @param mixed $server
	 * @param array $config
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function eventListener(mixed $server, array $config): mixed
	{
		foreach ($config['events'] as $key => $value) {
			if (is_array($value) && is_string($value[0])) {
				$value[0] = Kiri::getDi()->get($value[0]);
			}
			$server->on($key, $value);
		}
		return $server;
	}

}
