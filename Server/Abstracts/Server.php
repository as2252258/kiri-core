<?php


namespace Server\Abstracts;


use Closure;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Event;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Server\Port;


/**
 * Class Server
 * @package Server\Abstracts
 */
abstract class Server
{


	protected array $_events = [];


	protected Event $_event;



	/**
	 * @param $prefix
	 * @throws ConfigException
	 */
	protected function setProcessName($prefix)
	{
		if (Kiri::getPlatform()->isMac()) {
			return;
		}
		$name = Config::get('id', 'system-service');
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
		swoole_set_process_name($name);
	}


	/**
	 * Server constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->_event = Kiri::getApp('event');
	}


	/**
	 * @param string $name
	 * @param array|null $events
	 * @throws Exception
	 */
	public function setEvents(string $name, ?array $events): void
	{
		if (is_array($events) && is_string($events[0])) {
			$events[0] = Kiri::getDi()->get($events[0]);
		}
		if (!is_callable($events)) {
			return;
		}
		$this->_events[$name] = $events;
	}


	/**
	 * @return array
	 */
	public function getEvents(): array
	{
		return $this->_events;
	}


	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getEvent(string $name): mixed
	{
		return $this->_events[$name] ?? null;
	}


	/**
	 * @param $name
	 * @param Closure|null $closure
	 * @param array $params
	 * @return mixed
	 */
	public function runEvent($name, ?Closure $closure, array $params): void
	{
		$event = $this->getEvent($name);
		if (empty($event)) {
			if (!is_callable($closure)) {
				return;
			}
			call_user_func($closure, ...$params);
		} else {
			call_user_func($event, ...$params);
		}
	}

}
