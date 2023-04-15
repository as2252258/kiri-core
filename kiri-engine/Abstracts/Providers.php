<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
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
	public ContainerInterface $container;

}
