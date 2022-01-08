<?php

namespace Kiri\Core;

class Network
{


	/**
	 * @return string
	 */
	public static function local(): string
	{
		return current(swoole_get_local_ip());
	}


}
