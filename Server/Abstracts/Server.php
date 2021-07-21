<?php


namespace Server\Abstracts;


use Closure;
use Exception;
use Snowflake\Snowflake;


/**
 * Class Server
 * @package Server\Abstracts
 */
abstract class Server
{


	protected array $_events = [];


	/**
	 * @param string $name
	 * @param array|null $events
	 * @throws Exception
	 */
	public function setEvents(string $name, ?array $events): void
	{
		if (is_array($events) && is_string($events[0])) {
			$reflect = Snowflake::getDi()->getReflect($events[0]);
			if (!$reflect) {
				throw new Exception('Checks the class is c\'not instantiable.');
			}
			$events[0] = $reflect->newInstance();
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
