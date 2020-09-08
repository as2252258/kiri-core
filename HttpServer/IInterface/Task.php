<?php


namespace HttpServer\IInterface;



interface Task
{

	/**
	 * @return array
	 */
	public function getParams();

	/**
	 * @param array $params
	 * @return $this
	 */
	public function setParams(array $params);


	/**
	 * @return mixed|void
	 */
	public function onHandler();

}
