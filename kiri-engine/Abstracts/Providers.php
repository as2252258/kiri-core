<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Kiri\Di\Inject\Container;
use Psr\Container\ContainerInterface;

/**
 * Class Providers
 * @package Kiri\Abstracts
 * @property-read ContainerInterface $container
 */
abstract class Providers extends Component implements Provider
{


	/**
	 * @return ContainerInterface
	 */
	public function getContainer(): ContainerInterface
	{
		return \Kiri::getDi();
	}

}
