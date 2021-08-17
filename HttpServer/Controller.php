<?php
declare(strict_types=1);

namespace HttpServer;


use Annotation\Inject;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\TraitApplication;
use Kiri\Application;
use Kiri\Di\Container;
use Kiri\Di\ContainerInterface;
use Kiri\Kiri;
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
     * @param Application $application
     */
    #[Pure] public function __construct(protected Application $application)
    {
    }


	/**
	 * inject di container
	 *
	 * @var ContainerInterface|null
	 */
    #[Inject(ContainerInterface::class)]
    public ?ContainerInterface $container = null;


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
