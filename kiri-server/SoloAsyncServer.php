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
use Swoole\Http\Server as HServer;
use Swoole\Process;
use Swoole\Server;
use Swoole\WebSocket\Server as WServer;

class SoloAsyncServer implements SwooleServerInterface
{

	use TraitServer;


	private HServer|WServer|Server|null $server = null;


	#[Inject(Logger::class)]
	public Logger $logger;


	const SERVER_CLASS = [
		Constant::SERVER_TYPE_BASE, Constant::SERVER_TYPE_TCP,
		Constant::SERVER_TYPE_UDP       => Server::class,
		Constant::SERVER_TYPE_HTTP      => HServer::class,
		Constant::SERVER_TYPE_WEBSOCKET => WServer::class
	];


	/**
	 * @param array $configs
	 * @param bool $daemon
	 * @throws Exception
	 */
	public function initBaseServer(array $configs, bool $daemon)
	{
		$configs['ports'] = $this->sortService($configs['ports']);
		foreach ($configs['ports'] as $config) {
			$service = $this->addListener($config);
			if (!$this->server) {
				$this->server = $service;
			}
		}
		$this->startProcess();
	}


	/**
	 * @throws ConfigException|ReflectionException
	 */
	private function startProcess()
	{
		$system = sprintf('[%s].process', Config::get('id', 'system-service'));
		foreach ($this->process as $process) {
			/** @var BaseProcess $process */
			if (is_string($process)) {
				$process = Kiri::getDi()->get($process);
			}
			$sowProcess = new Process([$process, 'onHandler'], $process->getRedirectStdinAndStdout(),
				$process->getPipeType(), $process->isEnableCoroutine());
			if (Kiri::getPlatform()->isLinux()) {
				$sowProcess->name($system . '(' . $process->getName() . ')');
			}
			$this->server->addProcess($sowProcess);
		}
	}


	/**
	 * @param array $config
	 * @return mixed
	 * @throws Exception
	 */
	private function addListener(array $config): Server\Port
	{
		$config = $this->resetConfig($config);
		if (!$this->server) {
			$class = self::SERVER_CLASS[$config['type']];
			$port = new $class($config['host'], $config['port'], SWOOLE_PROCESS, $config['mode']);
			$config['settings'] = array_merge(Config::get('server.settings', []), $config['settings']);
			$config['settings'][Constant::OPTION_DAEMONIZE] = 0;
		} else {
			$port = $this->server->addlistener($config['host'], $config['port'], $config['mode']);
			if ($port === false) {
				throw new Exception("The port is already in use[{$config['host']}::{$config['port']}]");
			}
		}
		$port->set($config['settings'] ?? []);
		return $this->eventListener($port, $config);
	}


	/**
	 * @param Server\Port|Server|HServer|WServer $server
	 * @throws ReflectionException
	 */
	private function eventListener(mixed $server, array $config): Server\Port|HServer|Server|WServer
	{
		foreach ($config['events'] as $key => $value) {
			if (is_array($value) && is_string($value[0])) {
				$value[0] = Kiri::getDi()->get($value[0]);
			}
			$server->on($key, $value);
		}
		return $server;
	}


	public function start()
	{
		$this->server->start();
	}


	/**
	 * @param array $config
	 * @return array
	 */
	private function resetConfig(array $config): array
	{
		if ($config['type'] == Constant::SERVER_TYPE_HTTP && !isset($config['settings']['open_http_protocol'])) {
			$config['settings']['open_http_protocol'] = true;
			if ($this->server && in_array($this->server->setting['dispatch_mode'], [2, 4])) {
				$config['settings']['open_http2_protocol'] = true;
			}
		}
		if ($config['type'] == Constant::SERVER_TYPE_WEBSOCKET && !isset($config['settings']['open_websocket_protocol'])) {
			$config['settings']['open_websocket_protocol'] = true;
		}
		return $config;
	}


}
