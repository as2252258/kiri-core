<?php


namespace Kiri;


interface IAspect
{


	public function before(): void;


	/**
	 * @param mixed $response
	 */
	public function after(mixed $response): void;


	/**
	 * @param mixed $handler
	 * @param array $params
	 * @return mixed
	 */
	public function invoke(mixed $handler, array $params = []): mixed;

}
