<?php


namespace Annotation;


/**
 * Class Target
 * @package Annotation
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
	public function __construct(public string $only = Target::ALL)
	{
	}

}
