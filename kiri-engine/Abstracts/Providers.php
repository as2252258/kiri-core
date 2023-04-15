<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Kiri\Di\Inject\Container;
use Psr\Container\ContainerInterface;

/**
 * Class Providers
 * @package Kiri\Abstracts
 */
abstract class Providers extends Component implements Provider
{


	/**
	 * @var ContainerInterface
	 */
	#[Container(ContainerInterface::class)]
	public ContainerInterface $container;

}
