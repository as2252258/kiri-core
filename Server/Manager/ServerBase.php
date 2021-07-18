<?php

namespace Server\Manager;

class ServerBase
{


	public function onStart()
	{
		var_dump(func_get_args());
	}


	public function onShutdown()
	{
		var_dump(func_get_args());
	}


	public function onPipeMessage()
	{

	}


	public function onBeforeReload()
	{

	}


	public function onAfterReload()
	{

	}

}
