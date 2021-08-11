<?php


namespace Rpc\Annotation;


use Annotation\Attribute;
use Exception;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
use Kiri\Kiri;


/**
 * Class RpcProducer
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class RpcService extends Attribute
{

	const PROTOCOL_JSON = 'json';
	const PROTOCOL_SERIALIZE = 'serialize';


	/**
	 * Route constructor.
	 * @param string $package
	 * @param string $protocol
	 * @param string $server
	 * @param string $version
	 */
	#[Pure] public function __construct(public string $package, public string $protocol = self::PROTOCOL_SERIALIZE, public string $server = 'json-rpc',
	                                    public string $version = 'v1.0')
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return Router
	 * @throws Exception
	 */
	public function execute(mixed $class, mixed $method = null): Router
	{
		// TODO: Implement setHandler() method.
		$router = Kiri::app()->getRouter();

		$methods = Kiri::getDi()->getMethods($class::class);
		foreach ($methods as $method => $reflectionMethod) {
			$router->addRoute(':rpc/' . $this->package . '/' . $method . '/' . $this->version, [$class, $method], $this->server);
		}
		return $router;
	}


}
