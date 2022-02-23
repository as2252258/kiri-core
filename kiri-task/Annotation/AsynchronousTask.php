<?php

namespace Kiri\Task\Annotation;


use Kiri\Annotation\AbstractAttribute;
use Kiri\Task\TaskManager;

#[\Attribute(\Attribute::TARGET_CLASS)] class AsynchronousTask extends AbstractAttribute
{


	/**
	 * @param string $name
	 */
	public function __construct(public string $name)
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed $method
	 * @return mixed
	 */
	public function execute(mixed $class, mixed $method = ''): mixed
	{
		$AsyncTaskExecute = \Kiri::getDi()->get(TaskManager::class);
		$AsyncTaskExecute->add($this->name, $class::class);
		return parent::execute($class, $method); // TODO: Change the autogenerated stub
	}

}
