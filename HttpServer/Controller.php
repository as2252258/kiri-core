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
 * @property-read HttpHeaders $header
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
    protected ?Request $request = null;


    /**
     * @var \HttpServer\Http\HttpParams
     */
    #[Inject(className: 'input', withContext: true)]
    protected ?HttpParams $input = null;


    /**
     * @var \HttpServer\Http\HttpHeaders
     */
    #[Inject(className: 'header', withContext: true)]
    protected ?HttpHeaders $header = null;


    /**
     * inject response
     *
     * @var \HttpServer\Http\Response
     */
    #[Inject('response')]
    protected ?Response $response = null;


}
