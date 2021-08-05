<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Snowflake\Abstracts\TraitApplication;
use Snowflake\Application;
use Server\Constrict\Response as CrResponse;
use Server\Constrict\Request as CrRequest;

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
	 * @var CrRequest|null
	 */
	#[Inject(CrRequest::class)]
	public ?CrRequest $request = null;


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
	 * @var CrResponse|null
	 */
	#[Inject(CrResponse::class)]
	public ?CrResponse $response = null;


}
