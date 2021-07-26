<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use Snowflake\Abstracts\Input;
use Snowflake\Abstracts\TraitApplication;
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 * @property-read HttpParams $input
 * @property-read HttpHeaders $headers
 */
class Controller
{

    use TraitApplication;


    /**
     * inject request
     *
     * @var \HttpServer\Http\Request
     */
    #[Inject(className: 'request', withContext: true)]
    protected Request $request;


    /**
     * inject response
     *
     * @var \HttpServer\Http\Response
     */
    #[Inject('response')]
    protected Response $response;


}
