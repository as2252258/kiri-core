<?php


namespace Kiri\Annotation;


/**
 * Class Target
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Target extends AbstractAttribute
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
