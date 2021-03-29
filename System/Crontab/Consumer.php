<?php


namespace Snowflake\Crontab;


use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;


/**
 * Class Consumer
 * @package Snowflake\Crontab
 */
class Consumer extends Process
{

	public Coroutine\Channel $channel;


	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		if (Snowflake::getPlatform()->isLinux()) {
			name($this->pid, 'Crontab consumer');
		}

		$this->channel = new Coroutine\Channel(2000);
		go(function () {
			$this->popChannel();
		});
		$this->tick($process);
	}


	/**
	 * @throws Exception
	 */
	public function popChannel()
	{
		/** @var Crontab $crontab */
		$crontab = $this->channel->pop(-1);
		go(function () use ($crontab) {
			try {
				$crontab->increment()->execute();
				if ($crontab->getExecuteNumber() < $crontab->getMaxExecuteNumber()) {
					Consumer::addTask($crontab);
				} else if ($crontab->isLoop()) {
					Consumer::addTask($crontab);
				}
			} catch (\Throwable $throwable) {
				$this->application->addError($throwable->getMessage());
			} finally {
				fire(Event::SYSTEM_RESOURCE_RELEASES);
			}
		});
		$this->popChannel();
	}


	/**
	 * @param \Swoole\Process $process
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function tick(\Swoole\Process $process)
	{
		$value = $process->read(40);

		$redis = Snowflake::app()->getRedis();

		$crontab = swoole_unserialize($redis->get($value));
		$redis->del($value);
		if (is_object($crontab)) {
			$this->channel->push($crontab);
		}

		$redis->release();

		$this->tick($process);
	}


	/**
	 * @param Crontab $crontab
	 * @throws Exception
	 */
	private static function addTask(Crontab $crontab)
	{
		$redis = Snowflake::app()->getRedis();

		$name = md5($crontab->getName());

		$redis->set('crontab:' . $name, swoole_serialize($crontab));

		$tickTime = time() + $crontab->getTickTime();

		$redis->zAdd(Producer::CRONTAB_KEY, $tickTime, $crontab->getName());
	}


}
