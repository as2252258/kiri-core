<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Kiri\Abstracts\TraitApplication;
use Kiri\Application;
use Server\Constrict\Response as CrResponse;
use Server\Constrict\Request as CrRequest;
use Kiri\Kiri;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property Application $container
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



}
