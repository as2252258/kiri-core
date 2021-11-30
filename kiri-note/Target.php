<?php


namespace Note;


/**
 * Class Target
 * @package Note
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Target extends Attribute
{


	const WORKER = 'worker';
	const ALL = 'any';
	const PROCESS = 'process';
	const TASK = 'task';


	/**
	 * @param string $only
	 */
	public function __construct(string $only = Target::ALL)
	{
	}

}
