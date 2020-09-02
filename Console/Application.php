<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:16
 */

namespace Console;


use Snowflake\Abstracts\BaseApplication;
use Swoole\Coroutine\Channel;
use Swoole\Runtime;
use Swoole\Timer;
use Snowflake\Snowflake;

/**
 * Class Application
 * @package Console
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';

	private $console;

	/** @var Channel */
	private $channel;

	/**
	 * Application constructor.
	 * @param array $config
	 * @throws
	 */
	public function __construct(array $config = [])
	{
		parent::__construct($config);

		$this->channel = new Channel(1);
		$this->console = new Console($config);
	}

	/**
	 * @param $class
	 * @throws
	 */
	public function register($class)
	{
		if (is_string($class) || is_callable($class, true)) {
			$class = Snowflake::createObject($class);
		}
		$this->console->signCommand($class);
	}


	/**
	 * @param null $kernel
	 * @return string|void
	 * @throws \Exception
	 */
	public function run($kernel = null)
	{
		try {
			$kernel = make($kernel, Kernel::class);

			Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

			$this->console->setParameters();
			$this->console->batch($kernel);
			$class = $this->console->search();
			$params = response()->send($this->console->execCommand($class));
		} catch (\Exception $exception) {
			$params = response()->send(implode("\n", [
				'Msg: ' . $exception->getMessage(),
				'Line: ' . $exception->getLine(),
				'File: ' . $exception->getFile()
			]));
		} finally {
			Timer::clearAll();
			return $params;
		}
	}

}
