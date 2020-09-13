<?php


namespace Snowflake\Abstracts;


/**
 * Class Listener
 * @package Snowflake\Abstracts
 * 监听的名称
 */
abstract class Listener extends Component implements IListener
{

	protected $trigger = '';


	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getName()
	{
		if (empty($this->trigger)) {
			throw new \Exception('Listener name con\'t empty.');
		}
		return $this->trigger;
	}


}
