<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
use Kiri\Di\ContainerInterface;

/**
 * Class Providers
 * @package Kiri\Abstracts
 */
abstract class Providers extends Component implements Provider
{


	/**
	 * @param ContainerInterface $container
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public ContainerInterface $container, array $config = [])
	{
		parent::__construct($config);
	}

}
