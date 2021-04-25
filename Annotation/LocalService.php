<?php


namespace Annotation;


use Exception;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class LocalService
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class LocalService extends Attribute
{


	/**
	 * LocalService constructor.
	 * @param string $service
	 * @param array|null $args
	 * @param bool $async_reload
	 * @throws Exception
	 */
	public function __construct(public string $service, public ?array $args = [], public bool $async_reload = true)
	{
		if ($this->async_reload !== true) {
			return;
		}
		$event = Snowflake::app()->getEvent();
		$event->on(Event::SERVER_WORKER_EXIT, function () {
			Snowflake::app()->remove($this->service);
		});
	}


	/**
	 * @param array $handler
	 * @return mixed
	 * @throws Exception
	 */
	public function execute(array $handler): mixed
	{
		$class = ['class' => $handler[0]::class];
		if (!empty($this->args)) {
			$class = array_merge($class, $this->args);
		}

		Snowflake::set($this->service, $class);

		return parent::execute($handler); // TODO: Change the autogenerated stub
	}


}
