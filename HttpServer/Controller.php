<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use Kiri\Abstracts\TraitApplication;
use Kiri\Application;
use Server\RequestInterface;
use Server\ResponseInterface;

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
     * @var RequestInterface|null
     */
    #[Inject(RequestInterface::class)]
    public ?RequestInterface $request = null;


    /**
     * inject response
     *
     * @var ResponseInterface|null
     */
    #[Inject(ResponseInterface::class)]
    public ?ResponseInterface $response = null;



}
