<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use Snowflake\Abstracts\TraitApplication;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 */
class Controller
{

    use TraitApplication;


    /**
     * inject request
     *
     * @var \HttpServer\Http\Request|null
     */
    #[Inject(value: 'request', withContext: true)]
    public ?Request $request = null;


    /**
     * @var \HttpServer\Http\HttpParams|null
     */
    #[Inject(value: HttpParams::class, withContext: true)]
    public ?HttpParams $input = null;


    /**
     * @var \HttpServer\Http\HttpHeaders|null
     */
    #[Inject(value: HttpHeaders::class, withContext: true)]
    public ?HttpHeaders $header = null;


    /**
     * inject response
     *
     * @var \HttpServer\Http\Response|null
     */
    #[Inject('response')]
    public ?Response $response = null;


}
