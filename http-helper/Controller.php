<?php
declare(strict_types=1);

namespace Http;


use Annotation\Inject;
use JetBrains\PhpStorm\Pure;
use Kiri\Application;
use Kiri\Di\ContainerInterface;
use Psr\Log\LoggerInterface;
use Server\RequestInterface;
use Server\ResponseInterface;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 */
class Controller
{


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



	/**
	 * inject logger
	 *
	 * @var LoggerInterface
	 */
	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;



}
