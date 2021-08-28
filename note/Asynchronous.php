<?php


namespace Annotation;


use Exception;
use Http\IInterface\Task;
use Kiri\Kiri;


/**
 * Class Asynchronous
 * @package Annotation
 * Task任务
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Asynchronous extends Attribute
{


	/**
	 * Asynchronous constructor.
	 * @param string $name
	 */
	public function __construct(string $name)
	{

	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws Exception
	 */
    public static function execute(mixed $params, mixed $class, mixed $method = null): bool
    {
		$async = Kiri::app()->getAsync();
		$async->addAsync($params->name, $class);
		return true;
	}

}
