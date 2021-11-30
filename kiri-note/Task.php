<?php


namespace Annotation;


use Exception;
use Kiri\Kiri;
use Server\Tasker\AsyncTaskExecute;


/**
 * Class Task
 * @package Annotation
 * Task任务
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Task extends Attribute
{


	/**
	 * Task constructor.
	 * @param string $name
	 */
	public function __construct(public string $name)
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
		$task = Kiri::getDi()->get(AsyncTaskExecute::class);
		$task->reg($this->name, $class);
		return true;
	}

}
