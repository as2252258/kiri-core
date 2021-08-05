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
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 * @property Application $container
 * @property HttpParams $input
 * @property HttpHeaders $header
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
     * inject response
     *
     * @var CrResponse|null
     */
    #[Inject(CrResponse::class)]
    public ?CrResponse $response = null;


    /**
     * @return \Snowflake\Application|null
     */
    protected function getContainer()
    {
        return Snowflake::app();
    }


    /**
     * @return \HttpServer\Http\HttpParams|null
     */
    private function getInput()
    {
        return $this->request->params;
    }


    /**
     * @return \HttpServer\Http\HttpHeaders|null
     */
    private function getHeader()
    {
        return $this->request->headers;
    }


    /**
     * @param $name
     * @return \Snowflake\Application|null
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        return $this->{$name} ?? null;
    }


}
