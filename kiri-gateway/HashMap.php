<?php

namespace Kiri\Gateway;

class HashMap
{


	const HTTP = 1;
	const TCP = 2;
	const UDP = 2;


	public string $domain;


	public string $path;


	public string $scheme;


	public string $method;


	public string $proxy_host;


	public string $proxy_port;


	public int $type = self::HTTP;


}
