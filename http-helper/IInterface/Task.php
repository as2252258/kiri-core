<?php
declare(strict_types=1);


namespace Http\IInterface;


interface Task
{

	/**
	 * @return array
	 */
	public function getParams(): array;

	/**
	 * @param array $params
	 * @return $this
	 */
	public function setParams(array $params): static;


	/**
	 * @return mixed
	 */
	public function onHandler(): mixed;

}