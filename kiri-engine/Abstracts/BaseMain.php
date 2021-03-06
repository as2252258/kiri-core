<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
use Kiri;
use Kiri\Di\LocalService;
use Kiri\Error\{ErrorHandler, StdoutLogger, StdoutLoggerInterface};
use Kiri\Exception\{InitException};
use Kiri\Di\ContainerInterface;
use Kiri\Message\Constrict\Request;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\Response;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\Emitter;
use Kiri\Message\ResponseEmitter;
use Kiri\Server\{Server};
use Psr\Log\LoggerInterface;
use Kiri\Events\EventProvider;

/**
 * Class BaseApplication
 * @package Kiri\Base
 */
abstract class BaseMain extends Component
{


	/**
	 * @var string
	 */
	public string $storage = APP_PATH . 'storage';

	public string $envPath = APP_PATH . '.env';

	/**
	 * Init constructor.
	 *
	 *
	 * @throws
	 */
	public function __construct(public ContainerInterface $container, public EventProvider $provider)
	{
		$config = sweep(APP_PATH . '/config');
		$this->mapping($config['mapping'] ?? []);
		$this->parseInt($config);
		$this->parseEvents($config);
		$this->enableEnvConfig();
		parent::__construct();
	}


	/**
	 * @param array $mapping
	 */
	public function mapping(array $mapping)
	{
		$di = Kiri::getDi();
		$di->mapping(StdoutLoggerInterface::class, StdoutLogger::class);
		$di->mapping(LoggerInterface::class, Logger::class);
		$di->mapping(Emitter::class, ResponseEmitter::class);
		$di->mapping(ResponseInterface::class, Response::class);
		$di->mapping(RequestInterface::class, Request::class);
		foreach ($mapping as $interface => $class) {
			$di->mapping($interface, $class);
		}
	}


	/**
	 * @return array
	 */
	public function enableEnvConfig(): array
	{
		if (!file_exists($this->envPath)) {
			return [];
		}
		$lines = $this->readLinesFromFile($this->envPath);
		foreach ($lines as $line) {
			if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
				[$key, $value] = explode('=', $line);
				putenv(trim($key) . '=' . trim($value));
			}
		}
		return $lines;
	}


	/**
	 * Read lines from the file, auto detecting line endings.
	 *
	 * @param string $filePath
	 *
	 * @return array
	 */
	protected function readLinesFromFile(string $filePath): array
	{
		// Read file into an array of lines with auto-detected line endings
//		$autodetect = ini_get('auto_detect_line_endings');
//		ini_set('auto_detect_line_endings', '1');
		//		ini_set('auto_detect_line_endings', $autodetect);

		return file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}

	/**
	 * Determine if the line in the file is a comment, e.g. begins with a #.
	 *
	 * @param string $line
	 *
	 * @return bool
	 */
	protected function isComment(string $line): bool
	{
		$line = ltrim($line);

		return isset($line[0]) && $line[0] === '#';
	}

	/**
	 * Determine if the given line looks like it's setting a variable.
	 *
	 * @param string $line
	 *
	 * @return bool
	 */
	protected function looksLikeSetter(string $line): bool
	{
		return str_contains($line, '=');
	}


	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseInt($config)
	{
		Config::sets($config);
		if ($storage = Config::get('storage', 'storage')) {
			if (!str_contains($storage, APP_PATH)) {
				$storage = APP_PATH . $storage . '/';
			}
			if (!is_dir($storage)) {
				mkdir($storage);
			}
			if (!is_dir($storage) || !is_writeable($storage)) {
				throw new InitException("Directory {$storage} does not have write permission");
			}
		}
	}

	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseEvents($config)
	{
		if (!isset($config['events']) || !is_array($config['events'])) {
			return;
		}
		foreach ($config['events'] as $key => $value) {
			if (is_string($value)) {
				$value = Kiri::createObject($value);
			}
			$this->addEvent($key, $value);
		}
	}


	/**
	 * @param $key
	 * @param $value
	 * @return void
	 * @throws InitException
	 * @throws Exception
	 */
	private function addEvent($key, $value): void
	{
		if ($value instanceof \Closure || is_object($value)) {
			$this->provider->on($key, $value, 0);
			return;
		}
		if (!is_array($value)) {
			return;
		}
		if (is_object($value[0]) && !($value[0] instanceof \Closure)) {
			$this->provider->on($key, $value, 0);
			return;
		} else if (is_string($value[0])) {
			$value[0] = Kiri::createObject($value[0]);
			$this->provider->on($key, $value, 0);
			return;
		}
		foreach ($value as $item) {
			if (!is_callable($item, true)) {
				throw new InitException("Class does not hav callback.");
			}
			$this->provider->on($key, $item, 0);
		}
	}


	/**
	 * @return mixed
	 */
	public function getLocalIps(): mixed
	{
		return swoole_get_local_ip();
	}


	/**
	 * @return mixed
	 */
	public function getFirstLocal(): mixed
	{
		return current($this->getLocalIps());
	}


	/**
	 *
	 * @return Server
	 * @throws
	 */
	public function getServer(): Server
	{
		return Kiri::getDi()->get(Server::class);
	}


	/**
	 * @param string $name
	 * @return mixed|null
	 * @throws Exception
	 */
	public function __get(string $name)
	{
		$localService = Kiri::getDi()->get(LocalService::class);
		if ($localService->has($name)) {
			return $localService->get($name);
		}
		return parent::__get($name); // TODO: Change the autogenerated stub
	}


	/**
	 * @param $id
	 * @param $definition
	 */
	public function set($id, $definition): void
	{
		Kiri::getDi()->get(LocalService::class)->set($id, $definition);
	}


	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id): bool
	{
		return Kiri::getDi()->get(LocalService::class)->has($id);
	}
}
