<?php

namespace Kiri\Annotation\Route;

use Kiri\Annotation\Aspect;
use Kiri\Error\LoggerAspect;

class TestController
{


	/**
	 * @return void
	 */
	#[RequestMapping(method: Method::REQUEST_GET, path: '/', version: 'v1')]
	#[Aspect(aspect: LoggerAspect::class)]
	#[Middleware(middleware: LoggerAspect::class)]
	public function index()
	{

	}


}
