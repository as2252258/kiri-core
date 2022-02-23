<?php


namespace Kiri\Annotation;


use Exception;
use Kiri;
use Kiri\Events\EventProvider;


/**
 * Class Event
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Event extends AbstractAttribute
{


	/**
	 * Event constructor.
	 * @param string $name
	 * @param array $params
	 */
	public function __construct(public string $name, public array $params = [])
	{
	}


	public function __serialize()
	{
		// TODO: Implement __serialize() method.
	}


	public function __unserialize(array $data)
	{
		// TODO: Implement __unserialize() method.
	}


	public function serialize(): array
	{
		// TODO: Implement __serialize() method.
	}


	public function unserialize(array|string $data): void
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws Exception
	 */
	public function execute(mixed $class, mixed $method = null): bool
	{
		$pro = Kiri::getDi()->get(EventProvider::class);
		if (is_string($class)) {
			$class = Kiri::getDi()->get($class);
		}
		$pro->on($this->name, [$class, $method]);
		return true;
	}

}
