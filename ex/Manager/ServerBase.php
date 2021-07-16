<?php


class ServerBase
{


	public static function onStart()
	{
		var_dump(func_get_args());
	}


	public static function onShutdown()
	{
		var_dump(func_get_args());
	}


	public static function onPipeMessage()
	{

	}


	public static function onBeforeReload()
	{

	}


	public static function onAfterReload()
	{

	}

}
