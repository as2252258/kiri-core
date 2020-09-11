<?php


namespace Queue;


use Exception;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Timer;

/**
 * Class Queue
 * @package Queue
 */
class Queue extends \Snowflake\Process\Process
{

	/** @var Waiting */
	private $waiting;

	/** @var Complete */
	private $complete;

	/** @var Running */
	private $running;


	private $shutdown = false;


	/**
	 * Queue constructor.
	 * @param $application
	 * @param $name
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function __construct($application, $name)
	{
		parent::__construct($application, $name);
		$this->waiting = Snowflake::createObject(Waiting::class);
		$this->running = Snowflake::createObject(Running::class);
		$this->complete = Snowflake::createObject(Complete::class);
	}


	/**
	 * @param Process $process
	 */
	public function onHandler(Process $process)
	{
		Timer::tick(50, function () {
			$redis = Snowflake::app()->getRedis();
			try {
				if ($this->shutdown) {
					return;
				}
				$data = $redis->zRevRange(Waiting::QUEUE_WAITING, 0, 20);
				if (empty($data)) {
					Coroutine::sleep(0.05);
				} else {
					$this->scheduler($data);
				}
			} catch (\Throwable $exception) {
				$this->application->error($exception->getMessage());
			} finally {
				$redis->release();
			}
		});
	}


	/**
	 * @param Consumer $consumer
	 * @param int $score
	 * @throws ComponentException
	 */
	public function delivery(Consumer $consumer, $score = 0)
	{
		try {
			$consumer->onWaiting();
		} catch (\Throwable $exception) {
			$this->application->error($exception->getMessage());
		} finally {
			$this->waiting->add($consumer, $score);
		}
	}


	/**
	 * @param array $data
	 * @throws Exception
	 */
	private function scheduler($data)
	{
		foreach ($data as $datum) {
			$this->runner($datum);
		}
		if ($this->shutdown === true) {
			Snowflake::shutdown($this);
		}
	}


	/**
	 * @param $class
	 * @return mixed|void
	 * @throws ComponentException
	 */
	private function runner(string $class)
	{
		$logger = $this->application->getLogger();
		try {
			$rely_on = unserialize($class);
			$this->waiting->del($rely_on);
			if (!($rely_on instanceof Consumer)) {
				return;
			}
			$this->running->add($rely_on);
			$rely_on->onRunning();
		} catch (\Throwable $exception) {
			$logger->write($exception->getMessage(), 'queue');
		} finally {
			$this->running->del($rely_on);
			if (isset($rely_on) && $rely_on instanceof Consumer) {
				$rely_on->onComplete();
				$this->complete->add($rely_on);
			}
		}
	}


	/**
	 * @return Waiting
	 */
	public function getWaiting(): Waiting
	{
		return $this->waiting;
	}

	/**
	 * @return Complete
	 */
	public function getComplete(): Complete
	{
		return $this->complete;
	}

	/**
	 * @return Running
	 */
	public function getRunning(): Running
	{
		return $this->running;
	}

}
