<?php


namespace Kiri\Annotation\Route;


use Kiri\Annotation\Attribute;
use Kiri\Message\Handler\Router;
use Kiri;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)] class Route extends Attribute
{

	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 * @param string $version
	 */
	public function __construct(public string $uri, public string $method, public string $version = 'v.1.0')
	{
		$this->uri = '/' . ltrim($this->uri, '/');
		$this->method = strtoupper($this->method);
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function execute(mixed $class, mixed $method = null): bool
	{
		$di = Kiri::getDi()->get(Router::class);
		$di->addRoute($this->method, $this->uri, $class . '@' . $method);
		return parent::execute($class, $method);
	}


}
