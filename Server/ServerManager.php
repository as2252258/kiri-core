<?php

namespace Server;

use Closure;
use Exception;
use ReflectionException;
use Server\Manager\OnPipeMessage;
use Server\SInterface\CustomProcess;
use Server\SInterface\TaskExecute;
use Server\Task\OnServerTask;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Server as HServer;
use Swoole\Process;
use Swoole\Server;
use Swoole\Server\Port;
use Swoole\WebSocket\Server as WServer;


/**
 * Class OnServerManager
 * @package HttpServer\Service
 */
class ServerManager extends Abstracts\Server
{

    /** @var string  */
	public string $host = '';

	public int $port = 0;


	/** @var Server\Port[] */
	public array $ports = [];

	public int $mode = SWOOLE_TCP;


	private Server|WServer|HServer|null $server = null;


	private static ?ServerManager $BASEServerListener = null;


	/**
	 * @return static
	 */
	public static function getContext(): static
	{
		if (!(static::$BASEServerListener)) {
			static::$BASEServerListener = new ServerManager();
		}
		return static::$BASEServerListener;
	}


	/**
	 * @return Server|WServer|HServer|null
	 */
	public function getServer(): Server|WServer|HServer|null
	{
		return $this->server;
	}


	/**
	 * @param string $type
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function addListener(string $type, string $host, int $port, int $mode, array $settings = [])
	{
		if ($this->portIsAready($port)) $this->stopServer($port);
		if (!$this->server) {
			$this->createBaseServer($type, $host, $port, $mode, $settings);
		} else {
			if (!isset($settings['settings'])) {
				$settings['settings'] = [];
			}
			$this->addNewListener($type, $host, $port, $mode, $settings);
		}
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function initBaseServer($configs): void
	{
		$context = ServerManager::getContext();
		foreach ($this->sortService($configs['ports']) as $config) {
			$this->startListenerHandler($context, $config);
		}
		$this->bindCallback($this->server, [Constant::PIPE_MESSAGE => [OnPipeMessage::class, 'onPipeMessage']]);
		$this->bindCallback($this->server, $this->getSystemEvents($configs));
	}


	/**
	 * @param $port
	 * @return bool
	 * @throws Exception
	 */
	private function checkPort($port): bool
	{
		if (Snowflake::getPlatform()->isLinux()) {
			exec('netstat -tunlp | grep ' . $port, $output);
		} else {
			exec('lsof -i :' . $port . ' | grep -i "LISTEN"', $output);
		}
		return !empty($output);
	}


	/**
	 * @param string|CustomProcess $customProcess
	 * @param null $redirect_stdin_and_stdout
	 * @param int|null $pipe_type
	 * @param bool $enable_coroutine
	 * @throws Exception
	 */
	public function addProcess(string|CustomProcess $customProcess, $redirect_stdin_and_stdout = null, ?int $pipe_type = SOCK_DGRAM, bool $enable_coroutine = true)
	{
		if (is_string($customProcess)) {
			$implements = class_implements($customProcess);
			if (!in_array(CustomProcess::class, $implements)) {
				trigger_error('custom process must implement ' . CustomProcess::class);
			}
			$customProcess = new $customProcess($this->server);
		}
		/** @var Process $process */
		$this->server->addProcess(new Process(function (Process $soloProcess) use ($customProcess) {
			$system = sprintf('%s.process[%d]', Config::get('id', 'system-service'), $soloProcess->pid);
			if (Snowflake::getPlatform()->isLinux()) {
				$soloProcess->name($system . '.' . $customProcess->getProcessName($soloProcess) . ' start.');
			}

			scan_directory(directory('app'), 'App', [CONTROLLER_PATH]);

			echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Process %s start.", $customProcess->getProcessName($soloProcess)) . PHP_EOL;
			$customProcess->onHandler($soloProcess);
		},
			$redirect_stdin_and_stdout, $pipe_type, $enable_coroutine));
	}


	/**
	 * @param array $ports
	 * @return array
	 */
	private function sortService(array $ports): array
	{
		$array = [];
		foreach ($ports as $port) {
			if ($port['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
				array_unshift($array, $port);
			} else if ($port['type'] == Constant::SERVER_TYPE_HTTP) {
				if (!empty($array) && $array[0]['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
					$array[] = $port;
				} else {
					array_unshift($array, $port);
				}
			} else {
				$array[] = $port;
			}
		}
		return $array;
	}


	/**
	 * @param array $configs
	 * @return array
	 */
	private function getSystemEvents(array $configs): array
	{
		return array_intersect_key($configs['events'] ?? [], [
			Constant::SHUTDOWN      => '',
			Constant::WORKER_START  => '',
			Constant::WORKER_ERROR  => '',
			Constant::WORKER_EXIT   => '',
			Constant::WORKER_STOP   => '',
			Constant::MANAGER_START => '',
			Constant::MANAGER_STOP  => '',
			Constant::BEFORE_RELOAD => '',
			Constant::AFTER_RELOAD  => '',
			Constant::DISCONNECT    => '',
			Constant::START         => '',
		]);
	}


	/**
	 * @param ServerManager $context
	 * @param array $config
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function startListenerHandler(ServerManager $context, array $config)
	{
		if (!$this->server) {
			$config['settings'] = $config['settings'] ?? [];
			if (!isset($config['settings']['log_file'])) {
				$config['settings']['log_file'] = storage('system.log');
			}
			if (!isset($config['settings']['pid_file'])) {
				$config['settings']['pid_file'] = storage('swoole.pid');
			}
			$config['events'] = $config['events'] ?? [];
		}
		$context->addListener($config['type'], $config['host'], $config['port'], $config['mode'], $config);
	}


	/**
	 * @param string $type
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array $settings
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function addNewListener(string $type, string $host, int $port, int $mode, array $settings = [])
	{
		echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m $type service %s::%d start.", $host, $port) . PHP_EOL;
		/** @var Server\Port $service */
		$this->ports[$port] = $this->server->addlistener($host, $port, $mode);
		if ($this->ports[$port] === false) {
			throw new Exception("The port is already in use[$host::$port]");
		}
		$this->ports[$port]->set($settings['settings'] ?? []);
		$reflect = match ($type) {
			Constant::SERVER_TYPE_TCP => Snowflake::getDi()->newObject(TCPServerListener::class),
			Constant::SERVER_TYPE_UDP => Snowflake::getDi()->newObject(UDPServerListener::class),
			Constant::SERVER_TYPE_HTTP => Snowflake::getDi()->newObject(HTTPServerListener::class),
			Constant::SERVER_TYPE_WEBSOCKET => Snowflake::getDi()->newObject(WebSocketServerListener::class),
			default => throw new Exception(''),
		};
		$reflect->bindCallback($this->ports[$port], $settings['events'] ?? []);
	}


	/**
	 * @param int $port
	 * @param string $event
	 * @return Closure|array|null
	 */
	public function getPortCallback(int $port, string $event): Closure|array|null
	{
		/** @var Server\Port $_port */
		$_port = $this->ports[$port] ?? null;
		if (is_null($_port)) {
			return null;
		}
		return $_port->getCallback($event);
	}


	/**
	 * @param string $type
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws ConfigException
	 */
	private function createBaseServer(string $type, string $host, int $port, int $mode, array $settings = [])
	{
		$match = match ($type) {
			Constant::SERVER_TYPE_BASE, Constant::SERVER_TYPE_TCP,
			Constant::SERVER_TYPE_UDP => Server::class,
			Constant::SERVER_TYPE_HTTP => HServer::class,
			Constant::SERVER_TYPE_WEBSOCKET => WServer::class
		};
		$this->server = new $match($host, $port, SWOOLE_PROCESS, $mode);
		$this->server->set(array_merge(Config::get('server.settings', []), $settings['settings']));

		echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m $type service %s::%d start.", $host, $port) . PHP_EOL;

		$this->addDefaultListener($type, $settings);
	}


	/**
	 * @param int $port
	 */
	public function stopServer(int $port)
	{
		if (!($pid = $this->portIsAready($port))) {
			return;
		}

		exec('kill ' . $pid, $execResult);
		while ($this->portIsAready($port)) {
			usleep(100);
		}
	}


	/**
	 * @param $port
	 * @return bool|string
	 */
	private function portIsAready($port): bool|string
	{
		exec('netstat -lnp | grep ' . $port . ' | grep "LISTEN" | awk \'{print $7}\'', $output);
		if (empty($output)) {
			return false;
		}
		return explode('/', $output[0])[0];
	}


	/**
	 * @param string $type
	 * @param array $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function addDefaultListener(string $type, array $settings): void
	{
		if (($this->server->setting['task_worker_num'] ?? 0) > 0) {
			$this->addTaskListener($settings['events']);
		}
		if ($type === Constant::SERVER_TYPE_WEBSOCKET) {
			/** @var WebSocketServerListener $reflect */
			$reflect = $this->getNewInstance(WebSocketServerListener::class);
			$reflect->bindCallback($this->server, $settings);
		} else if ($type === Constant::SERVER_TYPE_UDP) {
			/** @var UDPServerListener $reflect */
			$reflect = $this->getNewInstance(UDPServerListener::class);
			$reflect->bindCallback($this->server, $settings);
		} else if ($type === Constant::SERVER_TYPE_HTTP) {
			/** @var HTTPServerListener $reflect */
			$reflect = $this->getNewInstance(HTTPServerListener::class);
			$reflect->bindCallback($this->server, $settings);
		} else {
			/** @var TCPServerListener $reflect */
			$reflect = $this->getNewInstance(TCPServerListener::class);
			$reflect->bindCallback($this->server, $settings);
		}
	}


	/**
	 *
	 */
	public function start()
	{
		$this->server->start();
	}


	/**
	 * @param string $class
	 * @return object
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function getNewInstance(string $class): object
	{
		return Snowflake::getDi()->newObject($class);
	}


	/**
	 * @param TaskExecute|string $handler
	 * @param array $params
	 * @param int $workerId
	 * @throws Exception
	 */
	public function task(TaskExecute|string $handler, array $params = [], int $workerId = 0)
	{
		if ($workerId === null || $workerId <= $this->server->setting['worker_num']) {
			$workerId = random_int($this->server->setting['worker_num'] + 1,
				$this->server->setting['worker_num'] + 1 + $this->server->setting['task_worker_num']);
		}
		if (is_string($handler)) {
			$implements = Snowflake::getDi()->getReflect($handler);
			if (!in_array(TaskExecute::class, $implements->getInterfaceNames())) {
				throw new Exception('Task must instance ' . TaskExecute::class);
			}
			$handler = $implements->newInstanceArgs($params);
		}
		$this->server->task(serialize($handler), $workerId);
	}


	/**
	 * @param array $events
	 * @throws ReflectionException
	 */
	private function addTaskListener(array $events = []): void
	{
		$task_use_object = $this->server->setting['task_object'] ?? $this->server->setting['task_use_object'] ?? false;
		$reflect = Snowflake::getDi()->getReflect(OnServerTask::class)?->newInstance();
		if ($task_use_object || $this->server->setting['task_enable_coroutine']) {
			$this->server->on('task', $events[Constant::TASK] ?? [$reflect, 'onCoroutineTask']);
		} else {
			$this->server->on('task', $events[Constant::TASK] ?? [$reflect, 'onTask']);
		}
		$this->server->on('finish', $events[Constant::FINISH] ?? [$reflect, 'onFinish']);
	}


	/**
	 * @param Port|Server $server
	 * @param array|null $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function bindCallback(Port|Server $server, ?array $settings = [])
	{
		// TODO: Implement bindCallback() method.
		if (count($settings) < 1) {
			return;
		}
		foreach ($settings as $event_type => $callback) {
			if ($this->server->getCallback($event_type) !== null) {
				continue;
			}
			if (is_array($callback) && !is_object($callback[0])) {
				$callback[0] = Snowflake::getDi()->get($callback[0]);
			}
			$this->server->on($event_type, $callback);
		}
	}
}
