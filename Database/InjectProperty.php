<?php


namespace Database;


use JetBrains\PhpStorm\Pure;
use Snowflake\IAspect;


/**
 * Class InjectProperty
 * @package Database
 */
class InjectProperty implements IAspect
{

	private string $className = '';
	private string $methodName = '';


	/**
	 * InjectProperty constructor.
	 * @param array $handler
	 */
	public function __construct(public array $handler)
	{
	}


	/**
	 * @return mixed
	 */
	public function invoke(): mixed
	{
		$data = call_user_func($this->handler, func_get_args());

		$this->handler[0]->createAnnotation();

		echo 'inject property.' . PHP_EOL;

		return $data;
	}

}
