<?php

namespace Kiri\Annotation\Route;

enum Method
{


	case REQUEST_POST;
	case REQUEST_GET;
	case REQUEST_HEAD;
	case REQUEST_OPTIONS;
	case REQUEST_DELETE;
	case REQUEST_PUT;


	/**
	 * @return string
	 */
	public function getString(): string
	{
		return match ($this) {
			self::REQUEST_POST => 'POST',
			self::REQUEST_GET => 'GET',
			self::REQUEST_HEAD => 'HEAD',
			self::REQUEST_OPTIONS => 'OPTIONS',
			self::REQUEST_DELETE => 'DELETE',
			self::REQUEST_PUT => 'PUT'
		};
	}

}
