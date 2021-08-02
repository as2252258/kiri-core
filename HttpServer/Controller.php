<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use Snowflake\Abstracts\TraitApplication;
use Snowflake\Application;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 */
class Controller
{

	use TraitApplication;


	/**
	 * @param Application $container
	 */
	public function __construct(protected Application $container)
	{

	}


	/**
	 * inject request
	 *
	 * @var Request|null
	 */
	#[Inject('request')]
	public ?Request $request = null;


	/**
	 * @var HttpParams|null
	 */
	#[Inject('input')]
	public ?HttpParams $input = null;


	/**
	 * @var HttpHeaders|null
	 */
	#[Inject('header')]
	public ?HttpHeaders $header = null;


	/**
	 * inject response
	 *
	 * @var Response|null
	 */
	#[Inject('response')]
	public ?Response $response = null;


}
