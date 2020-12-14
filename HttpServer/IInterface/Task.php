<?php
declare(strict_types=1);


namespace HttpServer\IInterface;


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
	 * @return void
	 */
	public function onHandler(): void;

}
