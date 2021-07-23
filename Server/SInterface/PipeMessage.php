<?php


namespace Server\SInterface;


/**
 * Interface PipeMessage
 * @package Server\SInterface
 */
interface PipeMessage
{

	/**
	 *
	 */
	public function process(): void;


	/**
	 *
	 */
	public function max_execute(): void;


	/**
	 * @return bool
	 */
	public function isStop(): bool;


}
