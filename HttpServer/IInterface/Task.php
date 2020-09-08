<?php


namespace HttpServer\IInterface;


interface Task
{

	public function getParams();

	public function setParams(array $params);

	public function handler();

}
