<?php


namespace HttpServer\IInterface;


interface Task
{

	public function getParams();

	public function handler();

}
